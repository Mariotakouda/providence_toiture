<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private SettingService $settings)
    {
    }

    public function edit(): View
    {
        return view('settings.edit', [
            'values' => $this->settings->all(),
            'logo' => $this->settings->logoDataUri(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $keys = array_diff(array_keys(SettingService::DEFAULTS), ['company_logo']);

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = (string) ($request->input($key) ?? '');
        }

        $values['tax_enabled'] = $request->boolean('tax_enabled') ? '1' : '0';
        $values['numbering_reset_yearly'] = $request->boolean('numbering_reset_yearly') ? '1' : '0';
        $values['tax_rate'] = $values['tax_rate'] !== '' ? $values['tax_rate'] : '0';

        $oldLogo = $this->settings->get('company_logo');

        if ($request->hasFile('company_logo')) {
            $values['company_logo'] = $request->file('company_logo')->store('branding', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $values['company_logo'] = '';
        }

        $this->settings->set($values, $request->user());

        if (array_key_exists('company_logo', $values) && $oldLogo && $oldLogo !== $values['company_logo']) {
            Storage::disk('public')->delete($oldLogo);
        }

        return redirect()->route('settings.edit')->with('success', 'Paramètres enregistrés.');
    }
}
