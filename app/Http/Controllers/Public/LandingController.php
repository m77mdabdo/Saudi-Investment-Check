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

        return view('public.landing', [
            'page' => $page,
            'content' => $page?->content ?? [],
            'event' => $event,
            'hero' => $this->media->slot('hero'),
            'eventImage' => $this->media->slot('event'),
            'cta' => $this->settings->ctaLinks(),
            'questionCount' => $this->scoring->questions()->count(),
            'footerNote' => $this->settings->get('footer_note', '© Creative Mark'),
        ]);
    }
}
