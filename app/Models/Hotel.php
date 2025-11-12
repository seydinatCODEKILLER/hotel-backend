<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\StatutHotel;
use App\Enums\Device;

/**
 * @OA\Schema(
 *     schema="Hotel",
 *     title="Hotel",
 *     description="Schéma d'un hôtel",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Hôtel Paradise"),
 *     @OA\Property(property="adresse", type="string", example="123 Avenue des Champs-Élysées"),
 *     @OA\Property(property="mail", type="string", format="email", example="contact@hotelparadise.com"),
 *     @OA\Property(property="telephone", type="string", example="+33123456789"),
 *     @OA\Property(property="prix_par_nuit", type="number", format="float", example=150.00),
 *     @OA\Property(property="device", type="string", example="EURO"),
 *     @OA\Property(property="statut", type="string", example="actif"),
 *     @OA\Property(property="photo", type="string", format="url", example="https://example.com/photo.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
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