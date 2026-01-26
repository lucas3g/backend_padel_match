<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    use HasFactory;

    protected $fillable = [
        'clubd_id',
        'name',
        'description',
        'type',
        'covered',
        'images',
        'main_image_url',
        'rating',
        'total_reviews',
        'active'
    ];    

     public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
