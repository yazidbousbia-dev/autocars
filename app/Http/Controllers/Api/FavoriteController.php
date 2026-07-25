<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    // AUTH: list logged-in user's favorite cars
    public function index(Request $request)
    {
        $favorites = Favorite::where('user_id', $request->user()->id)
            ->with(['car.brand', 'car.primaryImage'])
            ->orderByDesc('created_at')
            ->paginate(12);

        return response()->json($favorites);
    }

    // AUTH: toggle favorite on/off for a given car
    public function toggle(Request $request, $carId)
    {
        $existing = Favorite::where('user_id', $request->user()->id)
            ->where('car_id', $carId)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['message' => 'Removed from favorites', 'favorited' => false]);
        }

        Favorite::create([
            'user_id' => $request->user()->id,
            'car_id' => $carId,
        ]);

        return response()->json(['message' => 'Added to favorites', 'favorited' => true]);
    }
}
