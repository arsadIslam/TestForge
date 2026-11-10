<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Subjects A–Z with their topics A–Z eager loaded (2 queries) — for grouped topic dropdowns.
     */
    public function scopeWithSortedTopics(Builder $query): void
    {
        $query->with(['topics' => fn (HasMany $topics) => $topics->orderBy('name')])->orderBy('name');
    }

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
