@if (session('duplicates'))
    <div class="mb-4 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-700">
        <p class="font-medium">Un contact similaire existe déjà (même téléphone ou même e-mail) :</p>

        <ul class="mt-2 list-disc pl-5">
            @foreach (session('duplicates') as $duplicate)
                <li>
                    <a href="{{ route($routePrefix.'.show', $duplicate['id']) }}" target="_blank" class="underline">
                        {{ $duplicate['name'] }}
                    </a>
                    ({{ $duplicate['phone'] ?? '-' }}, {{ $duplicate['email'] ?? '-' }}){{ $duplicate['is_active'] ? '' : ', inactif' }}
                </li>
            @endforeach
        </ul>

        <label class="mt-3 flex items-center gap-2">
            <input type="checkbox" name="confirm_duplicate" value="1">
            Je confirme qu’il s’agit bien d’un contact différent
        </label>
    </div>
@endif
