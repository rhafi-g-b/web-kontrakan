<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UnitStatus;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        /** @var Unit $unit */
        $unit = $this->route('unit');

        return [
            'unit_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'unit_number')->ignore($unit->id),
            ],
            'monthly_price' => ['required', 'integer', 'min:100000', 'max:100000000'],
            'status' => ['required', Rule::enum(UnitStatus::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_number.required' => 'Nomor atau nama pintu wajib diisi.',
            'unit_number.unique' => 'Nomor pintu ini sudah digunakan pintu lain.',
            'monthly_price.required' => 'Harga sewa bulanan wajib diisi.',
            'monthly_price.min' => 'Harga sewa minimal Rp 100.000.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 10MB.',
            'photo.uploaded' => 'Gagal mengunggah foto. Pastikan ukuran foto tidak melebihi batas server.',
        ];
    }
}