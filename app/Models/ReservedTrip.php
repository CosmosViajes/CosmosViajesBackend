<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservedTrip extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'trip_id', 'quantity'];

    // Relación con el modelo User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con el modelo Trip
    public function trip()
    {
        return $this->belongsTo(SpaceTrip::class);
    }
}

