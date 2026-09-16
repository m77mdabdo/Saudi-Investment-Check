<?php

namespace Tests;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\QuizSeeder;
use Database\Seeders\ResultRuleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Seeds the quiz definition, result rules and platform defaults. */
    protected function seedPlatform(): void
    {
        $this->seed([PlatformSeeder::class, QuizSeeder::class, ResultRuleSeeder::class]);
    }

    /** A full set of answers that scores the maximum (12). */
    protected function perfectAnswers(): array
    {
        return [
            'company_stage' => 'established',
            'sector' => 'tech',
            'saudi_goal' => 'establish',
            'saudi_traction' => 'clients',
            'timeline' => 'soon',
            'budget' => 'ready',
            'operational_readiness' => 'ready',
            'main_question' => 'cost',
        ];
    }

    /** Answers scoring exactly $score by stepping each scored question down. */
    protected function answersScoring(int $score): array
    {
        $ladders = [
            'company_stage' => ['starting' => 'idea', 1 => 'operating', 2 => 'established'],
            'saudi_goal' => [0 => 'explore', 1 => 'expand', 2 => 'establish'],
            'saudi_traction' => [0 => 'none', 1 => 'inquiries', 2 => 'clients'],
            'timeline' => [0 => 'later', 1 => 'mid', 2 => 'soon'],
            'budget' => [0 => 'undefined', 1 => 'limited', 2 => 'ready'],
            'operational_readiness' => [0 => 'starting', 1 => 'partial', 2 => 'ready'],
        ];
        $ladders['company_stage'] = [0 => 'idea', 1 => 'operating', 2 => 'established'];

        $answers = ['sector' => 'tech', 'main_question' => 'cost'];
        $remaining = $score;

        foreach ($ladders as $question => $options) {
            $points = max(0, min(2, $remaining));
            $answers[$question] = $options[$points];
            $remaining -= $points;
        }

        return $answers;
    }

    /** @param array<string,mixed> $overrides */
    protected function leadPayload(array $overrides = [], ?array $answers = null): array
    {
        return array_merge([
            'name' => 'Ahmed Samir',
            'company' => 'XYZ Technologies',
            'country_code' => '+20',
            'phone' => '1000000000',
            'email' => 'ahmed@example.com',
            'consent' => '1',
            'answers' => $answers ?? $this->perfectAnswers(),
        ], $overrides);
    }

    protected function submitQuiz(array $overrides = [], ?array $answers = null)
    {
        return $this->post('/quiz/submit', $this->leadPayload($overrides, $answers));
    }

    protected function admin(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    protected function latestLead(): ?Lead
    {
        return Lead::query()->latest('id')->first();
    }
}
