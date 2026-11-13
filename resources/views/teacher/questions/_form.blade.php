{{-- Shared by create and edit. All variables come from QuestionController::formData(). --}}
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="topic_id" value="Topic" />
        <x-topic-select id="topic_id" name="topic_id" class="mt-1 block w-full" required
                        :subjects="$subjects" :selected="old('topic_id', $question->topic_id)" />
        <x-input-error class="mt-2" :messages="$errors->get('topic_id')" />
    </div>

    <div>
        <x-input-label for="difficulty" value="Difficulty" />
        <x-select-input id="difficulty" name="difficulty" class="mt-1 block w-full" required>
            @foreach ($difficulties as $difficulty)
                <option value="{{ $difficulty->value }}" @selected(old('difficulty', $question->difficulty?->value) === $difficulty->value)>{{ $difficulty->label() }}</option>
            @endforeach
        </x-select-input>
        <x-input-error class="mt-2" :messages="$errors->get('difficulty')" />
    </div>
</div>

<div x-data="{ text: @js(old('body', $question->body) ?? '') }">
    <x-input-label for="body" value="Question" />
    <textarea id="body" name="body" rows="4" maxlength="5000" required x-model="text"
              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body', $question->body) }}</textarea>
    <p class="mt-1 text-xs text-gray-500">Maths: <code>\( x^2 \)</code> inline, <code>$$ \frac{a}{b} $$</code> on its own line, chemistry <code>\( \ce{H2O} \)</code>. A single <code>$</code> stays a dollar sign.</p>
    @include('teacher.questions._math-preview')
    <x-input-error class="mt-2" :messages="$errors->get('body')" />
</div>

{{-- Question diagram/image (optional). Editable even when the question is locked, like its text. --}}
<div x-data="imagePicker({ savedUrl: @js($question->image_url) })">
    <x-input-label value="Diagram / image (optional)" />
    <div class="mt-1 flex flex-wrap items-start gap-4">
        <template x-if="shown">
            <img :src="shown" alt="Question image preview" class="max-h-48 rounded-lg border border-gray-200 bg-white object-contain">
        </template>
        <div class="flex flex-col gap-2 text-sm">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-gray-300 px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50">
                <input x-ref="file" type="file" name="image" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick($event)">
                <span x-text="shown ? 'Change image' : 'Add image'">Add image</span>
            </label>
            <button type="button" x-show="shown" @click="clear()" class="text-left text-red-600 hover:underline">Remove image</button>
            <span class="text-xs text-gray-500">JPG, PNG or WebP · up to 2 MB</span>
        </div>
    </div>
    <input type="hidden" name="remove_image" :value="remove ? 1 : 0">
    <x-input-error class="mt-2" :messages="$errors->get('image')" />
</div>

@if ($errors->any())
    <p class="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">
        If you had chosen new images, please choose them again — browsers can't keep selected files after an error.
    </p>
@endif

@if ($locked)
    {{-- Frozen answer key: shown read-only and NOT submitted. --}}
    <div class="rounded-md border border-amber-200 bg-amber-50 p-4">
        <p class="text-sm text-amber-800">
            This question is used in a published test, so its type, options and correct answers are locked.
            You can still edit the text, explanation, difficulty, marks, topic and tags.
        </p>
        <p class="mt-3 text-sm font-medium text-gray-700">{{ $question->type->label() }}</p>
        <ol class="mt-2 space-y-1 text-sm">
            @foreach ($options as $option)
                <li @class(['flex items-center gap-2', 'font-semibold text-green-700' => $option['is_correct']])>
                    <span>{{ chr(64 + $loop->iteration) }}. {{ $option['text'] }} @if ($option['is_correct']) ✓ @endif</span>
                    <x-question-image :src="$option['image_url']" size="thumb" :alt="'Option '.chr(64 + $loop->iteration)" />
                </li>
            @endforeach
        </ol>
    </div>
