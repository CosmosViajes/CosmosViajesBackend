<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationHistory extends Model
{
    protected $fillable = ['user_id', 'trip_id', 'quantity'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(SpaceTrip::class);
    }
}