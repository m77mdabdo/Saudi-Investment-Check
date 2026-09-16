<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResultRule;
use App\Services\ScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultRuleController extends Controller
{
    public function __construct(protected ScoringService $scoring) {}

    public function index(): View
    {
        return view('admin.results.index', [
            'rules' => ResultRule::query()->orderBy('position')->get(),
            'maxScore' => $this->scoring->maxScore(),
        ]);
    }

    public function create(): View
    {
        return view('admin.results.edit', ['rule' => new ResultRule(['indicator' => 'green', 'is_active' => true]), 'maxScore' => $this->scoring->maxScore()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rule = ResultRule::create($this->validated($request));

        return redirect()->route('admin.results.edit', $rule)->with('success', 'تم إنشاء النتيجة.');
    }

    public function edit(ResultRule $result): View
    {
        return view('admin.results.edit', ['rule' => $result, 'maxScore' => $this->scoring->maxScore()]);
    }

    public function update(Request $request, ResultRule $result): RedirectResponse
    {
        $result->update($this->validated($request, $result));

        return back()->with('success', 'تم حفظ النتيجة.');
    }

    public function destroy(ResultRule $result): RedirectResponse
    {
        $result->delete();

        return redirect()->route('admin.results.index')->with('success', 'تم حذف النتيجة.');
    }

    protected function validated(Request $request, ?ResultRule $rule = null): array
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/', 'unique:result_rules,key'.($rule ? ','.$rule->id : '')],
            'classification' => ['required', 'string', 'max:40'],
            'indicator' => ['required', 'in:green,amber,coral'],
            'min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'max_score' => ['required', 'integer', 'min:0', 'max:100', 'gte:min_score'],
            'headline' => ['required', 'string', 'max:180'],
            'main_text' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:2000'],
            'highlight' => ['nullable', 'string', 'max:300'],
            'bullets_text' => ['nullable', 'string', 'max:2000'],
            'primary_cta_label' => ['nullable', 'string', 'max:120'],
            'primary_cta_url' => ['nullable', 'url', 'max:500'],
            'secondary_cta_label' => ['nullable', 'string', 'max:120'],
            'secondary_cta_url' => ['nullable', 'url', 'max:500'],
            'disclaimer' => ['nullable', 'string', 'max:500'],
            'image_query' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $data['bullets'] = collect(preg_split('/\r?\n/', (string) ($data['bullets_text'] ?? '')))
            ->map(fn ($line) => trim($line))->filter()->values()->all();
        unset($data['bullets_text']);

        $data['is_active'] = $request->boolean('is_active');
        $data['position'] = $data['position'] ?? 0;

        return $data;
    }
}
