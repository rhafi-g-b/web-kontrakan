<script lang="ts">
    import { page } from '@inertiajs/svelte';

    interface SharedSiteSettings {
        site_name: string;
        site_initial: string;
        logo_url: string | null;
        whatsapp_number: string;
        footer_description: string;
        survey_hours: string;
        address: string;
        survey_note: string;
        copyright_text: string;
    }

    let {
        adminPhone = '081312132075',
    }: {
        adminPhone?: string;
    } = $props();

    const site = $derived(
        (page.props.siteSettings as SharedSiteSettings | undefined) ?? {
            site_name: 'Kontrakan Ibu',
            site_initial: 'K',
            logo_url: null,
            whatsapp_number: adminPhone,
            footer_description:
                'Penyedia hunian kontrakan bulanan yang mengutamakan kebersihan lingkungan, keamanan penghuni, dan kemudahan komunikasi langsung dengan pengelola.',
            survey_hours: 'Setiap Hari: 08.00 – 20.00 WIB',
            address: '',
            survey_note:
                '*Mohon konfirmasi melalui WhatsApp terlebih dahulu sebelum datang ke lokasi agar pengelola dapat menyiapkan kunci pintu yang ingin dilihat.',
            copyright_text: 'Sistem Informasi Manajemen Kontrakan & Portal Penghuni',
        }
    );

    function buildWhatsAppUrl(): string {
        const phoneToUse = site.whatsapp_number || adminPhone;
        const cleaned = phoneToUse.replace(/[^0-9]/g, '');
        const internationalPhone = cleaned.startsWith('0') ? `62${cleaned.slice(1)}` : cleaned;
        const text = `Halo pengelola ${site.site_name}, saya ingin bertanya mengenai informasi sewa pintu kontrakan.`;
        return `https://wa.me/${internationalPhone}?text=${encodeURIComponent(text)}`;
    }
</script>

<footer id="kontak-lokasi" class="bg-slate-950 text-slate-400">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 border-b border-slate-800 pb-12 md:grid-cols-12">
            <div class="space-y-4 md:col-span-5">
                <div class="flex items-center gap-3">
                    {#if site.logo_url}
                        <img
                            src={site.logo_url}
                            alt={site.site_name}
                            class="h-9 w-9 rounded-xl bg-white/10 object-contain p-1"
                        />
                    {:else}
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 font-extrabold text-white">
                            {site.site_initial}
                        </div>
                    {/if}
                    <span class="text-lg font-bold text-white">{site.site_name}</span>
                </div>
                <p class="max-w-sm text-xs leading-relaxed text-slate-400 sm:text-sm">
                    {site.footer_description}
                </p>
                {#if site.address}
                    <p class="max-w-sm text-xs text-slate-300">
                        <strong class="text-white">Lokasi:</strong> {site.address}
                    </p>
                {/if}
                <div class="pt-1">
                    <a
                        href={buildWhatsAppUrl()}
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-500"
                    >
                        <span>Chat Pengelola ({site.whatsapp_number})</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <div class="space-y-3 md:col-span-3">
                <p class="text-xs font-bold tracking-wider text-white uppercase">Navigasi Halaman</p>
                <ul class="space-y-2.5 text-xs sm:text-sm">
                    <li>
                        <a href="#katalog-pintu" class="transition hover:text-white">Daftar Pintu Kontrakan</a>
                    </li>
                    <li>
                        <a href="#alur-sewa" class="transition hover:text-white">Prosedur & Cara Sewa</a>
                    </li>
                    <li>
                        <a href="/login" class="transition hover:text-white">Login Portal Penghuni</a>
                    </li>
                    <li>
                        <a href="/login" class="transition hover:text-white">Panel Pengelola (Admin)</a>
                    </li>
                </ul>
            </div>

            <div class="space-y-3 md:col-span-4">
                <p class="text-xs font-bold tracking-wider text-white uppercase">Informasi Survei & Layanan</p>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-xs leading-relaxed">
                    <p class="font-semibold text-slate-200">Jam Kunjungan / Survei Pintu:</p>
                    <p class="mt-0.5 text-slate-400">{site.survey_hours}</p>
                    <p class="mt-3 font-semibold text-slate-200">Kontak WhatsApp Resmi:</p>
                    <p class="mt-0.5 text-blue-400">{site.whatsapp_number}</p>
                    {#if site.survey_note}
                        <p class="mt-3 text-[11px] text-slate-500">
                            {site.survey_note}
                        </p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-col items-center justify-between gap-4 text-xs text-slate-500 sm:flex-row">
            <p>&copy; {new Date().getFullYear()} {site.site_name}. Seluruh hak cipta dilindungi.</p>
            <p>{site.copyright_text}</p>
        </div>
    </div>
</footer>