<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'amount',
        'status',
        'metadata',
        'is_verified'
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'is_verified' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }
}