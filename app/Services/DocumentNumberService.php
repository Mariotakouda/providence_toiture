<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentNumberService
{
    public function __construct(private SettingService $settings)
    {
    }

    /**
     * Prochain numéro d'un document : quote, order, invoice, payment, delivery.
     * Doit être appelé dans la même transaction que la création du document :
     * si la création échoue, le compteur est annulé aussi, sans trou.
     */
    public function next(string $type): string
    {
        if (! array_key_exists("prefix_{$type}", SettingService::DEFAULTS)) {
            throw new InvalidArgumentException("Type de document inconnu : {$type}");
        }

        return DB::transaction(function () use ($type) {
            $year = (int) now()->year;
            $sequenceYear = $this->settings->bool('numbering_reset_yearly') ? $year : 0;

            // Garantit l'existence de la ligne, puis la verrouille : deux créations
            // simultanées sont sérialisées et reçoivent deux numéros différents.
            DB::table('document_sequences')->insertOrIgnore([
                'type' => $type,
                'year' => $sequenceYear,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('document_sequences')
                ->where('type', $type)
                ->where('year', $sequenceYear)
                ->lockForUpdate()
                ->first();

            $number = $row->last_number + 1;

            DB::table('document_sequences')
                ->where('id', $row->id)
                ->update(['last_number' => $number, 'updated_at' => now()]);

            $padding = max(3, (int) $this->settings->get('numbering_padding', '4'));

            return sprintf(
                '%s-%d-%s',
                $this->settings->get("prefix_{$type}"),
                $year,
                str_pad((string) $number, $padding, '0', STR_PAD_LEFT)
            );
        });
    }
}