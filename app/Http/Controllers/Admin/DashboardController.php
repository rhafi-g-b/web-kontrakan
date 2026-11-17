<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ComplaintStatus;
use App\Enums\PaymentStatus;
use App\Enums\UnitStatus;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $currentPeriod = now()->format('Y-m');

        $units = Unit::all();
        $totalUnits = $units->count();
        $occupiedUnits = $units->filter(
            fn (Unit $u): bool => ($u->status instanceof UnitStatus ? $u->status->value : (string) $u->status) === UnitStatus::Occupied->value
        )->count();
        $availableUnits = $units->filter(
            fn (Unit $u): bool => ($u->status instanceof UnitStatus ? $u->status->value : (string) $u->status) === UnitStatus::Available->value
        )->count();
        $maintenanceUnits = $units->filter(
            fn (Unit $u): bool => ($u->status instanceof UnitStatus ? $u->status->value : (string) $u->status) === UnitStatus::Maintenance->value
        )->count();

        $paymentQuery = Payment::with(['lease.unit', 'lease.tenant']);

        if (Schema::hasColumn('payments', 'billing_period')) {
            $paymentQuery->where('billing_period', $currentPeriod);
        } elseif (Schema::hasColumn('payments', 'period_month') && Schema::hasColumn('payments', 'period_year')) {
            $paymentQuery->where('period_year', (int) now()->year)
                ->where('period_month', (int) now()->month);
        }

        $currentMonthPayments = $paymentQuery->get();

        $paidRevenue = 0;
        $unpaidRevenue = 0;

        foreach ($currentMonthPayments as $payment) {
            if (! $payment instanceof Payment) {
                continue;
            }

            $status = $payment->status instanceof PaymentStatus ? $payment->status->value : (string) $payment->status;

            if ($status === PaymentStatus::Paid->value) {
                $paidRevenue += (int) $payment->amount;
            } elseif (in_array($status, [PaymentStatus::Unpaid->value, PaymentStatus::Rejected->value], true)) {
                $unpaidRevenue += (int) $payment->amount;
            }
        }

        $waitingModels = Payment::with(['lease.unit', 'lease.tenant'])
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::WaitingVerification->value])
            ->latest('id')
            ->limit(5)
            ->get();

        $pendingVerifications = [];

        foreach ($waitingModels as $item) {
            if (! $item instanceof Payment) {
                continue;
            }

            $pendingVerifications[] = [
                'id' => $item->id,
                'unit_number' => $item->lease?->unit?->unit_number ?? '-',
                'tenant_name' => $item->lease?->tenant?->name ?? '-',
                'amount' => (int) $item->amount,
                'billing_period' => (string) ($item->getAttribute('billing_period') ?? $currentPeriod),
            ];
        }

        $activeComplaints = [];
        $activeComplaintsCount = 0;

        if (Schema::hasTable('complaints')) {
            $complaintModels = Complaint::with(['lease.unit', 'lease.tenant', 'unit', 'tenant'])
                ->whereIn('status', ['pending', 'open', 'in_progress'])
                ->latest('id')
                ->get();

            $activeComplaintsCount = $complaintModels->count();

            foreach ($complaintModels->take(5) as $complaint) {
                if (! $complaint instanceof Complaint) {
                    continue;
                }

                $rawStatus = $complaint->status instanceof ComplaintStatus
                    ? $complaint->status->value
                    : (string) $complaint->status;

                $activeComplaints[] = [
                    'id' => $complaint->id,
                    'title' => (string) ($complaint->getAttribute('title') ?? 'Laporan Kerusakan'),
                    'status' => $rawStatus === 'open' ? 'pending' : $rawStatus,
                    'unit_number' => $complaint->unit?->unit_number
                        ?? $complaint->lease?->unit?->unit_number
                        ?? '-',
                    'tenant_name' => $complaint->tenant?->name
                        ?? $complaint->lease?->tenant?->name
                        ?? '-',
                    'created_at' => $complaint->created_at !== null
                        ? Carbon::parse((string) $complaint->created_at)->format('d M Y')
                        : '-',
                ];
            }
        }

        return Inertia::render('Admin/Dashboard', [
            'currentPeriod' => $currentPeriod,
            'stats' => [
                'total_units' => $totalUnits,
                'occupied_units' => $occupiedUnits,
                'available_units' => $availableUnits,
                'maintenance_units' => $maintenanceUnits,
                'paid_revenue' => $paidRevenue,
                'unpaid_revenue' => $unpaidRevenue,
                'waiting_payments_count' => count($pendingVerifications),
                'active_complaints_count' => $activeComplaintsCount,
            ],
            'pendingVerifications' => $pendingVerifications,
            'activeComplaints' => $activeComplaints,
        ]);
    }
}