<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuizBuilderController extends Controller
{
    public function __construct(protected ScoringService $scoring) {}

    public function index(): View
    {
        return view('admin.quiz.index', [
            'questions' => QuizQuestion::query()->with('options')->orderBy('position')->get(),
            'maxScore' => $this->scoring->maxScore(),
        ]);
    }

    public function create(): View
    {
        return view('admin.quiz.edit', ['question' => new QuizQuestion(['type' => 'single', 'is_active' => true, 'is_required' => true, 'is_scored' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['position'] = $data['position'] ?? ((int) QuizQuestion::max('position') + 1);

        $question = QuizQuestion::create($data);

        return redirect()->route('admin.quiz.edit', $question)->with('success', 'تم إنشاء السؤال. ضيف الاختيارات دلوقتي.');
    }

    public function edit(QuizQuestion $question): View
    {
        return view('admin.quiz.edit', ['question' => $question->load('options')]);
    }

    public function update(Request $request, QuizQuestion $question): RedirectResponse
    {
        $question->update($this->validated($request, $question));

        return back()->with('success', 'تم حفظ السؤال.');
    }

    public function destroy(QuizQuestion $question): RedirectResponse
    {
        $question->delete();

        return redirect()->route('admin.quiz.index')->with('success', 'تم حذف السؤال.');
    }

    public function toggle(QuizQuestion $question): RedirectResponse
    {
        $question->update(['is_active' => ! $question->is_active]);

        return back()->with('success', $question->is_active ? 'السؤال اتفعّل.' : 'السؤال اتوقف.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:quiz_questions,id'],
        ]);

        foreach ($data['order'] as $position => $id) {
            QuizQuestion::query()->whereKey($id)->update(['position' => $position + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function storeOption(Request $request, QuizQuestion $question): RedirectResponse
    {
        $data = $this->validatedOption($request, $question);
        $data['position'] = $data['position'] ?? ((int) $question->options()->max('position') + 1);

        $question->options()->create($data);

        return back()->with('success', 'تمت إضافة الاختيار.');
    }

    public function updateOption(Request $request, QuizQuestion $question, QuizOption $option): RedirectResponse
    {
        abort_unless($option->quiz_question_id === $question->id, 404);

        $option->update($this->validatedOption($request, $question, $option));

        return back()->with('success', 'تم حفظ الاختيار.');
    }

    public function destroyOption(QuizQuestion $question, QuizOption $option): RedirectResponse
    {
        abort_unless($option->quiz_question_id === $question->id, 404);

        $option->delete();

        return back()->with('success', 'تم حذف الاختيار.');
    }

    public function reorderOptions(Request $request, QuizQuestion $question): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($data['order'] as $position => $id) {
            $question->options()->whereKey($id)->update(['position' => $position + 1]);
        }

        return response()->json(['ok' => true]);
    }

    protected function validated(Request $request, ?QuizQuestion $question = null): array
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/', 'unique:quiz_questions,key'.($question ? ','.$question->id : '')],
            'type' => ['required', 'in:'.implode(',', QuizQuestion::TYPES)],
            'title' => ['required', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'icon' => ['nullable', 'string', 'max:8'],
            'placeholder' => ['nullable', 'string', 'max:120'],
            'is_required' => ['nullable', 'boolean'],
            'is_scored' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $data['key'] = Str::slug($data['key'], '_');
        $data['is_required'] = $request->boolean('is_required');
        $data['is_scored'] = $request->boolean('is_scored');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function validatedOption(Request $request, QuizQuestion $question, ?QuizOption $option = null): array
    {
        $unique = 'unique:quiz_options,key,'.($option?->id ?? 'NULL').',id,quiz_question_id,'.$question->id;

        $data = $request->validate([
            'key' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/', $unique],
            'label' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:160'],
            'icon' => ['nullable', 'string', 'max:8'],
            'score' => ['required', 'integer', 'min:0', 'max:20'],
            'requires_detail' => ['nullable', 'boolean'],
            'detail_label' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $data['requires_detail'] = $request->boolean('requires_detail');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
