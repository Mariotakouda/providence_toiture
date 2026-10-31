<style nonce="{{ Vite::cspNonce() }}">
    .fv-invalid { border-color: #dc2626 !important; box-shadow: 0 0 0 1px #dc2626; }
    .fv-msg { margin-top: .25rem; font-size: .875rem; line-height: 1.25rem; color: #dc2626; }
    .fv-req { margin-left: .15rem; color: #dc2626; font-weight: 600; }
</style>
<script nonce="{{ Vite::cspNonce() }}">
(function () {
    // Messages d'erreur renvoyés par le serveur (Laravel), par champ.
    var serverErrors = @json($errors->messages());
    var SKIP = { hidden: 1, submit: 1, button: 1, reset: 1, image: 1 };

    function isField(el) {
        return !!el && /^(INPUT|SELECT|TEXTAREA)$/.test(el.tagName) && !SKIP[el.type] && !el.disabled;
    }

    // Message en français pour une erreur détectée par le navigateur.
    function messageFor(f) {
        var v = f.validity, t = f.type;
        if (v.valueMissing) {
            if (f.tagName === 'SELECT') return 'Choisissez une option dans la liste.';
            if (t === 'checkbox') return 'Vous devez cocher cette case pour continuer.';
            if (t === 'radio') return 'Choisissez une des options proposées.';
            if (t === 'file') return 'Choisissez un fichier.';
            return 'Ce champ est obligatoire.';
        }
        if (v.typeMismatch) {
            if (t === 'email') return 'Saisissez une adresse e-mail valide, par exemple nom\u0040exemple.com.';
            if (t === 'url') return 'Saisissez une adresse web valide (commençant par https://).';
            return 'Le format de cette valeur n’est pas valide.';
        }
        if (v.badInput) return 'Saisissez un nombre valide.';
        if (v.rangeUnderflow) return 'La valeur doit être supérieure ou égale à ' + f.min + '.';
        if (v.rangeOverflow) return 'La valeur doit être inférieure ou égale à ' + f.max + '.';
        if (v.stepMismatch) return Number(f.step) >= 1 ? 'Saisissez un nombre entier.' : 'Cette valeur a trop de décimales (deux au maximum).';
        if (v.tooShort) return 'Saisissez au moins ' + f.minLength + ' caractères.';
        if (v.tooLong) return 'Maximum ' + f.maxLength + ' caractères.';
        if (v.patternMismatch) return f.title || 'Le format de cette valeur n’est pas valide.';
        return f.validationMessage || 'Cette valeur n’est pas valide.';
    }

    // Une case à cocher placée dans un <label> reçoit son message après le label.
    function anchor(f) {
        if (f.type === 'checkbox' || f.type === 'radio') return f.closest('label') || f;
        return f;
    }

    function clear(f) {
        f.classList.remove('fv-invalid');
        f.removeAttribute('aria-invalid');
        var n = anchor(f).nextElementSibling;
        if (n && n.classList.contains('fv-msg')) n.remove();
    }

    function show(f, msg) {
        clear(f);
        f.classList.add('fv-invalid');
        f.setAttribute('aria-invalid', 'true');
        var p = document.createElement('p');
        p.className = 'fv-msg';
        p.setAttribute('role', 'alert');
        p.textContent = msg;
        anchor(f).insertAdjacentElement('afterend', p);
    }

    // Le navigateur signale un champ invalide à l'envoi : on affiche notre message sous le champ
    // (la bulle native est souvent invisible sur téléphone) et on se place sur le premier champ en erreur.
    var pending = null;
    document.addEventListener('invalid', function (e) {
        var f = e.target;
        if (!isField(f)) return;
        e.preventDefault();
        show(f, messageFor(f));
        if (pending) return;
        pending = f;
        setTimeout(function () {
            var t = pending;
            pending = null;
            if (!t.getClientRects().length) { window.alert(messageFor(t)); return; }
            try {
                t.scrollIntoView({ block: 'center', behavior: 'smooth' });
                t.focus({ preventScroll: true });
            } catch (err) {}
        }, 0);
    }, true);

    // Dès que la valeur redevient correcte, l'erreur disparaît.
    function onEdit(e) {
        var f = e.target;
        if (isField(f) && f.classList.contains('fv-invalid') && f.validity.valid) clear(f);
    }
    document.addEventListener('input', onEdit);
    document.addEventListener('change', onEdit);

    // En quittant un champ rempli mais mal rempli, on signale l'erreur tout de suite.
    // Les champs vides sont signalés à l'envoi, pour ne pas décaler les boutons pendant la saisie.
    document.addEventListener('focusout', function (e) {
        var f = e.target;
        if (!isField(f) || f.type === 'file' || f.value === '') return;
        if (f.validity.valid) { if (f.classList.contains('fv-invalid')) clear(f); }
        else show(f, messageFor(f));
    });

    // Astérisque rouge sur les champs obligatoires.
    document.querySelectorAll('form').forEach(function (form) {
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (f) {
            if (!isField(f) || f.type === 'checkbox' || f.type === 'radio') return;
            var label = f.id ? form.querySelector('label[for="' + f.id + '"]') : null;
            if (!label) label = f.closest('label');
            if (!label || label.querySelector('.fv-req')) return;
            var s = document.createElement('span');
            s.className = 'fv-req';
            s.title = 'Champ obligatoire';
            s.setAttribute('aria-hidden', 'true');
            s.textContent = ' *';
            label.appendChild(s);
        });
    });

    // Messages déjà affichés par les pages (messages d’erreur du serveur) : on les met en rouge sur le champ,
    // et ils disparaissent quand la valeur est corrigée.
    document.querySelectorAll('p.text-red-600').forEach(function (p) {
        var prev = p.previousElementSibling;
        if (!prev) return;
        var f = isField(prev) ? prev : (prev.tagName === 'LABEL' ? prev.querySelector('input, select, textarea') : null);
        if (!f || !isField(f)) return;
        p.classList.add('fv-msg');
        f.classList.add('fv-invalid');
        f.setAttribute('aria-invalid', 'true');
    });

    // Erreurs du serveur pour les champs dont la page n'affiche pas de message.
    function fieldByKey(key) {
        var parts = key.split('.');
        var name = parts[0] + parts.slice(1).map(function (p) { return '[' + p + ']'; }).join('');
        var f = document.querySelector('[name="' + name + '"]:not([type="hidden"])')
            || document.querySelector('[name="' + name + '[]"]:not([type="hidden"])');
        return f && isField(f) ? f : null;
    }
    Object.keys(serverErrors).forEach(function (key) {
        var f = fieldByKey(key);
        if (!f) return;
        var msg = serverErrors[key][0];
        var n = anchor(f).nextElementSibling;
        if (n && n.classList.contains('fv-msg')) return;
        var par = f.parentElement;
        var already = par && Array.prototype.some.call(par.querySelectorAll('.text-red-600, .fv-msg'), function (x) {
            return x.textContent.trim() === msg;
        });
        if (already) { f.classList.add('fv-invalid'); f.setAttribute('aria-invalid', 'true'); return; }
        show(f, msg);
    });

    // Après un envoi refusé par le serveur, on amène l'erreur à l'écran.
    var banner = document.getElementById('form-errors');
    var first = banner || (Object.keys(serverErrors).length ? document.querySelector('.fv-invalid') : null);
    if (first) {
        try { first.scrollIntoView({ block: banner ? 'start' : 'center' }); } catch (err) {}
    }
})();
</script>
