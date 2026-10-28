<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlateEntry;
use App\Models\ParkingRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display the Analytics & Cash Flow Reports dashboard.
     */
    public function index(Request $request)
    {
        $range = $request->query('range', '30days');
        $customFrom = $request->query('from');
        $customTo = $request->query('to');
        $reportType = $request->query('tab', 'daily'); // 'daily', 'weekly', 'monthly', 'transactions'

        $now = Carbon::now();
        $startDate = null;
        $endDate = $now->copy()->endOfDay();

        switch ($range) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $rangeLabel = 'Today (' . $now->format('M d, Y') . ')';
                break;
            case '7days':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $rangeLabel = 'Last 7 Days (' . $startDate->format('M d') . ' – ' . $endDate->format('M d, Y') . ')';
                break;
            case '30days':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $rangeLabel = 'Last 30 Days (' . $startDate->format('M d') . ' – ' . $endDate->format('M d, Y') . ')';
                break;
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $rangeLabel = 'This Month (' . $now->format('F Y') . ')';
                break;
            case 'custom':
                if ($customFrom) {
                    $startDate = Carbon::parse($customFrom)->startOfDay();
                }
                if ($customTo) {
                    $endDate = Carbon::parse($customTo)->endOfDay();
                }
                $rangeLabel = ($startDate ? $startDate->format('M d, Y') : 'Beginning') . ' — ' . $endDate->format('M d, Y');
                break;
            case 'all':
            default:
                $startDate = null;
                $rangeLabel = 'All Time History';
                break;
        }

        // Base query with date range
        $query = PlateEntry::query();
        if ($startDate) {
            $query->where('entry_time', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('entry_time', '<=', $endDate);
        }

        // ─── REPORT SUMMARY METRICS (Requirement 7) ───────────────────
        $totalVehicles = (clone $query)->count();
        $totalCompletedSessions = (clone $query)->where('status', 'exited')->count();
        $currentlyParked = PlateEntry::where('status', 'entered')->count();

        // Free sessions: exited with 15-min grace applied or fee == 0
        $totalFreeSessions = (clone $query)
            ->where('status', 'exited')
            ->where(function ($q) {
                $q->where('is_free_grace', true)
                  ->orWhere('parking_fee', '<=', 0)
                  ->orWhere('duration_minutes', '<=', 15);
            })
            ->count();

        $totalPaidSessions = max(0, $totalCompletedSessions - $totalFreeSessions);

        // Fees Assessed vs Actual Revenue Collected
        $totalFeesAssessed = (float)(clone $query)
            ->where('status', 'exited')
            ->sum('parking_fee');

        $totalRevenueCollected = (float)(clone $query)
            ->where('status', 'exited')
            ->where('payment_status', 'paid')
            ->sum('parking_fee');

        $totalUnpaidAssessed = (float)(clone $query)
            ->where('status', 'exited')
            ->where('payment_status', 'unpaid')
            ->sum('parking_fee');

        // ─── MEMBERSHIP REVENUE & PERFORMANCE (Requirement 9: Separate Ledger) ───
        $memberPaymentsQuery = \App\Models\MemberPayment::query();
        if ($startDate) {
            $memberPaymentsQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $memberPaymentsQuery->where('created_at', '<=', $endDate);
        }

        $totalMembershipRevenue = (float)(clone $memberPaymentsQuery)
            ->where('payment_status', 'paid')
            ->sum('amount');

        $activeMembersCount = \App\Models\Member::active()->count();
        $expiredMembersCount = \App\Models\Member::expired()->count();
        $membershipRenewalsCount = (clone $memberPaymentsQuery)
            ->where('payment_type', 'renewal')
            ->where('payment_status', 'paid')
            ->count();
        $membershipPaymentsCount = (clone $memberPaymentsQuery)
            ->where('payment_status', 'paid')
            ->count();
        $memberSessionsCount = (clone $query)
            ->where('is_member', true)
            ->count();
        $recentMemberPayments = (clone $memberPaymentsQuery)
            ->with('member')
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        $avgDurationMinutes = (float)(clone $query)
            ->where('status', 'exited')
            ->whereNotNull('duration_minutes')
            ->avg('duration_minutes');

        $avgConfidence = (float)(clone $query)->avg('entry_confidence');

        // Format duration
        $avgHours = intdiv((int)$avgDurationMinutes, 60);
        $avgMins = (int)$avgDurationMinutes % 60;
        $avgDurationFormatted = $avgHours > 0 ? "{$avgHours}h {$avgMins}m" : "{$avgMins}m";

        // Vehicle types breakdown
        $vehicleTypesData = (clone $query)
            ->select('vehicle_type', DB::raw('count(*) as count'))
            ->groupBy('vehicle_type')
            ->pluck('count', 'vehicle_type')
            ->toArray();

        $vehicleTypes = [
            'car' => $vehicleTypesData['car'] ?? 0,
            'motorcycle' => $vehicleTypesData['motorcycle'] ?? 0,
            'truck' => $vehicleTypesData['truck'] ?? 0,
            'van' => $vehicleTypesData['van'] ?? 0,
        ];

        // Gate distribution
        $gateEntryData = (clone $query)
            ->select('gate_entry', DB::raw('count(*) as count'))
            ->groupBy('gate_entry')
            ->pluck('count', 'gate_entry')
            ->toArray();

        $gateExitData = (clone $query)
            ->whereNotNull('gate_exit')
            ->select('gate_exit', DB::raw('count(*) as count'))
            ->groupBy('gate_exit')
            ->pluck('count', 'gate_exit')
            ->toArray();

        // ─── DAILY CASH FLOW REPORT ─────────────────────────────────
        $dailyCashFlow = (clone $query)
            ->select(
                DB::raw('DATE(entry_time) as report_date'),
                DB::raw('count(*) as total_vehicles'),
                DB::raw('SUM(CASE WHEN status = "exited" THEN 1 ELSE 0 END) as completed_sessions'),
                DB::raw('SUM(CASE WHEN status = "exited" AND (is_free_grace = 1 OR parking_fee <= 0 OR duration_minutes <= 15) THEN 1 ELSE 0 END) as free_sessions'),
                DB::raw('SUM(CASE WHEN status = "exited" AND (is_free_grace = 0 AND parking_fee > 0 AND (duration_minutes > 15 OR duration_minutes IS NULL)) THEN 1 ELSE 0 END) as paid_sessions'),
                DB::raw('SUM(CASE WHEN status = "exited" THEN parking_fee ELSE 0 END) as fees_assessed'),
                DB::raw('SUM(CASE WHEN status = "exited" AND payment_status = "paid" THEN parking_fee ELSE 0 END) as revenue_collected'),
                DB::raw('SUM(CASE WHEN status = "exited" AND payment_status = "unpaid" THEN parking_fee ELSE 0 END) as unpaid_fees'),
                DB::raw('AVG(CASE WHEN status = "exited" THEN duration_minutes ELSE NULL END) as avg_mins'),
                DB::raw('AVG(entry_confidence) as avg_conf')
            )
            ->whereNotNull('entry_time')
            ->groupBy(DB::raw('DATE(entry_time)'))
            ->orderBy('report_date', 'DESC')
            ->take(30)
            ->get();

        // ─── WEEKLY CASH FLOW REPORT ────────────────────────────────
        $weeklyCashFlow = (clone $query)
            ->select(
                DB::raw('YEARWEEK(entry_time, 1) as year_week'),
                DB::raw('MIN(DATE(entry_time)) as week_start'),
                DB::raw('MAX(DATE(entry_time)) as week_end'),
                DB::raw('count(*) as total_vehicles'),
                DB::raw('SUM(CASE WHEN status = "exited" THEN 1 ELSE 0 END) as completed_sessions'),
                DB::raw('SUM(CASE WHEN status = "exited" AND (is_free_grace = 1 OR parking_fee <= 0 OR duration_minutes <= 15) THEN 1 ELSE 0 END) as free_sessions'),
                DB::raw('SUM(CASE WHEN status = "exited" AND (is_free_grace = 0 AND parking_fee > 0 AND (duration_minutes > 15 OR duration_minutes IS NULL)) THEN 1 ELSE 0 END) as paid_sessions'),
                DB::raw('SUM(CASE WHEN status = "exited" THEN parking_fee ELSE 0 END) as fees_assessed'),
                DB::raw('SUM(CASE WHEN status = "exited" AND payment_status = "paid" THEN parking_fee ELSE 0 END) as revenue_collected'),
                DB::raw('SUM(CASE WHEN status = "exited" AND payment_status = "unpaid" THEN parking_fee ELSE 0 END) as unpaid_fees')
            )
            ->whereNotNull('entry_time')
            ->groupBy(DB::raw('YEARWEEK(entry_time, 1)'))
            ->orderBy('year_week', 'DESC')
            ->take(12)
            ->get();

        // ─── MONTHLY CASH FLOW REPORT ───────────────────────────────
        $monthlyCashFlow = (clone $query)
            ->select(
                DB::raw('DATE_FORMAT(entry_time, "%Y-%m") as month_key'),
                DB::raw('DATE_FORMAT(entry_time, "%M %Y") as month_label'),
                DB::raw('count(*) as total_vehicles'),
                DB::raw('SUM(CASE WHEN status = "exited" THEN 1 ELSE 0 END) as completed_sessions'),
                DB::raw('SUM(CASE WHEN status = "exited" AND (is_free_grace = 1 OR parking_fee <= 0 OR duration_minutes <= 15) THEN 1 ELSE 0 END) as free_sessions'),
                DB::raw('SUM(CASE WHEN status = "exited" AND (is_free_grace = 0 AND parking_fee > 0 AND (duration_minutes > 15 OR duration_minutes IS NULL)) THEN 1 ELSE 0 END) as paid_sessions'),
                DB::raw('SUM(CASE WHEN status = "exited" THEN parking_fee ELSE 0 END) as fees_assessed'),
                DB::raw('SUM(CASE WHEN status = "exited" AND payment_status = "paid" THEN parking_fee ELSE 0 END) as revenue_collected'),
                DB::raw('SUM(CASE WHEN status = "exited" AND payment_status = "unpaid" THEN parking_fee ELSE 0 END) as unpaid_fees')
            )
            ->whereNotNull('entry_time')
            ->groupBy(DB::raw('DATE_FORMAT(entry_time, "%Y-%m")'), DB::raw('DATE_FORMAT(entry_time, "%M %Y")'))
            ->orderBy('month_key', 'DESC')
            ->take(12)
            ->get();

        // ─── INDIVIDUAL TRANSACTION RECORDS ─────────────────────────
        $transactions = (clone $query)
            ->latest('entry_time')
            ->take(50)
            ->get();

        // Trend graph points
        $trendLabels = [];
        $trendEntries = [];
        $trendExits = [];
        $trendRevenue = [];
        $trendFree = [];

        // Reverse daily data for chronological chart
        $chronoDaily = $dailyCashFlow->reverse();
        foreach ($chronoDaily as $row) {
            $formattedDate = Carbon::parse($row->report_date)->format('M d');
            $trendLabels[] = $formattedDate;
            $trendEntries[] = (int)$row->total_vehicles;
            $trendExits[] = (int)$row->completed_sessions;
            $trendRevenue[] = round((float)$row->fees_assessed, 2);
            $trendFree[] = (int)$row->free_sessions;
        }

        $activeRate = ParkingRate::getActiveRate();

        return view('analytics.index', [
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'customFrom' => $customFrom,
            'customTo' => $customTo,
            'reportType' => $reportType,
            'totalVehicles' => $totalVehicles,
            'totalCompletedSessions' => $totalCompletedSessions,
            'totalFreeSessions' => $totalFreeSessions,
            'totalPaidSessions' => $totalPaidSessions,
            'currentlyParked' => $currentlyParked,
            'totalFeesAssessed' => $totalFeesAssessed,
            'totalRevenueCollected' => $totalRevenueCollected,
            'totalUnpaidAssessed' => $totalUnpaidAssessed,
            'totalMembershipRevenue' => $totalMembershipRevenue,
            'activeMembersCount' => $activeMembersCount,
            'expiredMembersCount' => $expiredMembersCount,
            'membershipRenewalsCount' => $membershipRenewalsCount,
            'membershipPaymentsCount' => $membershipPaymentsCount,
            'memberSessionsCount' => $memberSessionsCount,
            'recentMemberPayments' => $recentMemberPayments,
            'avgDurationFormatted' => $avgDurationFormatted,
            'avgConfidence' => round($avgConfidence, 1),
            'vehicleTypes' => $vehicleTypes,
            'gateEntryData' => $gateEntryData,
            'gateExitData' => $gateExitData,
            'dailyCashFlow' => $dailyCashFlow,
            'weeklyCashFlow' => $weeklyCashFlow,
            'monthlyCashFlow' => $monthlyCashFlow,
            'transactions' => $transactions,
            'trendLabels' => $trendLabels,
            'trendEntries' => $trendEntries,
            'trendExits' => $trendExits,
            'trendRevenue' => $trendRevenue,
            'trendFree' => $trendFree,
            'activeRate' => $activeRate,
        ]);
    }

    /**
     * Export detailed parking logs and cash flow reports to CSV.
     */
    public function exportCsv(Request $request)
    {
        $range = $request->query('range', '30days');
        $customFrom = $request->query('from');
        $customTo = $request->query('to');

        $now = Carbon::now();
        $startDate = null;
        $endDate = $now->copy()->endOfDay();

        switch ($range) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                break;
            case '7days':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                break;
            case '30days':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                break;
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                break;
            case 'custom':
                if ($customFrom) $startDate = Carbon::parse($customFrom)->startOfDay();
                if ($customTo) $endDate = Carbon::parse($customTo)->endOfDay();
                break;
            case 'all':
            default:
                $startDate = null;
                break;
        }

        $query = PlateEntry::query()->orderBy('entry_time', 'DESC');
        if ($startDate) {
            $query->where('entry_time', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('entry_time', '<=', $endDate);
        }

        $records = $query->get();

        $filename = 'parking_cash_flow_report_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'License Plate',
                'Vehicle Type',
                'Entry Time',
                'Exit Time',
                'Duration (Minutes)',
                '15-Min Free Grace Applied',
                'Gate Entry',
                'Gate Exit',
                'Status',
                'Parking Fee Assessed (PHP)',
                'Payment Status',
                'Revenue Collected (PHP)',
                'Entry Confidence (%)',
                'Exit Confidence (%)',
                'Rate Details',
                'Remarks'
            ]);

            foreach ($records as $row) {
                $isFree = $row->isFreeGrace();
                $fee = (float)($row->parking_fee ?? 0);
                $collected = ($row->payment_status === 'paid') ? $fee : 0.00;

                fputcsv($file, [
                    $row->id,
                    $row->plate_number,
                    ucfirst($row->vehicle_type ?? 'Car'),
                    $row->entry_time ? $row->entry_time->format('Y-m-d H:i:s') : 'N/A',
                    $row->exit_time ? $row->exit_time->format('Y-m-d H:i:s') : 'N/A',
                    $row->duration_minutes ?? 0,
                    $isFree ? 'YES (Free ≤ 15 mins)' : 'NO',
                    $row->gate_entry ?? 'Gate 1',
                    $row->gate_exit ?? 'N/A',
                    ucfirst($row->status ?? 'Unknown'),
                    number_format($fee, 2),
                    ucfirst($row->payment_status ?? 'Unpaid'),
                    number_format($collected, 2),
                    $row->entry_confidence ?? 0,
                    $row->exit_confidence ?? 0,
                    is_array($row->applied_rate_snapshot) ? json_encode($row->applied_rate_snapshot) : '',
                    $row->remarks ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
