<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CarController extends Controller
{
    /**
     * PUBLIC: list approved cars with filters + pagination.
     * GET /api/public/cars
     */
    public function index(Request $request)
    {
        $query = Car::approved()
            ->with(['brand', 'primaryImage'])
            ->filter($request->only([
                'brand_id', 'wilaya', 'fuel_type', 'transmission',
                'condition', 'price_min', 'price_max', 'year_min', 'year_max', 'search',
            ]))
            ->orderByDesc('is_featured'); // sponsored listings always float to top

        match ($request->get('sort')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'mileage_asc' => $query->orderBy('mileage'),
            'year_desc' => $query->orderByDesc('year'),
            default => $query->orderByDesc('created_at'), // 'newest' / default
        };

        $cars = $query->paginate($request->get('per_page', 12));

        return response()->json($cars);
    }

    /**
     * PUBLIC: car detail (increments view count).
     * GET /api/public/cars/{car}
     */
    public function show(Car $car)
    {
        if ($car->status !== 'approved' && (! auth('sanctum')->check() || (auth('sanctum')->user()->id !== $car->user_id && ! auth('sanctum')->user()->isAdmin()))) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $car->increment('views_count');
        $car->load(['brand', 'images', 'user:id,name,phone', 'dealer']);

        // "Similar cars" — same brand or similar price range, excludes current car
        $similar = Car::approved()
            ->where('id', '!=', $car->id)
            ->where(function ($q) use ($car) {
                $q->where('brand_id', $car->brand_id)
                    ->orWhereBetween('price', [$car->price * 0.8, $car->price * 1.2]);
            })
            ->with(['brand', 'primaryImage'])
            ->limit(4)
            ->get();

        return response()->json([
            ...$car->toArray(),
            'similar_cars' => $similar,
        ]);
    }

    /**
     * ADMIN ONLY: create a new car listing. This is a single-owner shop —
     * only the admin manages inventory, so every car is auto-approved.
     * POST /api/admin/cars
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|exists:brands,id',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1970|max:'.(date('Y') + 1),
            'price' => 'required|integer|min:0',
            'mileage' => 'required|integer|min:0',
            'fuel_type' => 'required|in:essence,diesel,hybride,electrique,gpl',
            'transmission' => 'required|in:manuelle,automatique',
            'condition' => 'required|in:neuve,occasion,accidentee',
            'wilaya' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'doors' => 'nullable|integer|min:2|max:6',
            'quantity' => 'nullable|integer|min:1|max:9999', // how many units of this car the seller has
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|max:5120', // 5MB per image
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        unset($data['images']);

        $data['user_id'] = $request->user()->id; // the admin/shop owner
        $data['quantity'] = $data['quantity'] ?? 1;
        $data['status'] = 'approved'; // admin-created listings go live immediately
        // listing stays live on the market for 10 days, then needs renewal (see renew())
        $data['expires_at'] = now()->addDays(10);

        $car = Car::create($data);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('cars', 'public');
                CarImage::create([
                    'car_id' => $car->id,
                    'image_url' => asset(Storage::url($path)), // absolute URL — frontend is on a different domain
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }

        return response()->json([
            'message' => 'Car listing created',
            'car' => $car->load('images', 'brand'),
        ], 201);
    }

    /**
     * ADMIN ONLY: edit/update an existing car.
     * PUT/PATCH /api/admin/cars/{car}
     */
    public function update(Request $request, Car $car)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'sometimes|exists:brands,id',
            'model' => 'sometimes|string|max:255',
            'year' => 'sometimes|integer|min:1970|max:'.(date('Y') + 1),
            'price' => 'sometimes|integer|min:0',
            'mileage' => 'sometimes|integer|min:0',
            'fuel_type' => 'sometimes|in:essence,diesel,hybride,electrique,gpl',
            'transmission' => 'sometimes|in:manuelle,automatique',
            'condition' => 'sometimes|in:neuve,occasion,accidentee',
            'wilaya' => 'sometimes|string|max:100',
            'city' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'doors' => 'nullable|integer|min:2|max:6',
            'quantity' => 'sometimes|integer|min:1|max:9999',
            'status' => 'sometimes|in:pending,approved,rejected,sold,expired',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $car->update($validator->validated());

        return response()->json([
            'message' => 'Car listing updated',
            'car' => $car->fresh(['brand', 'images']),
        ]);
    }

    /**
     * ADMIN ONLY: add more images to an existing car.
     * POST /api/admin/cars/{car}/images
     */
    public function addImages(Request $request, Car $car)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|max:10',
            'images.*' => 'image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $existingCount = $car->images()->count();

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('cars', 'public');
            CarImage::create([
                'car_id' => $car->id,
                'image_url' => asset(Storage::url($path)), // absolute URL — frontend is on a different domain
                'is_primary' => $existingCount === 0 && $index === 0,
                'sort_order' => $existingCount + $index,
            ]);
        }

        return response()->json([
            'message' => 'Images added',
            'car' => $car->fresh('images'),
        ]);
    }

    /**
     * ADMIN ONLY: delete a single image from a car.
     * DELETE /api/admin/cars/{car}/images/{image}
     */
    public function deleteImage(Request $request, Car $car, CarImage $image)
    {
        if ($image->car_id !== $car->id) {
            return response()->json(['message' => 'Image does not belong to this car'], 404);
        }

        $image->delete();

        return response()->json(['message' => 'Image deleted']);
    }

    /**
     * ADMIN ONLY: delete a car listing entirely.
     * DELETE /api/admin/cars/{car}
     */
    public function destroy(Request $request, Car $car)
    {
        $car->delete();

        return response()->json(['message' => 'Car listing deleted']);
    }

    /**
     * ADMIN ONLY: mark a car as sold.
     * PATCH /api/admin/cars/{car}/sold
     */
    public function markSold(Request $request, Car $car)
    {
        $car->update(['status' => 'sold', 'sold_count' => $car->quantity]);

        return response()->json(['message' => 'Marked as sold', 'car' => $car]);
    }

    /**
     * ADMIN ONLY: sell ONE unit out of the stock (when there are several of the same car).
     * Automatically flips the listing to "sold" once every unit is gone.
     * PATCH /api/admin/cars/{car}/sell-unit
     */
    public function sellUnit(Request $request, Car $car)
    {
        if ($car->available_quantity <= 0) {
            return response()->json(['message' => 'Aucune unité disponible à vendre'], 422);
        }

        $car->increment('sold_count');

        if ($car->sold_count >= $car->quantity) {
            $car->update(['status' => 'sold']);
        }

        return response()->json([
            'message' => 'Unité marquée comme vendue',
            'car' => $car->fresh(),
        ]);
    }

    /**
     * ADMIN ONLY: renew a listing for 10 more days on the market (also un-expires it).
     * PATCH /api/admin/cars/{car}/renew
     */
    public function renew(Request $request, Car $car)
    {
        if ($car->status === 'sold') {
            return response()->json(['message' => 'Cette annonce est déjà marquée comme vendue'], 422);
        }

        $car->update([
            'expires_at' => now()->addDays(10),
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'Annonce renouvelée pour 10 jours',
            'car' => $car->fresh(),
        ]);
    }

    // ================= ADMIN CONTROL PANEL ENDPOINTS =================

    /**
     * ADMIN: list ALL cars (any status), with filters — powers the admin control panel table.
     * GET /api/admin/cars
     */
    public function adminIndex(Request $request)
    {
        $cars = Car::with(['brand', 'user:id,name,email,phone', 'primaryImage'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->filter($request->only(['brand_id', 'wilaya', 'search']))
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json($cars);
    }

    /**
     * ADMIN: approve a pending car listing.
     * PATCH /api/admin/cars/{car}/approve
     */
    public function approve(Car $car)
    {
        $car->update(['status' => 'approved']);

        return response()->json(['message' => 'Car approved', 'car' => $car]);
    }

    /**
     * ADMIN: reject a pending car listing.
     * PATCH /api/admin/cars/{car}/reject
     */
    public function reject(Car $car)
    {
        $car->update(['status' => 'rejected']);

        return response()->json(['message' => 'Car rejected', 'car' => $car]);
    }

    /**
     * ADMIN: toggle "featured" (sponsored/highlighted) status for a car.
     * PATCH /api/admin/cars/{car}/feature
     */
    public function toggleFeatured(Car $car)
    {
        $car->update(['is_featured' => ! $car->is_featured]);

        return response()->json(['message' => 'Featured status updated', 'car' => $car]);
    }
}
