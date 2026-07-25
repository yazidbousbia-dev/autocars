<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DealerController extends Controller
{
    // PUBLIC: list verified dealers (showcase page)
    public function index()
    {
        $dealers = Dealer::where('verified', true)
            ->withCount('cars')
            ->orderBy('company_name')
            ->paginate(12);

        return response()->json($dealers);
    }

    // PUBLIC: dealer profile + their approved cars
    public function show(Dealer $dealer)
    {
        $dealer->load(['cars' => fn ($q) => $q->approved()->with('brand', 'primaryImage')]);

        return response()->json($dealer);
    }

    // AUTH: a regular user requests to become a dealer
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'wilaya' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if ($user->dealer) {
            return response()->json(['message' => 'You already have a dealer profile'], 422);
        }

        $dealer = Dealer::create([
            ...$validator->validated(),
            'user_id' => $user->id,
            'verified' => false, // admin must verify
        ]);

        $user->update(['role' => 'dealer']);

        return response()->json(['message' => 'Dealer application submitted, pending verification', 'dealer' => $dealer], 201);
    }

    // ADMIN: list all dealer applications/profiles (for control panel)
    public function adminIndex(Request $request)
    {
        $dealers = Dealer::with('user:id,name,email,phone')
            ->when($request->has('verified'), fn ($q) => $q->where('verified', $request->boolean('verified')))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($dealers);
    }

    // ADMIN: verify a dealer
    public function verify(Dealer $dealer)
    {
        $dealer->update(['verified' => true]);

        return response()->json(['message' => 'Dealer verified', 'dealer' => $dealer]);
    }

    // ADMIN: revoke dealer verification
    public function unverify(Dealer $dealer)
    {
        $dealer->update(['verified' => false]);

        return response()->json(['message' => 'Dealer verification revoked', 'dealer' => $dealer]);
    }
}
