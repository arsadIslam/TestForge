<?php

namespace App\Support;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Topic;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The validation rules for a question, in ONE place: used by the create/edit form
 * (Store/UpdateQuestionRequest) and by the CSV importer, so a question that the form
 * would reject can never get in through a bulk upload either.
 */
final class QuestionRules
{
    public const MIN_OPTIONS = 2;

    public const MAX_OPTIONS = 6;

    public const IMAGE_MAX_KB = 2048;

    /** JPG/PNG/WebP only. No SVG: an SVG served from our own domain can contain <script>. */
    public const IMAGE_MIMES = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Rules for an uploaded image file.
     *
     * @return list<string>
     */
    public static function image(): array
    {
        return [
            'nullable',
            'file',
            'mimes:'.implode(',', self::IMAGE_MIMES), // checked against the file's real content, not its name
            'max:'.self::IMAGE_MAX_KB,
            'dimensions:max_width=4000,max_height=4000',
        ];
    }

    /**
     * Everything except the options.
     *
     * @param  bool  $uploads  true for the form (image = uploaded file); false for the importer
     *                         (image = a file name inside the ZIP, checked separately)
     * @return array<string, array<int, mixed>>
     */
    public static function question(bool $uploads = true): array
    {
        return [
            'topic_id' => ['required', 'integer', Rule::exists(Topic::class, 'id')],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'body' => ['required', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'marks' => ['required', 'numeric', 'decimal:0,2', 'min:0.25', 'max:100'],
            'negative_marks' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'lte:marks'],
            'is_active' => ['required', 'boolean'],
            'tags' => ['nullable', 'string', 'max:500'],
            'tag_names' => ['array', 'max:10'],
            'tag_names.*' => ['string', 'max:50'],
            'image' => $uploads ? self::image() : ['nullable', 'string', 'max:255'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function options(bool $uploads = true): array
    {
        return [
            'options' => ['required', 'array', 'min:'.self::MIN_OPTIONS, 'max:'.self::MAX_OPTIONS],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.text' => ['nullable', 'string', 'max:1000'],
            'options.*.is_correct' => ['required', 'boolean'],
            'options.*.image' => $uploads ? self::image() : ['nullable', 'string', 'max:255'],
            'options.*.remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Checks that span several fields (run from a validator's after() hook):
     *  - single choice: exactly one correct option; multiple choice: at least one;
     *  - every option needs text or an image;
     *  - no two options with the same text (image-only options are not "duplicates").
     *
     * @param  array<int, array<string, mixed>>  $options
     * @param  array<int, bool>  $existingImages  option id => it already has an image (editing)
     */
    public static function checkOptions(Validator $validator, ?string $type, array $options, array $existingImages = []): void
    {
        $correct = collect($options)
            ->filter(fn ($option): bool => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))
            ->count();

        if ($type === QuestionType::Single->value && $correct !== 1) {
            $validator->errors()->add('options', 'A single-choice question needs exactly one correct option.');
        }

        if ($type === QuestionType::Multiple->value && $correct < 1) {
            $validator->errors()->add('options', 'Mark at least one option as correct.');
        }

        $seen = [];

        foreach (array_values($options) as $index => $option) {
            $number = $index + 1;
            $text = trim((string) ($option['text'] ?? ''));

            $hasImage = self::hasNewImage($option['image'] ?? null)
                || (! empty($option['id'])
                    && ($existingImages[(int) $option['id']] ?? false)
                    && ! filter_var($option['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN));

            if ($text === '' && ! $hasImage) {
                $validator->errors()->add("options.{$index}.text", "Option {$number} needs text or an image.");

                continue;
            }

            if ($text !== '') {
                $key = mb_strtolower($text);

                if (isset($seen[$key])) {
                    $validator->errors()->add("options.{$index}.text", "Options {$seen[$key]} and {$number} have the same text.");
                }

                $seen[$key] ??= $number;
            }
        }
    }

    /**
     * An uploaded file (form) or a non-empty file name (importer).
     */
    private static function hasNewImage(mixed $image): bool
    {
        return $image instanceof UploadedFile || (is_string($image) && trim($image) !== '');
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'options.min' => 'Add at least :min options.',
            'options.max' => 'A question can have at most :max options.',
            'negative_marks.lte' => 'Negative marks cannot be more than the marks for a correct answer.',
            'image.mimes' => 'The image must be a JPG, PNG or WebP file.',
            'image.max' => 'The image must be 2 MB or smaller.',
            'image.dimensions' => 'The image can be at most 4000 × 4000 pixels.',
            'options.*.image.mimes' => 'Option images must be JPG, PNG or WebP files.',
            'options.*.image.max' => 'Option images must be 2 MB or smaller.',
            'options.*.image.dimensions' => 'Option images can be at most 4000 × 4000 pixels.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'topic_id' => 'topic',
            'options.*.text' => 'option text',
            'tag_names.*' => 'tag',
        ];
    }
}
