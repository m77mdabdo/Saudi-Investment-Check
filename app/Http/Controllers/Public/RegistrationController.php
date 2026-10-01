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
        // Honeypot: a filled 'website' means a bot. Fail the same way a human
        // never would notice, without writing a row.
        if ($request->filled('website')) {
            return redirect()->to(lroute('landing'));
        }

        $ctx = $this->context->get($request);
        $uuid = (string) Str::uuid();
        $photoPath = null;
        $photoFailed = false;

        // The photo is optional, so NO photo problem may cost someone their
        // registration — at an event they have already walked away. Both ways a
        // photo can fail are handled here, outside the request's own rules:
        //
        //   1. it fails validation (an iPhone's HEIC, something oversized), and
        //   2. it fails to write (a full disk).
        //
        // Either way the row is saved with photo_path null, the failure is
        // logged, and the confirmation says the photo did not save. A row never
        // claims a photo that is not on disk.
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');

            $validator = Validator::make(
                ['photo' => $photo],
                EventRegistrationRequest::photoRules(),
                EventRegistrationRequest::photoMessages(),
            );

            if ($validator->fails()) {
                $photoFailed = true;

                // Previously this path wrote nothing at all, which is why a
                // whole class of lost registrations was invisible. No contact
                // details here — the filename is omitted because people name
                // photos after themselves.
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
            // The photo was written before the row existed. Without this the
            // file survives with nothing pointing at it — a photograph of a
            // real person that can no longer be found or deleted on request.
            $this->photos->delete($photoPath);

            throw $e;
        }

        // The sales alert is internal, so the visitor has no reason to wait for
        // it — at a crowded stand on bad wifi the SMTP round-trip was adding
        // about a second to every submit. terminating() runs after the response
        // has been sent, so the confirmation is already on their screen.
        //
        // The row is re-fetched by id rather than captured by reference: the
        // insert above is a single auto-committed statement so it is already
        // durable here, but re-reading makes that a fact at send time instead
        // of an assumption, and skips cleanly if the row has since gone.
        // NotificationService::safely() still wraps every step, so a failure
        // after the response is logged to notification_logs exactly as before.
        $registrationId = $registration->id;

        app()->terminating(function () use ($registrationId) {
            $fresh = EventRegistration::find($registrationId);

            if ($fresh) {
                $this->notifications->registrationCreated($fresh);
            }
        });

        // Ids and outcome only — never the contact details themselves.
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
