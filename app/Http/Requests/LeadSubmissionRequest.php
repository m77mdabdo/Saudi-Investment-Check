<?php

namespace App\Http\Requests;

use App\Services\ScoringService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LeadSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dials = collect(config('countries.list'))->pluck('dial')->all();

        return array_merge([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'company' => ['required', 'string', 'min:2', 'max:160'],
            'country_code' => ['required', 'string', 'in:'.implode(',', $dials)],
            'phone' => ['required', 'string', 'min:6', 'max:20', 'regex:/^[0-9\s\-\(\)]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'size:0'], // honeypot
        ], app(ScoringService::class)->validationRules());
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'company' => 'اسم الشركة',
            'phone' => 'رقم الواتساب',
            'country_code' => 'كود الدولة',
            'email' => 'البريد الإلكتروني',
            'consent' => 'الموافقة',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'من فضلك املأ :attribute.',
            'in' => ':attribute غير صالح — اختار من القائمة.',
            'max' => ':attribute طويل أوي.',
            'country_code.in' => 'اختار كود الدولة من القائمة.',
            'name.min' => 'اكتب اسمك كامل من فضلك.',
            'company.required' => 'اكتب اسم الشركة من فضلك.',
            'phone.required' => 'محتاجين رقم الواتساب عشان نبعتلك النتيجة.',
            'phone.regex' => 'اكتب رقم واتساب صحيح (أرقام فقط).',
            'phone.min' => 'الرقم قصير — راجعه من فضلك.',
            'email.email' => 'البريد الإلكتروني مش مظبوط.',
            'consent.accepted' => 'لازم توافق على التواصل عشان نطلعلك النتيجة.',
            'answers.*.required' => 'في سؤال لسه مش مجاوب عليه — ارجع وكمّله.',
            'answers.*.in' => 'في إجابة غير صالحة — ابدأ التقييم من جديد.',
        ];
    }

    /** Normalised E.164-style number, e.g. +966501234567 */
    public function whatsapp(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->input('phone'));
        $dial = ltrim((string) $this->input('country_code'), '+');
        $digits = ltrim((string) $digits, '0');

        if (Str::startsWith($digits, $dial)) {
            $digits = substr($digits, strlen($dial));
        }

        return '+'.$dial.$digits;
    }

    /** @return array<string,mixed> */
    public function contact(): array
    {
        return [
            'name' => trim(strip_tags((string) $this->input('name'))),
            'company' => trim(strip_tags((string) $this->input('company'))),
            'whatsapp' => $this->whatsapp(),
            'whatsapp_country' => (string) $this->input('country_code'),
            'email' => $this->input('email') ? trim((string) $this->input('email')) : null,
            'consent' => true,
        ];
    }

    /** @return array<string,mixed> */
    public function answers(): array
    {
        $answers = $this->input('answers', []);

        return is_array($answers) ? $answers : [];
    }
}