@else
    <div x-data="questionForm({
            options: @js(old('options', $options)),
            savedImages: @js($optionImages),
            min: {{ $minOptions }},
            max: {{ $maxOptions }},
        })" class="space-y-4">
        <div>
            <div class="flex items-baseline justify-between">
                <x-input-label value="Options" />
                <span class="text-xs text-gray-500">Tick every correct option</span>
            </div>

            <div class="mt-2 space-y-2">
                <template x-for="(option, index) in options" :key="option.key">
                    <div class="rounded-md border border-gray-200 p-3" data-option-row>
                    <div class="flex items-center gap-3">
                        {{-- What is actually submitted --}}
                        <input type="hidden" :name="`options[${index}][id]`" :value="option.id ?? ''">
                        <input type="hidden" :name="`options[${index}][is_correct]`" :value="option.is_correct ? 1 : 0">
                        <input type="hidden" :name="`options[${index}][remove_image]`" :value="option.remove_image ? 1 : 0">

                        {{-- UI only (no name, so not submitted): the hidden is_correct input above is what counts --}}
                        <input type="checkbox"
                               :checked="option.is_correct"
                               @change="toggleCorrect(index)"
                               :aria-label="`Option ${index + 1} is correct`"
                               class="h-5 w-5 rounded border-gray-300 text-green-600 focus:ring-green-500">

                        <span class="w-5 text-sm font-semibold text-gray-500" x-text="String.fromCharCode(65 + index) + '.'"></span>

                        <input type="text" :name="`options[${index}][text]`" x-model="option.text" maxlength="1000"
                               placeholder="Option text (optional if it has an image)"
                               class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                               :class="option.is_correct && 'border-green-400 bg-green-50'">

                        <button type="button" @click="remove(index)" x-show="canRemove"
                                class="text-sm text-red-600 hover:underline">Remove</button>
                    </div>

                    {{-- Live maths preview of this option (only when it contains LaTeX) --}}
                    <template x-if="hasMath(option.text)">
                        <div class="mt-2 ps-14 text-sm text-gray-800" x-text="option.text"
                             x-effect="option.text; $nextTick(() => renderMath($el))"></div>
                    </template>

                    {{-- Option image --}}
                    <div class="mt-2 flex items-center gap-3 ps-14 text-xs">
                        <template x-if="shownImage(option)">
                            <img :src="shownImage(option)" :alt="`Option ${String.fromCharCode(65 + index)} image`"
                                 class="h-16 max-w-[10rem] rounded border border-gray-200 bg-white object-contain">
                        </template>
                        <label class="cursor-pointer font-medium text-indigo-600 hover:underline">
                            <input type="file" class="sr-only" accept="image/png,image/jpeg,image/webp"
                                   :name="`options[${index}][image]`" @change="pickImage(option, $event)">
                            <span x-text="shownImage(option) ? 'Change image' : '+ Image'"></span>
                        </label>
                        <button type="button" x-show="shownImage(option)" @click="clearImage(option, $event)"
                                class="text-red-600 hover:underline">Remove image</button>
                    </div>
                    </div>
                </template>
            </div>

            <div class="mt-3 flex items-center gap-4">
                <x-secondary-button @click="add()" x-show="canAdd">+ Add option</x-secondary-button>
                <span class="text-xs text-gray-500" x-text="`${options.length} of ${max} options · ${correctCount} correct`"></span>
            </div>

            {{-- Single/multiple is derived from the ticks, never chosen: students may only pick
                 several options when several are correct. --}}
            <p class="mt-3 rounded-md px-3 py-2 text-sm"
               :class="correctCount > 1 ? 'bg-indigo-50 text-indigo-800' : (correctCount === 1 ? 'bg-gray-50 text-gray-700' : 'bg-amber-50 text-amber-800')"
               x-text="correctCount > 1
                   ? `Multiple choice: students must select all ${correctCount} correct answers (they are not told how many).`
                   : (correctCount === 1 ? 'Single choice: students pick one answer.' : 'Tick at least one correct option.')"></p>

            {{-- errors for 'options' and every 'options.N.field' --}}
            <x-input-error class="mt-2" :messages="array_merge($errors->get('options'), ...array_values($errors->get('options.*')))" />
            <p class="mt-1 text-xs text-gray-500">An option can be text, an image, or both. Option images: JPG, PNG or WebP, up to 2 MB.</p>
        </div>
    </div>
@endif

<div x-data="{ text: @js(old('explanation', $question->explanation) ?? '') }">
    <x-input-label for="explanation" value="Explanation (shown to students after the result)" />
    <textarea id="explanation" name="explanation" rows="3" maxlength="5000" x-model="text"
              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('explanation', $question->explanation) }}</textarea>
    @include('teacher.questions._math-preview')
    <x-input-error class="mt-2" :messages="$errors->get('explanation')" />
</div>

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="marks" value="Marks for a correct answer" />
        <x-text-input id="marks" name="marks" type="number" step="0.25" min="0.25" max="100" class="mt-1 block w-full" required
                      :value="old('marks', $question->marks)" />
        <x-input-error class="mt-2" :messages="$errors->get('marks')" />
    </div>
    <div>
        <x-input-label for="negative_marks" value="Negative marks for a wrong answer" />
        <x-text-input id="negative_marks" name="negative_marks" type="number" step="0.25" min="0" max="100" class="mt-1 block w-full" required
                      :value="old('negative_marks', $question->negative_marks)" />
        <x-input-error class="mt-2" :messages="$errors->get('negative_marks')" />
    </div>
</div>
<p class="-mt-4 text-xs text-gray-500">Tests keep their own copy of the marks, so changing them here never alters existing tests or results.</p>

<div>
    <x-input-label for="tags" value="Tags (comma separated)" />
    <x-text-input id="tags" name="tags" type="text" class="mt-1 block w-full" maxlength="500" list="existing-tags"
                  :value="old('tags', $tagString)" placeholder="e.g. Important, Previous Year" />
    <datalist id="existing-tags">
        @foreach ($existingTags as $tag)
            <option value="{{ $tag }}">
        @endforeach
    </datalist>
    <x-input-error class="mt-2" :messages="array_merge($errors->get('tags'), $errors->get('tag_names'), ...array_values($errors->get('tag_names.*')))" />
    <p class="mt-1 text-xs text-gray-500">Existing tags: {{ $existingTags->implode(', ') }}. New names create new tags.</p>
</div>

<label class="inline-flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $question->is_active))
           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
    <span class="text-sm text-gray-700">Active (inactive questions cannot be added to tests)</span>
</label>
