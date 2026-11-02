<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class EventRegistrationRequest extends FormRequest
{
    /** 8 MB, expressed in the kilobytes Laravel's max: rule expects. */
    public const MAX_PHOTO_KB = 8192;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dials = collect(config('countries.list'))->pluck('dial')->all();

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'country_code' => ['required', 'string', 'in:'.implode(',', $dials)],
            'phone' => ['required', 'string', 'min:6', 'max:20', 'regex:/^[0-9\s\-\(\)]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:190'],

            'website' => ['nullable', 'size:0'], // honeypot
        ];
    }

    /**
     * Kept out of rules() on purpose: an optional field must not be able to
     * fail the whole request. The controller applies these separately.
     *
     * 'image' runs getimagesize() and 'mimetypes' sniffs the real content type,
     * so neither trusts the extension. HEIC is out because GD cannot decode it.
     *
     * @return array<string,array<int,string>>
     */
    public static function photoRules(): array
    {
        return [
            'photo' => [
                'nullable', 'file', 'image',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.self::MAX_PHOTO_KB,
            ],
        ];
    }

    /**
     * The fragment matters: without it the redirect lands at the top of the
     * page, where the portal stage covers the form and its errors.
     */
    protected function getRedirectUrl(): string
    {
        return $this->redirector->getUrlGenerator()->previous().'#register';
    }

    public function attributes(): array
    {
        return [
            'name' => __('registration.fields.name'),
            'phone' => __('registration.fields.phone'),
            'country_code' => __('registration.fields.country_code'),
            'email' => __('registration.fields.email'),
            'photo' => __('registration.fields.photo'),
        ];
    }

    /** Shared with the controller's separate photo check. */
    public static function photoMessages(): array
    {
        return [
            'photo.mimetypes' => __('registration.errors.photo_format'),
            'photo.image' => __('registration.errors.photo_format'),
            'photo.file' => __('registration.errors.photo_format'),
            'photo.max' => __('registration.errors.photo_size', ['mb' => (int) (self::MAX_PHOTO_KB / 1024)]),
        ];
    }

    public function messages(): array
    {
        return [
            // Generic "invalid file" tells the visitor nothing actionable at a
            // live event; naming the formats does.
            'phone.regex' => __('registration.errors.phone_format'),
        ];
    }

    /** Normalised E.164 number, same rules as the quiz lead form. */
    public function e164(): string
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
            'phone' => $this->e164(),
            'phone_country' => (string) $this->input('country_code'),
            'email' => $this->input('email') ? trim((string) $this->input('email')) : null,
        ];
    }
}
