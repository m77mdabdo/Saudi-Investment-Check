<?php

namespace Database\Seeders;

use App\Models\ResultRule;
use Illuminate\Database\Seeder;

class ResultRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'key' => 'ready',
                'classification' => 'Hot Lead',
                'indicator' => 'green',
                'min_score' => 9,
                'max_score' => 12,
                'headline' => '💥 السعودية مستنياك فعلًا!',
                'main_text' => 'شركتك عندها مؤشرات قوية للجاهزية لدخول السوق السعودي.',
                'body' => "من إجاباتك، عندك أساس قوي يسمح لك تبدأ تقييم خطوة الدخول بشكل جدي.\nالخطوة الجاية مش إنك تبدأ إجراءات فورًا...\nالخطوة الجاية إننا نحدد أنسب مسار لدخول شركتك.",
                'highlight' => 'أنسب مسار دخول = وقت أقل، تكلفة أقل، ومخاطرة أقل.',
                'bullets' => ['تحديد الكيان الأنسب لنشاطك', 'خريطة الإجراءات والتكلفة المتوقعة', 'خطة دخول عملية خلال أول 90 يوم'],
                'primary_cta_label' => 'احجز تقييمك المجاني داخل Techne',
                'secondary_cta_label' => 'قابل مستشار Creative Mark في الـBooth',
                'disclaimer' => 'النتيجة دي تقييم مبدئي لمستوى الجاهزية، وليست استشارة قانونية أو مالية أو قرار تأسيس.',
                'image_query' => 'riyadh skyline business district',
                'position' => 1,
            ],
            [
                'key' => 'needs_prep',
                'classification' => 'Warm Lead',
                'indicator' => 'amber',
                'min_score' => 5,
                'max_score' => 8,
                'headline' => 'قريب جدًا... بس محتاج نجهز كام نقطة الأول.',
                'main_text' => 'السوق السعودي ممكن يكون خطوة مناسبة لشركتك، لكن في شوية نقط محتاجة تتراجع قبل قرار التأسيس.',
                'body' => 'النقط اللي محتاجة مراجعة معاك قبل ما تتحرك:',
                'highlight' => 'الأفضل تعرف النقط دي قبل ما تستثمر، مش بعد ما تبدأ.',
                'bullets' => ['حجم الطلب على نشاطك', 'خطة دخول السوق', 'التكلفة المتوقعة', 'الجاهزية التشغيلية', 'اختيار النشاط الصح', 'اختيار المسار الصحيح'],
                'primary_cta_label' => 'اعمل Saudi Readiness Review مجاني',
                'secondary_cta_label' => 'قابل مستشارنا داخل الـBooth',
                'disclaimer' => 'النتيجة دي تقييم مبدئي لمستوى الجاهزية، وليست استشارة قانونية أو مالية أو قرار تأسيس.',
                'image_query' => 'saudi business meeting modern office',
                'position' => 2,
            ],
            [
                'key' => 'early',
                'classification' => 'Early Lead',
                'indicator' => 'coral',
                'min_score' => 0,
                'max_score' => 4,
                'headline' => 'استنى قبل ما تبدأ إجراءات التأسيس.',
                'main_text' => 'ممكن السعودية تكون فرصة قوية ليك... لكن القرار محتاج معلومات أكتر الأول.',
                'body' => 'قبل ما تبدأ، محتاج تعرف:',
                'highlight' => 'قرار صح قبل التأسيس ممكن يوفر عليك وقت وفلوس كتير بعده.',
                'bullets' => ['هل فيه طلب على اللي بتقدمه؟', 'مين عميلك؟', 'إيه حجم الاستثمار المتوقع؟', 'وإيه أنسب طريقة تدخل بيها؟'],
                'primary_cta_label' => 'خد Checklist دخول السوق السعودي',
                'secondary_cta_label' => 'اسأل مستشار Creative Mark عن أول خطوة تناسب حالتك',
                'disclaimer' => 'النتيجة دي تقييم مبدئي لمستوى الجاهزية، وليست استشارة قانونية أو مالية أو قرار تأسيس.',
                'image_query' => 'entrepreneur planning strategy office',
                'position' => 3,
            ],
        ];

        foreach ($rules as $rule) {
            ResultRule::updateOrCreate(['key' => $rule['key']], $rule + ['is_active' => true]);
        }
    }
}
