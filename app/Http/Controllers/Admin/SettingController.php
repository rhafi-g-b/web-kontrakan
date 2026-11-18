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
            'hero_slide_1_label' => ['nullable', 'string', 'max:100'],
            'hero_slide_2_label' => ['nullable', 'string', 'max:100'],
            'hero_slide_3_label' => ['nullable', 'string', 'max:100'],
            'hero_slide_1' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'hero_slide_2' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'hero_slide_3' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'remove_hero_slide_1' => ['nullable', 'boolean'],
            'remove_hero_slide_2' => ['nullable', 'boolean'],
            'remove_hero_slide_3' => ['nullable', 'boolean'],
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
            'hero_slide_1.max' => 'Ukuran foto Slide 1 maksimal 10MB.',
            'hero_slide_2.max' => 'Ukuran foto Slide 2 maksimal 10MB.',
            'hero_slide_3.max' => 'Ukuran foto Slide 3 maksimal 10MB.',
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

        $slidePaths = [
            1 => $setting->hero_slide_1_path,
            2 => $setting->hero_slide_2_path,
            3 => $setting->hero_slide_3_path,
        ];

        foreach ([1, 2, 3] as $num) {
            if ($request->boolean("remove_hero_slide_{$num}") && $slidePaths[$num] !== null) {
                Storage::disk('public')->delete($slidePaths[$num]);
                $slidePaths[$num] = null;
            }

            $slideFile = $request->file("hero_slide_{$num}");
            if ($slideFile instanceof UploadedFile) {
                if ($slidePaths[$num] !== null) {
                    Storage::disk('public')->delete($slidePaths[$num]);
                }
                $slidePaths[$num] = (string) $slideFile->store('hero-slides', 'public');
            }
        }

        $setting->update([
            'site_name' => trim((string) $validated['site_name']),
            'site_tagline' => isset($validated['site_tagline']) ? trim((string) $validated['site_tagline']) : null,
            'logo_path' => $logoPath,
            'hero_title' => trim((string) $validated['hero_title']),
            'hero_subtitle' => isset($validated['hero_subtitle']) ? trim((string) $validated['hero_subtitle']) : null,
            'hero_slide_1_path' => $slidePaths[1],
            'hero_slide_1_label' => isset($validated['hero_slide_1_label']) && trim((string) $validated['hero_slide_1_label']) !== ''
                ? trim((string) $validated['hero_slide_1_label'])
                : 'Tampak Depan & Lingkungan',
            'hero_slide_2_path' => $slidePaths[2],
            'hero_slide_2_label' => isset($validated['hero_slide_2_label']) && trim((string) $validated['hero_slide_2_label']) !== ''
                ? trim((string) $validated['hero_slide_2_label'])
                : 'Area Parkir & Teras Unit',
            'hero_slide_3_path' => $slidePaths[3],
            'hero_slide_3_label' => isset($validated['hero_slide_3_label']) && trim((string) $validated['hero_slide_3_label']) !== ''
                ? trim((string) $validated['hero_slide_3_label'])
                : 'Bagian Dalam Ruangan',
            'whatsapp_number' => trim((string) $validated['whatsapp_number']),
            'footer_description' => isset($validated['footer_description']) ? trim((string) $validated['footer_description']) : null,
            'survey_hours' => isset($validated['survey_hours']) ? trim((string) $validated['survey_hours']) : null,
            'address' => isset($validated['address']) ? trim((string) $validated['address']) : null,
            'survey_note' => isset($validated['survey_note']) ? trim((string) $validated['survey_note']) : null,
            'copyright_text' => isset($validated['copyright_text']) ? trim((string) $validated['copyright_text']) : null,
        ]);

        return back()->with('success', 'Pengaturan website dan foto slide Hero berhasil diperbarui.');
    }
}