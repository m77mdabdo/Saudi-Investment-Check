<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRegistrationRequest;
use App\Models\EventRegistration;
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

        // The photo is optional, so a photo problem must never cost someone
        // their registration — at an event they have already walked away. The
        // row is saved either way; the failure is logged and surfaced quietly
        // on the confirmation. A row must also never claim a photo that is not
        // on disk, hence photo_path stays null.
        if ($request->hasFile('photo')) {
            try {
                $photoPath = $this->photos->store($request->file('photo'), $uuid);
            } catch (Throwable $e) {
                $photoFailed = true;

                Log::error('event_registration.photo_failed', [
                    'uuid' => $uuid,
                    'reason' => $e->getMessage(),
                    'original_name' => $request->file('photo')?->getClientOriginalName(),
                    'original_mime' => $request->file('photo')?->getMimeType(),
                    'original_bytes' => $request->file('photo')?->getSize(),
                    'locale' => app()->getLocale(),
                ]);
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
            ->to(lroute('landing'))
            ->with('registered', $registration->name)
            ->with('photo_failed', $photoFailed);
    }
}
