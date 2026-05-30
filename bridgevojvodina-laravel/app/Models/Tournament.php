<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'details', 'user_id'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
