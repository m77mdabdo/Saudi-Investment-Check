<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LandingPage;
use App\Services\AnalyticsService;
use App\Services\MediaService;
use App\Services\ScoringService;
use App\Services\SettingsService;
use App\Services\VisitorContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __construct(
        protected VisitorContext $context,
        protected AnalyticsService $analytics,
        protected MediaService $media,
        protected SettingsService $settings,
        protected ScoringService $scoring,
    ) {}

    public function __invoke(Request $request): View
    {
        $this->context->capture($request);
        $this->analytics->recordOnce('landing_page_view', $request);

        $page = LandingPage::query()->where('slug', 'default')->where('is_active', true)->first();
        $event = Event::current();
        $questionCount = $this->scoring->questions()->count();

        return view('public.landing', [
            'page' => $page,
            'content' => $page?->localizedContent() ?? [],
            'event' => $event,
            'hero' => $this->media->slot('hero'),
            'portal' => $this->media->slot('portal'),
            'eventImage' => $this->media->slot('event'),
            'cta' => $this->settings->ctaLinks(),
            'questionCount' => $questionCount,
            'seoTitle' => $page?->seoTitle() ?: __('seo.home.title'),
            'seoDescription' => $page?->seoDescription() ?: __('seo.home.description'),
            'footerNote' => $this->settings->localized('footer_note'),
        ]);
    }
}
