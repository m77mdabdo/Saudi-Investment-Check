<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Lead;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notifications) {}

    public function index(): View
    {
        return view('admin.notifications.index', [
            'notifications' => AdminNotification::query()->with('lead')->latest()->paginate(25),
            'unread' => AdminNotification::query()->whereNull('read_at')->count(),
        ]);
    }

    public function read(AdminNotification $notification): RedirectResponse
    {
        $notification->update(['read_at' => now()]);

        return $notification->url ? redirect()->to($notification->url) : back();
    }

    public function readAll(): RedirectResponse
    {
        AdminNotification::query()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'تم تعليم الكل كمقروء.');
    }

    public function templates(): View
    {
        return view('admin.notifications.templates', [
            'templates' => NotificationTemplate::query()->orderBy('audience')->get(),
            'variables' => NotificationTemplate::VARIABLES,
            'logs' => NotificationLog::query()->with('lead')->latest()->limit(25)->get(),
            'recipients' => $this->notifications->adminRecipients(),
        ]);
    }

    public function updateTemplate(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'تم حفظ القالب.');
    }

    public function previewTemplate(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        try {
            $this->notifications->sendPreview($template, $data['email'], Lead::query()->latest()->first());
        } catch (\Throwable $e) {
            return back()->with('error', 'تعذر إرسال المعاينة. راجع إعدادات البريد.');
        }

        return back()->with('success', 'تم إرسال المعاينة إلى '.$data['email']);
    }
}
