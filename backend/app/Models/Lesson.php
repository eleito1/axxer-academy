<?php

namespace App\Models;

use App\Services\Videos\VideoStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'module_id',
        'title',
        'description',
        'video_url',
        'video_provider',
        'video_path',
        'video_original_name',
        'video_size',
        'video_extension',
        'video_duration',
        'video_uploaded_at',
        'duration',
        'support_material',
        'order',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'duration' => 'integer',
            'order' => 'integer',
            'video_size' => 'integer',
            'video_duration' => 'integer',
            'video_uploaded_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function videoUrl(): string
    {
        if ($this->video_provider === 'hostinger' && $this->video_path) {
            return app(VideoStorage::class)->url($this->video_path);
        }

        return $this->video_url;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }
}
