<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Subject;
use App\Models\User;
use App\Support\ImportPreview;
use App\Support\QuestionImages;
use App\Support\QuestionRules;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use SplFileObject;
use Throwable;
use ZipArchive;

/**
 * Bulk import of questions from a CSV file (+ an optional ZIP of images).
 *
 *   1. store()   — keep the uploaded files under storage/app/private/imports/{teacher}/{uuid}/
 *   2. preview() — read + validate every row (same rules as the form: QuestionRules), check every
 *                  referenced image inside the ZIP — nothing is saved
 *   3. import()  — RE-reads and RE-validates (never trusts an earlier preview), then creates all
 *                  questions in ONE transaction through QuestionService::create(). All-or-nothing:
 *                  a file with any error imports nothing, so fixing it and re-uploading never
 *                  creates duplicates.
 *
 * Single vs multiple choice comes from the `correct` column (1 number → single, 2+ → multiple).
 */
class QuestionImportService
{
    public const MAX_ROWS = 500;

    public const MAX_ZIP_ENTRIES = 500;

    public const MAX_OPTIONS = QuestionRules::MAX_OPTIONS;

    public const DISK = 'local'; // storage/app/private — never web-accessible

    /** Columns that must be present in the header row. */
    public const REQUIRED_COLUMNS = ['subject', 'topic', 'body', 'option_1', 'option_2', 'correct'];

    public function __construct(private QuestionService $questions) {}

    /**
     * Column order for the downloadable template.
     *
     * @return list<string>
     */
    public static function columns(): array
    {
        $columns = ['subject', 'topic', 'difficulty', 'marks', 'negative_marks', 'body', 'explanation', 'tags', 'image'];

        foreach (range(1, self::MAX_OPTIONS) as $n) {
            $columns[] = "option_{$n}";
            $columns[] = "option_{$n}_image";
        }

        $columns[] = 'correct';

        return $columns;
    }

    /**
     * @return string the import id (a UUID) — the folder name
     */
    public function store(User $teacher, UploadedFile $csv, ?UploadedFile $zip): string
    {
        $uuid = (string) Str::uuid();
        $dir = $this->directory($teacher, $uuid);

        Storage::disk(self::DISK)->putFileAs($dir, $csv, 'questions.csv');

        if ($zip) {
            Storage::disk(self::DISK)->putFileAs($dir, $zip, 'images.zip');
        }

        return $uuid;
    }

    /**
     * True if this teacher has an upload with this id. The teacher id is part of the path,
     * so one teacher can never open another teacher's upload.
     */
    public function exists(User $teacher, string $uuid): bool
    {
        return Str::isUuid($uuid)
            && Storage::disk(self::DISK)->exists($this->directory($teacher, $uuid).'/questions.csv');
    }

    public function discard(User $teacher, string $uuid): void
    {
        if (Str::isUuid($uuid)) {
            Storage::disk(self::DISK)->deleteDirectory($this->directory($teacher, $uuid));
        }
    }

    public function preview(User $teacher, string $uuid): ImportPreview
    {
        $disk = Storage::disk(self::DISK);
        $dir = $this->directory($teacher, $uuid);

        $zip = null;
        if ($disk->exists("{$dir}/images.zip")) {
            $zip = new ZipArchive;
            if ($zip->open($disk->path("{$dir}/images.zip"), ZipArchive::RDONLY) !== true) {
                return new ImportPreview([], ['The ZIP file could not be opened. Is it a valid .zip?']);
            }
        }

        try {
            return $this->validate($disk->path("{$dir}/questions.csv"), $zip);
        } finally {
            $zip?->close();
        }
    }

    /**
     * @return int number of questions created
     */
    public function import(User $teacher, string $uuid): int
    {
        $preview = $this->preview($teacher, $uuid); // re-read and re-validate, never trust the earlier preview

        if (! $preview->isValid()) {
            throw new BusinessRuleException('This file has errors, so nothing was imported. Fix them and upload it again.');
        }

        $zipPath = Storage::disk(self::DISK)->path($this->directory($teacher, $uuid).'/images.zip');
        $zip = null;
        if (is_file($zipPath)) {
            $zip = new ZipArchive;
            $zip->open($zipPath, ZipArchive::RDONLY);
        }

        $images = new QuestionImages; // shared by every row: one failure discards ALL stored files
        $tempFiles = [];

        try {
            $count = DB::transaction(function () use ($teacher, $preview, $zip, $images, &$tempFiles): int {
                foreach ($preview->rows as $row) {
                    $data = $row['data'];
                    $data['image'] = $this->extract($zip, $data['image'] ?? null, $tempFiles);

                    foreach ($data['options'] as $i => $option) {
                        $data['options'][$i]['image'] = $this->extract($zip, $option['image'] ?? null, $tempFiles);
                    }

                    $this->questions->create($teacher, $data, $images);
                }

                return count($preview->rows);
            });
        } catch (Throwable $e) {
            $images->discardStored();

            throw $e;
        } finally {
            $zip?->close();
            array_map('unlink', array_filter($tempFiles, 'is_file'));
        }

        $this->discard($teacher, $uuid);

        return $count;
    }

