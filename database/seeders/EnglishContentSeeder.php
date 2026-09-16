<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use App\Models\NotificationTemplate;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\ResultRule;
use App\Models\SalesStatus;
use Illuminate\Database\Seeder;

/**
 * English copy for the editable content. Arabic stays in the base columns;
 * this fills `translations.en` so the /en site is genuinely English.
 * Safe to re-run — it only writes the English side.
 */
class EnglishContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->questions();
        $this->results();
        $this->landing();
        $this->statuses();
        $this->templates();
    }

    private function questions(): void
    {
        $questions = [
            'company_stage' => [
                'title' => 'What stage is your company at?',
                'subtitle' => 'Pick the closest description',
                'options' => [
                    'idea' => ['label' => 'I have an idea', 'description' => 'Or a business still being set up'],
                    'operating' => ['label' => 'We are operating', 'description' => 'Still in the growth stage'],
                    'established' => ['label' => 'An established company', 'description' => 'Steady clients and revenue'],
                ],
            ],
            'sector' => [
                'title' => 'Which sector are you in?',
                'subtitle' => 'Choose the closest one',
                'options' => [
                    'tech' => ['label' => 'Technology & software'],
                    'marketing' => ['label' => 'Marketing & advertising'],
                    'trade' => ['label' => 'Trade, import & export'],
                    'contracting' => ['label' => 'Contracting & construction'],
                    'industry' => ['label' => 'Industry & manufacturing'],
                    'consulting' => ['label' => 'Consulting & professional services'],
                    'health' => ['label' => 'Health & beauty'],
                    'food' => ['label' => 'Food & hospitality'],
                    'education' => ['label' => 'Education & training'],
                    'logistics' => ['label' => 'Transport & logistics'],
                    'other' => ['label' => 'Another sector', 'detail_label' => 'Tell us what your company does'],
                ],
            ],
            'saudi_goal' => [
                'title' => 'What is your goal in the Saudi market?',
                'subtitle' => 'What is driving you towards Saudi Arabia?',
                'options' => [
                    'explore' => ['label' => 'Exploring the market', 'description' => 'Still gathering information'],
                    'expand' => ['label' => 'Expanding my business', 'description' => 'Saudi Arabia is a target market'],
                    'establish' => ['label' => 'Ready to establish', 'description' => 'A branch or legal entity in Saudi Arabia'],
                ],
            ],
            'saudi_traction' => [
                'title' => 'Any traction in the Saudi market yet?',
                'subtitle' => 'Even something small counts',
                'options' => [
                    'none' => ['label' => 'Not yet', 'description' => 'First time considering this market'],
                    'inquiries' => ['label' => 'Interest and enquiries', 'description' => 'Clients asked or requested quotes'],
                    'clients' => ['label' => 'Actual clients', 'description' => 'Live deals or contracts'],
                ],
            ],
            'timeline' => [
                'title' => 'When do you plan to start?',
                'subtitle' => 'Timing changes which route fits you',
                'options' => [
                    'later' => ['label' => 'In a year or more', 'description' => 'Still planning'],
                    'mid' => ['label' => 'Within 6 to 12 months', 'description' => 'On the near-term plan'],
                    'soon' => ['label' => 'Within 3 months', 'description' => 'Ready to move quickly'],
                ],
            ],
            'budget' => [
                'title' => 'What budget have you set aside?',
                'subtitle' => 'A rough estimate is fine',
                'options' => [
                    'undefined' => ['label' => 'Not defined yet', 'description' => 'I need to know the costs first'],
                    'limited' => ['label' => 'A limited budget', 'description' => 'I can start as lean as possible'],
                    'ready' => ['label' => 'Budget is ready', 'description' => 'Allocated for setup and operations'],
                ],
            ],
            'operational_readiness' => [
                'title' => 'How operationally ready are you?',
                'subtitle' => 'Team, documents, ability to deliver',
                'options' => [
                    'starting' => ['label' => 'Starting from scratch', 'description' => 'I need full preparation'],
                    'partial' => ['label' => 'A team, but preparation needed', 'description' => 'Missing documents or market know-how'],
                    'ready' => ['label' => 'Operationally ready', 'description' => 'Team, documents and delivery capacity'],
                ],
            ],
            'main_question' => [
                'title' => 'What do you most need answered?',
                'subtitle' => 'So our consultant comes prepared',
                'options' => [
                    'cost' => ['label' => 'Setup cost'],
                    'procedures' => ['label' => 'Procedures and licensing'],
                    'entry_path' => ['label' => 'The best entry route'],
                    'demand' => ['label' => 'Demand for my activity'],
                    'partner' => ['label' => 'Do I need a Saudi partner?'],
                    'other' => ['label' => 'Something else', 'detail_label' => 'Write your question'],
                ],
            ],
        ];

        foreach ($questions as $key => $data) {
            $question = QuizQuestion::query()->where('key', $key)->first();

            if (! $question) {
                continue;
            }

            $question->setTranslations('en', [
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
            ]);
            $question->save();

            foreach ($data['options'] ?? [] as $optionKey => $values) {
                $option = QuizOption::query()
                    ->where('quiz_question_id', $question->id)
                    ->where('key', $optionKey)
                    ->first();

                if (! $option) {
                    continue;
                }

                $option->setTranslations('en', [
                    'label' => $values['label'] ?? null,
                    'description' => $values['description'] ?? null,
                    'detail_label' => $values['detail_label'] ?? null,
                ]);
                $option->save();
            }
        }
    }

    private function results(): void
    {
        $rules = [
            'ready' => [
                'headline' => '💥 Saudi Arabia really is waiting for you!',
                'main_text' => 'Your company shows strong signals of readiness for the Saudi market.',
                'body' => "Your answers show a solid base to seriously evaluate market entry.\nThe next step is not to rush into paperwork...\nThe next step is choosing the entry route that fits your company.",
                'highlight' => 'The right entry route means less time, lower cost and less risk.',
                'bullets' => ['The right legal entity for your activity', 'A map of procedures and expected cost', 'A practical entry plan for the first 90 days'],
                'primary_cta_label' => 'Book your free assessment at Techne',
                'secondary_cta_label' => 'Meet a Creative Mark consultant at the booth',
                'disclaimer' => 'This result is a preliminary readiness indication — not legal, financial or incorporation advice.',
            ],
            'needs_prep' => [
                'headline' => 'Very close — a few things to prepare first.',
                'main_text' => 'The Saudi market could be the right step, but a few points need review before you commit to setting up.',
                'body' => 'Points worth reviewing together before you move:',
                'highlight' => 'Better to learn these before you invest, not after you start.',
                'bullets' => ['Demand for your activity', 'Market entry plan', 'Expected cost', 'Operational readiness', 'Choosing the right activity', 'Choosing the right route'],
                'primary_cta_label' => 'Get a free Saudi Readiness Review',
                'secondary_cta_label' => 'Meet our consultant at the booth',
                'disclaimer' => 'This result is a preliminary readiness indication — not legal, financial or incorporation advice.',
            ],
            'early' => [
                'headline' => 'Hold on before starting the setup process.',
                'main_text' => 'Saudi Arabia may well be a strong opportunity for you — but this decision needs more information first.',
                'body' => 'Before you start, you need to know:',
                'highlight' => 'The right decision before setup can save a lot of time and money afterwards.',
                'bullets' => ['Is there demand for what you offer?', 'Who exactly is your customer?', 'What investment should you expect?', 'And which entry route suits you?'],
                'primary_cta_label' => 'Get the Saudi market entry checklist',
                'secondary_cta_label' => 'Ask a Creative Mark consultant about your first step',
                'disclaimer' => 'This result is a preliminary readiness indication — not legal, financial or incorporation advice.',
            ],
        ];

        foreach ($rules as $key => $values) {
            $rule = ResultRule::query()->where('key', $key)->first();

            if (! $rule) {
                continue;
            }

            $rule->setTranslations('en', $values);
            $rule->save();
        }
    }

    private function landing(): void
    {
        $page = LandingPage::query()->where('slug', 'default')->first();

        if (! $page) {
            return;
        }

        $page->setTranslations('en', [
            'seo_title' => 'Saudi-Ready Check — Is your company ready for the Saudi market?',
            'seo_description' => 'Assess your company’s readiness to enter the Saudi market in under a minute with Creative Mark.',
            'content' => [
                'eyebrow' => 'Creative Mark × TECHNE Alexandria',
                'hero_kicker' => 'Boom 💥 Saudi Arabia is waiting for you 🇸🇦',
                'hero_lead' => 'But here is the real question...',
                'hero_title' => 'Is your company ready to enter the Saudi market?',
                'hero_description' => "Answer a few quick questions and in under a minute you will know:\nReady to start?\nA little preparation needed?\nOr time to revisit the plan first?",
                'hero_meta' => '8 questions only • under 60 seconds',
                'cta_label' => 'Start the check',
                'quiz_intro' => 'Pick the answer closest to your company — there is no wrong answer.',
                'lead_headline' => 'Great — we have a good idea of where you stand 👀',
                'lead_text' => 'Leave your details and we will show your Saudi-Ready Check result.',
                'lead_cta' => 'Show my result',
                'consent_text' => 'I agree to be contacted by the Creative Mark team about my assessment result and Saudi market entry options.',
                'footer_note' => 'A preliminary readiness assessment — not legal or financial advice.',
                'benefits' => [
                    ['icon' => '⚡', 'title' => 'Under 60 seconds', 'text' => 'Tap to choose — nothing to type.'],
                    ['icon' => '🎯', 'title' => 'A clear result', 'text' => 'Know exactly where you stand.'],
                    ['icon' => '🤝', 'title' => 'A practical next step', 'text' => 'A consultant tells you where to begin.'],
                ],
            ],
        ]);
        $page->save();
    }

    private function statuses(): void
    {
        $labels = [
            'new' => 'New',
            'contacted' => 'Contacted',
            'follow_up' => 'Follow-up',
            'meeting' => 'Meeting',
            'qualified' => 'Qualified',
            'converted' => 'Converted',
            'lost' => 'Lost',
        ];

        foreach ($labels as $key => $label) {
            $status = SalesStatus::query()->where('key', $key)->first();

            if (! $status) {
                continue;
            }

            $status->setTranslations('en', ['label' => $label]);
            $status->save();
        }
    }

    private function templates(): void
    {
        $subjects = [
            'admin_new_lead' => '[{{classification}}] {{name}} — {{company}} ({{score}}/{{max_score}})',
            'customer_result' => 'Your Saudi-Ready Check result for {{company}}',
            'lead_status_update' => 'An update on your Creative Mark enquiry',
        ];

        foreach ($subjects as $key => $subject) {
            $template = NotificationTemplate::query()->where('key', $key)->first();

            if (! $template) {
                continue;
            }

            $template->setTranslations('en', ['subject' => $subject]);
            $template->save();
        }
    }
}
