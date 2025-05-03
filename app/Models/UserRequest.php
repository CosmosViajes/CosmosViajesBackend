<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
    protected $table = 'requests';
    protected $fillable = ['user_id', 'type', 'description', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
