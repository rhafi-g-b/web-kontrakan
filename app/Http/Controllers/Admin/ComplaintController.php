<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ComplaintStatus;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComplaintController extends Controller
{
    public function index(): Response
    {
        $complaintModels = Complaint::with(['lease.unit', 'lease.tenant', 'unit', 'tenant'])
            ->latest('id')
            ->get();

        $complaints = [];

        foreach ($complaintModels as $complaint) {
            if (! $complaint instanceof Complaint) {
                continue;
            }

            $rawStatus = $complaint->status instanceof ComplaintStatus
                ? $complaint->status->value
                : (string) $complaint->status;

            $normalizedStatus = $rawStatus === 'open' ? 'pending' : $rawStatus;

            $photoPath = (string) ($complaint->getAttribute('photo_path')
                ?? $complaint->getAttribute('image_path')
                ?? '');

            $unitNumber = $complaint->unit?->unit_number
                ?? $complaint->lease?->unit?->unit_number
                ?? '-';

            $tenantName = $complaint->tenant?->name
                ?? $complaint->lease?->tenant?->name
                ?? '-';

            $tenantPhone = $complaint->tenant?->phone
                ?? $complaint->lease?->tenant?->phone
                ?? '-';

            $adminResponse = $complaint->getAttribute('admin_response')
                ?? $complaint->getAttribute('admin_notes')
                ?? $complaint->getAttribute('response');

            $complaints[] = [
                'id' => $complaint->id,
                'title' => (string) ($complaint->getAttribute('title') ?? 'Laporan Kerusakan'),
                'description' => (string) ($complaint->getAttribute('description') ?? ''),
                'status' => $normalizedStatus,
                'admin_response' => $adminResponse !== null ? (string) $adminResponse : null,
                'has_photo' => $photoPath !== '',
                'created_at' => $complaint->created_at !== null
                    ? Carbon::parse((string) $complaint->created_at)->format('d M Y H:i')
                    : '-',
                'resolved_at' => $complaint->getAttribute('resolved_at') !== null
                    ? Carbon::parse((string) $complaint->getAttribute('resolved_at'))->format('d M Y H:i')
                    : null,
                'unit_number' => $unitNumber,
                'tenant_name' => $tenantName,
                'tenant_phone' => $tenantPhone,
            ];
        }

        $collection = collect($complaints);

        return Inertia::render('Admin/Complaints/Index', [
            'complaints' => $complaints,
            'stats' => [
                'total' => $collection->count(),
                'pending' => $collection->where('status', 'pending')->count(),
                'in_progress' => $collection->where('status', 'in_progress')->count(),
                'resolved' => $collection->where('status', 'resolved')->count(),
            ],
        ]);
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,resolved'],
            'admin_response' => ['nullable', 'string', 'max:1000'],
        ], [
            'status.required' => 'Status pengerjaan wajib dipilih.',
            'status.in' => 'Status pengerjaan tidak valid.',
        ]);

        $updateData = [
            'status' => $validated['status'],
        ];

        $responseText = isset($validated['admin_response'])
            ? trim((string) $validated['admin_response'])
            : null;

        if (Schema::hasColumn('complaints', 'admin_response')) {
            $updateData['admin_response'] = $responseText;
        } elseif (Schema::hasColumn('complaints', 'admin_notes')) {
            $updateData['admin_notes'] = $responseText;
        } elseif (Schema::hasColumn('complaints', 'response')) {
            $updateData['response'] = $responseText;
        }

        if (Schema::hasColumn('complaints', 'resolved_at')) {
            $updateData['resolved_at'] = $validated['status'] === 'resolved' ? now() : null;
        }

        $complaint->update($updateData);

        return back()->with('success', 'Status laporan kerusakan dan tanggapan berhasil diperbarui.');
    }

    public function destroy(Complaint $complaint): RedirectResponse
    {
        $photoPath = (string) ($complaint->getAttribute('photo_path')
            ?? $complaint->getAttribute('image_path')
            ?? '');

        if ($photoPath !== '') {
            Storage::disk('local')->delete($photoPath);
            Storage::disk('public')->delete($photoPath);
        }

        $complaint->delete();

        return back()->with('success', 'Data laporan kerusakan berhasil dihapus.');
    }

    public function showPhoto(Complaint $complaint): BinaryFileResponse
    {
        $photoPath = (string) ($complaint->getAttribute('photo_path')
            ?? $complaint->getAttribute('image_path')
            ?? '');

        if ($photoPath === '') {
            abort(404, 'Foto kerusakan tidak tersedia.');
        }

        if (Storage::disk('local')->exists($photoPath)) {
            return response()->file(Storage::disk('local')->path($photoPath));
        }

        if (Storage::disk('public')->exists($photoPath)) {
            return response()->file(Storage::disk('public')->path($photoPath));
        }

        abort(404, 'File foto kerusakan tidak ditemukan.');
    }
}