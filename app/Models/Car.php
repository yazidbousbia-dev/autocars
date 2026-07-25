<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'dealer_id', 'brand_id', 'model', 'year', 'price',
        'mileage', 'fuel_type', 'transmission', 'condition',
        'wilaya', 'city', 'description', 'color', 'doors',
        'status', 'is_featured', 'views_count',
        'quantity', 'sold_count', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'price' => 'integer',
            'mileage' => 'integer',
            'views_count' => 'integer',
            'quantity' => 'integer',
            'sold_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    // How many units are still available to sell
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->sold_count);
    }

    // How many days are left before this listing auto-expires from the market
    public function getDaysLeftAttribute(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false) >= 0 ? (int) now()->diffInDays($this->expires_at) : 0);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function images()
    {
        return $this->hasMany(CarImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(CarImage::class)->where('is_primary', true);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    // ---- Query scopes for filtering ----
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')
            // lazy safety net: hide expired listings from the public market even if
            // the daily cars:expire command hasn't run yet since expiry passed
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['brand_id'] ?? null, fn ($q, $v) => $q->where('brand_id', $v))
            ->when($filters['wilaya'] ?? null, fn ($q, $v) => $q->where('wilaya', $v))
            ->when($filters['fuel_type'] ?? null, fn ($q, $v) => $q->where('fuel_type', $v))
            ->when($filters['transmission'] ?? null, fn ($q, $v) => $q->where('transmission', $v))
            ->when($filters['condition'] ?? null, fn ($q, $v) => $q->where('condition', $v))
            ->when($filters['price_min'] ?? null, fn ($q, $v) => $q->where('price', '>=', $v))
            ->when($filters['price_max'] ?? null, fn ($q, $v) => $q->where('price', '<=', $v))
            ->when($filters['year_min'] ?? null, fn ($q, $v) => $q->where('year', '>=', $v))
            ->when($filters['year_max'] ?? null, fn ($q, $v) => $q->where('year', '<=', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('model', 'like', "%{$v}%")
                        ->orWhere('description', 'like', "%{$v}%")
                        ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$v}%"));
                });
            });
    }
}
