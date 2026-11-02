<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRegistrationRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\EventRegistration;
use App\Services\NotificationService;
use App\Services\PhotoStorage;
use App\Services\VisitorContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RegistrationController extends Controller
{
    public function __construct(
        protected VisitorContext $context,
        protected PhotoStorage $photos,
        protected NotificationService $notifications,
    ) {}

    public function store(EventRegistrationRequest $request): RedirectResponse
    {
        // Honeypot. A filled 'website' field means a bot; drop it silently.
        if ($request->filled('website')) {
            return redirect()->to(lroute('landing'));
        }

        $ctx = $this->context->get($request);
        $uuid = (string) Str::uuid();
        $photoPath = null;
        $photoFailed = false;

        // The photo is optional and must never block the registration, so it is
        // validated here rather than in the form request. Whether it is rejected
        // (HEIC, oversized) or fails to write, the row is still saved with
        // photo_path null and the confirmation says the photo did not save.
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');

            $validator = Validator::make(
                ['photo' => $photo],
                EventRegistrationRequest::photoRules(),
                EventRegistrationRequest::photoMessages(),
            );

            if ($validator->fails()) {
                $photoFailed = true;

                // No contact details, and no filename: people name photos
                // after themselves.
                Log::warning('event_registration.photo_rejected', [
                    'uuid' => $uuid,
                    'reason' => $validator->errors()->first('photo'),
                    'mime' => $photo->getMimeType(),
                    'client_mime' => $photo->getClientMimeType(),
                    'extension' => $photo->getClientOriginalExtension(),
                    'bytes' => $photo->getSize(),
                    'locale' => app()->getLocale(),
                ]);
            } else {
                try {
                    $photoPath = $this->photos->store($photo, $uuid);
                } catch (Throwable $e) {
                    $photoFailed = true;

                    Log::error('event_registration.photo_failed', [
                        'uuid' => $uuid,
                        'reason' => $e->getMessage(),
                        'mime' => $photo->getMimeType(),
                        'bytes' => $photo->getSize(),
                        'locale' => app()->getLocale(),
                    ]);
                }
            }
        }

        $attributes = $request->contact() + [
            'uuid' => $uuid,
            'event_id' => $ctx['event_id'] ?? null,
            'qr_source_id' => $ctx['qr_source_id'] ?? null,
            'photo_path' => $photoPath,
            'source' => $ctx['source'] ?? null,
            'device' => $ctx['device'] ?? null,
            'browser' => $ctx['browser'] ?? null,
            'platform' => $ctx['platform'] ?? null,
            'locale' => app()->getLocale(),
            'ip_hash' => $this->context->ipHash($request),
            'session_hash' => $ctx['session_hash'] ?? null,
        ];

        try {
            $registration = EventRegistration::create($attributes);
        } catch (Throwable $e) {
            // The photo was written before the row, so clean it up rather than
            // leave an orphaned file nothing points at.
            $this->photos->delete($photoPath);

            throw $e;
        }

        // The alert is internal, so it runs after the response instead of
        // adding the SMTP round-trip (~1s) to the visitor's wait. Re-fetching by
        // id guarantees the row is committed by then, and skips if it is gone.
        // NotificationService::safely() still records failures in
        // notification_logs.
        $registrationId = $registration->id;

        app()->terminating(function () use ($registrationId) {
            $fresh = EventRegistration::find($registrationId);

            if ($fresh) {
                $this->notifications->registrationCreated($fresh);
            }
        });

        // Ids and outcome only, never the contact details.
        Log::info('event_registration.created', [
            'registration_id' => $registration->id,
            'event_id' => $registration->event_id,
            'locale' => $registration->locale,
            'has_photo' => $registration->hasPhoto(),
            'photo_failed' => $photoFailed,
            'has_email' => (bool) $registration->email,
        ]);

        return redirect()
            ->to(lroute('landing').'#register')
            ->with('registered', $registration->name)
            ->with('photo_failed', $photoFailed);
    }
}
