<?php

namespace App\Models;

use App\Casts\TournamentResultsCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class RunningTournament extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'team_results',
        'tournament_id',
    ];

    protected $casts = [
        'team_results' => TournamentResultsCast::class,
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function boardSets(): HasMany
    {
        return $this->hasMany(BoardSet::class);
    }

    public function publishToTournament(): Tournament
    {
        return DB::transaction(function () {
            $tournament = $this->tournament;
            
            if (!$tournament) {
                $tournament = new Tournament();
                $tournament->user_id = auth()->id() ?? User::first()->id; 
            }

            $tournament->title = $this->title;
            $tournament->description = $this->title; 
            $tournament->details = ''; 
            $tournament->team_results = $this->team_results;
            $tournament->save();

            $this->tournament_id = $tournament->id;
            $this->save();
            $this->refresh();

            $tournament->boardSets()->each(function ($set) {
                $set->delete();
            });

            foreach ($this->boardSets as $boardSet) {
                $newBoardSet = $boardSet->replicate();
                $newBoardSet->running_tournament_id = null;
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
