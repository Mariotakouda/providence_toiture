<?php

namespace App\Support;

class PermissionCatalog
{
    /** @return array<string, array<string, string>> groupe => [permission => libellé] */
    public static function groups(): array
    {
        return [
            'Catalogue' => [
                'products.view' => 'Consulter les produits',
                'products.create' => 'Créer un produit',
                'products.update' => 'Modifier un produit',
                'products.archive' => 'Archiver ou réactiver un produit',
                'products.view_cost' => 'Voir et saisir les coûts d’achat (et la marge)',
                'categories.manage' => 'Gérer les catégories',
            ],
            'Stock' => [
                'stock.view' => 'Consulter les mouvements, alertes et stocks',
                'stock.move' => 'Saisir des entrées et sorties de stock',
                'stock.adjust' => 'Ajustements et inventaires',
            ],
            'Clients et fournisseurs' => [
                'customers.view' => 'Consulter les clients',
                'customers.create' => 'Créer un client',
                'customers.update' => 'Modifier ou désactiver un client',
                'suppliers.view' => 'Consulter les fournisseurs',
                'suppliers.manage' => 'Gérer les fournisseurs',
            ],
            'Devis' => [
                'quotes.view' => 'Consulter les devis',
                'quotes.create' => 'Créer un devis',
                'quotes.update' => 'Modifier un brouillon, marquer accepté ou refusé',
                'quotes.send' => 'Envoyer un devis',
                'quotes.convert' => 'Convertir un devis accepté en commande',
                'quotes.cancel' => 'Annuler un devis',
                'quotes.discount' => 'Accorder des remises et modifier les prix',
            ],
            'Commandes' => [
                'orders.view' => 'Consulter les commandes',
                'orders.create' => 'Créer une commande directe (réservé, pas encore utilisé)',
                'orders.update' => 'Confirmer une commande',
                'orders.prepare' => 'Passer une commande en préparation',
                'orders.cancel' => 'Annuler une commande',
            ],
            'Livraisons' => [
                'deliveries.view' => 'Consulter les livraisons',
                'deliveries.create' => 'Planifier, expédier et annuler un bon de livraison',
                'deliveries.confirm' => 'Confirmer le départ et la livraison',
            ],
            'Facturation' => [
                'invoices.view' => 'Consulter les factures',
                'invoices.create' => 'Créer, modifier et émettre une facture',
                'invoices.cancel' => 'Annuler une facture',
            ],
            'Paiements' => [
                'payments.view' => 'Consulter les paiements',
                'payments.record' => 'Enregistrer un paiement',
                'payments.cancel' => 'Annuler un paiement',
            ],
            'Rapports' => [
                'reports.sales' => 'Rapports et chiffres de ventes',
                'reports.financial' => 'Rapports financiers (encaissements, soldes)',
            ],
            'Administration' => [
                'users.manage' => 'Gérer les utilisateurs, rôles et permissions',
                'settings.manage' => 'Modifier les paramètres',
                'audit.view' => 'Consulter le journal d’audit',
            ],

            'Demandes en ligne' => [
                'requests.view' => 'Consulter les demandes de devis et de commande en ligne',
                'requests.manage' => 'Traiter les demandes (prise en charge, conversion en devis, clôture)',
            ],
        ];
    }

    /** Permissions présentes en base mais absentes du catalogue. */
    public static function others(array $existing): array
    {
        $known = collect(self::groups())->flatMap(fn($g) => array_keys($g))->all();

        return array_values(array_diff($existing, $known));
    }
}
