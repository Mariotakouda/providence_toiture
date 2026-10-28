@extends('layouts.app')

@section('title', 'Catégories')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher une catégorie"
                   class="rounded-lg border border-line px-3 py-2 text-sm">
            <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Rechercher</button>
        </form>

        @can('create', \App\Models\Category::class)
            <a href="{{ route('categories.create') }}"
               class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
                Nouvelle catégorie
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Photo</th>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Produits (actifs / total)</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3">
                            @if ($category->image)
                                <img src="{{ asset('storage/'.$category->image) }}" alt="" class="h-10 w-14 rounded-md object-cover ring-1 ring-line">
                            @else
                                <span class="inline-block h-10 w-14 rounded-md bg-soft align-middle" title="Aucune photo"></span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-muted">{{ $category->description ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $category->active_products_count }} / {{ $category->products_count }}</td>
                        <td class="px-4 py-3">
                            @if ($category->is_active)
                                <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">Active</span>
                            @else
                                <span class="rounded-full bg-soft px-2 py-1 text-xs text-muted">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @can('update', $category)
                                <div class="flex flex-wrap justify-end gap-x-3 gap-y-1">
                                    <a href="{{ route('categories.edit', $category) }}" class="text-link hover:underline">Modifier</a>
                                    <form method="POST" action="{{ route('categories.toggle-status', $category) }}"
                                          onsubmit="return confirm('{{ $category->is_active ? 'Désactiver' : 'Activer' }} cette catégorie ?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="{{ $category->is_active ? 'text-red-600' : 'text-green-600' }} hover:underline">
                                            {{ $category->is_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-muted">Aucune catégorie.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $categories->links() }}</div>
@endsection
