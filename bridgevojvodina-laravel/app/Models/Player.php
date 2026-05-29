<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = ['first_name', 'last_name', 'club_id'];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
