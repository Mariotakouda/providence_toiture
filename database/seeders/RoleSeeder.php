<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'products.view',
            'products.create',
            'products.update',
            'products.archive',
            'products.view_cost',
            'categories.manage',
            'stock.view',
            'stock.move',
            'stock.adjust',
            'customers.view',
            'customers.create',
            'customers.update',
            'suppliers.view',
            'suppliers.manage',
            'requests.view', 'requests.manage',
            'quotes.view',
            'quotes.create',
            'quotes.update',
            'quotes.send',
            'quotes.convert',
            'quotes.cancel',
            'quotes.discount',
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.prepare',
            'orders.cancel',
            'invoices.view',
            'invoices.create',
            'invoices.cancel',
            'payments.view',
            'payments.record',
            'payments.cancel',
            'deliveries.view',
            'deliveries.create',
            'deliveries.confirm',
            'reports.sales',
            'reports.financial',
            'users.manage',
            'settings.manage',
            'audit.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $viewPermissions = array_filter($permissions, fn($p) => str_ends_with($p, '.view'));

        $roles = [
            'admin' => $permissions,
            'direction' => array_merge($viewPermissions, ['reports.sales', 'reports.financial', 'quotes.discount', 'products.view_cost', 'orders.cancel', 'categories.manage']),
            'commercial' => [
                'customers.view',
                'customers.create',
                'customers.update',
                'products.view',
                'stock.view',
                'quotes.view',
                'quotes.create',
                'quotes.update',
                'quotes.send',
                'quotes.convert',
                'quotes.cancel',
                'orders.view',
                'orders.create',
                'orders.update',
                'orders.cancel',
                'requests.view',
                'requests.manage',
                'invoices.view',
                'payments.view',
                'deliveries.view',
            ],
            'magasinier' => [
                'products.view',
                'products.create',
                'products.update',
                'suppliers.view',
                'stock.view',
                'stock.move',
                'stock.adjust',
                'orders.view',
                'orders.prepare',
                'deliveries.view',
                'deliveries.create',
            ],
            'comptable' => [
                'customers.view',
                'quotes.view',
                'orders.view',
                'invoices.view',
                'invoices.create',
                'invoices.cancel',
                'deliveries.view',
                'payments.view',
                'payments.record',
                'payments.cancel',
                'reports.financial',
                'products.view_cost',
            ],
            'livreur' => ['deliveries.view', 'deliveries.confirm'],
        ];

       foreach ($roles as $name => $perms) {
    $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

    // L'administrateur a toujours toutes les permissions.
    if ($name === 'admin') {
        $role->syncPermissions($permissions);
        continue;
    }

    // Les autres rôles ne reçoivent leurs permissions par défaut qu'à la création.
    if ($role->wasRecentlyCreated || $role->permissions()->doesntExist()) {
        $role->syncPermissions($perms);
    }
}
    }
}
