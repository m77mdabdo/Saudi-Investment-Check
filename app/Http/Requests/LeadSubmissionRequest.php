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
        // Attribute names (and all messages) come from lang/{locale}/validation.php
        // so both languages are handled by the same rules.
        return [
            'name' => __('validation.attributes.name'),
            'company' => __('validation.attributes.company'),
            'phone' => __('validation.attributes.phone'),
            'country_code' => __('validation.attributes.country_code'),
            'email' => __('validation.attributes.email'),
            'consent' => __('validation.attributes.consent'),
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
