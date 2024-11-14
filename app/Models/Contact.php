<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'user_id',
        'contact_id',
        'is_favori',
        'last_transaction'
    ];

    protected $casts = [
        'is_favori' => 'boolean',
        'last_transaction' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contact()
    {
        return $this->belongsTo(User::class, 'contact_id');
    }
}