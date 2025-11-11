<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\StatutHotel;
use App\Enums\Device;

class Hotel extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'nom',
        'adresse',
        'mail',
        'telephone',
        'prix_par_nuit',
        'device',
        'photo',
        'statut',
        'user_id'
    ];

    protected $casts = [
        'prix_par_nuit' => 'decimal:2',
        'statut' => StatutHotel::class,
        'device' => Device::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActif($query)
    {
        return $query->where('statut', StatutHotel::ACTIF->value);
    }

    public function scopeInactif($query)
    {
        return $query->where('statut', StatutHotel::INACTIF->value);
    }
}