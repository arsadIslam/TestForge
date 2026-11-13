<?php

namespace App\Support;

/**
 * The result of reading and validating an uploaded question CSV (QuestionImportService).
 *
 * Every row carries the form-shaped `data` that QuestionService::create() expects (image
 * columns still hold ZIP file names), a short `summary` for the preview table, and its errors.
 */
final class ImportPreview
{
    /**
     * @param  list<array{number: int, data: array<string, mixed>, summary: array<string, mixed>, errors: list<string>}>  $rows
     * @param  list<string>  $fileErrors  problems with the file as a whole (headers, size, encoding, ZIP)
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $fileErrors = [],
    ) {}

    public function isValid(): bool
    {
        return $this->fileErrors === [] && $this->rows !== [] && $this->invalidCount() === 0;
    }

    public function invalidCount(): int
    {
        return count(array_filter($this->rows, fn (array $row): bool => $row['errors'] !== []));
    }

    public function validCount(): int
    {
        return count($this->rows) - $this->invalidCount();
    }
}
