<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Stores question/option images on the "public" disk and cleans up safely.
 *
 * - Files get a RANDOM name (putFile → hashName): an upload called "q1_correct.png"
 *   must never reach the browser and hint at the answer.
 * - Replaced/removed files are deleted only AFTER the DB transaction commits, so a
 *   rolled-back save never leaves a question pointing at a deleted file.
 * - Files stored during a save that then fails can be discarded, so no orphans are left.
 *
 * One instance per save operation (it remembers what it stored).
 */
class QuestionImages
{
    public const DISK = 'public';

    public const DIRECTORY = 'questions';

    /** @var list<string> paths stored by this instance */
    private array $stored = [];

    /**
     * @param  File  $file  an UploadedFile from a form, or an Illuminate\Http\File (e.g. extracted from a ZIP)
     */
    public function store(File $file): string
    {
        $path = Storage::disk(self::DISK)->putFile(self::DIRECTORY, $file);
        $this->stored[] = $path;

        return $path;
    }

    /**
     * Delete once the surrounding transaction commits (immediately if there is none).
     */
    public function deleteAfterCommit(?string $path): void
    {
        if ($path === null) {
            return;
        }

        DB::afterCommit(fn () => Storage::disk(self::DISK)->delete($path));
    }

    /**
     * The save failed: remove every file this instance stored.
     */
    public function discardStored(): void
    {
        Storage::disk(self::DISK)->delete($this->stored);
        $this->stored = [];
    }

    public static function url(?string $path): ?string
    {
        return $path === null ? null : Storage::disk(self::DISK)->url($path);
    }
}
