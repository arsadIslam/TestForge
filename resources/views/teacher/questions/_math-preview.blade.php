{{-- Live KaTeX preview of the surrounding x-data's `text`; only shown when it contains LaTeX. --}}
<div x-show="hasMath(text)" x-cloak class="mt-2 rounded-md border border-dashed border-indigo-200 bg-indigo-50/40 px-3 py-2">
    <div class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Preview</div>
    <div class="mt-1 whitespace-pre-line text-gray-900" x-ref="mathPreview" x-text="text"
         x-effect="text; $nextTick(() => renderMath($refs.mathPreview))"></div>
</div>
