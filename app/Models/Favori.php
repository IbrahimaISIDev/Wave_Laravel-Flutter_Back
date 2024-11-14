<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favori extends Model
{
    use HasFactory;

    protected $table = 'favoris';

    protected $fillable = [
        'user_id',
        'telephone',
        'favori_id',
        'nom_complet',
        'alias'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the favori
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function favoredUser()
    {
        return $this->belongsTo(User::class, 'favori_id');
    }
}