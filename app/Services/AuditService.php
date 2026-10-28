<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Journalise une action sensible. Ne jamais passer de mot de passe dans $old / $new.
     */
   public function log(string $action, Model $subject, ?array $old = null, ?array $new = null, bool $system = false): void
{
    AuditLog::create([
        'user_id' => $system ? null : Auth::id(),
        'action' => $action,
        'subject_type' => $subject::class,
        'subject_id' => $subject->getKey(),
        'old_values' => $old,
        'new_values' => $new,
        'ip_address' => $system ? null : request()->ip(),
    ]);
}
}
