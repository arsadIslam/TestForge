<?php

namespace App\Models;

use App\Support\QuestionImages;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    use HasFactory;

    /**
     * The question_options table has no created_at/updated_at columns.
     */
    public $timestamps = false;

    protected $fillable = [
        'option_text',
        'is_correct',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Public URL of the image (or null). image_path itself is not mass-assignable:
     * only QuestionService sets it, after storing the file.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => QuestionImages::url($this->image_path));
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
