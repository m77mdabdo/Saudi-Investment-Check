<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Services\MailHealth;
use App\Services\NotificationService;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Production visibility for outbound mail: what was sent, to whom, and why
 * anything failed — plus a safe way to test SMTP without touching lead data.
 */
class EmailLogController extends Controller
{
    public function __construct(
        protected NotificationService $notifications,
        protected MailHealth $health,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'status' => in_array($request->query('status'), ['sent', 'failed', 'skipped'], true) ? $request->query('status') : null,
            'template' => $request->filled('template') ? (string) $request->query('template') : null,
            'search' => $request->filled('q') ? (string) $request->query('q') : null,
        ];

        $logs = NotificationLog::query()
            ->with('lead')
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($filters['template'], fn ($q, $template) => $q->where('template_key', $template))
            ->search($filters['search'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.emails.index', [
            'logs' => $logs,
            'filters' => $filters,
            'templates' => NotificationLog::query()->distinct()->pluck('template_key')->filter()->values(),
            'health' => $this->health->report(),
            'stats' => [
                'sent' => NotificationLog::query()->where('status', 'sent')->count(),
                'failed' => NotificationLog::query()->where('status', 'failed')->count(),
                'skipped' => NotificationLog::query()->where('status', 'skipped')->count(),
                'today' => NotificationLog::query()->whereDate('created_at', today())->count(),
            ],
        ]);
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'locale' => ['nullable', 'string', 'in:'.implode(',', Locale::supported())],
        ]);

        try {
            $this->notifications->sendTest($data['email'], $data['locale'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', __('admin.flash.test_email_failed', ['error' => \Illuminate\Support\Str::limit($e->getMessage(), 160)]));
        }

        return back()->with('success', __('admin.flash.test_email_sent', ['email' => $data['email']]));
    }

    public function resend(NotificationLog $log): RedirectResponse
    {
        if (! $log->isRetryable()) {
            return back()->with('error', __('admin.flash.test_email_failed', ['error' => 'not retryable']));
        }

        return $this->notifications->resend($log)
            ? back()->with('success', __('admin.flash.email_resent'))
            : back()->with('error', __('admin.flash.test_email_failed', ['error' => 'send failed — see the log row']));
    }
}
