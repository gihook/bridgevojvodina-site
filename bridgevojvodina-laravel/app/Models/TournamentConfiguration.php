<?php

namespace App\Models;

use App\Casts\TournamentResultsCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class TournamentConfiguration extends Model
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
        return $this->hasMany(BoardSet::class, 'tournament_configuration_id');
    }

    public function publishToTournament(): Tournament
    {
        return DB::transaction(function () {
            // Find existing tournament with same ID or create new one with matching ID
            $tournament = Tournament::find($this->id);
            
            if (!$tournament) {
                $tournament = new Tournament();
                $tournament->id = $this->id; // Enforce identical UUID
                $tournament->user_id = $this->user_id ?? auth()->id() ?? User::first()->id; 
            }

            $tournament->title = $this->title;
            $tournament->description = $this->description ?? $this->title; 
            $tournament->details = $this->details ?? ''; 
            
            // Calculate Butler per player and recalculate standings before publishing
            if ($this->team_results) {
                $hydrationService = app(\App\Services\TournamentHydrationService::class);
                $hydrationService->recalculateStandings($this->team_results);
                $this->team_results->player_butlers = $hydrationService->calculatePlayerButlers($this->team_results);
            }

            $tournament->team_results = $this->team_results;
            $tournament->save();

            // Refresh to ensure we have any defaults
            $this->refresh();

            // Clear existing board sets on the published tournament
            $tournament->boardSets()->each(function ($set) {
                $set->delete();
            });

            // Copy all board sets, boards, and results
            foreach ($this->boardSets as $boardSet) {
                $newBoardSet = $boardSet->replicate();
                $newBoardSet->tournament_configuration_id = null; // Unlink from config
                $newBoardSet->tournament_id = $tournament->id;
                $newBoardSet->save();

                foreach ($boardSet->boards as $board) {
                    $newBoard = $board->replicate();
                    $newBoard->board_set_id = $newBoardSet->id;
                    $newBoard->save();

                    foreach ($board->results as $result) {
                        $newResult = $result->replicate();
                        $newResult->board_id = $newBoard->id;
                        $newResult->save();
                    }
                }
            }

            // Sync players
            $playerIds = [];
            if ($this->team_results && !empty($this->team_results->teams)) {
                foreach ($this->team_results->teams as $team) {
                    $playerIds = array_merge($playerIds, $team->player_ids);
                }
            }
            $tournament->players()->sync(array_unique($playerIds));

            return $tournament;
        });
    }
}
