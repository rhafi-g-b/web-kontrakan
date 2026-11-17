<script lang="ts">
    import { page } from '@inertiajs/svelte';

    interface SharedSiteSettings {
        site_name: string;
        whatsapp_number: string;
    }

    const site = $derived(
        (page.props.siteSettings as SharedSiteSettings | undefined) ?? {
            site_name: 'Kontrakan Ibu',
            whatsapp_number: '081312132075',
        }
    );

    const steps = [
        {
            number: '01',
            phase: 'Tahap Awal',
            title: 'Cek Ketersediaan & Survei Lokasi',
            description:
                'Lihat pintu yang berstatus Tersedia pada katalog di atas, lalu hubungi pengelola melalui WhatsApp untuk menjadwalkan kunjungan langsung ke lokasi.',
        },
        {
            number: '02',
            phase: 'Administrasi Mudah',
            title: 'Serah Terima Kunci & Aktivasi Akun',
            description:
                'Setelah cocok dengan unit pilihan Anda, cukup serahkan foto KTP kepada pengelola. Akun Portal Penghuni Anda akan langsung diaktifkan menggunakan nomor WhatsApp.',
        },
        {
            number: '03',
            phase: 'Selama Menghuni',
            title: 'Kelola Tagihan & Layanan Mandiri',
            description:
                'Pantau tagihan bulanan, unggah bukti transfer atau catat pembayaran tunai, hingga kirim laporan perbaikan fasilitas langsung dari ponsel Anda.',
        },
    ];

    const portalFeatures = [
        {
            title: 'Riwayat Pembayaran Tersimpan Rapi',
            desc: 'Tidak perlu khawatir kuitansi kertas terselip. Status pembayaran tunai maupun transfer tercatat jelas setiap bulannya.',
        },
        {
            title: 'Lapor Kerusakan Tanpa Ribet',
            desc: 'Kran bocor atau lampu bermasalah? Cukup kirim foto dan keterangan lewat portal, lalu pantau status perbaikannya.',
        },
        {
            title: 'Akses Praktis dengan Nomor WA',
            desc: 'Masuk ke portal kapan saja cukup menggunakan nomor WhatsApp aktif Anda tanpa perlu menghafal username yang rumit.',
        },
    ];

    function buildWhatsAppUrl(): string {
        const cleaned = site.whatsapp_number.replace(/[^0-9]/g, '');
        const internationalPhone = cleaned.startsWith('0') ? `62${cleaned.slice(1)}` : cleaned;
        const text = `Halo pengelola ${site.site_name}, saya ingin bertanya mengenai prosedur sewa pintu kontrakan.`;
        return `https://wa.me/${internationalPhone}?text=${encodeURIComponent(text)}`;
    }
</script>

<section id="alur-sewa" class="border-y border-slate-200 bg-white py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-12 lg:grid-cols-12 lg:items-start">
            <div class="lg:col-span-7">
                <p class="text-xs font-bold tracking-widest text-blue-600 uppercase">
                    Alur Sewa & Layanan
                </p>
                <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    Proses Menyewa yang Jelas dari Awal hingga Menghuni
                </h2>
                <p class="mt-2 max-w-xl text-xs leading-relaxed text-slate-600 sm:text-sm">
                    Kami menggabungkan keramahan pengelolaan langsung dengan sistem pencatatan digital agar Anda merasa tenang selama tinggal di {site.site_name}.
                </p>

                <div class="relative mt-10 space-y-8 before:absolute before:top-4 before:bottom-4 before:left-5 before:w-px before:bg-slate-200">
                    {#each steps as step}
                        <div class="relative flex items-start gap-5">
                            <div class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-xs font-extrabold text-slate-900 shadow-2xs">
                                {step.number}
                            </div>

                            <div class="flex-1 rounded-2xl border border-slate-200/80 bg-[#f8fafc] p-5 transition duration-200 hover:border-slate-300">
                                <span class="text-[11px] font-bold tracking-wider text-blue-600 uppercase">
                                    {step.phase}
                                </span>
                                <h3 class="mt-1 text-base font-bold text-slate-900">
                                    {step.title}
                                </h3>
                                <p class="mt-1.5 text-xs leading-relaxed text-slate-600 sm:text-sm">
                                    {step.description}
                                </p>
                            </div>
                        </div>
                    {/each}
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="rounded-3xl bg-slate-950 p-6 text-white shadow-xl sm:p-8">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-5">
                        <div>
                            <p class="text-[11px] font-bold tracking-widest text-blue-400 uppercase">
                                Fasilitas Digital Penghuni
                            </p>
                            <h3 class="mt-1 text-lg font-bold text-white sm:text-xl">
                                Portal Khusus Penghuni
                            </h3>
                        </div>
                        <span class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs font-semibold text-slate-300">
                            24/7 Online
                        </span>
                    </div>

                    <p class="mt-4 text-xs leading-relaxed text-slate-400 sm:text-sm">
                        Setiap penghuni aktif mendapatkan akses ke dashboard pribadi untuk mempermudah urusan bulanan tanpa harus bolak-balik konfirmasi manual:
                    </p>

                    <div class="mt-6 space-y-4">
                        {#each portalFeatures as feat}
                            <div class="rounded-2xl border border-slate-800/90 bg-slate-900/70 p-4">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-600/20 text-xs font-bold text-blue-400">
                                        ✓
                                    </span>
                                    <div>
                                        <h4 class="text-xs font-bold text-white sm:text-sm">
                                            {feat.title}
                                        </h4>
                                        <p class="mt-1 text-xs leading-relaxed text-slate-400">
                                            {feat.desc}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        {/each}
                    </div>

                    <div class="mt-7 flex flex-col gap-2.5 border-t border-slate-800 pt-5 sm:flex-row">
                        <a
                            href="/login"
                            class="flex-1 rounded-xl bg-blue-600 px-4 py-3 text-center text-xs font-bold text-white transition hover:bg-blue-500"
                        >
                            Masuk Portal Penghuni
                        </a>
                        <a
                            href={buildWhatsAppUrl()}
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex-1 rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-center text-xs font-semibold text-slate-200 transition hover:bg-slate-800"
                        >
                            Tanya Pengelola
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>