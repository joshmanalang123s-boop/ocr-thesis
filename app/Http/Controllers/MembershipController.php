<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Models\MembershipSetting;
use App\Models\MembershipAudit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MembershipController extends Controller
{
    /**
     * Display the Membership Management dashboard.
     */
    public function index(Request $request)
    {
        $search = trim($request->query('q', ''));
        $statusFilter = $request->query('status', 'all');

        $settings = MembershipSetting::getSettings();
        $today = Carbon::today();

        // Base metrics
        $totalMembers = Member::count();
        $activeMembers = Member::active()->count();
        $expiringSoonMembers = Member::expiringSoon($settings->expiration_warning_days)->count();
        $expiredMembers = Member::expired()->count();
        $unpaidMembers = Member::unpaid()->count();

        // Total Membership Revenue collected (strictly separate from parking session tariff)
        $totalMembershipRevenue = (float)MemberPayment::where('payment_status', 'paid')->sum('amount');

        // Query for member list
        $query = Member::with(['payments' => function ($q) {
            $q->latest('created_at')->take(1);
        }]);

        if ($search !== '') {
            $cleanSearch = Member::normalizePlate($search);
            $query->where(function ($q) use ($search, $cleanSearch) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('plate_number', 'LIKE', "%{$search}%")
                  ->orWhere('clean_plate', 'LIKE', "%{$cleanSearch}%")
                  ->orWhere('contact_number', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        switch ($statusFilter) {
            case 'active':
                $query->active();
                break;
            case 'expiring':
                $query->expiringSoon($settings->expiration_warning_days);
                break;
            case 'expired':
                $query->expired();
                break;
            case 'unpaid':
                $query->unpaid();
                break;
            case 'suspended':
                $query->suspended();
                break;
        }

        $members = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('memberships.index', compact(
            'members',
            'settings',
            'search',
            'statusFilter',
            'totalMembers',
            'activeMembers',
            'expiringSoonMembers',
            'expiredMembers',
            'unpaidMembers',
            'totalMembershipRevenue'
        ));
    }

    /**
     * Store a newly registered member.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'plate_number' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Z0-9\s\-]+$/i'],
            'vehicle_type' => 'required|in:car,motorcycle,truck,other',
            'start_date' => 'required|date',
            'duration_months' => 'required|integer|in:1,3,6,12',
            'price' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:paid,unpaid',
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $cleanPlate = Member::normalizePlate($validated['plate_number']);

        // Prevent accidental duplicate active registration for the same vehicle plate
        $startDate = Carbon::parse($validated['start_date']);
        $existingActive = Member::where('clean_plate', $cleanPlate)
            ->where('status', 'active')
            ->where('end_date', '>=', $startDate)
            ->first();

        if ($existingActive) {
            $formattedEnd = $existingActive->end_date->format('M d, Y');
            return back()->withInput()->with('error', "Vehicle {$validated['plate_number']} already has an active membership registered under {$existingActive->full_name} (Valid until {$formattedEnd}). Renew or edit that member instead.");
        }

        $settings = MembershipSetting::getSettings();
        $durationMonths = (int)$validated['duration_months'];
        $price = (isset($validated['price']) && $validated['price'] !== null && $validated['price'] !== '')
            ? (float)$validated['price']
            : $settings->getPriceForDuration($durationMonths);

        $endDate = $startDate->copy()->addMonths($durationMonths);

        DB::beginTransaction();
        try {
            $member = Member::create([
                'full_name' => trim($validated['full_name']),
                'plate_number' => strtoupper(trim($validated['plate_number'])),
                'clean_plate' => $cleanPlate,
                'vehicle_type' => $validated['vehicle_type'],
                'contact_number' => $validated['contact_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'duration_months' => $durationMonths,
                'price' => $price,
                'payment_status' => $validated['payment_status'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Record transaction in member_payments ledger
            MemberPayment::create([
                'member_id' => $member->id,
                'payment_type' => 'initial',
                'amount' => $price,
                'duration_months' => $durationMonths,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'payment_status' => $validated['payment_status'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_reference' => $validated['payment_reference'] ?? null,
                'paid_at' => ($validated['payment_status'] === 'paid') ? now() : null,
                'received_by' => auth()->user()->name ?? 'System Administrator',
                'notes' => 'Initial membership registration',
            ]);

            MembershipAudit::log('REGISTER', $member->id, [
                'full_name' => $member->full_name,
                'plate_number' => $member->plate_number,
                'duration_months' => $durationMonths,
                'price' => $price,
                'payment_status' => $validated['payment_status'],
                'start_date' => $member->start_date->toDateString(),
                'end_date' => $member->end_date->toDateString(),
            ]);

            DB::commit();

            return redirect()->route('memberships.index')->with('success', "Member {$member->full_name} ({$member->plate_number}) successfully registered for {$durationMonths} month(s). Valid until {$endDate->format('M d, Y')}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to register member: ' . $e->getMessage());
        }
    }

    /**
     * Show member details (API/AJAX JSON endpoint for modal view).
     */
    public function show($id)
    {
        $member = Member::with([
            'payments' => function ($q) { $q->latest('created_at'); },
            'audits' => function ($q) { $q->latest('created_at')->take(10); },
            'parkingEntries' => function ($q) { $q->latest('entry_time')->take(10); },
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'full_name' => $member->full_name,
                'plate_number' => $member->plate_number,
                'vehicle_type' => $member->vehicle_type,
                'contact_number' => $member->contact_number,
                'email' => $member->email,
                'start_date' => $member->start_date->format('Y-m-d'),
                'start_date_formatted' => $member->start_date->format('M d, Y'),
                'end_date' => $member->end_date->format('Y-m-d'),
                'end_date_formatted' => $member->end_date->format('M d, Y'),
                'duration_months' => $member->duration_months,
                'price' => (float)$member->price,
                'price_formatted' => '₱' . number_format($member->price, 2),
                'payment_status' => $member->payment_status,
                'payment_reference' => $member->payment_reference,
                'status' => $member->status,
                'computed_status' => $member->computed_status,
                'computed_status_label' => $member->computed_status_label,
                'badge_class' => $member->badge_class,
                'notes' => $member->notes,
                'created_at_formatted' => $member->created_at->format('M d, Y • h:i A'),
            ],
            'payments' => $member->payments->map(function ($p) {
                return [
                    'id' => $p->id,
                    'type' => ucfirst($p->payment_type),
                    'amount_formatted' => '₱' . number_format($p->amount, 2),
                    'duration' => $p->duration_months . ' mo',
                    'start_date' => $p->start_date ? $p->start_date->format('M d, Y') : '—',
                    'end_date' => $p->end_date ? $p->end_date->format('M d, Y') : '—',
                    'status' => ucfirst($p->payment_status),
                    'method' => strtoupper($p->payment_method),
                    'reference' => $p->payment_reference ?? '—',
                    'paid_at' => $p->paid_at ? $p->paid_at->format('M d, Y h:i A') : 'Unpaid',
                    'received_by' => $p->received_by ?? 'System',
                ];
            }),
            'parking_entries' => $member->parkingEntries->map(function ($e) {
                return [
                    'id' => $e->id,
                    'plate' => $e->plate_number,
                    'entry_time' => $e->entry_time ? $e->entry_time->format('M d, Y h:i A') : '—',
                    'exit_time' => $e->exit_time ? $e->exit_time->format('M d, Y h:i A') : 'Parked Inside',
                    'duration' => $e->duration_minutes ? "{$e->duration_minutes}m" : '—',
                    'fee' => '₱' . number_format($e->parking_fee ?? 0, 2),
                    'covered' => $e->covered_by_membership ? 'Member Free' : 'Standard Tariff',
                    'status' => $e->status,
                ];
            }),
            'audits' => $member->audits->map(function ($a) {
                return [
                    'action' => $a->action,
                    'user' => $a->user_name,
                    'date' => $a->created_at ? $a->created_at->format('M d, Y h:i A') : '—',
                    'details' => $a->details,
                ];
            }),
        ]);
    }

    /**
     * Update member personal information.
     */
    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'plate_number' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Z0-9\s\-]+$/i'],
            'vehicle_type' => 'required|in:car,motorcycle,truck,other',
            'contact_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $newPlateClean = Member::normalizePlate($validated['plate_number']);

        // If plate was changed, verify no active conflict
        if ($newPlateClean !== $member->clean_plate) {
            $conflict = Member::where('clean_plate', $newPlateClean)
                ->where('id', '!=', $member->id)
                ->where('status', 'active')
                ->where('end_date', '>=', Carbon::today())
                ->first();

            if ($conflict) {
                return back()->with('error', "Plate {$validated['plate_number']} is already registered to active member {$conflict->full_name}.");
            }
        }

        $oldData = $member->only(['full_name', 'plate_number', 'vehicle_type', 'contact_number', 'email']);

        $member->update([
            'full_name' => trim($validated['full_name']),
            'plate_number' => strtoupper(trim($validated['plate_number'])),
            'clean_plate' => $newPlateClean,
            'vehicle_type' => $validated['vehicle_type'],
            'contact_number' => $validated['contact_number'] ?? null,
            'email' => $validated['email'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        MembershipAudit::log('EDIT', $member->id, [
            'before' => $oldData,
            'after' => $member->only(['full_name', 'plate_number', 'vehicle_type', 'contact_number', 'email']),
        ]);

        return redirect()->route('memberships.index')->with('success', "Member {$member->full_name} details updated successfully.");
    }

    /**
     * Renew an existing membership.
     */
    public function renew(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $validated = $request->validate([
            'duration_months' => 'required|integer|in:1,3,6,12',
            'price' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:paid,unpaid',
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $settings = MembershipSetting::getSettings();
        $durationMonths = (int)$validated['duration_months'];
        $price = (isset($validated['price']) && $validated['price'] !== null && $validated['price'] !== '')
            ? (float)$validated['price']
            : $settings->getPriceForDuration($durationMonths);

        $today = Carbon::today();

        // Calculate seamless renewal dates
        // If current membership is still active in future: start from current end_date
        // If already expired: start from today
        $newStartDate = ($member->end_date && $member->end_date >= $today)
            ? $member->end_date->copy()
            : $today->copy();

        $newEndDate = $newStartDate->copy()->addMonths($durationMonths);

        DB::beginTransaction();
        try {
            // Record renewal payment ledger entry
            MemberPayment::create([
                'member_id' => $member->id,
                'payment_type' => 'renewal',
                'amount' => $price,
                'duration_months' => $durationMonths,
                'start_date' => $newStartDate->toDateString(),
                'end_date' => $newEndDate->toDateString(),
                'payment_status' => $validated['payment_status'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_reference' => $validated['payment_reference'] ?? null,
                'paid_at' => ($validated['payment_status'] === 'paid') ? now() : null,
                'received_by' => auth()->user()->name ?? 'System Administrator',
                'notes' => $validated['notes'] ?? 'Membership renewal',
            ]);

            $oldEndDate = $member->end_date ? $member->end_date->format('M d, Y') : 'N/A';

            $member->update([
                'end_date' => $newEndDate->toDateString(),
                'duration_months' => $durationMonths,
                'price' => $price,
                'payment_status' => $validated['payment_status'],
                'payment_reference' => $validated['payment_reference'] ?? $member->payment_reference,
                'status' => 'active',
                'updated_by' => auth()->id(),
            ]);

            MembershipAudit::log('RENEW', $member->id, [
                'previous_end_date' => $oldEndDate,
                'new_start_date' => $newStartDate->toDateString(),
                'new_end_date' => $newEndDate->toDateString(),
                'duration_months' => $durationMonths,
                'price' => $price,
                'payment_status' => $validated['payment_status'],
            ]);

            DB::commit();

            return redirect()->route('memberships.index')->with('success', "Membership for {$member->full_name} renewed for {$durationMonths} month(s). New expiration date: {$newEndDate->format('M d, Y')}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Renewal failed: ' . $e->getMessage());
        }
    }

    /**
     * Confirm/record payment for an unpaid member.
     */
    public function confirmPayment(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $validated = $request->validate([
            'payment_method' => 'required|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = isset($validated['amount']) && $validated['amount'] !== null && $validated['amount'] !== ''
            ? (float)$validated['amount']
            : (float)$member->price;

        DB::beginTransaction();
        try {
            $member->update([
                'payment_status' => 'paid',
                'payment_reference' => $validated['payment_reference'] ?? $member->payment_reference,
                'updated_by' => auth()->id(),
            ]);

            // Update latest unpaid payment record or create one
            $payment = MemberPayment::where('member_id', $member->id)
                ->where('payment_status', 'unpaid')
                ->latest('created_at')
                ->first();

            if ($payment) {
                $payment->update([
                    'amount' => $amount,
                    'payment_status' => 'paid',
                    'payment_method' => $validated['payment_method'],
                    'payment_reference' => $validated['payment_reference'] ?? null,
                    'paid_at' => now(),
                    'received_by' => auth()->user()->name ?? 'System Administrator',
                    'notes' => $validated['notes'] ?? $payment->notes,
                ]);
            } else {
                MemberPayment::create([
                    'member_id' => $member->id,
                    'payment_type' => 'adjustment',
                    'amount' => $amount,
                    'duration_months' => $member->duration_months,
                    'start_date' => $member->start_date->toDateString(),
                    'end_date' => $member->end_date->toDateString(),
                    'payment_status' => 'paid',
                    'payment_method' => $validated['payment_method'],
                    'payment_reference' => $validated['payment_reference'] ?? null,
                    'paid_at' => now(),
                    'received_by' => auth()->user()->name ?? 'System Administrator',
                    'notes' => $validated['notes'] ?? 'Payment confirmed manually',
                ]);
            }

            MembershipAudit::log('CONFIRM_PAYMENT', $member->id, [
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['payment_reference'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('memberships.index')->with('success', "Payment of ₱" . number_format($amount, 2) . " confirmed for member {$member->full_name}. Membership benefits now active.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Payment confirmation failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle member suspension (Deactivate / Reactivate).
     */
    public function toggleStatus(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $newStatus = ($member->status === 'active') ? 'suspended' : 'active';
        $member->update([
            'status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        MembershipAudit::log($newStatus === 'active' ? 'REACTIVATE' : 'SUSPEND', $member->id, [
            'previous_status' => $member->status,
            'new_status' => $newStatus,
        ]);

        $actionWord = ($newStatus === 'active') ? 'reactivated' : 'suspended';
        return redirect()->route('memberships.index')->with('success', "Member {$member->full_name} has been {$actionWord}.");
    }

    /**
     * Update system-wide membership pricing settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'default_monthly_price' => 'required|numeric|min:0',
            'price_3_months' => 'nullable|numeric|min:0',
            'price_6_months' => 'nullable|numeric|min:0',
            'price_12_months' => 'nullable|numeric|min:0',
            'expiration_warning_days' => 'required|integer|min:1|max:60',
            'allow_overnight' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $settings = MembershipSetting::getSettings();
        $oldPrices = $settings->toArray();

        $settings->update([
            'default_monthly_price' => (float)$validated['default_monthly_price'],
            'price_3_months' => !empty($validated['price_3_months']) ? (float)$validated['price_3_months'] : ((float)$validated['default_monthly_price'] * 3),
            'price_6_months' => !empty($validated['price_6_months']) ? (float)$validated['price_6_months'] : ((float)$validated['default_monthly_price'] * 6),
            'price_12_months' => !empty($validated['price_12_months']) ? (float)$validated['price_12_months'] : ((float)$validated['default_monthly_price'] * 12),
            'expiration_warning_days' => (int)$validated['expiration_warning_days'],
            'allow_overnight' => $request->boolean('allow_overnight', true),
            'notes' => $validated['notes'] ?? $settings->notes,
            'updated_by' => auth()->id(),
        ]);

        MembershipAudit::log('UPDATE_PRICING', null, [
            'previous_settings' => $oldPrices,
            'new_settings' => $settings->toArray(),
        ]);

        return redirect()->back()->with('success', "Membership pricing settings updated. New default monthly price is ₱" . number_format($settings->default_monthly_price, 2) . ". Existing paid memberships remain preserved.");
    }

    /**
     * Export member list to CSV.
     */
    public function exportCsv(Request $request)
    {
        $statusFilter = $request->query('status', 'all');
        $search = trim($request->query('q', ''));

        $query = Member::query();

        if ($search !== '') {
            $cleanSearch = Member::normalizePlate($search);
            $query->where(function ($q) use ($search, $cleanSearch) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('plate_number', 'LIKE', "%{$search}%")
                  ->orWhere('clean_plate', 'LIKE', "%{$cleanSearch}%");
            });
        }

        switch ($statusFilter) {
            case 'active': $query->active(); break;
            case 'expiring': $query->expiringSoon(); break;
            case 'expired': $query->expired(); break;
            case 'unpaid': $query->unpaid(); break;
            case 'suspended': $query->suspended(); break;
        }

        $members = $query->orderBy('created_at', 'desc')->get();

        $filename = "autotrace_members_" . date('Y-m-d_His') . ".csv";

        $response = new StreamedResponse(function () use ($members) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Member ID',
                'Full Name',
                'Plate Number',
                'Vehicle Type',
                'Computed Status',
                'Payment Status',
                'Start Date',
                'End Date',
                'Duration (Months)',
                'Registered Fee (PHP)',
                'Payment Reference',
                'Contact Number',
                'Email',
                'Registered Date',
            ]);

            foreach ($members as $m) {
                fputcsv($handle, [
                    $m->id,
                    $m->full_name,
                    $m->plate_number,
                    ucfirst($m->vehicle_type),
                    $m->computed_status_label,
                    ucfirst($m->payment_status),
                    $m->start_date ? $m->start_date->format('Y-m-d') : '',
                    $m->end_date ? $m->end_date->format('Y-m-d') : '',
                    $m->duration_months,
                    number_format($m->price, 2, '.', ''),
                    $m->payment_reference ?? '',
                    $m->contact_number ?? '',
                    $m->email ?? '',
                    $m->created_at ? $m->created_at->format('Y-m-d H:i') : '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}

