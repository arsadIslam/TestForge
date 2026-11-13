<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Just the files. The CONTENT of the CSV is validated row by row by QuestionImportService.
 */
class QuestionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 'can:create,Question' on the controller
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'zip' => ['nullable', 'file', 'mimes:zip', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv.mimes' => 'Please upload a .csv file (in Excel: Save as → CSV UTF-8).',
            'csv.max' => 'The CSV can be at most 2 MB.',
            'zip.mimes' => 'Images must be uploaded as a single .zip file.',
            'zip.max' => 'The ZIP can be at most 20 MB.',
            // PHP rejected the upload before Laravel saw it (upload_max_filesize / post_max_size).
            'csv.uploaded' => 'The CSV could not be uploaded — it may be larger than the server allows.',
            'zip.uploaded' => 'The ZIP could not be uploaded — it is larger than the server allows (see upload_max_filesize).',
        ];
    }
}
