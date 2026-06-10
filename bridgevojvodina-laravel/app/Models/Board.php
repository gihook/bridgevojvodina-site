<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_set_id',
        'board_number',
        'vulnerability',
        'cards_north',
        'cards_south',
        'cards_east',
        'cards_west',
        'double_dummy_analysis',
    ];

    protected $casts = [
        'cards_north' => 'array',
        'cards_south' => 'array',
        'cards_east' => 'array',
        'cards_west' => 'array',
        'double_dummy_analysis' => 'array',
    ];

    public function boardSet(): BelongsTo
    {
        return $this->belongsTo(BoardSet::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(BoardResult::class);
    }
}
