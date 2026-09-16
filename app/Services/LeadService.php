<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadAnswer;
use App\Models\QuizQuestion;
use App\Models\SalesStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadService
{
    public function __construct(
        protected ScoringService $scoring,
        protected VisitorContext $context,
    ) {}

    /**
     * Creates a lead from validated public input. Scoring happens here,
     * server-side; the browser only ever submits option keys.
     *
     * @param  array{name:string,company:string,whatsapp:string,email?:?string,consent:bool}  $contact
     * @param  array<string,mixed>  $answers
     */
    public function create(array $contact, array $answers, Request $request): Lead
    {
        $evaluation = $this->scoring->evaluate($answers);
        $ctx = $this->context->get($request);
        $rule = $evaluation['rule'];

        return DB::transaction(function () use ($contact, $evaluation, $ctx, $rule, $request) {
            $lead = Lead::create([
                'event_id' => $ctx['event_id'] ?? null,
                'qr_source_id' => $ctx['qr_source_id'] ?? null,
                'name' => $contact['name'],
                'company' => $contact['company'],
                'whatsapp' => $contact['whatsapp'],
                'whatsapp_country' => $contact['whatsapp_country'] ?? null,
                'email' => $contact['email'] ?? null,
                'consent' => (bool) $contact['consent'],
                'consent_at' => now(),
                'score' => $evaluation['score'],
                'max_score' => $evaluation['max'],
                'result_key' => $rule?->key,
                'classification' => $rule?->classification,
                'result_rule_id' => $rule?->id,
                'sales_status' => SalesStatus::defaultKey(),
                'status_changed_at' => now(),
                'source' => $ctx['source'] ?? null,
                'utm_source' => $ctx['utm_source'] ?? null,
                'utm_medium' => $ctx['utm_medium'] ?? null,
                'utm_campaign' => $ctx['utm_campaign'] ?? null,
                'utm_content' => $ctx['utm_content'] ?? null,
                'device' => $ctx['device'] ?? null,
                'browser' => $ctx['browser'] ?? null,
                'platform' => $ctx['platform'] ?? null,
                'locale' => $ctx['locale'] ?? null,
                'ip_hash' => $this->context->ipHash($request),
                'session_hash' => $ctx['session_hash'] ?? null,
                'answers_summary' => $this->summary($evaluation['breakdown']),
            ]);

            foreach ($evaluation['breakdown'] as $row) {
                /** @var QuizQuestion $question */
                $question = $row['question'];

                LeadAnswer::create([
                    'lead_id' => $lead->id,
                    'quiz_question_id' => $question->id,
                    'quiz_option_id' => $row['option']?->id,
                    'question_key' => $question->key,
                    'question_title' => $question->title,
                    'option_key' => $row['option']?->key,
                    'answer_label' => $row['label'],
                    'answer_text' => $row['text'],
                    'score' => $row['score'],
                ]);
            }

            $lead = $lead->fresh(['event', 'qrSource', 'rule', 'answers']);

            // Operational trail — ids and outcome only, never contact details.
            Log::info('lead.created', [
                'lead_id' => $lead->id,
                'score' => $lead->score,
                'result' => $lead->result_key,
                'locale' => $lead->locale,
                'source' => $lead->source,
                'event_id' => $lead->event_id,
                'has_email' => (bool) $lead->email,
            ]);

            return $lead;
        });
    }

    /** Flat, export-friendly snapshot of the answers. */
    protected function summary(array $breakdown): array
    {
        $summary = [];

        foreach ($breakdown as $row) {
            /** @var QuizQuestion $question */
            $question = $row['question'];
            $value = $row['label'] ?? $row['text'];

            if (isset($summary[$question->key])) {
                $summary[$question->key] .= ' | '.$value;
            } else {
                $summary[$question->key] = (string) $value;
            }

            if ($row['option']?->requires_detail && $row['text']) {
                $summary[$question->key.'_other'] = $row['text'];
            }
        }

        return $summary;
    }
}
