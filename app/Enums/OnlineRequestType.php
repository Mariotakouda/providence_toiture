<?php

namespace App\Enums;

enum OnlineRequestType: string
{
    case Quote = 'quote';
    case Order = 'order';

    public function label(): string
    {
        return match ($this) {
            self::Quote => 'Demande de devis',
            self::Order => 'Souhait de commande',
        };
    }
}
