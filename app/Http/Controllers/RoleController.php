<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function __construct(private AuditService $audit)
    {
    }

    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::withCount(['permissions', 'users'])->orderBy('id')->get(),
            'total' => Permission::count(),
        ]);
    }

    public function edit(Role $role): View
    {
        $existing = Permission::orderBy('name')->pluck('name')->all();

        return view('roles.edit', [
            'role' => $role,
            'groups' => PermissionCatalog::groups(),
            'others' => PermissionCatalog::others($existing),
            'existing' => $existing,
            'current' => $role->permissions()->pluck('name')->all(),
            'locked' => $role->name === 'admin',
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        // L'administrateur garde toutes les permissions : évite de se verrouiller dehors.
        abort_if($role->name === 'admin', 403, 'Le rôle administrateur n’est pas modifiable.');

        $valid = Permission::pluck('name')->all();

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($valid)],
        ]);

        $new = array_values(array_unique($data['permissions'] ?? []));
        $old = $role->permissions()->pluck('name')->all();

        $role->syncPermissions($new);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $added = array_values(array_diff($new, $old));
        $removed = array_values(array_diff($old, $new));

        if ($added || $removed) {
            $this->audit->log('role.permissions_updated', $role, null, [
                'role' => $role->name,
                'added' => $added,
                'removed' => $removed,
            ]);
        }

        return redirect()->route('roles.index')->with('success', 'Permissions du rôle mises à jour.');
    }
}
