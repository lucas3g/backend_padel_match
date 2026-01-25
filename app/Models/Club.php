<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'document', 'email', 'phone',
        'address', 'number', 'neighborhood', 'city',
        'state', 'zip_code', 'open_time', 'close_time', 'active'
    ];

    public function courts()
    {
        return $this->hasMany(Court::class);
    }
}
