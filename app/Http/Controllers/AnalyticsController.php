<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlateEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display the Analytics & Reports dashboard.
     */
    public function index(Request $request)
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
                $rangeLabel = 'Today (' . $now->format('M d, Y') . ')';
                break;
            case '7days':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $rangeLabel = 'Last 7 Days';
                break;
            case '30days':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $rangeLabel = 'Last 30 Days';
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
                $rangeLabel = ($startDate ? $startDate->format('M d, Y') : 'Start') . ' — ' . $endDate->format('M d, Y');
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

        // KPI Metrics
        $totalEntries = (clone $query)->count();
        $totalExits = (clone $query)->where('status', 'exited')->count();
        $currentlyParked = PlateEntry::where('status', 'entered')->count(); // Current live occupancy
        $totalRevenue = (float)(clone $query)->where('status', 'exited')->sum('parking_fee');
        $paidRevenue = (float)(clone $query)->where('status', 'exited')->where('payment_status', 'paid')->sum('parking_fee');
        $unpaidRevenue = (float)(clone $query)->where('status', 'exited')->where('payment_status', 'unpaid')->sum('parking_fee');
        
        $avgDurationMinutes = (float)(clone $query)->where('status', 'exited')->whereNotNull('duration_minutes')->avg('duration_minutes');
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

        // Hourly traffic distribution (0-23 hours)
        $hourlyTraffic = array_fill(0, 24, 0);
        $hourlyEntries = (clone $query)
            ->select(DB::raw('HOUR(entry_time) as hour'), DB::raw('count(*) as count'))
            ->whereNotNull('entry_time')
            ->groupBy(DB::raw('HOUR(entry_time)'))
            ->get();

        foreach ($hourlyEntries as $row) {
            $h = (int)$row->hour;
            if ($h >= 0 && $h < 24) {
                $hourlyTraffic[$h] = (int)$row->count;
            }
        }

        // Peak hour determination
        $peakHourVal = 0;
        $peakHourCount = 0;
        foreach ($hourlyTraffic as $h => $c) {
            if ($c > $peakHourCount) {
                $peakHourCount = $c;
                $peakHourVal = $h;
            }
        }
        $peakHourLabel = Carbon::createFromTime($peakHourVal, 0)->format('g A') . ' - ' . Carbon::createFromTime($peakHourVal + 1, 0)->format('g A');

        // Daily traffic trend (last 14 days or in range)
        $trendQuery = (clone $query)
            ->select(
                DB::raw('DATE(entry_time) as date'),
                DB::raw('count(*) as total_entries'),
                DB::raw('SUM(CASE WHEN status = "exited" THEN 1 ELSE 0 END) as total_exits'),
                DB::raw('SUM(CASE WHEN status = "exited" THEN parking_fee ELSE 0 END) as revenue'),
                DB::raw('AVG(duration_minutes) as avg_duration'),
                DB::raw('AVG(entry_confidence) as avg_conf')
            )
            ->whereNotNull('entry_time')
            ->groupBy(DB::raw('DATE(entry_time)'))
            ->orderBy('date', 'ASC')
            ->get();

        $trendLabels = [];
        $trendEntries = [];
        $trendExits = [];
        $trendRevenue = [];

        foreach ($trendQuery as $row) {
            $formattedDate = Carbon::parse($row->date)->format('M d');
            $trendLabels[] = $formattedDate;
            $trendEntries[] = (int)$row->total_entries;
            $trendExits[] = (int)$row->total_exits;
            $trendRevenue[] = round((float)$row->revenue, 2);
        }

        // Daily report records for table
        $dailyReports = (clone $query)
            ->select(
                DB::raw('DATE(entry_time) as report_date'),
                DB::raw('count(*) as entries_count'),
                DB::raw('SUM(CASE WHEN status = "exited" THEN 1 ELSE 0 END) as exits_count'),
                DB::raw('SUM(CASE WHEN status = "exited" THEN parking_fee ELSE 0 END) as daily_revenue'),
                DB::raw('AVG(CASE WHEN status = "exited" THEN duration_minutes ELSE NULL END) as avg_mins'),
                DB::raw('AVG(entry_confidence) as avg_conf')
            )
            ->whereNotNull('entry_time')
            ->groupBy(DB::raw('DATE(entry_time)'))
            ->orderBy('report_date', 'DESC')
            ->take(20)
            ->get();

        // Top Frequent Visitors / Plates
        $topPlates = (clone $query)
            ->select('plate_number', DB::raw('count(*) as visits'), DB::raw('SUM(parking_fee) as spent'), DB::raw('MAX(entry_time) as last_seen'))
            ->groupBy('plate_number')
            ->orderByDesc('visits')
            ->take(5)
            ->get();

        return view('analytics.index', [
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'customFrom' => $customFrom,
            'customTo' => $customTo,
            'totalEntries' => $totalEntries,
            'totalExits' => $totalExits,
            'currentlyParked' => $currentlyParked,
            'totalRevenue' => $totalRevenue,
            'paidRevenue' => $paidRevenue,
            'unpaidRevenue' => $unpaidRevenue,
            'avgDurationFormatted' => $avgDurationFormatted,
            'avgConfidence' => round($avgConfidence, 1),
            'peakHourLabel' => $peakHourLabel,
            'peakHourCount' => $peakHourCount,
            'vehicleTypes' => $vehicleTypes,
            'gateEntryData' => $gateEntryData,
            'gateExitData' => $gateExitData,
            'hourlyTraffic' => $hourlyTraffic,
            'trendLabels' => $trendLabels,
            'trendEntries' => $trendEntries,
            'trendExits' => $trendExits,
            'trendRevenue' => $trendRevenue,
            'dailyReports' => $dailyReports,
            'topPlates' => $topPlates,
        ]);
    }

    /**
     * Export parking logs and analytics to CSV.
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

        $filename = 'autotrace_report_' . date('Y-m-d_His') . '.csv';

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
                'Gate Entry',
                'Gate Exit',
                'Status',
                'Parking Fee (₱)',
                'Payment Status',
                'Entry Confidence (%)',
                'Exit Confidence (%)',
                'Remarks'
            ]);

            foreach ($records as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->plate_number,
                    ucfirst($row->vehicle_type ?? 'Car'),
                    $row->entry_time ? $row->entry_time->format('Y-m-d H:i:s') : 'N/A',
                    $row->exit_time ? $row->exit_time->format('Y-m-d H:i:s') : 'N/A',
                    $row->duration_minutes ?? 0,
                    $row->gate_entry ?? 'Gate 1',
                    $row->gate_exit ?? 'N/A',
                    ucfirst($row->status ?? 'Unknown'),
                    number_format((float)($row->parking_fee ?? 0), 2),
                    ucfirst($row->payment_status ?? 'Unpaid'),
                    $row->entry_confidence ?? 0,
                    $row->exit_confidence ?? 0,
                    $row->remarks ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
