<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TopicRequest;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TopicController extends Controller
{
    public function index(Request $request): View
    {
        $subjectId = $request->integer('subject_id') ?: null;

        $topics = Topic::query()
            ->with('subject')           // eager load: 1 extra query for the whole page
            ->withCount('questions')
            ->when($subjectId, fn (Builder $q) => $q->where('subject_id', $subjectId))
            ->orderBy('subject_id')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.topics.index', [
            'topics' => $topics,
            'subjects' => $this->subjectOptions(),
            'selectedSubjectId' => $subjectId,
        ]);
    }

    public function create(Request $request): View
    {
        // ?subject_id= pre-selects the subject when coming from a filtered list.
        $topic = new Topic(['subject_id' => $request->integer('subject_id') ?: null]);

        return view('admin.topics.create', ['topic' => $topic, 'subjects' => $this->subjectOptions()]);
    }

    public function store(TopicRequest $request): RedirectResponse
    {
        $topic = Topic::create($request->validated());

        return redirect()->route('admin.topics.index', ['subject_id' => $topic->subject_id])
            ->with('success', "Topic \"{$topic->name}\" created.");
    }

    public function edit(Topic $topic): View
    {
        return view('admin.topics.edit', ['topic' => $topic, 'subjects' => $this->subjectOptions()]);
    }

    public function update(TopicRequest $request, Topic $topic): RedirectResponse
    {
        $topic->update($request->validated());

        return redirect()->route('admin.topics.index', ['subject_id' => $topic->subject_id])
            ->with('success', "Topic \"{$topic->name}\" updated.");
    }

    public function destroy(Topic $topic): RedirectResponse
    {
        // questions.topic_id is RESTRICT; soft-deleted questions still count.
        if ($topic->questions()->withTrashed()->exists()) {
            return back()->with('error', "\"{$topic->name}\" still has questions. Move or delete them first.");
        }

        $topic->delete();

        return back()->with('success', "Topic \"{$topic->name}\" deleted.");
    }

    /**
     * id => name list for the subject dropdowns (a small table, so no pagination needed).
     *
     * @return Collection<int, string>
     */
    private function subjectOptions(): Collection
    {
        return Subject::orderBy('name')->pluck('name', 'id');
    }
}
