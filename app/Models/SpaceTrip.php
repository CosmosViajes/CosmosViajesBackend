<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpaceTrip extends Model {

    protected $fillable = [
        'name',
        'company_id',
        'type',
        'photo',
        'departure',
        'duration',
        'capacity',
        'price',
        'description',
    ];

    /**
     * Relación con el modelo User (empresa que creó el viaje).
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }
}
