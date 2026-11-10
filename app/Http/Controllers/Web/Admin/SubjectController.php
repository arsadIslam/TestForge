<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        $subjects = Subject::query()
            ->withCount(['topics', 'questions']) // two COUNT sub-selects, no N+1
            ->orderBy('name')
            ->paginate(15);

        return view('admin.subjects.index', ['subjects' => $subjects]);
    }

    public function create(): View
    {
        return view('admin.subjects.create', ['subject' => new Subject]);
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $subject = Subject::create($request->validated());

        return redirect()->route('admin.subjects.index')
            ->with('success', "Subject \"{$subject->name}\" created.");
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', ['subject' => $subject]);
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return redirect()->route('admin.subjects.index')
            ->with('success', "Subject \"{$subject->name}\" updated.");
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        // Topics cascade-delete, but questions.topic_id is RESTRICT. withTrashed():
        // soft-deleted questions are still rows, so the FK would block those too.
        if ($subject->questions()->withTrashed()->exists()) {
            return back()->with('error', "\"{$subject->name}\" still has questions. Move or delete them first.");
        }

        $subject->delete();

        return redirect()->route('admin.subjects.index')
            ->with('success', "Subject \"{$subject->name}\" deleted.");
    }
}
