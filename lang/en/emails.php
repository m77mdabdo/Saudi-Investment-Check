<?php

return [
    'greeting' => 'Hi :name 👋',
    'footer_note' => 'You received this email because you completed the Saudi-Ready Check with Creative Mark.',
    'footer_admin_note' => 'Internal notification from the Saudi-Ready Check platform.',
    'contact' => 'Contact',
    'view_in_browser' => 'Trouble viewing this? Open your result in the browser',

    'customer_result' => [
        'subject' => 'Your Saudi-Ready Check result for :company',
        'preheader' => 'The Saudi market readiness result for :company.',
        'title' => 'Your result is ready',
        'intro' => 'Thank you for taking the Saudi-Ready Check by Creative Mark at :event.',
        'score_label' => 'Your score',
        'classification_label' => 'Classification',
        'main_question_label' => 'What you most want to know',
        'cta' => 'Open your full result',
        'closing' => 'The Creative Mark team will reach out to help with your next step.',
    ],

    'admin_lead' => [
        'subject' => '[:classification] :name — :company (:score/:max)',
        'preheader' => 'New lead from :source scoring :score of :max.',
        'title' => 'New lead received 🎯',
        'intro' => ':name from :company completed the readiness assessment.',
        'contact_section' => 'Contact details',
        'assessment_section' => 'Assessment',
        'answers_section' => 'Answers',
        'source_section' => 'Attribution',
        'cta' => 'Open the lead in the dashboard',
    ],

    'status_update' => [
        'subject' => 'An update on your Creative Mark enquiry',
        'preheader' => 'Your enquiry status changed to :status.',
        'title' => 'Status update',
        'intro' => 'Here is the latest update on your enquiry with Creative Mark.',
        'status_label' => 'Current status',
        'closing' => 'If you have any questions, we are here to help.',
    ],

    'test' => [
        'subject' => 'Test email — :app',
        'title' => 'Mail configuration works ✅',
        'intro' => 'This message was sent from :app to verify the SMTP configuration.',
        'sent_at' => 'Sent at',
        'mailer' => 'Mailer',
        'host' => 'Host',
    ],

    'labels' => [
        'name' => 'Name',
        'company' => 'Company',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'phone' => 'Phone',
        'score' => 'Score',
        'result' => 'Result',
        'classification' => 'Classification',
        'source' => 'Source',
        'event' => 'Event',
        'date' => 'Date',
        'question' => 'Question',
        'answer' => 'Answer',
        'device' => 'Device',
    ],
];
