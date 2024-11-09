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
        'nom_complet'
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
}