<?php

namespace App\Casts;

use App\DTOs\Tournament\TournamentResultsDTO;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class TournamentResultsCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?TournamentResultsDTO
    {
        if (!$value) {
            return null;
        }

        $data = json_decode($value, true);
        return $data ? TournamentResultsDTO::fromArray($data) : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof TournamentResultsDTO) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
