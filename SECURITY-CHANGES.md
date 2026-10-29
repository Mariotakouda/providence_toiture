# Providence Toiture — correctifs de sécurité (audit assisté par IA)

> Ces correctifs n'ont PAS pu être exécutés (PHP absent de l'environnement de génération).
> Testez-les en local/préproduction avant tout déploiement. Ce document n'est pas une certification de sécurité.

## Comment appliquer ce zip
Ce zip est un **correctif à extraire par-dessus votre projet** : il ne contient que les fichiers modifiés ou nouveaux
(pas de `vendor/`, `node_modules/`, `.env`, `composer.json` ni `composer.lock`, pour ne pas écraser votre
`composer.lock` mis à jour avec `symfony/mime` corrigé).
1. Faites une sauvegarde (ou un commit Git) de votre projet.
2. Extrayez le zip à la racine du projet en acceptant le remplacement. Si vous avez modifié vous-même l'un des
   fichiers listés ci-dessous depuis l'envoi du zip initial, comparez avant de remplacer (`git diff`).
3. `npm run build` (obligatoire : nouveau `resources/js/app.js`), puis `php artisan view:clear config:clear`.
4. Testez les points de la section « Vérifications ».

## Fichiers modifiés
- `app/Http/Requests/User/StoreUserRequest.php`, `UpdateUserRequest.php`, `Auth/ResetPasswordRequest.php` — mot de passe via `Password::defaults()` ; le rôle `admin` ne peut être attribué que par un administrateur.
- `app/Providers/AppServiceProvider.php` — règle de mot de passe par défaut (12 car., lettres, chiffres, non compromis en production) ; limiteur `login` (5/min/IP).
- `routes/web.php` — `POST /login` protégé par `throttle:login`.
- `app/Http/Controllers/UserController.php` — un non-admin ne peut pas voir/modifier/(dés)activer un compte admin ; purge des sessions d'un compte quand son mot de passe change.
- `app/Http/Controllers/Auth/ResetPasswordController.php` — purge des sessions du compte après réinitialisation.
- `app/Http/Controllers/RoleController.php` + `resources/views/roles/index.blade.php` — **changement de comportement** : seule la modification des permissions des rôles est réservée aux administrateurs (le lien « Modifier » est masqué pour les autres). Pour revenir en arrière, retirer les deux `abort_unless`.
- `bootstrap/app.php` — middleware global `SecurityHeaders` ; contexte des logs d'erreur (route, utilisateur, IP ; ni URL complète ni corps de requête).
- `config/logging.php` — canal `secure` (`storage/logs/errors.log`, rotation 30 j, permissions 0640).
- `database/seeders/UserSeeder.php` — ignoré en production (comptes `@providence.test` / mot de passe `password`).
- `public/.htaccess` — blocage des fichiers cachés et sensibles, suppression de `X-Powered-By`. `public/robots.txt` — neutre (aucun chemin révélé).
- `resources/views/**` — 17 `onsubmit/onclick="return confirm(…)"` remplacés par `data-confirm`, nonce CSP sur les `<script>`/`<style>` (hors vues `pdf/`), `@json(…)` remplacé par `Js::from(…)` dans les scripts, texte « 12 caractères minimum ».
- `.gitignore` — ignore `*.sql` et les logs.

## Fichiers nouveaux
- `app/Http/Middleware/SecurityHeaders.php`, `config/security.php` — CSP stricte avec nonce, nosniff, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS (HTTPS), `X-Robots-Tag` sur les pages internes.
- `resources/js/app.js` — gestion des confirmations (`data-confirm`).
- `resources/views/errors/{403,404,419,429,500,503,layout}.blade.php` — pages d'erreur sobres.
- `app/Console/Commands/CreateAdmin.php` — `php artisan app:create-admin email@exemple.com` pour créer l'admin en production.
- `deploy/` — exemples Apache, Nginx, `php.ini` et `.env` de production.

## À faire manuellement (non appliqué par ce zip)
1. **Ne pas déployer** : `.env` de développement, `sauvegarde.sql`, `storage/logs/laravel.log`, `.git/`, `tests/`, `CLAUDE.md`, `AGENTS.md`, etc. Si `sauvegarde.sql` a déjà été en ligne, changer tous les mots de passe.
2. Créer `.env` sur le serveur à partir de `deploy/env.production.example` : `APP_DEBUG=false`, utilisateur MySQL dédié, `SESSION_SECURE_COOKIE=true`, HTTPS obligatoire.
3. En production : `composer install --no-dev --optimize-autoloader`, puis `php artisan config:cache route:cache view:cache`. Ne jamais lancer `db:seed` ; créer l'admin avec `php artisan app:create-admin`.
4. **Proxy / Cloudflare** : configurer `trustProxies` (sinon toutes les IP sont identiques et le limiteur de connexion bloquerait tout le site).
5. Déplacer `laravel/tinker` de `require` vers `require-dev` dans `composer.json`, puis `composer update --lock`.
6. Argon2id (optionnel) : `php artisan config:publish hashing`, `HASH_DRIVER=argon2id` et `HASH_VERIFY=false` le temps que les comptes migrent à leur prochaine connexion, puis `HASH_VERIFY=true`. bcrypt (12 tours) reste acceptable.
7. Non traité : échappement des jokers `%`/`_` dans les recherches `LIKE` (faible), double authentification (2FA) pour les comptes admin, WAF (Cloudflare ou ModSecurity).

## Vérifications
- Connexion : 6 essais ratés dans le navigateur → message de blocage à la 6e.
- DevTools → Application → Cookies : HttpOnly, Secure, SameSite=Strict (en HTTPS avec les variables `SESSION_*`).
- CSP : mettre `CSP_REPORT_ONLY=true`, parcourir toutes les pages (connexion, tableau de bord, devis, catégories avec aperçu d'image, accueil, gamme) console ouverte, corriger les messages « Refused to… », puis `CSP_REPORT_ONLY=false`.
- Les boutons de confirmation (annuler facture, livrer, désactiver…) demandent toujours confirmation.
- En production : `/.env`, `/sauvegarde.sql`, `/storage/logs/…` répondent 403/404 ; aucun en-tête `X-Powered-By`.
- Un compte non-admin avec `users.manage` ne peut ni voir un compte admin, ni attribuer le rôle admin, ni modifier les rôles.
