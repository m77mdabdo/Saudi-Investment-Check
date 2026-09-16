<?php

namespace Database\Seeders;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * 8 questions — 6 of them scored 0/1/2 → maximum score 12.
     * Everything here is editable later from Admin → Quiz Builder.
     */
    public function run(): void
    {
        foreach ($this->definition() as $position => $data) {
            $question = QuizQuestion::updateOrCreate(
                ['key' => $data['key']],
                [
                    'type' => $data['type'] ?? 'single',
                    'title' => $data['title'],
                    'subtitle' => $data['subtitle'] ?? null,
                    'icon' => $data['icon'] ?? null,
                    'placeholder' => $data['placeholder'] ?? null,
                    'is_required' => $data['required'] ?? true,
                    'is_scored' => $data['scored'] ?? true,
                    'is_active' => true,
                    'position' => $position + 1,
                ],
            );

            foreach ($data['options'] ?? [] as $index => $option) {
                QuizOption::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'key' => $option['key']],
                    [
                        'label' => $option['label'],
                        'description' => $option['description'] ?? null,
                        'icon' => $option['icon'] ?? null,
                        'score' => $option['score'] ?? 0,
                        'requires_detail' => $option['detail'] ?? false,
                        'detail_label' => ($option['detail'] ?? false) ? ($option['detail_label'] ?? 'اكتب التفاصيل') : null,
                        'is_active' => true,
                        'position' => $index + 1,
                    ],
                );
            }
        }
    }

    private function definition(): array
    {
        return [
            [
                'key' => 'company_stage',
                'title' => 'شركتك في أي مرحلة دلوقتي؟',
                'subtitle' => 'اختار اللي أقرب لوضعك الحالي',
                'icon' => '🏁',
                'options' => [
                    ['key' => 'idea', 'label' => 'عندي فكرة', 'description' => 'أو مشروع لسه تحت التأسيس', 'icon' => '💡', 'score' => 0],
                    ['key' => 'operating', 'label' => 'بدأت التشغيل', 'description' => 'ولسه في مرحلة النمو', 'icon' => '📈', 'score' => 1],
                    ['key' => 'established', 'label' => 'شركة قائمة ومستقرة', 'description' => 'عندي عملاء وإيرادات ثابتة', 'icon' => '🏢', 'score' => 2],
                ],
            ],
            [
                'key' => 'sector',
                'title' => 'نشاط شركتك في أي مجال؟',
                'subtitle' => 'اختار القطاع الأقرب',
                'icon' => '🧭',
                'scored' => false,
                'options' => [
                    ['key' => 'tech', 'label' => 'تكنولوجيا وبرمجيات', 'icon' => '💻'],
                    ['key' => 'marketing', 'label' => 'تسويق وإعلان', 'icon' => '📣'],
                    ['key' => 'trade', 'label' => 'تجارة واستيراد وتصدير', 'icon' => '📦'],
                    ['key' => 'contracting', 'label' => 'مقاولات وإنشاءات', 'icon' => '🏗️'],
                    ['key' => 'industry', 'label' => 'صناعة وتصنيع', 'icon' => '🏭'],
                    ['key' => 'consulting', 'label' => 'استشارات وخدمات مهنية', 'icon' => '📊'],
                    ['key' => 'health', 'label' => 'صحة وتجميل', 'icon' => '🩺'],
                    ['key' => 'food', 'label' => 'مطاعم وأغذية', 'icon' => '🍽️'],
                    ['key' => 'education', 'label' => 'تعليم وتدريب', 'icon' => '🎓'],
                    ['key' => 'logistics', 'label' => 'نقل ولوجستيات', 'icon' => '🚚'],
                    ['key' => 'other', 'label' => 'مجال تاني', 'icon' => '✍️', 'detail' => true, 'detail_label' => 'اكتب نشاط الشركة'],
                ],
            ],
            [
                'key' => 'saudi_goal',
                'title' => 'إيه هدفك من السوق السعودي؟',
                'subtitle' => 'إيه اللي بيحركك ناحية السعودية؟',
                'icon' => '🎯',
                'options' => [
                    ['key' => 'explore', 'label' => 'بستكشف السوق', 'description' => 'لسه بجمع معلومات', 'icon' => '🔍', 'score' => 0],
                    ['key' => 'expand', 'label' => 'عايز أوسّع نشاطي', 'description' => 'السعودية سوق مستهدف عندي', 'icon' => '🚀', 'score' => 1],
                    ['key' => 'establish', 'label' => 'قررت أأسس', 'description' => 'فرع أو كيان رسمي في السعودية', 'icon' => '🏛️', 'score' => 2],
                ],
            ],
            [
                'key' => 'saudi_traction',
                'title' => 'عندك أي تعاملات مع السوق السعودي؟',
                'subtitle' => 'حتى لو بسيطة',
                'icon' => '🤝',
                'options' => [
                    ['key' => 'none', 'label' => 'لسه مفيش', 'description' => 'أول مرة أفكر في السوق ده', 'icon' => '➖', 'score' => 0],
                    ['key' => 'inquiries', 'label' => 'فيه اهتمام واستفسارات', 'description' => 'عملاء سألوا أو طلبوا عروض', 'icon' => '💬', 'score' => 1],
                    ['key' => 'clients', 'label' => 'عندي عملاء فعليين', 'description' => 'تعاملات أو تعاقدات قائمة', 'icon' => '✅', 'score' => 2],
                ],
            ],
            [
                'key' => 'timeline',
                'title' => 'ناوي تبدأ إمتى؟',
                'subtitle' => 'التوقيت بيفرق جدًا في اختيار المسار',
                'icon' => '⏱️',
                'options' => [
                    ['key' => 'later', 'label' => 'بعد سنة أو أكتر', 'description' => 'لسه بخطط', 'icon' => '🗓️', 'score' => 0],
                    ['key' => 'mid', 'label' => 'خلال 6 لـ 12 شهر', 'description' => 'في الخطة القريبة', 'icon' => '📅', 'score' => 1],
                    ['key' => 'soon', 'label' => 'خلال 3 شهور', 'description' => 'جاهز أتحرك بسرعة', 'icon' => '⚡', 'score' => 2],
                ],
            ],
            [
                'key' => 'budget',
                'title' => 'الميزانية المخصصة للدخول؟',
                'subtitle' => 'تقدير تقريبي كفاية',
                'icon' => '💰',
                'options' => [
                    ['key' => 'undefined', 'label' => 'لسه مش محددة', 'description' => 'محتاج أعرف التكلفة الأول', 'icon' => '❔', 'score' => 0],
                    ['key' => 'limited', 'label' => 'ميزانية محدودة', 'description' => 'أقدر أبدأ بأقل تكلفة ممكنة', 'icon' => '🪙', 'score' => 1],
                    ['key' => 'ready', 'label' => 'ميزانية جاهزة', 'description' => 'مخصص مبلغ للتأسيس والتشغيل', 'icon' => '💼', 'score' => 2],
                ],
            ],
            [
                'key' => 'operational_readiness',
                'title' => 'جاهزيتك التشغيلية إيه؟',
                'subtitle' => 'فريق، مستندات، قدرة على التنفيذ',
                'icon' => '⚙️',
                'options' => [
                    ['key' => 'starting', 'label' => 'هبدأ من الصفر', 'description' => 'محتاج تجهيز كامل', 'icon' => '🌱', 'score' => 0],
                    ['key' => 'partial', 'label' => 'عندي فريق بس محتاج تجهيز', 'description' => 'ناقص مستندات أو خبرة بالسوق', 'icon' => '🔧', 'score' => 1],
                    ['key' => 'ready', 'label' => 'جاهز تشغيليًا', 'description' => 'فريق ومستندات وقدرة تنفيذ', 'icon' => '🛠️', 'score' => 2],
                ],
            ],
            [
                'key' => 'main_question',
                'title' => 'أكتر حاجة محتاج إجابة عنها؟',
                'subtitle' => 'عشان المستشار يجهزلك إجابة مظبوطة',
                'icon' => '❓',
                'scored' => false,
                'options' => [
                    ['key' => 'cost', 'label' => 'تكلفة التأسيس', 'icon' => '💵'],
                    ['key' => 'procedures', 'label' => 'الإجراءات والتراخيص', 'icon' => '📄'],
                    ['key' => 'entry_path', 'label' => 'أنسب مسار للدخول', 'icon' => '🧩'],
                    ['key' => 'demand', 'label' => 'حجم الطلب على نشاطي', 'icon' => '📊'],
                    ['key' => 'partner', 'label' => 'هل محتاج شريك سعودي؟', 'icon' => '🤝'],
                    ['key' => 'other', 'label' => 'حاجة تانية', 'icon' => '✍️', 'detail' => true, 'detail_label' => 'اكتب سؤالك'],
                ],
            ],
        ];
    }
}
