<?php

namespace App\Models;

use App\Casts\TournamentResultsCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'description',
        'details',
        'team_results',
        'user_id',
    ];

    protected $casts = [
        'team_results' => TournamentResultsCast::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boardSets(): HasMany
    {
        return $this->hasMany(BoardSet::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class);
    }
}
