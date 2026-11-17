<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\ComplaintStatus;
use App\Enums\LeaseStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Lease;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TenantPortalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $foreignKey = Schema::hasColumn('leases', 'user_id') ? 'user_id' : 'tenant_id';

        $activeLease = Lease::with(['unit', 'payments'])
            ->where($foreignKey, $user?->id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->latest('id')
            ->first();

        $leaseData = null;
        $payments = [];
        $complaints = [];

        if ($activeLease instanceof Lease) {
            $statusValue = $activeLease->status instanceof LeaseStatus
                ? $activeLease->status->value
                : (string) $activeLease->status;

            $monthlyPrice = (int) ($activeLease->getAttribute('agreed_monthly_price')
                ?? $activeLease->unit?->monthly_price
                ?? 0);

            $leaseData = [
                'id' => $activeLease->id,
                'status' => $statusValue,
                'start_date' => Carbon::parse((string) $activeLease->start_date)->format('d M Y'),
                'end_date' => $activeLease->end_date !== null
                    ? Carbon::parse((string) $activeLease->end_date)->format('d M Y')
                    : null,
                'unit_number' => $activeLease->unit?->unit_number ?? '-',
                'monthly_price' => $monthlyPrice,
                'description' => $activeLease->unit?->description,
            ];

            $paymentModels = Payment::where('lease_id', $activeLease->id)
                ->latest('id')
                ->get();

            foreach ($paymentModels as $payment) {
                if (! $payment instanceof Payment) {
                    continue;
                }

                $pStatus = $payment->status instanceof PaymentStatus
                    ? $payment->status->value
                    : (string) $payment->status;

                $pMethod = $payment->payment_method instanceof PaymentMethod
                    ? $payment->payment_method->value
                    : ($payment->payment_method !== null ? (string) $payment->payment_method : null);

                $proofPath = (string) ($payment->getAttribute('proof_photo_path')
                    ?? $payment->getAttribute('proof_path')
                    ?? '');

                $payments[] = [
                    'id' => $payment->id,
                    'billing_period' => (string) ($payment->getAttribute('billing_period') ?? '-'),
                    'amount' => (int) $payment->amount,
                    'status' => $pStatus,
                    'payment_method' => $pMethod,
                    'due_date' => $payment->due_date !== null
                        ? Carbon::parse((string) $payment->due_date)->format('d M Y')
                        : '-',
                    'paid_at' => $payment->paid_at !== null
                        ? Carbon::parse((string) $payment->paid_at)->format('d M Y H:i')
                        : null,
                    'has_proof' => $proofPath !== '',
                    'rejection_reason' => $payment->getAttribute('rejection_reason')
                        ?? $payment->getAttribute('notes'),
                ];
            }

            if (Schema::hasTable('complaints')) {
                $complaintQuery = Complaint::query();

                if (Schema::hasColumn('complaints', 'lease_id')) {
                    $complaintQuery->where('lease_id', $activeLease->id);
                } elseif (Schema::hasColumn('complaints', 'user_id')) {
                    $complaintQuery->where('user_id', $user?->id);
                } elseif (Schema::hasColumn('complaints', 'tenant_id')) {
                    $complaintQuery->where('tenant_id', $user?->id);
                }

                $complaintModels = $complaintQuery->latest('id')->get();

                foreach ($complaintModels as $complaint) {
                    if (! $complaint instanceof Complaint) {
                        continue;
                    }

                    $cStatus = $complaint->status instanceof ComplaintStatus
                        ? $complaint->status->value
                        : (string) $complaint->status;

                    $adminResponse = $complaint->getAttribute('admin_response')
                        ?? $complaint->getAttribute('admin_notes')
                        ?? $complaint->getAttribute('response');

                    $complaints[] = [
                        'id' => $complaint->id,
                        'title' => (string) ($complaint->getAttribute('title') ?? 'Laporan Kerusakan'),
                        'description' => (string) ($complaint->getAttribute('description') ?? ''),
                        'status' => $cStatus === 'open' ? 'pending' : $cStatus,
                        'admin_response' => $adminResponse !== null ? (string) $adminResponse : null,
                        'created_at' => $complaint->created_at !== null
                            ? Carbon::parse((string) $complaint->created_at)->format('d M Y H:i')
                            : '-',
                    ];
                }
            }
        }

        return Inertia::render('Tenant/Dashboard', [
            'lease' => $leaseData,
            'payments' => $payments,
            'complaints' => $complaints,
        ]);
    }

    public function uploadPaymentProof(Request $request, Payment $payment): RedirectResponse
    {
        $user = $request->user();
        $payment->loadMissing('lease');

        $leaseOwnerId = $payment->lease?->getAttribute('user_id')
            ?? $payment->lease?->getAttribute('tenant_id');

        if ((int) $leaseOwnerId !== (int) $user?->id) {
            abort(403, 'Tagihan ini bukan milik akun Anda.');
        }

        $request->validate([
            'proof_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'proof_photo.required' => 'Foto bukti transfer wajib dipilih.',
            'proof_photo.image' => 'File bukti transfer harus berupa gambar.',
            'proof_photo.max' => 'Ukuran foto maksimal 10MB.',
        ]);

        $file = $request->file('proof_photo');

        if (! $file instanceof UploadedFile) {
            return back()->with('error', 'File bukti transfer tidak valid.');
        }

        $oldPath = (string) ($payment->getAttribute('proof_photo_path')
            ?? $payment->getAttribute('proof_path')
            ?? '');

        if ($oldPath !== '') {
            Storage::disk('local')->delete($oldPath);
            Storage::disk('public')->delete($oldPath);
        }

        $storedPath = (string) $file->store('payment-proofs', 'local');

        $waitingStatus = PaymentStatus::WaitingVerification->value;

        try {
            $statusType = Schema::getColumnType('payments', 'status');
            if ($statusType !== 'varchar' && $statusType !== 'text') {
                $waitingStatus = 'pending';
            }
        } catch (\Throwable) {
        }

        $updateData = [
            'status' => $waitingStatus,
            'payment_method' => PaymentMethod::Transfer,
        ];

        if (Schema::hasColumn('payments', 'proof_photo_path')) {
            $updateData['proof_photo_path'] = $storedPath;
        } elseif (Schema::hasColumn('payments', 'proof_path')) {
            $updateData['proof_path'] = $storedPath;
        }

        if (Schema::hasColumn('payments', 'paid_at')) {
            $updateData['paid_at'] = now();
        }
        if (Schema::hasColumn('payments', 'rejection_reason')) {
            $updateData['rejection_reason'] = null;
        }

        try {
            $payment->update($updateData);
        } catch (\Throwable) {
            $updateData['status'] = 'pending';
            $payment->update($updateData);
        }

        return back()->with('success', 'Bukti transfer berhasil dikirim dan sedang menunggu validasi Ibu.');
    }

    public function storeComplaint(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'title.required' => 'Judul kerusakan wajib diisi.',
            'description.required' => 'Keterangan detail kerusakan wajib diisi.',
            'photo.image' => 'Lampiran harus berupa foto/gambar.',
            'photo.max' => 'Ukuran foto maksimal 10MB.',
        ]);

        $foreignKey = Schema::hasColumn('leases', 'user_id') ? 'user_id' : 'tenant_id';

        $activeLease = Lease::where($foreignKey, $user?->id)
            ->where('status', LeaseStatus::Active)
            ->latest('id')
            ->first();

        if (! $activeLease instanceof Lease) {
            return back()->with('error', 'Anda tidak memiliki kontrak sewa aktif.');
        }

        $photoPath = null;
        $photoFile = $request->file('photo');

        if ($photoFile instanceof UploadedFile) {
            $photoPath = (string) $photoFile->store('complaints', 'local');
        }

        $complaintData = [
            'title' => trim((string) $validated['title']),
            'description' => trim((string) $validated['description']),
            'status' => 'pending',
        ];

        if (Schema::hasColumn('complaints', 'lease_id')) {
            $complaintData['lease_id'] = $activeLease->id;
        }
        if (Schema::hasColumn('complaints', 'unit_id')) {
            $complaintData['unit_id'] = $activeLease->unit_id;
        }
        if (Schema::hasColumn('complaints', 'user_id')) {
            $complaintData['user_id'] = $user?->id;
        }
        if (Schema::hasColumn('complaints', 'tenant_id')) {
            $complaintData['tenant_id'] = $user?->id;
        }
        if ($photoPath !== null) {
            if (Schema::hasColumn('complaints', 'photo_path')) {
                $complaintData['photo_path'] = $photoPath;
            } elseif (Schema::hasColumn('complaints', 'image_path')) {
                $complaintData['image_path'] = $photoPath;
            }
        }

        try {
            Complaint::create($complaintData);
        } catch (\Throwable) {
            $complaintData['status'] = 'open';
            Complaint::create($complaintData);
        }

        return back()->with('success', 'Laporan kerusakan berhasil dikirim ke Ibu Pengelola.');
    }
}