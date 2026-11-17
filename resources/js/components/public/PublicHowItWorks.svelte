<script lang="ts">
    const steps = [
        {
            number: '01',
            badgeColor: 'bg-slate-900',
            title: 'Pilih Pintu & Survei Lokasi',
            description:
                'Lihat pintu yang berstatus Tersedia pada katalog di atas, lalu klik tombol WhatsApp untuk membuat janji lihat kondisi ruangan langsung di lokasi.',
        },
        {
            number: '02',
            badgeColor: 'bg-blue-600',
            title: 'Registrasi & Aktivasi Akun WA',
            description:
                'Saat cocok dan menyerahkan data KTP, pengelola akan mengaktifkan akun Portal Penghuni menggunakan nomor WhatsApp Anda beserta tanggal mulai sewa.',
        },
        {
            number: '03',
            badgeColor: 'bg-emerald-600',
            title: 'Upload Bukti & Lapor Keluhan',
            description:
                'Tiap bulan Anda bisa mengecek tagihan, mengunggah foto bukti transfer (atau bayar tunai), serta melapor jika ada fasilitas yang perlu diperbaiki.',
        },
    ];
</script>

<section id="alur-sewa" class="border-y border-slate-200 bg-white py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-xs font-bold tracking-widest text-blue-600 uppercase">Proses Mudah & Transparan</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                Cara Menyewa & Kemudahan Penghuni
            </h2>
            <p class="mt-2 text-xs leading-relaxed text-slate-600 sm:text-sm">
                Tidak perlu repot menyimpan kuitansi kertas yang mudah hilang. Setiap penghuni mendapatkan akun khusus di website ini.
            </p>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-3">
            {#each steps as step}
                <div class="rounded-2xl border border-slate-200/80 bg-[#f8fafc] p-6 transition duration-300 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl {step.badgeColor} text-sm font-extrabold text-white">
                        {step.number}
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-900">{step.title}</h3>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600 sm:text-sm">
                        {step.description}
                    </p>
                </div>
            {/each}
        </div>
    </div>
</section>