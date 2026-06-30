<?php

namespace App\DTOs\Tournament;

class TeamDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public int $captain_id,
        public array $player_ids,
        public float $total_vp = 0,
        public int $total_imp = 0,
        public ?int $number = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            captain_id: $data['captain_id'],
            player_ids: $data['player_ids'] ?? [],
            total_vp: (float) ($data['total_vp'] ?? 0),
            total_imp: (int) ($data['total_imp'] ?? 0),
            number: isset($data['number']) ? (int) $data['number'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'captain_id' => $this->captain_id,
            'player_ids' => $this->player_ids,
            'total_vp' => $this->total_vp,
            'total_imp' => $this->total_imp,
            'number' => $this->number,
        ];
    }
}
