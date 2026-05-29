<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['title', 'description', 'date', 'club_id'];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
