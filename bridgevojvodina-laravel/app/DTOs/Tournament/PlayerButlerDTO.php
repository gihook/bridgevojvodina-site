<?php

namespace App\DTOs\Tournament;

class PlayerButlerDTO
{
    public function __construct(
        public int $player_id,
        public int $boards_played,
        public float $total_imps,
        public float $imps_per_board
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            player_id: $data['player_id'],
            boards_played: $data['boards_played'] ?? 0,
            total_imps: (float) ($data['total_imps'] ?? 0.0),
            imps_per_board: (float) ($data['imps_per_board'] ?? 0.0)
        );
    }

    public function toArray(): array
    {
        return [
            'player_id' => $this->player_id,
            'boards_played' => $this->boards_played,
            'total_imps' => $this->total_imps,
            'imps_per_board' => $this->imps_per_board,
        ];
    }
}
