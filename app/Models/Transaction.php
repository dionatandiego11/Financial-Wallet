<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'related_user_id',
        'original_transaction_id',
        'description',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function relatedUser()
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    public function originalTransaction()
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }

    public function reversalTransaction()
    {
        return $this->hasOne(Transaction::class, 'original_transaction_id');
    }
}