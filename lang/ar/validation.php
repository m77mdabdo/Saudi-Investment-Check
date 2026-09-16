<?php

return [
    'accepted' => 'لازم توافق على :attribute.',
    'after' => ':attribute لازم يكون بعد :date.',
    'after_or_equal' => ':attribute لازم يكون :date أو بعدها.',
    'array' => ':attribute لازم يكون قائمة.',
    'before' => ':attribute لازم يكون قبل :date.',
    'boolean' => ':attribute لازم يكون صح أو خطأ.',
    'confirmed' => 'تأكيد :attribute مش متطابق.',
    'date' => ':attribute مش تاريخ صحيح.',
    'email' => ':attribute لازم يكون بريد إلكتروني صحيح.',
    'exists' => ':attribute المختار غير موجود.',
    'file' => ':attribute لازم يكون ملف.',
    'gte' => [
        'numeric' => ':attribute لازم يكون أكبر من أو يساوي :value.',
    ],
    'image' => ':attribute لازم يكون صورة.',
    'in' => ':attribute غير صالح — اختار من القائمة.',
    'integer' => ':attribute لازم يكون رقم صحيح.',
    'max' => [
        'array' => ':attribute مش المفروض يزيد عن :max عنصر.',
        'file' => ':attribute مش المفروض يزيد عن :max كيلوبايت.',
        'numeric' => ':attribute مش المفروض يزيد عن :max.',
        'string' => ':attribute طويل أوي (:max حرف كحد أقصى).',
    ],
    'mimes' => ':attribute لازم يكون ملف من نوع: :values.',
    'min' => [
        'array' => ':attribute لازم يحتوي على :min عنصر على الأقل.',
        'numeric' => ':attribute لازم يكون :min على الأقل.',
        'string' => ':attribute قصير أوي (:min حرف على الأقل).',
    ],
    'numeric' => ':attribute لازم يكون رقم.',
    'regex' => 'صيغة :attribute مش صحيحة.',
    'required' => 'من فضلك املأ :attribute.',
    'required_if' => ':attribute مطلوب.',
    'size' => [
        'string' => ':attribute لازم يكون :size حرف.',
    ],
    'string' => ':attribute لازم يكون نص.',
    'unique' => ':attribute مستخدم قبل كده.',
    'url' => ':attribute لازم يكون رابط صحيح.',
    'uploaded' => 'فشل رفع :attribute.',

    'custom' => [
        'phone' => [
            'required' => 'محتاجين رقم الواتساب عشان نبعتلك النتيجة.',
            'regex' => 'اكتب رقم واتساب صحيح (أرقام فقط).',
            'min' => 'الرقم قصير — راجعه من فضلك.',
        ],
        'consent' => [
            'accepted' => 'لازم توافق على التواصل عشان نطلعلك النتيجة.',
        ],
        'country_code' => [
            'in' => 'اختار كود الدولة من القائمة.',
        ],
        'website' => [
            'size' => 'حصلت مشكلة في إرسال النموذج. جرب تاني من فضلك.',
        ],
        'answers.*' => [
            'required' => 'في سؤال لسه مش مجاوب عليه — ارجع وكمّله.',
            'in' => 'في إجابة غير صالحة — ابدأ التقييم من جديد.',
        ],
    ],

    'attributes' => [
        'name' => 'الاسم',
        'company' => 'اسم الشركة',
        'phone' => 'رقم الواتساب',
        'country_code' => 'كود الدولة',
        'email' => 'البريد الإلكتروني',
        'consent' => 'الموافقة',
        'password' => 'كلمة المرور',
        'body' => 'النص',
        'label' => 'الاسم',
        'title' => 'العنوان',
        'subject' => 'عنوان الرسالة',
        'key' => 'المفتاح',
        'score' => 'النقاط',
        'min_score' => 'أقل نقاط',
        'max_score' => 'أعلى نقاط',
        'headline' => 'العنوان الرئيسي',
        'sales_status' => 'حالة المبيعات',
        'role' => 'الدور',
        'slug' => 'المعرّف',
        'status' => 'الحالة',
    ],
];
