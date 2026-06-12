<?php

namespace App\DTOs\Tournament;

class RoundDTO
{
    /**
     * @param MatchDTO[] $matches
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?int $board_set_id = null,
        public array $matches = [],
        public string $status = 'idle',
        public ?int $boards_per_round = null,
        public bool $exclude_from_butler = false
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            board_set_id: $data['board_set_id'] ?? null,
            matches: array_map(fn($m) => MatchDTO::fromArray($m), $data['matches'] ?? []),
            status: $data['status'] ?? 'idle',
            boards_per_round: isset($data['boards_per_round']) ? (int) $data['boards_per_round'] : null,
            exclude_from_butler: (bool) ($data['exclude_from_butler'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'board_set_id' => $this->board_set_id,
            'matches' => array_map(fn($m) => $m->toArray(), $this->matches),
            'status' => $this->status,
            'boards_per_round' => $this->boards_per_round,
            'exclude_from_butler' => $this->exclude_from_butler,
        ];
    }
}
