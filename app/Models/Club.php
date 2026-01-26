<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'description', 
        'document', 
        'email', 
        'phone',
        'whatsapp',
        'address', 
        'city',
        'state',
        'neighborhood', 
        'zip_code',
        'number', 
        'latitude',
        'longitude',
        'open_time', 
        'close_time', 
        'active'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function courts()
    {
        return $this->hasMany(Court::class);
    }
}
