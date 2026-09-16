<?php

return [
    'accepted' => 'The :attribute must be accepted.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'array' => 'The :attribute must be a list.',
    'before' => 'The :attribute must be a date before :date.',
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute is not a valid date.',
    'email' => 'Please enter a valid :attribute.',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute must be a file.',
    'gte' => [
        'numeric' => 'The :attribute must be greater than or equal to :value.',
    ],
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid — please choose from the list.',
    'integer' => 'The :attribute must be a whole number.',
    'max' => [
        'array' => 'The :attribute must not have more than :max items.',
        'file' => 'The :attribute must not be larger than :max kilobytes.',
        'numeric' => 'The :attribute must not be greater than :max.',
        'string' => 'The :attribute is too long (:max characters maximum).',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute must have at least :min items.',
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute is too short (:min characters minimum).',
    ],
    'numeric' => 'The :attribute must be a number.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'Please enter your :attribute.',
    'required_if' => 'The :attribute field is required.',
    'size' => [
        'string' => 'The :attribute must be :size characters.',
    ],
    'string' => 'The :attribute must be text.',
    'unique' => 'That :attribute is already taken.',
    'url' => 'The :attribute must be a valid URL.',
    'uploaded' => 'The :attribute failed to upload.',

    'custom' => [
        'phone' => [
            'required' => 'We need your WhatsApp number to send you the result.',
            'regex' => 'Please enter a valid WhatsApp number (digits only).',
            'min' => 'That number looks too short — please check it.',
        ],
        'consent' => [
            'accepted' => 'Please accept the contact consent so we can share your result.',
        ],
        'country_code' => [
            'in' => 'Please pick a country code from the list.',
        ],
        'website' => [
            'size' => 'We could not submit the form. Please try again.',
        ],
        'answers.*' => [
            'required' => 'One question is still unanswered — please go back and complete it.',
            'in' => 'One of the answers is invalid — please restart the assessment.',
        ],
    ],

    'attributes' => [
        'name' => 'name',
        'company' => 'company name',
        'phone' => 'WhatsApp number',
        'country_code' => 'country code',
        'email' => 'email address',
        'consent' => 'consent',
        'password' => 'password',
        'body' => 'content',
        'label' => 'label',
        'title' => 'title',
        'subject' => 'subject',
        'key' => 'key',
        'score' => 'score',
        'min_score' => 'minimum score',
        'max_score' => 'maximum score',
        'headline' => 'headline',
        'sales_status' => 'sales status',
        'role' => 'role',
        'slug' => 'slug',
        'status' => 'status',
    ],
];
