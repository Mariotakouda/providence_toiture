@extends('layouts.app')

@section('title', 'Journal d’audit')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Action (ex. quote., payment.)"
               class="rounded-lg border border-line px-3 py-2 text-sm">

        <select name="user_id" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Tous les utilisateurs</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>

        <select name="subject_type" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Tous les objets</option>
            @foreach ($subjectTypes as $type)
                <option value="{{ $type }}" @selected(request('subject_type') === $type)>{{ class_basename($type) }}</option>
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
                    <th class="px-4 py-3">Valeurs</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($logs as $log)
                    <tr class="align-top">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'Système' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $log->action }}</td>
                        <td class="px-4 py-3">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
                        <td class="px-4 py-3 text-xs text-muted">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($log->old_values || $log->new_values)
                                <details>
                                    <summary class="cursor-pointer text-link">Voir</summary>
                                    @if ($log->old_values)
                                        <p class="mt-1 text-xs text-muted">Avant</p>
                                        <pre class="max-w-md overflow-x-auto rounded bg-page p-2 text-xs">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @endif
                                    @if ($log->new_values)
                                        <p class="mt-1 text-xs text-muted">Après</p>
                                        <pre class="max-w-md overflow-x-auto rounded bg-page p-2 text-xs">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @endif
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
