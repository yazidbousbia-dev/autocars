<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // GET /api/admin/stats — powers the admin dashboard homepage widgets
    public function stats()
    {
        return response()->json([
            'total_cars' => Car::count(),
            'approved_cars' => Car::where('status', 'approved')->count(),
            'sold_cars' => Car::where('status', 'sold')->count(),
            'expired_cars' => Car::where('status', 'expired')->count(),
            'hidden_cars' => Car::where('status', 'rejected')->count(),
            'total_users' => User::where('role', 'user')->count(),
            'pending_requests' => PurchaseRequest::where('status', 'pending')->count(),
            'total_requests' => PurchaseRequest::count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
            'featured_cars' => Car::where('is_featured', true)->count(),
            'cars_this_month' => Car::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'most_viewed_cars' => Car::approved()
                ->orderByDesc('views_count')
                ->limit(5)
                ->get(['id', 'model', 'views_count', 'brand_id']),
            'recent_requests' => PurchaseRequest::with(['car:id,model', 'user:id,name'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
        ]);
    }

    // GET /api/admin/users — list & search all users
    public function users(Request $request)
    {
        $users = User::when($request->role, fn ($q, $v) => $q->where('role', $v))
            ->when($request->search, function ($q, $v) {
                $q->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%");
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($users);
    }

    // PATCH /api/admin/users/{user}/ban — deactivate a user (soft "ban" via role flag)
    public function toggleBan(User $user)
    {
        $user->update(['is_verified' => ! $user->is_verified]);

        return response()->json(['message' => 'User status updated', 'user' => $user]);
    }

    // GET /api/admin/reports — list reported car listings
    public function reports(Request $request)
    {
        $reports = Report::with(['car:id,model,status', 'user:id,name,email'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($reports);
    }

    // PATCH /api/admin/reports/{report}/resolve
    public function resolveReport(Report $report)
    {
        $report->update(['status' => 'resolved']);

        return response()->json(['message' => 'Report resolved', 'report' => $report]);
    }
}
