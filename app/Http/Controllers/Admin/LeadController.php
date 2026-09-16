<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LeadsExport;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\QrSource;
use App\Models\QuizOption;
use App\Models\SalesStatus;
use App\Models\User;
use App\Services\LeadQuery;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeadController extends Controller
{
    public function __construct(
        protected LeadQuery $leadQuery,
        protected NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->leadQuery->filters($request);

        $leads = $this->leadQuery->results($filters)
            ->with(['qrSource', 'event', 'owner'])
            ->orderBy($filters['sort'], $filters['dir'])
            ->paginate(20)
            ->withQueryString();

        return view('admin.leads.index', [
            'leads' => $leads,
            'filters' => $filters,
            'activeFilters' => $this->leadQuery->activeCount($filters),
            'statuses' => SalesStatus::cached(),
            'events' => Event::query()->orderByDesc('is_default')->get(),
            'qrSources' => QrSource::query()->orderBy('name')->get(),
            'sectors' => $this->sectorOptions(),
            'timelines' => $this->questionOptions('timeline'),
            'owners' => User::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(Lead $lead): View
    {
        $lead->load(['answers.question', 'event', 'qrSource', 'owner', 'rule', 'notes.user', 'notificationLogs', 'analyticsEvents']);

        return view('admin.leads.show', [
            'lead' => $lead,
            'statuses' => SalesStatus::cached(),
            'owners' => User::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'sales_status' => ['required', 'string', 'in:'.SalesStatus::cached()->pluck('key')->implode(',')],
        ]);

        if ($data['sales_status'] !== $lead->sales_status) {
            $from = $lead->statusMeta()?->label ?? $lead->sales_status;
            $lead->update(['sales_status' => $data['sales_status'], 'status_changed_at' => now()]);
            $lead->refresh();
            $status = $lead->statusMeta();
            $to = $status?->label ?? $lead->sales_status;

            LeadNote::create([
                'lead_id' => $lead->id,
                'user_id' => $request->user()->id,
                'type' => 'status',
                'body' => "Status: {$from} → {$to}",
            ]);

            // Statuses flagged as client-facing trigger an email to the lead.
            if ($status && $status->notify_client) {
                $notified = $this->notifications->leadStatusChanged($lead, $status);

                if ($notified) {
                    LeadNote::create([
                        'lead_id' => $lead->id,
                        'user_id' => $request->user()->id,
                        'type' => 'system',
                        'body' => __('admin.flash.status_email_sent', ['email' => $lead->email]),
                    ]);
                }
            }
        }

        return back()->with('success', __('admin.flash.status_updated'));
    }

    public function assign(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $lead->update(['assigned_to' => $data['assigned_to'] ?? null]);

        LeadNote::create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'type' => 'assignment',
            'body' => $lead->owner ? 'Assigned to '.$lead->owner->name : 'Unassigned',
        ]);

        return back()->with('success', __('admin.flash.owner_updated'));
    }

    public function storeNote(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        LeadNote::create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'type' => 'note',
            'body' => $data['body'],
        ]);

        return back()->with('success', __('admin.flash.note_added'));
    }

    public function destroy(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $lead->delete();

        return redirect()->route('admin.leads.index')->with('success', __('admin.flash.deleted'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = $this->leadQuery->filters($request);
        $filename = 'creative-mark-leads-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new LeadsExport($filters, $this->leadQuery), $filename);
    }

    /** @return array<string,string> */
    protected function sectorOptions(): array
    {
        return $this->questionOptions('sector');
    }

    /** @return array<string,string> */
    protected function questionOptions(string $questionKey): array
    {
        return QuizOption::query()
            ->whereHas('question', fn (Builder $q) => $q->where('key', $questionKey))
            ->orderBy('position')
            ->pluck('label', 'key')
            ->all();
    }
}
