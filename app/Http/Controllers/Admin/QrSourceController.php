<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\QrSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QrSourceController extends Controller
{
    public function index(): View
    {
        return view('admin.qr.index', [
            'sources' => QrSource::query()->with('event')->withCount(['leads', 'leads as hot_leads_count' => fn ($q) => $q->where('result_key', 'ready')])
                ->orderByDesc('leads_count')->get(),
            'events' => Event::query()->orderByDesc('is_default')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        QrSource::create($this->validated($request));

        return back()->with('success', 'تم إنشاء مصدر QR.');
    }

    public function update(Request $request, QrSource $qr): RedirectResponse
    {
        $qr->update($this->validated($request, $qr));

        return back()->with('success', 'تم حفظ مصدر QR.');
    }

    public function toggle(QrSource $qr): RedirectResponse
    {
        $qr->update(['is_active' => ! $qr->is_active]);

        return back()->with('success', $qr->is_active ? 'المصدر اتفعّل.' : 'المصدر اتوقف.');
    }

    public function destroy(QrSource $qr): RedirectResponse
    {
        abort_if($qr->leads()->exists(), 422, 'Cannot delete a QR source that already has leads.');

        $qr->delete();

        return back()->with('success', 'تم حذف المصدر.');
    }

    protected function validated(Request $request, ?QrSource $qr = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9_\-]+$/', 'unique:qr_sources,slug'.($qr ? ','.$qr->id : '')],
            'event_id' => ['nullable', 'exists:events,id'],
            'campaign' => ['nullable', 'string', 'max:80'],
            'medium' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = Str::slug(($data['slug'] ?? null) ?: $data['name'], '_');
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
