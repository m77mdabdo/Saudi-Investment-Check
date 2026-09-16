<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        return view('admin.events.index', [
            'events' => Event::query()->withCount('leads')->orderByDesc('is_default')->orderByDesc('starts_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.events.edit', ['event' => new Event(['status' => 'active'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = Event::create($this->validated($request));
        $this->syncDefault($event);

        return redirect()->route('admin.events.index')->with('success', 'تم إنشاء الفعالية.');
    }

    public function edit(Event $event): View
    {
        return view('admin.events.edit', ['event' => $event]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $event->update($this->validated($request, $event));
        $this->syncDefault($event);

        return back()->with('success', 'تم حفظ الفعالية.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        abort_if($event->leads()->exists(), 422, 'Cannot delete an event that already has leads.');

        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'تم حذف الفعالية.');
    }

    protected function validated(Request $request, ?Event $event = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:events,slug'.($event ? ','.$event->id : '')],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:active,upcoming,archived'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        $data['slug'] = Str::slug(($data['slug'] ?? null) ?: $data['name']);
        $data['is_default'] = $request->boolean('is_default');

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('events', 'public');
        }

        unset($data['logo']);

        return $data;
    }

    protected function syncDefault(Event $event): void
    {
        if ($event->is_default) {
            Event::query()->whereKeyNot($event->id)->update(['is_default' => false]);
        }
    }
}
