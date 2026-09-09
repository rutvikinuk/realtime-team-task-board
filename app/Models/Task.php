<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_column_id',
        'title',
        'description',
        'position',
    ];

    /**
     * The column this task currently sits in.
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(TaskColumn::class, 'task_column_id');
    }
}
