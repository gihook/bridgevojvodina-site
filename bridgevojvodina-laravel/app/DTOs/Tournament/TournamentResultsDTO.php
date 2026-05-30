<?php

namespace App\DTOs\Tournament;

class TournamentResultsDTO
{
    /**
     * @param TeamDTO[] $teams
     * @param RoundDTO[] $rounds
     */
    public function __construct(
        public array $teams = [],
        public array $rounds = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            teams: array_map(fn($t) => TeamDTO::fromArray($t), $data['teams'] ?? []),
            rounds: array_map(fn($r) => RoundDTO::fromArray($r), $data['rounds'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'teams' => array_map(fn($t) => $t->toArray(), $this->teams),
            'rounds' => array_map(fn($r) => $r->toArray(), $this->rounds),
        ];
    }
}
