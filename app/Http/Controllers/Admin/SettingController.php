<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        $setting = SiteSetting::current();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $setting->toSharedArray(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'site_tagline' => ['nullable', 'string', 'max:150'],
            'hero_title' => ['required', 'string', 'max:200'],
            'hero_subtitle' => ['nullable', 'string', 'max:500'],
            'whatsapp_number' => ['required', 'string', 'max:30'],
            'footer_description' => ['nullable', 'string', 'max:600'],
            'survey_hours' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:300'],
            'survey_note' => ['nullable', 'string', 'max:400'],
            'copyright_text' => ['nullable', 'string', 'max:150'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [
            'site_name.required' => 'Nama kontrakan wajib diisi.',
            'hero_title.required' => 'Judul utama Hero wajib diisi.',
            'whatsapp_number.required' => 'Nomor WhatsApp pengelola wajib diisi.',
            'logo.image' => 'File logo harus berupa gambar.',
            'logo.max' => 'Ukuran file logo maksimal 4MB.',
        ]);

        $setting = SiteSetting::current();

        $logoPath = $setting->logo_path;

        if ($request->boolean('remove_logo') && $logoPath !== null) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        $logoFile = $request->file('logo');
        if ($logoFile instanceof UploadedFile) {
            if ($logoPath !== null) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = (string) $logoFile->store('site-logo', 'public');
        }

        $setting->update([
            'site_name' => trim((string) $validated['site_name']),
            'site_tagline' => isset($validated['site_tagline']) ? trim((string) $validated['site_tagline']) : null,
            'logo_path' => $logoPath,
            'hero_title' => trim((string) $validated['hero_title']),
            'hero_subtitle' => isset($validated['hero_subtitle']) ? trim((string) $validated['hero_subtitle']) : null,
            'whatsapp_number' => trim((string) $validated['whatsapp_number']),
            'footer_description' => isset($validated['footer_description']) ? trim((string) $validated['footer_description']) : null,
            'survey_hours' => isset($validated['survey_hours']) ? trim((string) $validated['survey_hours']) : null,
            'address' => isset($validated['address']) ? trim((string) $validated['address']) : null,
            'survey_note' => isset($validated['survey_note']) ? trim((string) $validated['survey_note']) : null,
            'copyright_text' => isset($validated['copyright_text']) ? trim((string) $validated['copyright_text']) : null,
        ]);

        return back()->with('success', 'Pengaturan identitas website, navbar, dan footer berhasil diperbarui.');
    }
}