<?php

namespace App\Policies;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quotes.view');
    }

    public function view(User $user, Quote $quote): bool
    {
        return $user->can('quotes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('quotes.create');
    }

    /** Le contenu ne se modifie qu'en brouillon : ensuite le devis garde son instantané. */
    public function update(User $user, Quote $quote): bool
    {
        return $user->can('quotes.update') && $quote->status->isEditable();
    }

    public function transition(User $user, Quote $quote, QuoteStatus $to): bool
    {
        if (! $quote->status->canTransitionTo($to)) {
            return false;
        }

        return match ($to) {
            QuoteStatus::Sent => $user->can('quotes.send'),
            QuoteStatus::Cancelled => $user->can('quotes.cancel'),
            QuoteStatus::Accepted, QuoteStatus::Rejected, QuoteStatus::Draft => $user->can('quotes.update'),
            default => false,
        };
    }

    public function convert(User $user, Quote $quote): bool
    {
        return $user->can('quotes.convert')
            && $quote->status === QuoteStatus::Accepted
            && ! $quote->order()->exists();
    }
}
