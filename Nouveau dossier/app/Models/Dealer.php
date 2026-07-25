<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dealer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_name', 'logo', 'description',
        'address', 'wilaya', 'verified',
    ];

    protected function casts(): array
    {
        return ['verified' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
