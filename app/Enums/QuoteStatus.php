<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Sent => 'Envoyé',
            self::Accepted => 'Accepté',
            self::Rejected => 'Refusé',
            self::Expired => 'Expiré',
            self::Cancelled => 'Annulé',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-200 text-gray-700',
            self::Sent => 'bg-blue-100 text-blue-700',
            self::Accepted => 'bg-green-100 text-green-700',
            self::Rejected => 'bg-red-100 text-red-700',
            self::Expired => 'bg-orange-100 text-orange-700',
            self::Cancelled => 'bg-gray-300 text-gray-700',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent, self::Cancelled],
            self::Sent => [self::Accepted, self::Rejected, self::Cancelled, self::Draft],
            self::Expired => [self::Draft],
            self::Accepted, self::Rejected, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /** Libellé du bouton qui mène à ce statut. */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Sent => 'Marquer comme envoyé',
            self::Accepted => 'Marquer comme accepté',
            self::Rejected => 'Marquer comme refusé',
            self::Cancelled => 'Annuler le devis',
            self::Draft => 'Repasser en brouillon',
            self::Expired => 'Expirer',
        };
    }
}
