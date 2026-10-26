<?php

namespace App\Http\Controllers;

use App\Models\ParkingRate;
use App\Models\ParkingRateAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParkingFeeController extends Controller
{
    /**
     * Display the parking fee management view.
     */
    public function index()
    {
        $activeRate = ParkingRate::getActiveRate();
        $audits = ParkingRateAudit::latest('created_at')->take(30)->get();

        return view('settings.parking-rates', compact('activeRate', 'audits'));
    }

    /**
     * Return active rates as JSON for modals or AJAX clients.
     */
    public function getRatesJson()
    {
        $activeRate = ParkingRate::getActiveRate();
        $audits = ParkingRateAudit::latest('created_at')->take(10)->get();

        return response()->json([
            'success' => true,
            'rates' => $activeRate,
            'audits' => $audits,
        ]);
    }

    /**
     * Update parking rates (Authorized administrators only).
     */
    public function update(Request $request)
    {
        // 1. Role-based access control: Only authorized administrators
        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && !$user->isAdmin()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized. Only authorized administrators can change parking rates.',
                ], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized. Only authorized administrators can change parking rates.');
        }

        // 2. Strict Input Validation (Reject empty, negative, non-numeric values)
        $validated = $request->validate([
            'base_rate' => 'required|numeric|min:0|max:10000',
            'base_hours' => 'required|integer|min:1|max:24',
            'hourly_rate' => 'required|numeric|min:0|max:10000',
            'grace_period_minutes' => 'nullable|integer|min:15|max:120',
            'motorcycle_base_rate' => 'nullable|numeric|min:0|max:10000',
            'motorcycle_hourly_rate' => 'nullable|numeric|min:0|max:10000',
            'truck_base_rate' => 'nullable|numeric|min:0|max:10000',
            'truck_hourly_rate' => 'nullable|numeric|min:0|max:10000',
            'lost_ticket_fee' => 'nullable|numeric|min:0|max:10000',
            'overnight_fee' => 'nullable|numeric|min:0|max:10000',
            'change_reason' => 'nullable|string|max:255',
        ], [
            'base_rate.required' => 'Initial base parking fee is required.',
            'base_rate.numeric' => 'Base parking fee must be a valid numeric amount.',
            'base_rate.min' => 'Base parking fee cannot be negative.',
            'base_hours.required' => 'Base hours coverage is required.',
            'base_hours.min' => 'Base hours must be at least 1 hour.',
            'hourly_rate.required' => 'Succeeding hourly rate is required.',
            'hourly_rate.numeric' => 'Succeeding hourly rate must be a valid numeric amount.',
            'hourly_rate.min' => 'Hourly rate cannot be negative.',
            'grace_period_minutes.min' => 'The mandatory free grace period must be at least 15 minutes.',
        ]);

        try {
            DB::beginTransaction();

            $activeRate = ParkingRate::getActiveRate();
            $previousSnapshot = $activeRate->toArray();

            // Mandatory 15-minute free parking rule cannot be bypassed
            $graceMinutes = max(15, (int)($request->input('grace_period_minutes', 15)));

            $activeRate->update([
                'base_rate' => round($validated['base_rate'], 2),
                'base_hours' => (int)$validated['base_hours'],
                'hourly_rate' => round($validated['hourly_rate'], 2),
                'grace_period_minutes' => $graceMinutes,
                'motorcycle_base_rate' => isset($validated['motorcycle_base_rate']) ? round($validated['motorcycle_base_rate'], 2) : $activeRate->motorcycle_base_rate,
                'motorcycle_hourly_rate' => isset($validated['motorcycle_hourly_rate']) ? round($validated['motorcycle_hourly_rate'], 2) : $activeRate->motorcycle_hourly_rate,
                'truck_base_rate' => isset($validated['truck_base_rate']) ? round($validated['truck_base_rate'], 2) : $activeRate->truck_base_rate,
                'truck_hourly_rate' => isset($validated['truck_hourly_rate']) ? round($validated['truck_hourly_rate'], 2) : $activeRate->truck_hourly_rate,
                'lost_ticket_fee' => isset($validated['lost_ticket_fee']) ? round($validated['lost_ticket_fee'], 2) : $activeRate->lost_ticket_fee,
                'overnight_fee' => isset($validated['overnight_fee']) ? round($validated['overnight_fee'], 2) : $activeRate->overnight_fee,
                'updated_by' => $user?->id,
            ]);

            $newSnapshot = $activeRate->fresh()->toArray();

            // Record audit trail of fee change
            ParkingRateAudit::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Administrator',
                'action' => 'UPDATE_PARKING_RATES',
                'previous_rates' => $previousSnapshot,
                'new_rates' => $newSnapshot,
                'reason' => $request->input('change_reason') ?: 'Administrator rate configuration update',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            $successMsg = 'Parking rates have been updated successfully.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'rates' => $activeRate,
                ]);
            }

            return redirect()->back()->with('success', $successMsg);

        } catch (\Exception $e) {
            DB::rollBack();

            $errorMsg = 'Failed to update parking rates: ' . $e->getMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMsg,
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', $errorMsg);
        }
    }
}
