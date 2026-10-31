@if ($errors->any())
    <div id="form-errors" role="alert"
         style="margin-bottom:1rem;padding:.9rem 1rem;border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:.5rem;background:#fef2f2;color:#991b1b;font-size:.9rem;">
        <strong style="display:block;margin-bottom:.35rem;">
            {{ $errors->count() > 1 ? 'Le formulaire contient '.$errors->count().' erreurs.' : 'Le formulaire contient une erreur.' }}
            Corrigez-les puis réessayez.
        </strong>
        <ul style="margin:0;padding-left:1.1rem;list-style:disc;">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
