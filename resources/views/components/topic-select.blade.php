{{-- Topic <select> grouped by subject. Expects $subjects (with topics loaded). --}}
@props(['subjects', 'selected' => null, 'placeholder' => 'Choose a topic…'])

<x-select-input {{ $attributes }}>
    <option value="">{{ $placeholder }}</option>
    @foreach ($subjects as $subject)
        <optgroup label="{{ $subject->name }}">
            @foreach ($subject->topics as $topic)
                <option value="{{ $topic->id }}" @selected((int) $selected === $topic->id)>{{ $topic->name }}</option>
            @endforeach
        </optgroup>
    @endforeach
</x-select-input>
