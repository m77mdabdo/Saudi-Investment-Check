<?php

namespace App\Services;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\ResultRule;
use Illuminate\Support\Collection;

/**
 * Server-side scoring. Answers coming from the browser are never trusted for
 * points — only option keys are accepted and scores are read from the database.
 */
class ScoringService
{
    /** @return Collection<int, QuizQuestion> */
    public function questions(): Collection
    {
        return QuizQuestion::active()->with('activeOptions')->get();
    }

    public function maxScore(): int
    {
        return (int) $this->questions()
            ->filter(fn (QuizQuestion $q) => $q->is_scored && $q->isChoice())
            ->sum(function (QuizQuestion $q) {
                $scores = $q->activeOptions->pluck('score');

                if ($scores->isEmpty()) {
                    return 0;
                }

                return $q->type === 'multiple' ? max(0, (int) $scores->sum()) : (int) $scores->max();
            });
    }

    /**
     * @param  array<string,mixed>  $answers  question_key => option key|array|text
     * @return array{score:int,max:int,rule:?ResultRule,breakdown:array<int,array<string,mixed>>}
     */
    public function evaluate(array $answers): array
    {
        $questions = $this->questions();
        $score = 0;
        $breakdown = [];

        foreach ($questions as $question) {
            $raw = $answers[$question->key] ?? null;

            if ($question->isChoice()) {
                $keys = collect(is_array($raw) ? $raw : [$raw])
                    ->filter(fn ($v) => is_string($v) && $v !== '')
                    ->unique();

                if ($question->type === 'single') {
                    $keys = $keys->take(1);
                }

                /** @var Collection<int, QuizOption> $options */
                $options = $question->activeOptions->whereIn('key', $keys->all());

                foreach ($options as $option) {
                    $points = $question->is_scored ? (int) $option->score : 0;
                    $score += $points;

                    $detail = null;
                    if ($option->requires_detail) {
                        $detail = $this->cleanText($answers[$question->key.'_detail'] ?? null);
                    }

                    $breakdown[] = [
                        'question' => $question,
                        'option' => $option,
                        'label' => $option->label,
                        'text' => $detail,
                        'score' => $points,
                    ];
                }

                continue;
            }

            $text = $this->cleanText($raw);

            if ($text !== null) {
                $breakdown[] = [
                    'question' => $question,
                    'option' => null,
                    'label' => null,
                    'text' => $text,
                    'score' => 0,
                ];
            }
        }

        $max = $this->maxScore();
        $score = max(0, min($score, $max));

        return [
            'score' => $score,
            'max' => $max,
            'rule' => ResultRule::forScore($score),
            'breakdown' => $breakdown,
        ];
    }

    /** Validation rules derived from the live quiz definition. */
    public function validationRules(): array
    {
        $rules = [];

        foreach ($this->questions() as $question) {
            $field = 'answers.'.$question->key;
            $required = $question->is_required ? 'required' : 'nullable';

            if ($question->type === 'multiple') {
                $rules[$field] = [$required, 'array'];
                $rules[$field.'.*'] = ['string', 'in:'.$question->activeOptions->pluck('key')->implode(',')];
            } elseif ($question->type === 'single') {
                $rules[$field] = [$required, 'string', 'in:'.$question->activeOptions->pluck('key')->implode(',')];
            } else {
                $rules[$field] = [$required, 'string', 'max:500'];
            }

            foreach ($question->activeOptions as $option) {
                if ($option->requires_detail) {
                    $rules['answers.'.$question->key.'_detail'] = ['nullable', 'string', 'max:160'];
                }
            }
        }

        return $rules;
    }

    protected function cleanText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(strip_tags($value));

        return $value === '' ? null : mb_substr($value, 0, 500);
    }
}
