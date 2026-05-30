<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    const STATUS_ACTIVE = 'Active';
    const STATUS_INACTIVE = 'Inactive';

    protected $fillable = [
        'name', 'city', 'address', 'representative', 'email', 'phone', 'status', 'link'
    ];

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
