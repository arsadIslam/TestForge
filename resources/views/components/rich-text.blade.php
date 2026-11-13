{{--
    Question/option/explanation text that may contain LaTeX: \( … \), \[ … \], $$ … $$.
    {{ }} escapes the text first; app.js then lets KaTeX turn the formulas into maths.
--}}
@props(['text' => null, 'as' => 'div'])

@if (filled($text))
    <{{ $as }} data-math {{ $attributes->merge(['class' => 'whitespace-pre-line']) }}>{{ $text }}</{{ $as }}>
@endif
