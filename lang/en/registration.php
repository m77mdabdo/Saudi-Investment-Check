<?php

return [
    'title' => 'Welcome',
    'heading' => 'Welcome — let us know you are here',
    'intro' => 'Register in a few seconds so the team can follow up with you after the event.',

    'fields' => [
        'name' => 'Name',
        'phone' => 'Phone',
        'country_code' => 'Country code',
        'email' => 'Email',
        'photo' => 'Photo',
    ],

    'optional' => 'optional',
    'photo_choose' => 'Add a photo',
    'photo_replace' => 'Replace',
    'photo_remove' => 'Remove',
    'photo_selected' => 'Photo added',
    'privacy_note' => 'We use your details only to contact you about your enquiry.',
    'submit' => 'Register',
    'submitting' => 'Sending…',

    // The form is complete without a photo; say so rather than leaving a blank
    // field that reads like a missed step.

    'errors' => [
        'photo_format' => 'That photo format is not supported. Please choose a JPEG, PNG or WebP image.',
        'photo_size' => 'That photo is too large. Please choose one under :mb MB.',
        'photo_failed' => 'We could not process that photo. Please try another one.',
        'phone_format' => 'Please enter a valid phone number — digits only.',
    ],

    'success' => [
        'heading' => 'You are registered',
        'body' => 'Thank you, :name. We have your details and the team will be in touch.',
        'photo_failed' => "You're registered, but we couldn't save the photo. No problem — we have your details.",
        'again' => 'Register someone else',
    ],
];
