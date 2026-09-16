<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\MediaService;
use App\Services\PexelsClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $media,
        protected PexelsClient $pexels,
    ) {}

    public function index(Request $request): View
    {
        $slot = $request->query('slot');
        $slot = array_key_exists((string) $slot, MediaService::SLOTS) ? (string) $slot : 'hero';
        $query = (string) ($request->query('q') ?: $this->media->defaultQuery($slot));

        $results = $request->boolean('search') ? $this->pexels->search($query, 12) : [];

        return view('admin.media.index', [
            'slots' => MediaService::SLOTS,
            'slot' => $slot,
            'query' => $query,
            'results' => $results,
            'configured' => $this->pexels->configured(),
            'current' => collect(MediaService::SLOTS)->mapWithKeys(fn ($label, $key) => [$key => $this->media->slot($key)]),
            'assets' => MediaAsset::query()->where('is_active', true)->get()->keyBy('slot'),
        ]);
    }

    public function pin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slot' => ['required', 'string', 'in:'.implode(',', array_keys(MediaService::SLOTS))],
            'url' => ['required', 'url', 'max:1000'],
            'thumb_url' => ['nullable', 'url', 'max:1000'],
            'external_id' => ['nullable', 'string', 'max:40'],
            'photographer' => ['nullable', 'string', 'max:120'],
            'photographer_url' => ['nullable', 'url', 'max:500'],
            'avg_color' => ['nullable', 'string', 'max:20'],
            'query' => ['nullable', 'string', 'max:120'],
        ]);

        $this->media->pin($data['slot'], $data, $data['query'] ?? null);

        return back()->with('success', 'تم تعيين الصورة.');
    }

    public function refresh(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slot' => ['required', 'string', 'in:'.implode(',', array_keys(MediaService::SLOTS))],
            'query' => ['nullable', 'string', 'max:120'],
        ]);

        $asset = $this->media->refresh($data['slot'], $data['query'] ?? null);

        return back()->with($asset ? 'success' : 'error', $asset
            ? 'تم تحديث الصورة من Pexels.'
            : 'تعذر جلب صورة من Pexels — الصفحة هتستخدم الصورة الاحتياطية.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $data = $request->validate(['slot' => ['required', 'string', 'in:'.implode(',', array_keys(MediaService::SLOTS))]]);

        $this->media->clear($data['slot']);

        return back()->with('success', 'رجعنا للصورة الاحتياطية.');
    }
}