    /**
     * Remove upload folders older than $hours (abandoned previews). Used by imports:prune.
     *
     * @return int folders removed
     */
    public function prune(int $hours = 24): int
    {
        $disk = Storage::disk(self::DISK);
        $cutoff = now()->subHours($hours)->getTimestamp();
        $removed = 0;

        foreach ($disk->directories('imports') as $teacherDir) {
            foreach ($disk->directories($teacherDir) as $dir) {
                $csv = "{$dir}/questions.csv";

                if (! $disk->exists($csv) || $disk->lastModified($csv) < $cutoff) {
                    $disk->deleteDirectory($dir);
                    $removed++;
                }
            }
        }

        return $removed;
    }

    // ------------------------------------------------------------------ reading & validating

    private function validate(string $csvPath, ?ZipArchive $zip): ImportPreview
    {
        [$header, $lines, $fileErrors] = $this->read($csvPath);

        if ($fileErrors !== []) {
            return new ImportPreview([], $fileErrors);
        }

        if ($zip && $zip->numFiles > self::MAX_ZIP_ENTRIES) {
            return new ImportPreview([], ['The ZIP has more than '.self::MAX_ZIP_ENTRIES.' files.']);
        }

        $topics = $this->topicLookup();
        $rows = [];

        foreach ($lines as [$number, $cells]) {
            $values = array_combine($header, array_pad(array_slice($cells, 0, count($header)), count($header), ''));
            $rows[] = $this->validateRow($number, $values, $topics, $zip);
        }

        return new ImportPreview($rows);
    }

