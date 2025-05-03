<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\UserRole;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_company' => $this->is_company,
            'is_provider' => $this->is_provider,
            'photo' => $this->photo,
            'role' => $this->role
        ];
    }
    
    protected $fillable = [
        'name', 'email', 'password', 'role', 'photo'
    ];

    protected $hidden = ['password', 'remember_token'];

    public function spaceTrips(): HasMany
    {
        return $this->hasMany(SpaceTrip::class, 'company_id');
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => UserRole::class
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

}
