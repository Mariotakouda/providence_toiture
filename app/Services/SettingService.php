<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    private const CACHE_KEY = 'app.settings';

    /** Valeurs par défaut : toutes modifiables depuis l'écran Paramètres. */
    public const DEFAULTS = [
        'company_name' => 'Providence Toiture',
        'company_address' => '',
        'company_city' => '',
        'company_phone' => '',
        'company_email' => '',
        'company_whatsapp' => '+228 91 61 70 55',
        'company_tax_id' => '',
        'company_logo' => '',
        'currency_code' => 'FCFA',
        'tax_enabled' => '0',
        'tax_rate' => '0',
        'quote_validity_days' => '30',
        'quote_terms' => '',
        'invoice_terms' => '',
        'numbering_reset_yearly' => '1',
        'numbering_padding' => '4',
        'prefix_quote' => 'DEV',
        'prefix_order' => 'CMD',
        'prefix_invoice' => 'FAC',
        'prefix_payment' => 'REC',
        'prefix_delivery' => 'BL',
        'prefix_request' => 'WEB',
    ];

    private ?array $memo = null;

    public function __construct(private AuditService $audit)
    {
    }

    public function all(): array
    {
        if ($this->memo === null) {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());
            $this->memo = array_merge(self::DEFAULTS, array_map(fn ($v) => (string) ($v ?? ''), $stored));
        }

        return $this->memo;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    public function bool(string $key): bool
    {
        return in_array($this->get($key), ['1', 'true', 'on'], true);
    }

    /**
     * @param  array<string, string>  $values  clés autorisées : DEFAULTS
     */
    public function set(array $values, User $by): void
    {
        DB::transaction(function () use ($values, $by) {
            foreach ($values as $key => $value) {
                if (! array_key_exists($key, self::DEFAULTS)) {
                    continue;
                }

                $setting = Setting::firstOrNew(['key' => $key]);
                $old = $setting->exists ? (string) $setting->value : (self::DEFAULTS[$key]);

                if ($setting->exists && $old === $value) {
                    continue;
                }

                if (! $setting->exists && $value === self::DEFAULTS[$key]) {
                    continue;
                }

                $setting->fill(['value' => $value, 'updated_by' => $by->id])->save();

                $this->audit->log('setting.updated', $setting, ['value' => $old], ['value' => $value]);
            }
        });

        Cache::forget(self::CACHE_KEY);
        $this->memo = null;
    }

    public function logoDataUri(): ?string
    {
        $path = $this->get('company_logo');

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return 'data:'.Storage::disk('public')->mimeType($path).';base64,'.base64_encode(Storage::disk('public')->get($path));
    }

    /**
     * Logo des documents PDF : celui des paramètres, sinon le logo par défaut du site.
     */
    public function logoDataUriOrDefault(): ?string
    {
        if ($custom = $this->logoDataUri()) {
            return $custom;
        }

        $default = public_path('images/logo.png');

        return is_file($default)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($default))
            : null;
    }
}
