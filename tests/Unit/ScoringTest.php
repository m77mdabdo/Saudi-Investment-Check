<?php

namespace Tests\Unit;

use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringTest extends TestCase
{
    use RefreshDatabase;

    protected ScoringService $scoring;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        $this->scoring = app(ScoringService::class);
    }

    public function test_the_maximum_score_is_twelve(): void
    {
        $this->assertSame(12, $this->scoring->maxScore());
    }

    public static function boundaries(): array
    {
        return [
            '12 → READY' => [12, 'ready', 'Hot Lead'],
            '9 → READY' => [9, 'ready', 'Hot Lead'],
            '8 → NEEDS PREP' => [8, 'needs_prep', 'Warm Lead'],
            '5 → NEEDS PREP' => [5, 'needs_prep', 'Warm Lead'],
            '4 → EARLY' => [4, 'early', 'Early Lead'],
            '0 → EARLY' => [0, 'early', 'Early Lead'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('boundaries')]
    public function test_score_boundaries_map_to_the_right_result(int $score, string $key, string $classification): void
    {
        $result = $this->scoring->evaluate($this->answersScoring($score));

        $this->assertSame($score, $result['score'], 'The ladder did not produce the expected score.');
        $this->assertSame($key, $result['rule']->key);
        $this->assertSame($classification, $result['rule']->classification);
    }

    public function test_unknown_option_keys_score_nothing(): void
    {
        $result = $this->scoring->evaluate(['company_stage' => 'made-up', 'sector' => 'tech']);

        $this->assertSame(0, $result['score']);
        $this->assertSame('early', $result['rule']->key);
    }

    public function test_unscored_questions_do_not_add_points(): void
    {
        $result = $this->scoring->evaluate(['sector' => 'tech', 'main_question' => 'cost']);

        $this->assertSame(0, $result['score']);
    }

    public function test_free_text_answers_are_sanitised(): void
    {
        $answers = $this->perfectAnswers();
        $answers['sector'] = 'other';
        $answers['sector_detail'] = '<script>alert(1)</script> تجارة';

        $result = $this->scoring->evaluate($answers);
        $detail = collect($result['breakdown'])->firstWhere('text', '!=', null);

        $this->assertStringNotContainsString('<script>', $detail['text']);
    }

    public function test_validation_rules_follow_the_live_quiz(): void
    {
        $rules = $this->scoring->validationRules();

        $this->assertArrayHasKey('answers.company_stage', $rules);
        $this->assertContains('required', $rules['answers.company_stage']);
        $this->assertStringContainsString('established', implode(',', $rules['answers.company_stage']));
    }
}
