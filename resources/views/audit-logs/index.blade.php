@extends('layouts.app')

@section('title', 'Journal d’audit')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <select name="q" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Toutes les actions</option>
            @foreach ($families as $prefix => $label)
                <option value="{{ $prefix }}" @selected(request('q') === $prefix)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="user_id" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Tous les utilisateurs</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>

        <select name="subject_type" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Tous les objets</option>
            @foreach ($subjectTypes as $type)
                <option value="{{ $type }}" @selected(request('subject_type') === $type)>{{ \App\Support\AuditPresenter::subjectType($type) }}</option>
            @endforeach
        </select>

        <div>
            <label class="block text-xs text-muted">Du</label>
            <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-muted">Au</label>
            <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>

        <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Filtrer</button>
    </form>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Utilisateur</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Objet</th>
                    <th class="px-4 py-3">IP</th>
                    <th class="px-4 py-3">Détail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($logs as $log)
                    <tr class="align-top">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'Système' }}</td>
                        <td class="px-4 py-3"><span class="inline-block whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium {{ \App\Support\AuditPresenter::tone($log->action) }}" title="{{ $log->action }}">{{ \App\Support\AuditPresenter::actionLabel($log->action) }}</span></td>
                        <td class="px-4 py-3">{{ \App\Support\AuditPresenter::subject($log) }}</td>
                        <td class="px-4 py-3 text-xs text-muted">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @php($rows = \App\Support\AuditPresenter::changes($log))
                            @if ($rows)
                                <details>
                                    <summary class="cursor-pointer text-link">Voir le détail</summary>
                                    <dl class="mt-2 min-w-[16rem] space-y-1.5 text-sm">
                                        @foreach ($rows as [$label, $before, $after])
                                            <div class="flex flex-wrap items-baseline gap-x-2">
                                                <dt class="text-muted">{{ $label }} :</dt>
                                                <dd>
                                                    @if ($before !== null)
                                                        <span class="text-muted line-through">{{ $before }}</span>
                                                        <span aria-hidden="true">→</span>
                                                    @endif
                                                    <strong class="font-medium">{{ $after }}</strong>
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </details>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-muted">Aucune entrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
