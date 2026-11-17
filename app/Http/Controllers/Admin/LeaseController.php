<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\LeaseStatus;
use App\Enums\PaymentStatus;
use App\Enums\UnitStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeaseRequest;
use App\Models\Complaint;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeaseController extends Controller
{
    public function index(): Response
    {
        $leaseModels = Lease::with(['unit', 'tenant'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->latest('start_date')
            ->get();

        $leases = [];

        foreach ($leaseModels as $lease) {
            if (! $lease instanceof Lease) {
                continue;
            }

            $ktpPath = $lease->getAttribute('ktp_photo_path')
                ?? $lease->tenant?->getAttribute('ktp_photo_path')
                ?? $lease->tenant?->getAttribute('ktp_path');

            $leases[] = [
                'id' => $lease->id,
                'status' => $lease->status instanceof LeaseStatus ? $lease->status->value : (string) $lease->status,
                'start_date' => Carbon::parse((string) $lease->start_date)->format('Y-m-d'),
                'end_date' => $lease->end_date !== null
                    ? Carbon::parse((string) $lease->end_date)->format('Y-m-d')
                    : null,
                'has_ktp' => ! empty($ktpPath),
                'unit' => [
                    'id' => $lease->unit?->id,
                    'unit_number' => $lease->unit?->unit_number ?? '-',
                    'monthly_price' => $lease->unit?->monthly_price ?? 0,
                ],
                'tenant' => [
                    'id' => $lease->tenant?->id,
                    'name' => $lease->tenant?->name ?? '-',
                    'phone' => $lease->tenant?->phone ?? '-',
                ],
            ];
        }

        $availableUnits = Unit::where('status', UnitStatus::Available)
            ->orderBy('unit_number')
            ->get(['id', 'unit_number', 'monthly_price'])
            ->toArray();

        $leasesCollection = collect($leases);

        return Inertia::render('Admin/Leases/Index', [
            'leases' => $leases,
            'availableUnits' => $availableUnits,
            'stats' => [
                'active' => $leasesCollection->where('status', LeaseStatus::Active->value)->count(),
                'ended' => $leasesCollection->where('status', LeaseStatus::Ended->value)->count(),
                'available_units' => count($availableUnits),
            ],
        ]);
    }

    public function store(StoreLeaseRequest $request): RedirectResponse
    {
        /** @var Unit|null $unit */
        $unit = Unit::find($request->integer('unit_id'));

        if ($unit === null || $unit->status !== UnitStatus::Available) {
            return back()->with('error', 'Pintu kontrakan yang dipilih sedang tidak tersedia.');
        }

        $ktpFile = $request->file('ktp_photo');

        if (! $ktpFile instanceof UploadedFile) {
            return back()->with('error', 'Foto KTP tidak valid.');
        }

        DB::transaction(function () use ($request, $unit, $ktpFile): void {
            $ktpPath = (string) $ktpFile->store('ktp', 'local');

            $userData = [
                'name' => $request->string('name')->trim()->toString(),
                'phone' => $request->string('phone')->trim()->toString(),
                'role' => UserRole::Tenant,
                'password' => $request->string('password')->toString(),
                'is_active' => true,
            ];

            if (Schema::hasColumn('users', 'ktp_photo_path')) {
                $userData['ktp_photo_path'] = $ktpPath;
            } elseif (Schema::hasColumn('users', 'ktp_path')) {
                $userData['ktp_path'] = $ktpPath;
            }

            $tenant = User::create($userData);

            $startDate = Carbon::parse($request->string('start_date')->toString());

            $leaseData = [
                'unit_id' => $unit->id,
                'start_date' => $startDate->toDateString(),
                'status' => LeaseStatus::Active,
            ];

            if (Schema::hasColumn('leases', 'user_id')) {
                $leaseData['user_id'] = $tenant->id;
            }
            if (Schema::hasColumn('leases', 'tenant_id')) {
                $leaseData['tenant_id'] = $tenant->id;
            }
            if (Schema::hasColumn('leases', 'ktp_photo_path')) {
                $leaseData['ktp_photo_path'] = $ktpPath;
            }
            if (Schema::hasColumn('leases', 'agreed_monthly_price')) {
                $leaseData['agreed_monthly_price'] = $unit->monthly_price;
            }
            if (Schema::hasColumn('leases', 'monthly_price')) {
                $leaseData['monthly_price'] = $unit->monthly_price;
            }

            $lease = Lease::create($leaseData);

            $unit->update([
                'status' => UnitStatus::Occupied,
            ]);

            $paymentData = [
                'lease_id' => $lease->id,
                'amount' => $unit->monthly_price,
                'status' => PaymentStatus::Unpaid,
            ];

            if (Schema::hasColumn('payments', 'billing_period')) {
                $paymentData['billing_period'] = $startDate->format('Y-m');
            }
            if (Schema::hasColumn('payments', 'period_month')) {
                $paymentData['period_month'] = (int) $startDate->month;
            }
            if (Schema::hasColumn('payments', 'period_year')) {
                $paymentData['period_year'] = (int) $startDate->year;
            }
            if (Schema::hasColumn('payments', 'due_date')) {
                $paymentData['due_date'] = $startDate->copy()->addDays(7)->toDateString();
            }

            Payment::create($paymentData);
        });

        return back()->with('success', 'Penghuni baru berhasil didaftarkan dan tagihan bulan pertama telah dibuat.');
    }

    public function terminate(Lease $lease): RedirectResponse
    {
        if ($lease->status !== LeaseStatus::Active) {
            return back()->with('error', 'Kontrak sewa ini sudah berakhir sebelumnya.');
        }

        DB::transaction(function () use ($lease): void {
            $lease->update([
                'status' => LeaseStatus::Ended,
                'end_date' => now()->toDateString(),
            ]);

            $lease->unit()->update([
                'status' => UnitStatus::Available,
            ]);

            $lease->tenant()->update([
                'is_active' => false,
            ]);
        });

        return back()->with('success', 'Kontrak sewa berhasil diakhiri dan status pintu kembali Tersedia.');
    }

    public function destroy(Lease $lease): RedirectResponse
    {
        $lease->loadMissing(['tenant', 'unit', 'payments']);

        DB::transaction(function () use ($lease): void {
            $ktpPath = (string) ($lease->getAttribute('ktp_photo_path')
                ?? $lease->tenant?->getAttribute('ktp_photo_path')
                ?? $lease->tenant?->getAttribute('ktp_path')
                ?? '');

            if ($ktpPath !== '' && Storage::disk('local')->exists($ktpPath)) {
                Storage::disk('local')->delete($ktpPath);
            }

            foreach ($lease->payments as $payment) {
                if (! $payment instanceof Payment) {
                    continue;
                }

                $proofPath = (string) ($payment->getAttribute('proof_photo_path')
                    ?? $payment->getAttribute('proof_path')
                    ?? '');

                if ($proofPath !== '') {
                    Storage::disk('local')->delete($proofPath);
                    Storage::disk('public')->delete($proofPath);
                }
            }

            $lease->payments()->delete();

            if (Schema::hasTable('complaints') && Schema::hasColumn('complaints', 'lease_id')) {
                Complaint::where('lease_id', $lease->id)->delete();
            }

            $statusValue = $lease->status instanceof LeaseStatus ? $lease->status->value : (string) $lease->status;

            if ($statusValue === LeaseStatus::Active->value && $lease->unit !== null) {
                $lease->unit->update([
                    'status' => UnitStatus::Available,
                ]);
            }

            $tenant = $lease->tenant;

            $lease->delete();

            if ($tenant !== null && ! $tenant->isAdmin()) {
                $hasOtherLeases = Lease::where(
                    Schema::hasColumn('leases', 'user_id') ? 'user_id' : 'tenant_id',
                    $tenant->id
                )->exists();

                if (! $hasOtherLeases) {
                    if (Schema::hasTable('complaints') && Schema::hasColumn('complaints', 'user_id')) {
                        Complaint::where('user_id', $tenant->id)->delete();
                    }
                    $tenant->delete();
                }
            }
        });

        return back()->with('success', 'Data penghuni, kontrak sewa, dan tagihan terkait berhasil dihapus.');
    }

    public function showKtp(Lease $lease): BinaryFileResponse
    {
        $lease->loadMissing('tenant');

        $ktpPath = (string) ($lease->getAttribute('ktp_photo_path')
            ?? $lease->tenant?->getAttribute('ktp_photo_path')
            ?? $lease->tenant?->getAttribute('ktp_path')
            ?? '');

        if ($ktpPath === '' || ! Storage::disk('local')->exists($ktpPath)) {
            abort(404, 'File KTP tidak ditemukan.');
        }

        return response()->file(Storage::disk('local')->path($ktpPath));
    }
}