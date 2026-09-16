<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesStatus;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(protected SettingsService $settings) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'groups' => Setting::query()->orderBy('group')->orderBy('position')->get()->groupBy('group'),
            'statuses' => SalesStatus::query()->orderBy('position')->get(),
            'mail' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'from' => config('mail.from.address'),
                'configured' => filled(config('mail.mailers.smtp.username')),
            ],
            'integrations' => [
                'pexels' => filled(config('services.pexels.key')),
                'google' => filled(config('services.google.client_id')) && filled(config('services.google.client_secret')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $known = Setting::query()->get()->keyBy('key');

        foreach ((array) $request->input('settings', []) as $key => $value) {
            if (! $setting = $known->get($key)) {
                continue;
            }

            if ($setting->type === 'url' && filled($value) && ! filter_var($value, FILTER_VALIDATE_URL)) {
                return back()->withErrors([$key => 'الرابط ده مش صحيح.'])->withInput();
            }

            $setting->update(['value' => $setting->type === 'bool' ? (filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0') : $value]);
        }

        /*
         | Booleans are submitted as an explicit 0/1 pair (hidden input + checkbox)
         | so a form that does not render a toggle can never silently switch it
         | off — that is what stopped customer result emails from going out.
         */

        $this->settings->flush();

        return back()->with('success', __('admin.flash.saved'));
    }

    public function storeStatus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'color' => ['required', 'string', 'in:blue,indigo,violet,amber,emerald,gold,slate,coral'],
        ]);

        SalesStatus::create([
            'key' => Str::slug($data['label'], '_'),
            'label' => $data['label'],
            'color' => $data['color'],
            'position' => (int) SalesStatus::max('position') + 1,
            'is_active' => true,
        ]);

        return back()->with('success', __('admin.flash.created'));
    }

    public function updateStatus(Request $request, SalesStatus $status): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'color' => ['required', 'string', 'in:blue,indigo,violet,amber,emerald,gold,slate,coral'],
            'position' => ['nullable', 'integer', 'min:0', 'max:99'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'notify_client' => ['nullable', 'boolean'],
            'label_en' => ['nullable', 'string', 'max:40'],
        ]);

        $status->fill([
            'label' => $data['label'],
            'color' => $data['color'],
            'position' => $data['position'] ?? $status->position,
            'is_active' => $request->boolean('is_active'),
            'is_default' => $request->boolean('is_default'),
            'notify_client' => $request->boolean('notify_client'),
        ]);

        $status->setTranslations('en', ['label' => $data['label_en'] ?? null]);
        $status->save();

        if ($status->is_default) {
            SalesStatus::query()->whereKeyNot($status->id)->update(['is_default' => false]);
        }

        return back()->with('success', __('admin.flash.saved'));
    }

    public function destroyStatus(SalesStatus $status): RedirectResponse
    {
        abort_if($status->is_default, 422, 'Cannot delete the default status.');

        $status->delete();

        return back()->with('success', __('admin.flash.deleted'));
    }
}
