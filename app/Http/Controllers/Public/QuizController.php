<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadSubmissionRequest;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\QuizQuestion;
use App\Services\AnalyticsService;
use App\Services\LeadService;
use App\Services\MediaService;
use App\Services\NotificationService;
use App\Services\ScoringService;
use App\Services\SettingsService;
use App\Services\VisitorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function __construct(
        protected ScoringService $scoring,
        protected VisitorContext $context,
        protected AnalyticsService $analytics,
        protected LeadService $leads,
        protected NotificationService $notifications,
        protected MediaService $media,
        protected SettingsService $settings,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $this->context->capture($request);
        $questions = $this->scoring->questions();

        if ($questions->isEmpty()) {
            return redirect()->route('landing')->with('status', 'التقييم مش متاح حاليًا.');
        }

        $this->analytics->recordOnce('quiz_started', $request);

        $page = LandingPage::query()->where('slug', 'default')->first();

        return view('public.quiz', [
            'questions' => $questions->map(fn (QuizQuestion $q) => [
                'key' => $q->key,
                'type' => $q->type,
                'title' => $q->title,
                'subtitle' => $q->subtitle,
                'icon' => $q->icon,
                'placeholder' => $q->placeholder,
                'required' => (bool) $q->is_required,
                'options' => $q->activeOptions->map(fn ($o) => [
                    'key' => $o->key,
                    'label' => $o->label,
                    'description' => $o->description,
                    'icon' => $o->icon,
                    'requires_detail' => (bool) $o->requires_detail,
                    'detail_label' => $o->detail_label,
                ])->values(),
            ])->values(),
            'saved' => $this->savedAnswers($request),
            'content' => $page?->content ?? [],
            'countries' => config('countries.list'),
            'defaultCountry' => config('countries.default'),
            'cta' => $this->settings->ctaLinks(),
            'backdrop' => $this->media->slot('quiz'),
        ]);
    }

    /** Stores in-progress answers in the server session (nothing sensitive client-side). */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['array'],
            'answers.*' => ['nullable'],
            'index' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        // Flat scalars or flat arrays of scalars only — nothing nested ends up
        // in the session, and every value is re-validated on submit anyway.
        $answers = collect($validated['answers'] ?? [])
            ->take(60)
            ->map(function ($value) {
                if (is_array($value)) {
                    return collect($value)->filter(fn ($v) => is_string($v))
                        ->map(fn ($v) => mb_substr($v, 0, 200))->take(20)->values()->all();
                }

                return is_string($value) ? mb_substr($value, 0, 500) : null;
            })
            ->filter(fn ($v) => $v !== null)
            ->all();

        $request->session()->put(config('creativemark.quiz.session_key'), [
            'answers' => $answers,
            'index' => (int) ($validated['index'] ?? 0),
            'updated_at' => now()->timestamp,
        ]);

        return response()->json(['ok' => true]);
    }

    public function progress(Request $request): JsonResponse
    {
        $request->validate(['question' => ['required', 'string', 'max:60']]);

        $this->analytics->record('quiz_question_completed', $request, null, [
            'question' => $request->string('question')->limit(60)->value(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function completed(Request $request): JsonResponse
    {
        $this->analytics->recordOnce('quiz_completed', $request);
        $this->analytics->recordOnce('lead_form_viewed', $request);

        return response()->json(['ok' => true]);
    }

    public function submit(LeadSubmissionRequest $request): RedirectResponse
    {
        // Duplicate-submission protection: one lead per session per 10 minutes.
        if ($uuid = $request->session()->get('smrc.last_lead_uuid')) {
            $at = (int) $request->session()->get('smrc.last_lead_at', 0);

            if ($at > now()->subMinutes(10)->timestamp) {
                return redirect()->route('result', ['lead' => $uuid]);
            }
        }

        $lead = $this->leads->create($request->contact(), $request->answers(), $request);

        $request->session()->put('smrc.last_lead_uuid', $lead->uuid);
        $request->session()->put('smrc.last_lead_at', now()->timestamp);
        $request->session()->push('smrc.leads', $lead->uuid);
        $request->session()->forget(config('creativemark.quiz.session_key'));

        $this->analytics->record('lead_submitted', $request, $lead, ['score' => $lead->score]);
        $this->analytics->record(match ($lead->result_key) {
            'ready' => 'result_ready',
            'needs_prep' => 'result_needs_prep',
            default => 'result_early_stage',
        }, $request, $lead);

        try {
            $this->notifications->leadCreated($lead);
        } catch (\Throwable $e) {
            Log::error('lead.notification_failed', ['lead' => $lead->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('result', ['lead' => $lead->uuid]);
    }

    public function result(Request $request, string $lead): View|RedirectResponse
    {
        $model = Lead::query()->with(['rule', 'answers', 'event'])->where('uuid', $lead)->first();

        if (! $model) {
            return redirect()->route('landing');
        }

        $owned = in_array($lead, (array) $request->session()->get('smrc.leads', []), true)
            || $request->session()->get('smrc.last_lead_uuid') === $lead;

        if (! $owned && ! $request->hasValidSignature()) {
            return redirect()->route('landing');
        }

        $rule = $model->rule;
        $slot = match ($model->result_key) {
            'ready' => 'result_ready',
            'needs_prep' => 'result_needs_prep',
            default => 'result_early',
        };

        return view('public.result', [
            'lead' => $model,
            'rule' => $rule,
            'cta' => $this->settings->ctaLinks(),
            'image' => $this->media->slot($slot),
            'disclaimer' => $rule?->disclaimer ?: $this->settings->get('result_disclaimer'),
            'footerNote' => $this->settings->get('footer_note', '© Creative Mark'),
        ]);
    }

    /** Lightweight beacon for CTA / meeting clicks. */
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'in:cta_clicked,meeting_clicked'],
            'label' => ['nullable', 'string', 'max:80'],
            'lead' => ['nullable', 'string', 'max:64'],
        ]);

        $lead = ! empty($data['lead']) ? Lead::query()->where('uuid', $data['lead'])->first() : null;

        $this->analytics->record($data['name'], $request, $lead, ['label' => $data['label'] ?? null]);

        return response()->json(['ok' => true]);
    }

    protected function savedAnswers(Request $request): array
    {
        $state = $request->session()->get(config('creativemark.quiz.session_key'), []);
        $answers = is_array($state['answers'] ?? null) ? $state['answers'] : [];

        if ($old = old('answers')) {
            $answers = array_merge($answers, is_array($old) ? $old : []);
        }

        return [
            'answers' => (object) $answers,
            'index' => (int) ($state['index'] ?? 0),
            'contact' => [
                'name' => old('name', ''),
                'company' => old('company', ''),
                'phone' => old('phone', ''),
                'country_code' => old('country_code', ''),
                'email' => old('email', ''),
            ],
        ];
    }
}
