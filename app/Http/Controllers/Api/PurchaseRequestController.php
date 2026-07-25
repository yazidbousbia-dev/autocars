<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseRequestController extends Controller
{
    /**
     * CLIENT: send a "je veux acheter" request for a car.
     * POST /api/cars/{car}/request
     */
    public function store(Request $request, Car $car)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|max:30',
            'message' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($car->status === 'sold') {
            return response()->json(['message' => 'Cette voiture est déjà vendue'], 422);
        }

        $purchaseRequest = PurchaseRequest::create([
            'car_id' => $car->id,
            'user_id' => $request->user()->id,
            'phone' => $request->phone ?? $request->user()->phone,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Demande envoyée, l\'équipe va vous contacter bientôt',
            'data' => $purchaseRequest,
        ], 201);
    }

    /**
     * CLIENT: list own purchase requests (to track status).
     * GET /api/my-requests
     */
    public function myRequests(Request $request)
    {
        $requests = PurchaseRequest::where('user_id', $request->user()->id)
            ->with(['car:id,model,price,status', 'car.primaryImage'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($requests);
    }

    /**
     * ADMIN: list ALL purchase requests — powers the admin dashboard "Demandes" tab.
     * GET /api/admin/requests
     */
    public function adminIndex(Request $request)
    {
        $requests = PurchaseRequest::with(['car:id,model,price,status', 'user:id,name,phone,email'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json($requests);
    }

    /**
     * ADMIN: update the status of a purchase request (contacted / confirmed / cancelled).
     * PATCH /api/admin/requests/{purchaseRequest}/status
     */
    public function updateStatus(Request $request, PurchaseRequest $purchaseRequest)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,contacted,confirmed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $purchaseRequest->update(['status' => $request->status]);

        // if the deal is confirmed, mark the car as sold automatically
        if ($request->status === 'confirmed') {
            $purchaseRequest->car->update(['status' => 'sold', 'sold_count' => $purchaseRequest->car->quantity]);
        }

        return response()->json([
            'message' => 'Statut de la demande mis à jour',
            'data' => $purchaseRequest->fresh(['car', 'user:id,name,phone,email']),
        ]);
    }
}
