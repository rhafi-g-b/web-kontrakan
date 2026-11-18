<script lang="ts">
    import { router, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';

    interface HeroSlideSettingItem {
        label: string;
        photo_url: string | null;
        bgClass: string;
    }

    interface SiteSettingsData {
        site_name: string;
        site_initial: string;
        site_tagline: string;
        logo_url: string | null;
        hero_title: string;
        hero_subtitle: string;
        hero_slides: HeroSlideSettingItem[];
        whatsapp_number: string;
        footer_description: string;
        survey_hours: string;
        address: string;
        survey_note: string;
        copyright_text: string;
    }

    let {
        settings,
    }: {
        settings: SiteSettingsData;
    } = $props();

    let siteName = $state('');
    let siteTagline = $state('');
    let heroTitle = $state('');
    let heroSubtitle = $state('');

    let slide1Label = $state('');
    let slide2Label = $state('');
    let slide3Label = $state('');

    let removeSlide1 = $state(false);
    let removeSlide2 = $state(false);
    let removeSlide3 = $state(false);

    let slide1Input = $state<HTMLInputElement | null>(null);
    let slide2Input = $state<HTMLInputElement | null>(null);
    let slide3Input = $state<HTMLInputElement | null>(null);

    let whatsappNumber = $state('');
    let footerDescription = $state('');
    let surveyHours = $state('');
    let address = $state('');
    let surveyNote = $state('');
    let copyrightText = $state('');
    let removeLogo = $state(false);
    let logoInput = $state<HTMLInputElement | null>(null);
    let isSubmitting = $state(false);

    const errors = $derived((page.props.errors ?? {}) as Record<string, string>);

    $effect(() => {
        siteName = settings.site_name;
        siteTagline = settings.site_tagline;
        heroTitle = settings.hero_title;
        heroSubtitle = settings.hero_subtitle;
        slide1Label = settings.hero_slides?.[0]?.label ?? 'Tampak Depan & Lingkungan';
        slide2Label = settings.hero_slides?.[1]?.label ?? 'Area Parkir & Teras Unit';
        slide3Label = settings.hero_slides?.[2]?.label ?? 'Bagian Dalam Ruangan';
        whatsappNumber = settings.whatsapp_number;
        footerDescription = settings.footer_description;
        surveyHours = settings.survey_hours;
        address = settings.address;
        surveyNote = settings.survey_note;
        copyrightText = settings.copyright_text;
    });

    function submitSettings(event: SubmitEvent): void {
        event.preventDefault();
        isSubmitting = true;

        const formData = new FormData();
        formData.append('site_name', siteName);
        formData.append('site_tagline', siteTagline);
        formData.append('hero_title', heroTitle);
        formData.append('hero_subtitle', heroSubtitle);

        formData.append('hero_slide_1_label', slide1Label);
        formData.append('hero_slide_2_label', slide2Label);
        formData.append('hero_slide_3_label', slide3Label);

        formData.append('remove_hero_slide_1', removeSlide1 ? '1' : '0');
        formData.append('remove_hero_slide_2', removeSlide2 ? '1' : '0');
        formData.append('remove_hero_slide_3', removeSlide3 ? '1' : '0');

        const rawSlide1 = slide1Input?.files?.[0];
        if (rawSlide1) {
            formData.append('hero_slide_1', rawSlide1);
        }

        const rawSlide2 = slide2Input?.files?.[0];
        if (rawSlide2) {
            formData.append('hero_slide_2', rawSlide2);
        }

        const rawSlide3 = slide3Input?.files?.[0];
        if (rawSlide3) {
            formData.append('hero_slide_3', rawSlide3);
        }

        formData.append('whatsapp_number', whatsappNumber);
        formData.append('footer_description', footerDescription);
        formData.append('survey_hours', surveyHours);
        formData.append('address', address);
        formData.append('survey_note', surveyNote);
        formData.append('copyright_text', copyrightText);
        formData.append('remove_logo', removeLogo ? '1' : '0');

        const rawLogo = logoInput?.files?.[0];
        if (rawLogo) {
            formData.append('logo', rawLogo);
        }

        router.post('/admin/settings', formData, {
            onFinish: () => {
                isSubmitting = false;
                removeLogo = false;
                removeSlide1 = false;
                removeSlide2 = false;
                removeSlide3 = false;
                if (logoInput) logoInput.value = '';
                if (slide1Input) slide1Input.value = '';
                if (slide2Input) slide2Input.value = '';
                if (slide3Input) slide3Input.value = '';
            },
        });
    }
</script>

<AppHead title="Pengaturan Website - Panel Admin" />

<AdminLayout
    title="Pengaturan Website & Identitas"
    subtitle="Atur logo, nama kontrakan, 3 foto slide hero, nomor WhatsApp, dan informasi footer"
    activeMenu="settings"
>
    <form onsubmit={submitSettings} class="space-y-6">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs sm:p-6">
            <h2 class="text-base font-bold text-slate-900">1. Identitas Kontrakan & Navbar</h2>
            <p class="text-xs text-slate-500">Tampil pada pojok kiri atas Navbar, Sidebar Admin, dan Portal Penghuni</p>

            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="site_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Nama Kontrakan
                    </label>
                    <input
                        id="site_name"
                        type="text"
                        bind:value={siteName}
                        required
                        placeholder="Contoh: Kontrakan Ibu"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                    />
                    {#if errors.site_name}
                        <p class="mt-1 text-xs font-medium text-rose-600">{errors.site_name}</p>
                    {/if}
                </div>

                <div>
                    <label for="site_tagline" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Slogan / Subjudul Navbar
                    </label>
                    <input
                        id="site_tagline"
                        type="text"
                        bind:value={siteTagline}
                        placeholder="Contoh: Hunian Sewa Bulanan Terpadu"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                    />
                    {#if errors.site_tagline}
                        <p class="mt-1 text-xs font-medium text-rose-600">{errors.site_tagline}</p>
                    {/if}
                </div>

                <div class="sm:col-span-2">
                    <label for="logo" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Logo Kontrakan (Opsional)
                    </label>
                    <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center">
                        {#if settings.logo_url && !removeLogo}
                            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-gray-50 p-2.5">
                                <img
                                    src={settings.logo_url}
                                    alt={settings.site_name}
                                    class="h-10 w-10 rounded-lg object-contain"
                                />
                                <button
                                    type="button"
                                    onclick={() => (removeLogo = true)}
                                    class="cursor-pointer rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-600 hover:text-white"
                                >
                                    Hapus Logo
                                </button>
                            </div>
                        {:else}
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 font-bold text-white">
                                {siteName.trim().charAt(0).toUpperCase() || 'K'}
                            </div>
                        {/if}

                        <div class="flex-1">
                            <input
                                id="logo"
                                type="file"
                                bind:this={logoInput}
                                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                class="block w-full text-xs text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3.5 file:py-2 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p class="mt-1 text-[11px] text-slate-400">
                                Jika tidak mengunggah gambar logo, sistem otomatis menampilkan ikon huruf inisial nama kontrakan.
                            </p>
                        </div>
                    </div>
                    {#if errors.logo}
                        <p class="mt-1 text-xs font-medium text-rose-600">{errors.logo}</p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs sm:p-6">
            <h2 class="text-base font-bold text-slate-900">2. Teks & 3 Foto Berjalan (Slide Hero)</h2>
            <p class="text-xs text-slate-500">
                Atur kalimat sambutan dan 3 foto latar belakang berjalan di halaman utama. Jika foto belum diunggah, otomatis tampil sebagai placeholder.
            </p>

            <div class="mt-5 space-y-4">
                <div>
                    <label for="hero_title" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Judul Utama Hero
                    </label>
                    <input
                        id="hero_title"
                        type="text"
                        bind:value={heroTitle}
                        required
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                    />
                    {#if errors.hero_title}
                        <p class="mt-1 text-xs font-medium text-rose-600">{errors.hero_title}</p>
                    {/if}
                </div>

                <div>
                    <label for="hero_subtitle" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Deskripsi Singkat Hero
                    </label>
                    <textarea
                        id="hero_subtitle"
                        rows="2"
                        bind:value={heroSubtitle}
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                    ></textarea>
                    {#if errors.hero_subtitle}
                        <p class="mt-1 text-xs font-medium text-rose-600">{errors.hero_subtitle}</p>
                    {/if}
                </div>

                <div class="grid grid-cols-1 gap-4 pt-3 lg:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-gray-50/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-800">Slide Foto 1</p>

                        <div class="mt-2.5 overflow-hidden rounded-xl border border-slate-200 bg-slate-900">
                            {#if settings.hero_slides?.[0]?.photo_url && !removeSlide1}
                                <img
                                    src={settings.hero_slides[0].photo_url}
                                    alt={slide1Label}
                                    class="h-32 w-full object-cover"
                                />
                            {:else}
                                <div class="flex h-32 items-center justify-center p-3 text-center">
                                    <span class="text-[11px] font-medium uppercase tracking-widest text-white/30">
                                        Placeholder — {slide1Label || 'Slide 1'}
                                    </span>
                                </div>
                            {/if}
                        </div>

                        {#if settings.hero_slides?.[0]?.photo_url && !removeSlide1}
                            <button
                                type="button"
                                onclick={() => (removeSlide1 = true)}
                                class="mt-2 w-full cursor-pointer rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-600 hover:text-white"
                            >
                                Hapus Foto (Gunakan Placeholder)
                            </button>
                        {/if}

                        <div class="mt-3">
                            <label for="slide_1_label" class="block text-[11px] font-semibold uppercase text-slate-600">
                                Keterangan Slide 1
                            </label>
                            <input
                                id="slide_1_label"
                                type="text"
                                bind:value={slide1Label}
                                placeholder="Tampak Depan & Lingkungan"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-blue-600 focus:outline-none"
                            />
                        </div>

                        <div class="mt-3">
                            <label for="slide_1_file" class="block text-[11px] font-semibold uppercase text-slate-600">
                                Unggah / Ganti Foto 1
                            </label>
                            <input
                                id="slide_1_file"
                                type="file"
                                bind:this={slide1Input}
                                accept="image/png,image/jpeg,image/webp"
                                class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                            />
                            {#if errors.hero_slide_1}
                                <p class="mt-1 text-xs font-medium text-rose-600">{errors.hero_slide_1}</p>
                            {/if}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-gray-50/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-800">Slide Foto 2</p>

                        <div class="mt-2.5 overflow-hidden rounded-xl border border-slate-200 bg-slate-950">
                            {#if settings.hero_slides?.[1]?.photo_url && !removeSlide2}
                                <img
                                    src={settings.hero_slides[1].photo_url}
                                    alt={slide2Label}
                                    class="h-32 w-full object-cover"
                                />
                            {:else}
                                <div class="flex h-32 items-center justify-center p-3 text-center">
                                    <span class="text-[11px] font-medium uppercase tracking-widest text-white/30">
                                        Placeholder — {slide2Label || 'Slide 2'}
                                    </span>
                                </div>
                            {/if}
                        </div>

                        {#if settings.hero_slides?.[1]?.photo_url && !removeSlide2}
                            <button
                                type="button"
                                onclick={() => (removeSlide2 = true)}
                                class="mt-2 w-full cursor-pointer rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-600 hover:text-white"
                            >
                                Hapus Foto (Gunakan Placeholder)
                            </button>
                        {/if}

                        <div class="mt-3">
                            <label for="slide_2_label" class="block text-[11px] font-semibold uppercase text-slate-600">
                                Keterangan Slide 2
                            </label>
                            <input
                                id="slide_2_label"
                                type="text"
                                bind:value={slide2Label}
                                placeholder="Area Parkir & Teras Unit"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-blue-600 focus:outline-none"
                            />
                        </div>

                        <div class="mt-3">
                            <label for="slide_2_file" class="block text-[11px] font-semibold uppercase text-slate-600">
                                Unggah / Ganti Foto 2
                            </label>
                            <input
                                id="slide_2_file"
                                type="file"
                                bind:this={slide2Input}
                                accept="image/png,image/jpeg,image/webp"
                                class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                            />
                            {#if errors.hero_slide_2}
                                <p class="mt-1 text-xs font-medium text-rose-600">{errors.hero_slide_2}</p>
                            {/if}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-gray-50/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-800">Slide Foto 3</p>

                        <div class="mt-2.5 overflow-hidden rounded-xl border border-slate-200 bg-neutral-900">
                            {#if settings.hero_slides?.[2]?.photo_url && !removeSlide3}
                                <img
                                    src={settings.hero_slides[2].photo_url}
                                    alt={slide3Label}
                                    class="h-32 w-full object-cover"
                                />
                            {:else}
                                <div class="flex h-32 items-center justify-center p-3 text-center">
                                    <span class="text-[11px] font-medium uppercase tracking-widest text-white/30">
                                        Placeholder — {slide3Label || 'Slide 3'}
                                    </span>
                                </div>
                            {/if}
                        </div>

                        {#if settings.hero_slides?.[2]?.photo_url && !removeSlide3}
                            <button
                                type="button"
                                onclick={() => (removeSlide3 = true)}
                                class="mt-2 w-full cursor-pointer rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-600 hover:text-white"
                            >
                                Hapus Foto (Gunakan Placeholder)
                            </button>
                        {/if}

                        <div class="mt-3">
                            <label for="slide_3_label" class="block text-[11px] font-semibold uppercase text-slate-600">
                                Keterangan Slide 3
                            </label>
                            <input
                                id="slide_3_label"
                                type="text"
                                bind:value={slide3Label}
                                placeholder="Bagian Dalam Ruangan"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-blue-600 focus:outline-none"
                            />
                        </div>

                        <div class="mt-3">
                            <label for="slide_3_file" class="block text-[11px] font-semibold uppercase text-slate-600">
                                Unggah / Ganti Foto 3
                            </label>
                            <input
                                id="slide_3_file"
                                type="file"
                                bind:this={slide3Input}
                                accept="image/png,image/jpeg,image/webp"
                                class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                            />
                            {#if errors.hero_slide_3}
                                <p class="mt-1 text-xs font-medium text-rose-600">{errors.hero_slide_3}</p>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs sm:p-6">
            <h2 class="text-base font-bold text-slate-900">3. Informasi Kontak, Survei & Footer</h2>
            <p class="text-xs text-slate-500">Mengatur nomor tujuan tombol WhatsApp serta rincian informasi di bagian bawah halaman</p>

            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="whatsapp_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Nomor WhatsApp Resmi Pengelola
                    </label>
                    <input
                        id="whatsapp_number"
                        type="tel"
                        bind:value={whatsappNumber}
                        required
                        placeholder="081312132075"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                    />
                    {#if errors.whatsapp_number}
                        <p class="mt-1 text-xs font-medium text-rose-600">{errors.whatsapp_number}</p>
                    {/if}
                </div>

                <div>
                    <label for="survey_hours" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Jam Kunjungan / Survei Lokasi
                    </label>
                    <input
                        id="survey_hours"
                        type="text"
                        bind:value={surveyHours}
                        placeholder="Setiap Hari: 08.00 – 20.00 WIB"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label for="address" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Alamat Lengkap Kontrakan (Opsional)
                    </label>
                    <input
                        id="address"
                        type="text"
                        bind:value={address}
                        placeholder="Contoh: Jl. Merdeka No. 12, Kota Tasikmalaya"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label for="footer_description" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Deskripsi Singkat di Footer
                    </label>
                    <textarea
                        id="footer_description"
                        rows="2"
                        bind:value={footerDescription}
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                    ></textarea>
                </div>

                <div class="sm:col-span-2">
                    <label for="survey_note" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Catatan Tambahan Survei di Footer
                    </label>
                    <input
                        id="survey_note"
                        type="text"
                        bind:value={surveyNote}
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label for="copyright_text" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Keterangan Kaki Footer (Sebelah Kanan Copyright)
                    </label>
                    <input
                        id="copyright_text"
                        type="text"
                        bind:value={copyrightText}
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end border-t border-slate-100 pt-4">
                <button
                    type="submit"
                    disabled={isSubmitting}
                    class="w-full cursor-pointer rounded-xl bg-blue-600 px-6 py-3 text-xs font-bold text-white shadow-xs transition hover:bg-blue-700 disabled:opacity-50 sm:w-auto sm:text-sm"
                >
                    {isSubmitting ? 'Menyimpan Perubahan...' : 'Simpan Pengaturan Website'}
                </button>
            </div>
        </div>
    </form>
</AdminLayout>