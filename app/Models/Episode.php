<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $title
 * @property Part $parts
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * */
class Episode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title'];

    /**
     * @return HasMany
     */
    public function parts(): HasMany
    {
        return $this->hasMany(Part::class)->orderBy('position');
    }
}