    /**
     * @return array{0: list<string>, 1: list<array{0: int, 1: list<string>}>, 2: list<string>}
     */
    private function read(string $path): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);

        $header = null;
        $lines = [];
        $number = 1; // spreadsheet row number; the header is row 1

        foreach ($file as $cells) {
            if ($cells === [null] || $cells === false) {
                continue;
            }

            if ($header === null) {
                // Excel's "CSV UTF-8" starts the file with a byte-order mark: strip it.
                $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $cells[0]);
                $header = array_map(fn ($c): string => Str::lower(trim((string) $c)), $cells);

                continue;
            }

            $number++;
            $cells = array_map(fn ($c): string => trim((string) $c), $cells);

            if (implode('', $cells) === '') {
                continue; // a blank line in the middle of the sheet
            }

            if (! mb_check_encoding(implode('', $cells), 'UTF-8')) {
                return [[], [], ["Row {$number} is not UTF-8 text. In Excel use \"Save as → CSV UTF-8\"."]];
            }

            $lines[] = [$number, $cells];
        }

        if ($header === null) {
            return [[], [], ['The file is empty.']];
        }

        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        if ($missing !== []) {
            return [[], [], ['Missing column(s): '.implode(', ', $missing).'. Download the template to see the expected columns.']];
        }

        if ($lines === []) {
            return [[], [], ['The file has a header row but no questions.']];
        }

        if (count($lines) > self::MAX_ROWS) {
            return [[], [], ['A file can contain at most '.self::MAX_ROWS.' questions; this one has '.count($lines).'.']];
        }

        return [$header, $lines, []];
    }

    /**
     * @param  array<string, string>  $values  column => cell
     * @param  array<string, int>  $topics  "subject|topic" (lower case) => topic id
     * @return array{number: int, data: array<string, mixed>, summary: array<string, mixed>, errors: list<string>}
     */
    private function validateRow(int $number, array $values, array $topics, ?ZipArchive $zip): array
    {
        $errors = [];
        $get = fn (string $column): string => $values[$column] ?? '';

        // --- topic by name
        $topicKey = Str::lower($get('subject')).'|'.Str::lower($get('topic'));
        $topicId = $topics[$topicKey] ?? null;
        if ($topicId === null) {
            $errors[] = "Unknown topic \"{$get('topic')}\" in subject \"{$get('subject')}\" (ask an admin to add it).";
        }

        // --- options (kept by their column number, so `correct` can refer to them)
        $options = [];
        foreach (range(1, self::MAX_OPTIONS) as $n) {
            $text = $get("option_{$n}");
            $image = $get("option_{$n}_image");

            if ($text !== '' || $image !== '') {
                $options[$n] = ['text' => $text, 'image' => $image !== '' ? $image : null, 'is_correct' => false];
            }
        }

        // --- correct: "2" or "1|3" (commas and spaces work too)
        $correct = preg_split('/[\s|,;]+/', $get('correct'), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($correct as $value) {
            if (! ctype_digit($value) || ! isset($options[(int) $value])) {
                $errors[] = "\"correct\" refers to option {$value}, which is empty or doesn't exist.";

                continue;
            }

            $options[(int) $value]['is_correct'] = true;
        }

        $data = [
            'topic_id' => $topicId,
            'body' => $get('body'),
            'explanation' => $get('explanation') !== '' ? $get('explanation') : null,
            'difficulty' => Str::lower($get('difficulty') !== '' ? $get('difficulty') : 'medium'),
            'marks' => $get('marks') !== '' ? $get('marks') : '1',
            'negative_marks' => $get('negative_marks') !== '' ? $get('negative_marks') : '0',
            'is_active' => true,
            'tags' => str_replace('|', ',', $get('tags')),
            'tag_names' => QuestionRules::tagNames($get('tags'), '|'),
            'image' => $get('image') !== '' ? $get('image') : null,
            'options' => array_values($options),
        ];

        // --- the same rules as the form
        $validator = Validator::make(
            $data,
            [...QuestionRules::question(uploads: false), ...QuestionRules::options(uploads: false)],
            QuestionRules::messages(),
            QuestionRules::attributes(),
        );
        $validator->after(fn ($v) => QuestionRules::checkOptions($v, $data['options']));

        foreach ($validator->errors()->messages() as $field => $messages) {
            if ($field === 'topic_id' && $topicId === null) {
                continue; // already reported by name above
            }

            array_push($errors, ...$messages);
        }

        // --- images inside the ZIP
        $imageNames = array_filter([$data['image'], ...array_column($data['options'], 'image')]);
        foreach ($imageNames as $name) {
            if ($problem = $this->imageProblem($zip, $name)) {
                $errors[] = $problem;
            }
        }

        $correctCount = QuestionRules::correctCount($data['options']);

        return [
            'number' => $number,
            'data' => $data,
            'summary' => [
                'body' => Str::limit($data['body'], 90),
                'topic' => $get('subject').' › '.$get('topic'),
                'options' => count($data['options']),
                'type' => $correctCount > 1 ? 'Multiple' : 'Single',
                'correct' => implode(', ', array_keys(array_filter($options, fn (array $o): bool => $o['is_correct']))),
                'difficulty' => ucfirst($data['difficulty']),
                'marks' => $data['marks'].($data['negative_marks'] !== '0' ? ' / −'.$data['negative_marks'] : ''),
                'images' => count($imageNames),
            ],
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /**
     * Checks an image inside the ZIP WITHOUT extracting it to disk: it must exist, be at most
     * 2 MB, really be a JPG/PNG/WebP (checked from its bytes, not its name) and ≤ 4000 px.
     */
    private function imageProblem(?ZipArchive $zip, string $name): ?string
    {
        if ($zip === null) {
            return "Uses image \"{$name}\" but no ZIP file was uploaded.";
        }

        $index = $this->locate($zip, $name);
        if ($index === null) {
            return "Image \"{$name}\" is not in the ZIP.";
        }

        $stat = $zip->statIndex($index);
        if ($stat['size'] > QuestionRules::IMAGE_MAX_KB * 1024) { // uncompressed size: no zip-bomb surprises
            return "Image \"{$name}\" is larger than 2 MB.";
        }

        $info = @getimagesizefromstring((string) $zip->getFromIndex($index));
        if ($info === false || ! in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return "\"{$name}\" is not a JPG, PNG or WebP image.";
        }

        if ($info[0] > 4000 || $info[1] > 4000) {
            return "Image \"{$name}\" is larger than 4000 × 4000 pixels.";
        }

        return null;
    }

    /**
     * Find an entry by its file name, ignoring case and any folders inside the ZIP.
     */
    private function locate(ZipArchive $zip, string $name): ?int
    {
        $index = $zip->locateName(basename($name), ZipArchive::FL_NOCASE | ZipArchive::FL_NODIR);

        return $index === false ? null : $index;
    }

    /**
     * Copy one image out of the ZIP into a temporary file. Only the file's BYTES are used —
     * never its path inside the ZIP — so a crafted "../../evil.php" entry can't write anywhere.
     *
     * @param  list<string>  $tempFiles  collects temp paths for cleanup
     */
    private function extract(?ZipArchive $zip, ?string $name, array &$tempFiles): ?File
    {
        if ($zip === null || $name === null) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'tf-img-');
        file_put_contents($tmp, $zip->getFromIndex($this->locate($zip, $name)));
        $tempFiles[] = $tmp;

        return new File($tmp);
    }

    /**
     * @return array<string, int> "subject|topic" (lower case) => topic id
     */
    private function topicLookup(): array
    {
        $map = [];

        foreach (Subject::with('topics')->get() as $subject) {
            foreach ($subject->topics as $topic) {
                $map[Str::lower($subject->name).'|'.Str::lower($topic->name)] = $topic->id;
            }
        }

        return $map;
    }

    private function directory(User $teacher, string $uuid): string
    {
        return "imports/{$teacher->id}/{$uuid}";
    }
}
