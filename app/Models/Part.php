<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $episode_id
 * @property int $position
 * @property Episode $episode
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Part extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['episode_id', 'title', 'position'];

    /**
     * @return BelongsTo
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
