<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',
            self::MobileMoney => 'Mobile Money',
            self::BankTransfer => 'Virement',
            self::Cheque => 'Chèque',
        };
    }

    public function requiresReference(): bool
    {
        return $this !== self::Cash;
    }
}
