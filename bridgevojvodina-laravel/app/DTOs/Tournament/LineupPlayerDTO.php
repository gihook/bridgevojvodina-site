<?php

namespace App\DTOs\Tournament;

use App\Models\Player;

class LineupPlayerDTO
{
    public function __construct(
        public int $player_id,
        public int $boards_played,
        public float $butler_score,
        public ?Player $player = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            player_id: $data['player_id'],
            boards_played: $data['boards_played'] ?? 0,
            butler_score: (float) ($data['butler_score'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'player_id' => $this->player_id,
            'boards_played' => $this->boards_played,
            'butler_score' => $this->butler_score,
        ];
    }
}
