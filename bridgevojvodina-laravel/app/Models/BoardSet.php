<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_configuration_id',
        'tournament_id',
        'name',
    ];

    public function tournamentConfiguration(): BelongsTo
    {
        return $this->belongsTo(TournamentConfiguration::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }
}
