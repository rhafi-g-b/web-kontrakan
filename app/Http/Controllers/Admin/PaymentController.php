<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\LeaseStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedPeriod = $request->string('period', now()->format('Y-m'))->toString();

        if (! preg_match('/^\d{4}-\d{2}$/', $selectedPeriod)) {
            $selectedPeriod = now()->format('Y-m');
        }

        $query = Payment::with(['lease.unit', 'lease.tenant']);

        if (Schema::hasColumn('payments', 'billing_period')) {
            $query->where('billing_period', $selectedPeriod);
        } elseif (Schema::hasColumn('payments', 'period_month') && Schema::hasColumn('payments', 'period_year')) {
            [$year, $month] = explode('-', $selectedPeriod);
            $query->where('period_year', (int) $year)->where('period_month', (int) $month);
        }

        $paymentModels = $query->latest('id')->get();

        $payments = [];

        foreach ($paymentModels as $payment) {
            if (! $payment instanceof Payment) {
                continue;
            }

            $statusValue = $payment->status instanceof PaymentStatus
                ? $payment->status->value
                : (string) $payment->status;

            $methodValue = $payment->payment_method instanceof PaymentMethod
                ? $payment->payment_method->value
                : ($payment->payment_method !== null ? (string) $payment->payment_method : null);

            $proofPath = (string) ($payment->getAttribute('proof_photo_path')
                ?? $payment->getAttribute('proof_path')
                ?? '');

            $billingPeriod = (string) ($payment->getAttribute('billing_period') ?? $selectedPeriod);

            $payments[] = [
                'id' => $payment->id,
                'billing_period' => $billingPeriod,
                'amount' => (int) $payment->amount,
                'status' => $statusValue,
                'payment_method' => $methodValue,
                'due_date' => $payment->due_date !== null
                    ? Carbon::parse((string) $payment->due_date)->format('Y-m-d')
                    : null,
                'paid_at' => $payment->paid_at !== null
                    ? Carbon::parse((string) $payment->paid_at)->format('d M Y H:i')
                    : null,
                'has_proof' => $proofPath !== '',
                'rejection_reason' => $payment->getAttribute('rejection_reason')
                    ?? $payment->getAttribute('notes'),
                'unit_number' => $payment->lease?->unit?->unit_number ?? '-',
                'tenant_name' => $payment->lease?->tenant?->name ?? '-',
                'tenant_phone' => $payment->lease?->tenant?->phone ?? '-',
            ];
        }

        $paymentsCollection = collect($payments);

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'selectedPeriod' => $selectedPeriod,
            'stats' => [
                'total_count' => $paymentsCollection->count(),
                'paid_amount' => (int) $paymentsCollection->where('status', PaymentStatus::Paid->value)->sum('amount'),
                'unpaid_amount' => (int) $paymentsCollection->whereIn('status', [
                    PaymentStatus::Unpaid->value,
                    PaymentStatus::Rejected->value,
                ])->sum('amount'),
                'waiting_count' => $paymentsCollection->whereIn('status', [
                    PaymentStatus::Pending->value,
                    PaymentStatus::WaitingVerification->value,
                ])->count(),
            ],
        ]);
    }

    public function generateMonthly(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $period = (string) $validated['period'];
        [$year, $month] = explode('-', $period);

        $activeLeases = Lease::with('unit')
            ->where('status', LeaseStatus::Active)
            ->get();

        if ($activeLeases->isEmpty()) {
            return back()->with('error', 'Belum ada kontrak penghuni aktif untuk dibuatkan tagihan.');
        }

        $createdCount = 0;

        DB::transaction(function () use ($activeLeases, $period, $year, $month, &$createdCount): void {
            foreach ($activeLeases as $lease) {
                if (! $lease instanceof Lease) {
                    continue;
                }

                $existsQuery = Payment::where('lease_id', $lease->id);

                if (Schema::hasColumn('payments', 'billing_period')) {
                    $existsQuery->where('billing_period', $period);
                } elseif (Schema::hasColumn('payments', 'period_month')) {
                    $existsQuery->where('period_year', (int) $year)->where('period_month', (int) $month);
                }

                if ($existsQuery->exists()) {
                    continue;
                }

                $startDay = $lease->start_date !== null
                    ? Carbon::parse((string) $lease->start_date)->day
                    : 1;

                $dueDate = Carbon::createFromDate((int) $year, (int) $month, 1)
                    ->day(min($startDay, 28))
                    ->addDays(7);

                $amount = (int) ($lease->getAttribute('agreed_monthly_price')
                    ?? $lease->unit?->monthly_price
                    ?? 0);

                $paymentData = [
                    'lease_id' => $lease->id,
                    'amount' => $amount,
                    'status' => PaymentStatus::Unpaid,
                ];

                if (Schema::hasColumn('payments', 'billing_period')) {
                    $paymentData['billing_period'] = $period;
                }
                if (Schema::hasColumn('payments', 'period_month')) {
                    $paymentData['period_month'] = (int) $month;
                }
                if (Schema::hasColumn('payments', 'period_year')) {
                    $paymentData['period_year'] = (int) $year;
                }
                if (Schema::hasColumn('payments', 'due_date')) {
                    $paymentData['due_date'] = $dueDate->toDateString();
                }

                Payment::create($paymentData);
                $createdCount++;
            }
        });

        if ($createdCount === 0) {
            return back()->with('error', "Seluruh tagihan untuk periode {$period} sudah pernah dibuat sebelumnya.");
        }

        return back()->with('success', "Berhasil menerbitkan {$createdCount} tagihan baru untuk periode {$period}.");
    }

    public function markCashPaid(Request $request, Payment $payment): RedirectResponse
    {
        $updateData = [
            'status' => PaymentStatus::Paid,
            'payment_method' => PaymentMethod::Cash,
        ];

        if (Schema::hasColumn('payments', 'paid_at')) {
            $updateData['paid_at'] = now();
        }
        if (Schema::hasColumn('payments', 'verified_by')) {
            $updateData['verified_by'] = $request->user()?->id;
        }
        if (Schema::hasColumn('payments', 'verified_at')) {
            $updateData['verified_at'] = now();
        }

        $payment->update($updateData);

        return back()->with('success', 'Pembayaran tunai berhasil dicatat sebagai Lunas.');
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        $updateData = [
            'status' => PaymentStatus::Paid,
        ];

        if ($payment->payment_method === null) {
            $updateData['payment_method'] = PaymentMethod::Transfer;
        }
        if (Schema::hasColumn('payments', 'paid_at') && $payment->paid_at === null) {
            $updateData['paid_at'] = now();
        }
        if (Schema::hasColumn('payments', 'verified_by')) {
            $updateData['verified_by'] = $request->user()?->id;
        }
        if (Schema::hasColumn('payments', 'verified_at')) {
            $updateData['verified_at'] = now();
        }
        if (Schema::hasColumn('payments', 'rejection_reason')) {
            $updateData['rejection_reason'] = null;
        }

        $payment->update($updateData);

        return back()->with('success', 'Bukti transfer berhasil divalidasi dan status tagihan kini Lunas.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi agar penghuni mengetahui penyebabnya.',
        ]);

        $updateData = [
            'status' => PaymentStatus::Rejected,
        ];

        if (Schema::hasColumn('payments', 'rejection_reason')) {
            $updateData['rejection_reason'] = $validated['rejection_reason'];
        } elseif (Schema::hasColumn('payments', 'notes')) {
            $updateData['notes'] = $validated['rejection_reason'];
        }

        if (Schema::hasColumn('payments', 'verified_by')) {
            $updateData['verified_by'] = $request->user()?->id;
        }
        if (Schema::hasColumn('payments', 'verified_at')) {
            $updateData['verified_at'] = now();
        }

        $payment->update($updateData);

        return back()->with('success', 'Bukti pembayaran ditolak dan catatan telah dikirim ke penghuni.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $proofPath = (string) ($payment->getAttribute('proof_photo_path')
            ?? $payment->getAttribute('proof_path')
            ?? '');

        if ($proofPath !== '') {
            Storage::disk('local')->delete($proofPath);
            Storage::disk('public')->delete($proofPath);
        }

        $payment->delete();

        return back()->with('success', 'Data tagihan pembayaran berhasil dihapus.');
    }

    public function showProof(Payment $payment): BinaryFileResponse
    {
        $proofPath = (string) ($payment->getAttribute('proof_photo_path')
            ?? $payment->getAttribute('proof_path')
            ?? '');

        if ($proofPath === '') {
            abort(404, 'Bukti pembayaran belum diunggah.');
        }

        if (Storage::disk('local')->exists($proofPath)) {
            return response()->file(Storage::disk('local')->path($proofPath));
        }

        if (Storage::disk('public')->exists($proofPath)) {
            return response()->file(Storage::disk('public')->path($proofPath));
        }

        abort(404, 'File bukti pembayaran tidak ditemukan.');
    }
}