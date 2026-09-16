<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LandingPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CmsController extends Controller
{
    public function edit(): View
    {
        $page = LandingPage::query()->firstOrCreate(['slug' => 'default'], ['name' => 'Landing', 'content' => []]);

        return view('admin.cms.edit', [
            'page' => $page,
            'content' => $page->content ?? [],
            'events' => Event::query()->orderByDesc('is_default')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $page = LandingPage::query()->firstOrCreate(['slug' => 'default'], ['name' => 'Landing', 'content' => []]);

        $data = $request->validate([
            'event_id' => ['nullable', 'exists:events,id'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'hero_image_query' => ['nullable', 'string', 'max:120'],
            'content' => ['array'],
            'content.eyebrow' => ['nullable', 'string', 'max:120'],
            'content.hero_kicker' => ['nullable', 'string', 'max:160'],
            'content.hero_lead' => ['nullable', 'string', 'max:160'],
            'content.hero_title' => ['nullable', 'string', 'max:200'],
            'content.hero_description' => ['nullable', 'string', 'max:800'],
            'content.hero_meta' => ['nullable', 'string', 'max:120'],
            'content.cta_label' => ['nullable', 'string', 'max:80'],
            'content.quiz_intro' => ['nullable', 'string', 'max:200'],
            'content.lead_headline' => ['nullable', 'string', 'max:200'],
            'content.lead_text' => ['nullable', 'string', 'max:300'],
            'content.lead_cta' => ['nullable', 'string', 'max:80'],
            'content.consent_text' => ['nullable', 'string', 'max:400'],
            'content.footer_note' => ['nullable', 'string', 'max:300'],
            'benefits' => ['array'],
            'benefits.*.icon' => ['nullable', 'string', 'max:8'],
            'benefits.*.title' => ['nullable', 'string', 'max:80'],
            'benefits.*.text' => ['nullable', 'string', 'max:160'],
        ]);

        $content = array_merge($page->content ?? [], array_map(
            fn ($v) => is_string($v) ? trim($v) : $v,
            $data['content'] ?? []
        ));

        $content['benefits'] = collect($data['benefits'] ?? [])
            ->filter(fn ($b) => filled($b['title'] ?? null))
            ->map(fn ($b) => ['icon' => $b['icon'] ?? '', 'title' => $b['title'], 'text' => $b['text'] ?? ''])
            ->values()->all();

        $page->update([
            'event_id' => $data['event_id'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'hero_image_query' => $data['hero_image_query'] ?? null,
            'content' => $content,
        ]);

        return back()->with('success', 'تم حفظ محتوى الصفحة.');
    }
}
