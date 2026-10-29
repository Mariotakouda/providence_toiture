@php($isEdit = $category !== null)

<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium">Nom</label>
        <input id="name" name="name" type="text" value="{{ old('name', $category?->name) }}" required
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium">Description</label>
        <textarea id="description" name="description" rows="3"
                  class="mt-1 w-full rounded-lg border border-line px-3 py-2">{{ old('description', $category?->description) }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <span class="block text-sm font-medium">Photo de la gamme (affichée sur la page d’accueil)</span>

        @if ($category?->image)
            <div class="mt-2">
                <img src="{{ asset('storage/'.$category->image) }}" alt="Photo actuelle" class="h-32 w-52 rounded-lg object-cover ring-1 ring-line">
                <label class="mt-2 flex items-center gap-2 text-sm">
                    <input type="hidden" name="remove_image" value="0">
                    <input type="checkbox" name="remove_image" value="1"> Supprimer la photo actuelle
                </label>
            </div>
        @endif

        <label for="image" id="image-zone"
               style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.4rem;margin-top:.5rem;padding:1.25rem;border:2px dashed #96C11F;border-radius:.75rem;background:#F0F6DC;cursor:pointer;text-align:center;">
            <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="#5E7D0B" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="M21 16l-5-5-8 9"/>
            </svg>
            <strong style="color:#222;font-size:.9rem;">{{ $category?->image ? 'Remplacer la photo' : 'Cliquer pour ajouter une photo' }}</strong>
            <span id="image-name" style="font-size:.8rem;color:#6B7280;">JPG, PNG ou WebP. La photo est réduite automatiquement. Format paysage conseillé.</span>
            <img id="image-preview" alt="" style="display:none;margin-top:.5rem;max-height:9rem;border-radius:.5rem;">
        </label>
        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" style="position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;">
        @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

        <script nonce="{{ Vite::cspNonce() }}">
            // Réduit automatiquement la photo (1600 px max, JPEG) avant l'envoi : évite les erreurs de taille.
            document.getElementById('image').addEventListener('change', async function () {
                var input = this, f = input.files[0], img = document.getElementById('image-preview');
                if (!f) { img.style.display = 'none'; return; }
                try {
                    var bmp = await createImageBitmap(f);
                    var r = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
                    var c = document.createElement('canvas');
                    c.width = Math.round(bmp.width * r); c.height = Math.round(bmp.height * r);
                    var ctx = c.getContext('2d');
                    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
                    ctx.drawImage(bmp, 0, 0, c.width, c.height);
                    var blob = await new Promise(function (res) { c.toBlob(res, 'image/jpeg', 0.85); });
                    if (blob && blob.size < f.size) {
                        var nf = new File([blob], f.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
                        var dt = new DataTransfer(); dt.items.add(nf); input.files = dt.files; f = nf;
                    }
                } catch (e) { /* on garde le fichier d'origine */ }
                document.getElementById('image-name').textContent = f.name + ' (' + Math.round(f.size / 1024) + ' Ko)';
                img.src = URL.createObjectURL(f); img.style.display = 'block';
            });
        </script>
    </div>

    <div>
        <input type="hidden" name="is_active" value="0">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $category?->is_active ?? true))>
            Catégorie active
        </label>
        @error('is_active') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex gap-3">
    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
        {{ $isEdit ? 'Enregistrer' : 'Créer la catégorie' }}
    </button>
    <a href="{{ route('categories.index') }}" class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Annuler</a>
</div>
