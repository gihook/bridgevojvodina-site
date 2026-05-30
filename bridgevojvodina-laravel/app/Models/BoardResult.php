<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id',
        'north_player_id',
        'south_player_id',
        'east_player_id',
        'west_player_id',
        'contract',
        'declarer',
        'tricks',
        'score',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function northPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'north_player_id');
    }

    public function southPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'south_player_id');
    }

    public function eastPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'east_player_id');
    }

    public function westPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'west_player_id');
    }
}
