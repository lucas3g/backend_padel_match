<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'city',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'court_type',
        'surface_type'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

     public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
