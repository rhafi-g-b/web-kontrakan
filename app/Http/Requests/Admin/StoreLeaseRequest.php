<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'start_date' => ['required', 'date'],
            'ktp_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_id.required' => 'Pintu kontrakan wajib dipilih.',
            'name.required' => 'Nama lengkap penghuni wajib diisi.',
            'phone.required' => 'Nomor WhatsApp penghuni wajib diisi.',
            'phone.unique' => 'Nomor WhatsApp ini sudah terdaftar pada akun lain.',
            'password.required' => 'Kata sandi awal penghuni wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
            'start_date.required' => 'Tanggal mulai sewa wajib diisi.',
            'ktp_photo.required' => 'Foto KTP penghuni wajib diunggah.',
            'ktp_photo.image' => 'File KTP harus berupa gambar.',
            'ktp_photo.max' => 'Ukuran foto KTP maksimal 10MB.',
        ];
    }
}