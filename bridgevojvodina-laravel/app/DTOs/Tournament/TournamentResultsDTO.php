<?php

namespace App\DTOs\Tournament;

class TournamentResultsDTO
{
    /**
     * @param TeamDTO[] $teams
     * @param RoundDTO[] $rounds
     * @param PlayerButlerDTO[] $player_butlers
     */
    public function __construct(
        public array $teams = [],
        public array $rounds = [],
        public float $bye_vp = 12.0,
        public int $boards_per_round = 16,
        public array $player_butlers = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            teams: array_map(fn($t) => TeamDTO::fromArray($t), $data['teams'] ?? []),
            rounds: array_map(fn($r) => RoundDTO::fromArray($r), $data['rounds'] ?? []),
            bye_vp: (float) ($data['bye_vp'] ?? 12.0),
            boards_per_round: (int) ($data['boards_per_round'] ?? 16),
            player_butlers: array_map(fn($pb) => PlayerButlerDTO::fromArray($pb), $data['player_butlers'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'teams' => array_map(fn($t) => $t->toArray(), $this->teams),
            'rounds' => array_map(fn($r) => $r->toArray(), $this->rounds),
            'bye_vp' => $this->bye_vp,
            'boards_per_round' => $this->boards_per_round,
            'player_butlers' => array_map(fn($pb) => $pb->toArray(), $this->player_butlers),
        ];
    }
}
