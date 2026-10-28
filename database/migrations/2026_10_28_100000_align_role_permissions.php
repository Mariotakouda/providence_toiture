<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Complète les rôles déjà créés avec les droits qui manquaient (ajout seulement,
 * rien n'est retiré : les réglages faits depuis la page Rôles sont conservés).
 */
return new class extends Migration
{
    private array $grants = [
        'direction' => ['orders.cancel', 'categories.manage'],
        'commercial' => ['orders.cancel', 'requests.view', 'requests.manage', 'invoices.view', 'payments.view', 'deliveries.view'],
        'magasinier' => ['products.create', 'products.update', 'suppliers.view', 'stock.adjust'],
        'comptable' => ['deliveries.view'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->grants as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }

            $role->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->grants as $roleName => $permissions) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->revokePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
