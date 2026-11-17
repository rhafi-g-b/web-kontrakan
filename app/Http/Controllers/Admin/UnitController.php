<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UnitStatus;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(): Response
    {
        $unitModels = Unit::with('activeLease.tenant')
            ->orderByRaw('LENGTH(unit_number), unit_number ASC')
            ->get();

        $units = [];

        foreach ($unitModels as $unit) {
            if (! $unit instanceof Unit) {
                continue;
            }

            $statusValue = $unit->status instanceof UnitStatus
                ? $unit->status->value
                : (string) $unit->status;

            $photoUrl = $unit->photo_path
                ? Storage::url($unit->photo_path)
                : null;

            $activeLease = $unit->activeLease;
            $activeTenant = null;

            if ($activeLease && $activeLease->tenant) {
                $activeTenant = [
                    'name' => (string) $activeLease->tenant->name,
                    'phone' => (string) $activeLease->tenant->phone,
                ];
            }

            $units[] = [
                'id' => $unit->id,
                'unit_number' => (string) $unit->unit_number,
                'monthly_price' => (int) $unit->monthly_price,
                'status' => $statusValue,
                'description' => $unit->description,
                'photo_url' => $photoUrl,
                'active_tenant' => $activeTenant,
            ];
        }

        $collection = collect($units);

        return Inertia::render('Admin/Units/Index', [
            'units' => $units,
            'stats' => [
                'total' => $collection->count(),
                'available' => $collection->where('status', 'available')->count(),
                'occupied' => $collection->where('status', 'occupied')->count(),
                'maintenance' => $collection->where('status', 'maintenance')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'unit_number' => ['required', 'string', 'max:50', 'unique:units,unit_number'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:available,occupied,maintenance'],
            'description' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'unit_number.required' => 'Nama atau nomor pintu wajib diisi.',
            'unit_number.unique' => 'Nama atau nomor pintu ini sudah terdaftar.',
            'monthly_price.required' => 'Harga sewa bulanan wajib diisi.',
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 10MB.',
        ]);

        $photoPath = null;
        $file = $request->file('photo');

        if ($file instanceof UploadedFile) {
            $photoPath = (string) $file->store('units', 'public');
        }

        Unit::create([
            'unit_number' => trim((string) $validated['unit_number']),
            'monthly_price' => (int) $validated['monthly_price'],
            'status' => $validated['status'],
            'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
            'photo_path' => $photoPath,
        ]);

        return back()->with('success', 'Pintu kontrakan baru berhasil ditambahkan.');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'unit_number' => ['required', 'string', 'max:50', 'unique:units,unit_number,' . $unit->id],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:available,occupied,maintenance'],
            'description' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'unit_number.required' => 'Nama atau nomor pintu wajib diisi.',
            'unit_number.unique' => 'Nama atau nomor pintu ini sudah terdaftar.',
            'monthly_price.required' => 'Harga sewa bulanan wajib diisi.',
        ]);

        $photoPath = $unit->photo_path;
        $file = $request->file('photo');

        if ($file instanceof UploadedFile) {
            if ($photoPath !== null) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = (string) $file->store('units', 'public');
        }

        $unit->update([
            'unit_number' => trim((string) $validated['unit_number']),
            'monthly_price' => (int) $validated['monthly_price'],
            'status' => $validated['status'],
            'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
            'photo_path' => $photoPath,
        ]);

        return back()->with('success', 'Data pintu kontrakan berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->photo_path !== null) {
            Storage::disk('public')->delete($unit->photo_path);
        }

        $unit->delete();

        return back()->with('success', 'Data pintu kontrakan berhasil dihapus.');
    }
}