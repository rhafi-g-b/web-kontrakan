<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $guarded = ['id'];

    public static function current(): self
    {
        $defaults = [
            'site_name' => 'Kontrakan Ibu',
            'site_tagline' => 'Hunian Sewa Bulanan Terpadu',
            'hero_title' => 'Hunian sewa bulanan yang tenang, bersih, dan terawat.',
            'hero_subtitle' => 'Lingkungan nyaman untuk beristirahat dengan akses mudah dan pengelolaan langsung yang rapi.',
            'hero_slide_1_label' => 'Tampak Depan & Lingkungan',
            'hero_slide_2_label' => 'Area Parkir & Teras Unit',
            'hero_slide_3_label' => 'Bagian Dalam Ruangan',
            'whatsapp_number' => '081312132075',
            'footer_description' => 'Penyedia hunian kontrakan bulanan yang mengutamakan kebersihan lingkungan, keamanan penghuni, dan kemudahan komunikasi langsung dengan pengelola.',
            'survey_hours' => 'Setiap Hari: 08.00 – 20.00 WIB',
            'address' => '',
            'survey_note' => '*Mohon konfirmasi melalui WhatsApp terlebih dahulu sebelum datang ke lokasi agar pengelola dapat menyiapkan kunci pintu yang ingin dilihat.',
            'copyright_text' => 'Sistem Informasi Manajemen Kontrakan & Portal Penghuni',
        ];

        if (! Schema::hasTable('site_settings')) {
            return new self($defaults);
        }

        $setting = self::query()->first();

        if ($setting instanceof self) {
            return $setting;
        }

        return self::query()->create($defaults);
    }

    public function toSharedArray(): array
    {
        $name = (string) ($this->site_name ?: 'Kontrakan Ibu');

        return [
            'site_name' => $name,
            'site_initial' => mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: 'K',
            'site_tagline' => (string) ($this->site_tagline ?? 'Hunian Sewa Bulanan Terpadu'),
            'logo_url' => $this->logo_path ? Storage::url($this->logo_path) : null,
            'hero_title' => (string) ($this->hero_title ?: 'Hunian sewa bulanan yang tenang, bersih, dan terawat.'),
            'hero_subtitle' => (string) ($this->hero_subtitle ?: 'Lingkungan nyaman untuk beristirahat dengan akses mudah dan pengelolaan langsung yang rapi.'),
            'hero_slides' => [
                [
                    'label' => (string) ($this->hero_slide_1_label ?: 'Tampak Depan & Lingkungan'),
                    'photo_url' => $this->hero_slide_1_path ? Storage::url($this->hero_slide_1_path) : null,
                    'bgClass' => 'bg-slate-900',
                ],
                [
                    'label' => (string) ($this->hero_slide_2_label ?: 'Area Parkir & Teras Unit'),
                    'photo_url' => $this->hero_slide_2_path ? Storage::url($this->hero_slide_2_path) : null,
                    'bgClass' => 'bg-slate-950',
                ],
                [
                    'label' => (string) ($this->hero_slide_3_label ?: 'Bagian Dalam Ruangan'),
                    'photo_url' => $this->hero_slide_3_path ? Storage::url($this->hero_slide_3_path) : null,
                    'bgClass' => 'bg-neutral-900',
                ],
            ],
            'whatsapp_number' => (string) ($this->whatsapp_number ?: '081312132075'),
            'footer_description' => (string) ($this->footer_description ?: 'Penyedia hunian kontrakan bulanan yang mengutamakan kebersihan lingkungan, keamanan penghuni, dan kemudahan komunikasi langsung dengan pengelola.'),
            'survey_hours' => (string) ($this->survey_hours ?: 'Setiap Hari: 08.00 – 20.00 WIB'),
            'address' => (string) ($this->address ?? ''),
            'survey_note' => (string) ($this->survey_note ?? '*Mohon konfirmasi melalui WhatsApp terlebih dahulu sebelum datang ke lokasi agar pengelola dapat menyiapkan kunci pintu yang ingin dilihat.'),
            'copyright_text' => (string) ($this->copyright_text ?: 'Sistem Informasi Manajemen Kontrakan & Portal Penghuni'),
        ];
    }
}