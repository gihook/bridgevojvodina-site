<?php

namespace App\DTOs\Tournament;

class MatchBoardDTO
{
    public function __construct(
        public int $board_number,
        public ?string $home_contract = null,
        public ?string $home_declarer = null,
        public ?int $home_tricks = null,
        public ?int $home_score = null,
        public ?string $away_contract = null,
        public ?string $away_declarer = null,
        public ?int $away_tricks = null,
        public ?int $away_score = null,
        public int $home_imp = 0,
        public int $away_imp = 0
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            board_number: $data['board_number'],
            home_contract: $data['home_contract'] ?? null,
            home_declarer: $data['home_declarer'] ?? null,
            home_tricks: $data['home_tricks'] ?? null,
            home_score: $data['home_score'] ?? null,
            away_contract: $data['away_contract'] ?? null,
            away_declarer: $data['away_declarer'] ?? null,
            away_tricks: $data['away_tricks'] ?? null,
            away_score: $data['away_score'] ?? null,
            home_imp: $data['home_imp'] ?? 0,
            away_imp: $data['away_imp'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'board_number' => $this->board_number,
            'home_contract' => $this->home_contract,
            'home_declarer' => $this->home_declarer,
            'home_tricks' => $this->home_tricks,
            'home_score' => $this->home_score,
            'away_contract' => $this->away_contract,
            'away_declarer' => $this->away_declarer,
            'away_tricks' => $this->away_tricks,
            'away_score' => $this->away_score,
            'home_imp' => $this->home_imp,
            'away_imp' => $this->away_imp,
        ];
    }
}
