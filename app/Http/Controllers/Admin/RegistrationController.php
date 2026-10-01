<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RegistrationsExport;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\PhotoStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationController extends Controller
{
    public function __construct(protected PhotoStorage $photos) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $registrations = $this->query($filters)
            ->with(['event', 'qrSource'])
            ->orderBy($filters['sort'], $filters['dir'])
            ->paginate(20)
            ->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'filters' => $filters,
            'events' => Event::query()->orderByDesc('is_default')->get(),
        ]);
    }

    public function show(EventRegistration $registration): View
    {
        $registration->load(['event', 'qrSource']);

        return view('admin.registrations.show', ['registration' => $registration]);
    }

    /**
     * Streams the photo from the private disk. This is the ONLY way to read it:
     * the file lives in storage/app/private, which has no URL and is not behind
     * the public/storage symlink, so an unauthenticated guess cannot reach it.
     */
    public function photo(EventRegistration $registration): StreamedResponse
    {
        abort_unless($registration->hasPhoto(), 404);
        abort_unless(Storage::disk(PhotoStorage::DISK)->exists($registration->photo_path), 404);

        return Storage::disk(PhotoStorage::DISK)->response(
            $registration->photo_path,
            $registration->uuid.'.webp',
            ['Content-Type' => 'image/webp', 'Cache-Control' => 'private, max-age=600'],
        );
    }

    public function destroyPhoto(EventRegistration $registration): RedirectResponse
    {
        $this->photos->delete($registration->photo_path);
        $registration->update(['photo_path' => null]);

        return back()->with('success', __('admin.registrations.photo_deleted'));
    }

    public function destroy(EventRegistration $registration): RedirectResponse
    {
        // The photo is removed by the model's deleting hook, so every deletion
        // path cleans up — not just this one.
        $registration->delete();

        return redirect()
            ->route('admin.registrations.index')
            ->with('success', __('admin.registrations.deleted'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = $this->filters($request);

        return Excel::download(
            new RegistrationsExport($filters, $this),
            'registrations-'.now()->format('Y-m-d-His').'.csv',
            \Maatwebsite\Excel\Excel::CSV,
        );
    }

    /** @param array<string,mixed> $filters */
    public function query(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        // Public and also called by the export, so it tolerates a partial
        // filter array rather than assuming filters() built it.
        $hasPhoto = $filters['has_photo'] ?? '';

        return EventRegistration::query()
            ->search($filters['q'] ?? '')
            ->when($filters['event_id'] ?? null, fn ($q, $id) => $q->where('event_id', $id))
            ->when($hasPhoto === 'yes', fn ($q) => $q->whereNotNull('photo_path'))
            ->when($hasPhoto === 'no', fn ($q) => $q->whereNull('photo_path'));
    }

    /** @return array<string,mixed> */
    protected function filters(Request $request): array
    {
        $sort = (string) $request->query('sort', 'created_at');
        $dir = strtolower((string) $request->query('dir', 'desc'));

        return [
            'q' => (string) $request->query('q', ''),
            'event_id' => $request->query('event_id'),
            'has_photo' => (string) $request->query('has_photo', ''),
            'sort' => in_array($sort, ['created_at', 'name'], true) ? $sort : 'created_at',
            'dir' => in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc',
        ];
    }
}
