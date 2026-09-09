<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
    ];

    /**
     * The columns that belong to this board, ordered left to right.
     */
    public function columns(): HasMany
    {
        return $this->hasMany(TaskColumn::class)->orderBy('position');
    }
}
