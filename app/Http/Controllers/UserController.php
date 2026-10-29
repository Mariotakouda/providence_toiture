<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private AuditService $audit)
    {
    }

    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->role($request->input('role')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::orderBy('id')->pluck('name'),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::orderBy('id')->pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->syncRoles($data['role']);

        $this->audit->log('user.created', $user, null, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $data['role'],
            'is_active' => $user->is_active,
        ]);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->denyIfAdminTarget($request, $user);

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('id')->pluck('name'),
            'currentRole' => $user->roles->first()?->name,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->denyIfAdminTarget($request, $user);

        $data = $request->validated();
        $newRole = $data['role'];
        $isActive = $request->boolean('is_active');
        $currentRole = $user->roles->first()?->name;

        if ($user->is($request->user()) && ($newRole !== $currentRole || ! $isActive)) {
            return back()->withInput()->withErrors([
                'role' => 'Vous ne pouvez pas modifier votre propre rôle ni désactiver votre propre compte.',
            ]);
        }

        if ($this->wouldRemoveLastAdmin($user, $newRole, $isActive)) {
            return back()->withInput()->withErrors([
                'role' => 'Il doit rester au moins un administrateur actif.',
            ]);
        }

        $old = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $currentRole,
            'is_active' => $user->is_active,
        ];

        $user->fill(Arr::only($data, ['name', 'email', 'phone']));
        $user->is_active = $isActive;

        $passwordChanged = ! empty($data['password']);
        if ($passwordChanged) {
            $user->password = $data['password'];
        }

        $user->save();
        $user->syncRoles($newRole);

        $new = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $newRole,
            'is_active' => $user->is_active,
        ];
        if ($passwordChanged) {
            $new['password_changed'] = true;
        }

        $this->audit->log('user.updated', $user, $old, $new);

        if (! $isActive) {
            $this->purgeSessions($user);
        } elseif ($passwordChanged) {
            // Les autres appareils/sessions de ce compte doivent se reconnecter.
            $this->purgeSessions($user, $request->user()->is($user) ? $request->session()->getId() : null);
        }

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $this->denyIfAdminTarget($request, $user);

        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $newStatus = ! $user->is_active;
        $role = $user->roles->first()?->name ?? '';

        if (! $newStatus && $this->wouldRemoveLastAdmin($user, $role, false)) {
            return back()->with('error', 'Il doit rester au moins un administrateur actif.');
        }

        $user->update(['is_active' => $newStatus]);

        $this->audit->log(
            $newStatus ? 'user.activated' : 'user.deactivated',
            $user,
            ['is_active' => ! $newStatus],
            ['is_active' => $newStatus]
        );

        if (! $newStatus) {
            $this->purgeSessions($user);
        }

        return back()->with('success', $newStatus ? 'Compte activé.' : 'Compte désactivé.');
    }

    private function wouldRemoveLastAdmin(User $user, string $newRole, bool $isActive): bool
    {
        if (! $user->hasRole('admin')) {
            return false;
        }

        if ($newRole === 'admin' && $isActive) {
            return false;
        }

        return User::role('admin')
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }

    private function purgeSessions(User $user, ?string $exceptSessionId = null): void
    {
        if (config('session.driver') === 'database') {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->when($exceptSessionId, fn ($query) => $query->where('id', '!=', $exceptSessionId))
                ->delete();
        }
    }

    /** Seul un administrateur peut consulter, modifier ou (dés)activer un compte administrateur. */
    private function denyIfAdminTarget(Request $request, User $user): void
    {
        abort_if(
            $user->hasRole('admin') && ! $request->user()->hasRole('admin'),
            403,
            'Seul un administrateur peut gérer un compte administrateur.'
        );
    }
}
