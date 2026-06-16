<?php

namespace App\DTOs\Tournament;

class MatchDTO
{
    /**
     * @param LineupPlayerDTO[] $home_lineup
     * @param LineupPlayerDTO[] $away_lineup
     * @param MatchBoardDTO[] $boards
     */
    public function __construct(
        public string $id,
        public ?string $home_team_id,
        public ?string $away_team_id,
        public int $home_imp,
        public int $away_imp,
        public float $home_vp,
        public float $away_vp,
        public string $status = 'pending',
        public ?int $boards_count = null,
        public array $home_lineup = [],
        public array $away_lineup = [],
        public array $boards = [],
        public array $open_ns_ids = [],
        public array $open_ew_ids = [],
        public array $closed_ns_ids = [],
        public array $closed_ew_ids = [],
        public array $open_ns = [],
        public array $open_ew = [],
        public array $closed_ns = [],
        public array $closed_ew = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            home_team_id: $data['home_team_id'],
            away_team_id: $data['away_team_id'] ?? null,
            home_imp: $data['home_imp'] ?? 0,
            away_imp: $data['away_imp'] ?? 0,
            home_vp: (float) ($data['home_vp'] ?? 0),
            away_vp: (float) ($data['away_vp'] ?? 0),
            status: $data['status'] ?? 'pending',
            boards_count: isset($data['boards_count']) ? (int) $data['boards_count'] : null,
            home_lineup: array_map(fn($l) => LineupPlayerDTO::fromArray($l), $data['home_lineup'] ?? []),
            away_lineup: array_map(fn($l) => LineupPlayerDTO::fromArray($l), $data['away_lineup'] ?? []),
            boards: array_map(fn($b) => MatchBoardDTO::fromArray($b), $data['boards'] ?? []),
            open_ns_ids: $data['open_ns_ids'] ?? [],
            open_ew_ids: $data['open_ew_ids'] ?? [],
            closed_ns_ids: $data['closed_ns_ids'] ?? [],
            closed_ew_ids: $data['closed_ew_ids'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'home_team_id' => $this->home_team_id,
            'away_team_id' => $this->away_team_id,
            'home_imp' => $this->home_imp,
            'away_imp' => $this->away_imp,
            'home_vp' => $this->home_vp,
            'away_vp' => $this->away_vp,
            'status' => $this->status,
            'boards_count' => $this->boards_count,
            'home_lineup' => array_map(fn($l) => $l->toArray(), $this->home_lineup),
            'away_lineup' => array_map(fn($l) => $l->toArray(), $this->away_lineup),
            'boards' => array_map(fn($b) => $b->toArray(), $this->boards),
            'open_ns_ids' => $this->open_ns_ids,
            'open_ew_ids' => $this->open_ew_ids,
            'closed_ns_ids' => $this->closed_ns_ids,
            'closed_ew_ids' => $this->closed_ew_ids,
        ];
    }
}
