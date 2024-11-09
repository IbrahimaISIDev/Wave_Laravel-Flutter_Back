<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Model
{
    use HasFactory;
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
        'adresse',
        'date_naissance',
        'secret',
        'role_id',
        'solde',
        'photo',
        'promo',
        'etatcarte',
        'code',
        'carte'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'etatcarte' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    
    public function cadeaux()
    {
        return $this->hasMany(Cadeau::class);
    }

    public function cagnotte()
    {
        return $this->hasMany(Cagnotte::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'exp');
    }

    /* public function role()
    {
        return $this->belongsTo(Role::class);
    } */
}
