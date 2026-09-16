<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\LandingPage;
use App\Models\NotificationTemplate;
use App\Models\QrSource;
use App\Models\SalesStatus;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::updateOrCreate(
            ['slug' => 'techne-alexandria-2026'],
            [
                'name' => 'TECHNE — Alexandria 2026',
                'city' => 'Alexandria',
                'country' => 'Egypt',
                'starts_at' => '2026-09-20',
                'ends_at' => '2026-09-22',
                'status' => 'active',
                'is_default' => true,
                'description' => 'Creative Mark — Saudi Market Readiness Check @ TECHNE Alexandria.',
            ],
        );

        $sources = [
            ['name' => 'Walking QR', 'slug' => 'walking_qr', 'medium' => 'qr', 'campaign' => 'techne_2026', 'description' => 'Roaming team badges & flyers'],
            ['name' => 'Booth QR', 'slug' => 'booth_qr', 'medium' => 'qr', 'campaign' => 'techne_2026', 'description' => 'Main booth stand'],
            ['name' => 'Portfolio QR', 'slug' => 'portfolio_qr', 'medium' => 'qr', 'campaign' => 'techne_2026', 'description' => 'Printed portfolio / brochure'],
            ['name' => 'VIP QR', 'slug' => 'vip_qr', 'medium' => 'qr', 'campaign' => 'techne_2026', 'description' => 'VIP invitations & speakers'],
            ['name' => 'Partner QR', 'slug' => 'partner_qr', 'medium' => 'qr', 'campaign' => 'techne_2026', 'description' => 'Partner booths'],
        ];

        foreach ($sources as $source) {
            QrSource::updateOrCreate(['slug' => $source['slug']], $source + ['event_id' => $event->id, 'is_active' => true]);
        }

        $statuses = [
            ['key' => 'new', 'label' => 'New', 'color' => 'blue', 'is_default' => true],
            ['key' => 'contacted', 'label' => 'Contacted', 'color' => 'indigo'],
            ['key' => 'follow_up', 'label' => 'Follow-up', 'color' => 'amber'],
            ['key' => 'meeting', 'label' => 'Meeting', 'color' => 'violet', 'notify_client' => true],
            ['key' => 'qualified', 'label' => 'Qualified', 'color' => 'emerald'],
            ['key' => 'converted', 'label' => 'Converted', 'color' => 'gold'],
            ['key' => 'lost', 'label' => 'Lost', 'color' => 'slate'],
        ];

        foreach ($statuses as $i => $status) {
            SalesStatus::updateOrCreate(['key' => $status['key']], $status + ['position' => $i + 1, 'is_active' => true]);
        }

        LandingPage::updateOrCreate(
            ['slug' => 'default'],
            [
                'event_id' => $event->id,
                'name' => 'Landing — TECHNE',
                'seo_title' => 'Saudi-Ready Check — Creative Mark',
                'seo_description' => 'قيّم جاهزية شركتك لدخول السوق السعودي في أقل من دقيقة مع Creative Mark.',
                'hero_image_query' => 'riyadh skyline modern architecture',
                'is_active' => true,
                'content' => [
                    'eyebrow' => 'Creative Mark × TECHNE Alexandria',
                    'hero_kicker' => 'بوووم 💥 السعودية مستنياك 🇸🇦',
                    'hero_lead' => 'بس السؤال الأهم...',
                    'hero_title' => 'هل شركتك جاهزة تدخل السوق السعودي؟',
                    'hero_description' => "جاوب على كام سؤال سريع، وفي أقل من دقيقة هنعرفك:\nجاهز تبدأ؟\nمحتاج تجهيز بسيط؟\nولا محتاج تراجع خطتك الأول؟",
                    'hero_meta' => '8 أسئلة فقط • أقل من 60 ثانية',
                    'cta_label' => 'ابدأ الرحله',
                    'quiz_intro' => 'اختار الإجابة الأقرب لوضع شركتك — مفيش إجابة غلط.',
                    'lead_headline' => 'تمام... إحنا تقريبًا عرفنا أنت واقف فين 👀',
                    'lead_text' => 'سيب بياناتك ونطلع لك نتيجة الـSaudi-Ready Check.',
                    'lead_cta' => 'اعرف نتيجتك',
                    'consent_text' => 'أوافق على تواصل فريق Creative Mark معي بخصوص نتيجة التقييم وخيارات دخول السوق السعودي.',
                    'benefits' => [
                        ['icon' => '⚡', 'title' => 'أقل من 60 ثانية', 'text' => 'كله اختيارات — من غير كتابة.'],
                        ['icon' => '🎯', 'title' => 'نتيجة واضحة', 'text' => 'تعرف أنت في أي مرحلة بالظبط.'],
                        ['icon' => '🤝', 'title' => 'خطوة عملية', 'text' => 'مستشار يقولك تبدأ منين.'],
                    ],
                    'footer_note' => 'تقييم مبدئي لمستوى الجاهزية — وليس استشارة قانونية أو مالية.',
                ],
            ],
        );

        $settings = [
            ['key' => 'cta_whatsapp_url', 'value' => '', 'type' => 'url', 'group' => 'cta', 'label' => 'WhatsApp Business URL', 'hint' => 'https://wa.me/...', 'position' => 1],
            ['key' => 'cta_booking_url', 'value' => '', 'type' => 'url', 'group' => 'cta', 'label' => 'Booking / Meeting URL', 'hint' => 'Calendly, HubSpot meetings...', 'position' => 2],
            ['key' => 'cta_checklist_url', 'value' => '', 'type' => 'url', 'group' => 'cta', 'label' => 'Checklist download URL', 'position' => 3],
            ['key' => 'cta_phone', 'value' => '', 'type' => 'string', 'group' => 'cta', 'label' => 'Contact phone', 'position' => 4],
            ['key' => 'cta_email', 'value' => '', 'type' => 'string', 'group' => 'cta', 'label' => 'Contact email', 'position' => 5],
            ['key' => 'cta_website', 'value' => '', 'type' => 'url', 'group' => 'cta', 'label' => 'Website URL', 'position' => 6],
            ['key' => 'notify_admin', 'value' => '1', 'type' => 'bool', 'group' => 'notifications', 'label' => 'Email the sales team on every new lead', 'position' => 1],
            ['key' => 'notify_customer', 'value' => '1', 'type' => 'bool', 'group' => 'notifications', 'label' => 'Email the result to the lead (when email provided)', 'position' => 2],
            ['key' => 'notify_status_change', 'value' => '1', 'type' => 'bool', 'group' => 'notifications', 'label' => 'Email the lead when their status reaches a client-facing stage', 'position' => 3],
            ['key' => 'admin_email_locale', 'value' => 'ar', 'type' => 'string', 'group' => 'notifications', 'label' => 'Language of internal sales emails (ar / en)', 'position' => 5],
            ['key' => 'admin_notification_emails', 'value' => '', 'type' => 'string', 'group' => 'notifications', 'label' => 'Sales notification recipients', 'hint' => 'Comma separated. Falls back to MAIL_FROM_ADDRESS.', 'position' => 3],
            ['key' => 'footer_note', 'value' => '© Creative Mark — Creating The Future', 'type' => 'string', 'group' => 'general', 'label' => 'Public footer note (Arabic)', 'position' => 1],
            ['key' => 'footer_note_en', 'value' => '© Creative Mark — Creating The Future', 'type' => 'string', 'group' => 'general', 'label' => 'Public footer note (English)', 'position' => 2],
            ['key' => 'result_disclaimer', 'value' => 'النتيجة تقييم مبدئي لمستوى الجاهزية، وليست استشارة قانونية أو مالية أو قرار تأسيس.', 'type' => 'text', 'group' => 'general', 'label' => 'Result disclaimer (Arabic)', 'position' => 3],
            ['key' => 'result_disclaimer_en', 'value' => 'This result is a preliminary readiness indication — not legal, financial or incorporation advice.', 'type' => 'text', 'group' => 'general', 'label' => 'Result disclaimer (English)', 'position' => 4],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        NotificationTemplate::updateOrCreate(
            ['key' => 'admin_new_lead'],
            [
                'audience' => 'admin',
                'name' => 'Sales — new lead alert',
                'subject' => '[{{classification}}] {{name}} — {{company}} ({{score}}/{{max_score}})',
                'is_active' => true,
                'body' => <<<'HTML'
<h2>Lead جديد من {{event}}</h2>
<p><strong>{{name}}</strong> من <strong>{{company}}</strong> خلّص الـSaudi-Ready Check.</p>
<table>
  <tr><td>Score</td><td><strong>{{score}} / {{max_score}}</strong></td></tr>
  <tr><td>Result</td><td>{{result}}</td></tr>
  <tr><td>Classification</td><td>{{classification}}</td></tr>
  <tr><td>WhatsApp</td><td>{{whatsapp}}</td></tr>
  <tr><td>Email</td><td>{{email}}</td></tr>
  <tr><td>Main question</td><td>{{main_question}}</td></tr>
  <tr><td>Source</td><td>{{source}}</td></tr>
  <tr><td>Event</td><td>{{event}}</td></tr>
  <tr><td>Date</td><td>{{date}}</td></tr>
</table>
<p><a href="{{lead_url}}">افتح الـLead في الداشبورد</a></p>
HTML,
            ],
        );

        NotificationTemplate::updateOrCreate(
            ['key' => 'lead_status_update'],
            [
                'audience' => 'customer',
                'name' => 'Customer — status update',
                'subject' => 'تحديث بخصوص طلبك مع Creative Mark',
                'is_active' => true,
                'body' => '<p>حابين نطمنك على آخر تحديث في طلبك مع Creative Mark.</p>',
            ],
        );

        NotificationTemplate::updateOrCreate(
            ['key' => 'customer_result'],
            [
                'audience' => 'customer',
                'name' => 'Customer — result summary',
                'subject' => 'نتيجة الـSaudi-Ready Check الخاصة بـ{{company}}',
                'is_active' => true,
                'body' => <<<'HTML'
<h2>أهلًا {{name}} 👋</h2>
<p>شكرًا إنك جربت الـ<strong>Saudi-Ready Check</strong> من Creative Mark في {{event}}.</p>
<p>نتيجة <strong>{{company}}</strong>:</p>
<p style="font-size:22px"><strong>{{result}}</strong> — {{score}}/{{max_score}}</p>
<p>أكتر حاجة محتاج تعرفها: <strong>{{main_question}}</strong> — وده اللي مستشارنا هيبدأ بيه معاك.</p>
<p><a href="{{result_url}}">افتح صفحة نتيجتك كاملة</a></p>
<p>النتيجة دي تقييم مبدئي لمستوى الجاهزية، وليست استشارة قانونية أو مالية.</p>
HTML,
            ],
        );
    }
}
