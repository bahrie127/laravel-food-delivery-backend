<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TotalCounterController  extends Controller
{
    public function getTotalProduct(Request $request)
    {
        $user = $request->user();

        if ($user->roles !== 'restaurant') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $totalProducts = $user->products()->count();

        return response()->json([
            'status' => 'success',
            'message' => 'Total product count retrieved successfully',
            'data' => $totalProducts,
        ]);
    }

    public function getTotalOrder(Request $request) {
        $user = $request->user();

        if ($user->roles !== 'restaurant') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $totalOrders = Order::where('restaurant_id', $user->id)->count();

        return response()->json([
            'status' => 'success',
            'message' => 'Total order count retrieved successfully',
            'data' => $totalOrders,
        ]);
    }

    public function getTodayTransaction(Request $request) {
        $user = $request->user();

        if ($user->roles !== 'restaurant') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $today = Carbon::today();

        $totalTransactions = Payment::whereHas('order', function ($query) use ($user) {
            $query->where('restaurant_id', $user->id);
        })
        ->whereDate('created_at', $today)
        ->count();

        return response()->json([
            'status' => 'success',
            'message' => 'Total number of transactions for today retrieved successfully',
            'data' => $totalTransactions,
        ]);
    }

    public function getTotalIncome(Request $request) {
        $user = $request->user();

        if ($user->roles !== 'restaurant') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $totalIncome = Payment::whereHas('order', function ($query) use ($user) {
            $query->where('restaurant_id', $user->id);
        })
        ->sum('amount');

        return response()->json([
            'status' => 'success',
            'message' => 'Total income retrieved successfully',
            'data' => (int)$totalIncome,
        ]);
    }
}
