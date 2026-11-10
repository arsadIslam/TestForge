{{-- Status actions for one test; each button only shows if the policy allows it. --}}
<div class="flex flex-wrap items-center justify-end gap-3">
    @can('update', $test)
        <a href="{{ route('teacher.tests.questions.index', $test) }}" class="text-indigo-600 hover:underline">Questions</a>
        <a href="{{ route('teacher.tests.edit', $test) }}" class="text-indigo-600 hover:underline">Edit</a>
    @endcan
    @can('publish', $test)
        <form method="POST" action="{{ route('teacher.tests.publish', $test) }}"
              onsubmit="return confirm('Publish this test? Its questions and settings will be locked.')">
            @csrf
            @method('PATCH')
            <button type="submit" class="font-semibold text-green-700 hover:underline">Publish</button>
        </form>
    @endcan
    @can('archive', $test)
        <form method="POST" action="{{ route('teacher.tests.archive', $test) }}"
              onsubmit="return confirm('Archive this test? Students will no longer be able to start it.')">
            @csrf
            @method('PATCH')
            <button type="submit" class="text-amber-700 hover:underline">Archive</button>
        </form>
    @endcan
    @can('delete', $test)
        <form method="POST" action="{{ route('teacher.tests.destroy', $test) }}" onsubmit="return confirm('Delete this draft test?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-600 hover:underline">Delete</button>
        </form>
    @endcan
</div>
