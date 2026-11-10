<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    /**
     * subjects → topics → questions, without a subject_id column on questions.
     */
    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(Question::class, Topic::class);
    }
}
