<script lang="ts">
    import { onMount } from 'svelte';
    import { page } from '@inertiajs/svelte';

    interface SharedSiteSettings {
        site_name: string;
        site_initial: string;
        site_tagline: string;
        logo_url: string | null;
        whatsapp_number: string;
    }

    let {
        adminPhone = '081312132075',
    }: {
        adminPhone?: string;
    } = $props();

    let isScrolled = $state(false);
    let mobileNavOpen = $state(false);

    const site = $derived(
        (page.props.siteSettings as SharedSiteSettings | undefined) ?? {
            site_name: 'Kontrakan Ibu',
            site_initial: 'K',
            site_tagline: 'Hunian Sewa Bulanan Terpadu',
            logo_url: null,
            whatsapp_number: adminPhone,
        }
    );

    onMount(() => {
        const handleScroll = () => {
            isScrolled = window.scrollY > 24;
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    });

    function buildWhatsAppUrl(): string {
        const phoneToUse = site.whatsapp_number || adminPhone;
        const cleaned = phoneToUse.replace(/[^0-9]/g, '');
        const internationalPhone = cleaned.startsWith('0') ? `62${cleaned.slice(1)}` : cleaned;
        const text = `Halo pengelola ${site.site_name}, saya ingin bertanya mengenai ketersediaan pintu kontrakan yang kosong saat ini.`;
        return `https://wa.me/${internationalPhone}?text=${encodeURIComponent(text)}`;
    }
</script>

<header
    class="fixed top-0 right-0 left-0 z-50 transition-all duration-300 {isScrolled
        ? 'border-b border-slate-200/80 bg-white/95 py-3 shadow-xs backdrop-blur-md'
        : 'bg-gradient-to-b from-slate-950/90 to-transparent py-5'}"
>
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="/" class="flex items-center gap-3">
            {#if site.logo_url}
                <img
                    src={site.logo_url}
                    alt={site.site_name}
                    class="h-10 w-10 rounded-xl bg-white/10 object-contain p-1 shadow-xs"
                />
            {:else}
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-base font-extrabold text-white shadow-md shadow-blue-600/30">
                    {site.site_initial}
                </div>
            {/if}
            <div>
                <p class="text-base font-bold tracking-tight transition-colors {isScrolled ? 'text-slate-950' : 'text-white'}">
                    {site.site_name}
                </p>
                {#if site.site_tagline}
                    <p class="text-[11px] font-medium transition-colors {isScrolled ? 'text-slate-500' : 'text-slate-300'}">
                        {site.site_tagline}
                    </p>
                {/if}
            </div>
        </a>

        <nav class="hidden items-center gap-7 text-xs font-semibold tracking-wide uppercase md:flex {isScrolled ? 'text-slate-600' : 'text-slate-200'}">
            <a href="#katalog-pintu" class="transition hover:text-blue-500">Katalog Pintu</a>
            <a href="#alur-sewa" class="transition hover:text-blue-500">Cara Menyewa</a>
            <a href="#kontak-lokasi" class="transition hover:text-blue-500">Kontak & Survei</a>
        </nav>

        <div class="flex items-center gap-2.5">
            {#if page.props.auth?.user}
                <a
                    href={page.props.auth.user.role === 'admin' ? '/admin/dashboard' : '/tenant/dashboard'}
                    class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-blue-700 sm:text-sm"
                >
                    Dashboard Saya &rarr;
                </a>
            {:else}
                <a
                    href={buildWhatsAppUrl()}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="hidden rounded-xl border px-3.5 py-2 text-xs font-semibold transition sm:inline-flex {isScrolled
                        ? 'border-slate-300 text-slate-700 hover:bg-slate-100'
                        : 'border-white/25 bg-white/10 text-white backdrop-blur-xs hover:bg-white/20'}"
                >
                    Hubungi WA
                </a>
                <a
                    href="/login"
                    class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-blue-600/25 transition hover:-translate-y-0.5 hover:bg-blue-500 sm:text-sm"
                >
                    Masuk Portal
                </a>
            {/if}

            <button
                type="button"
                onclick={() => (mobileNavOpen = !mobileNavOpen)}
                aria-label="Toggle Menu"
                class="rounded-xl border p-2 md:hidden {isScrolled
                    ? 'border-slate-200 text-slate-700'
                    : 'border-white/20 text-white'}"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    {#if mobileNavOpen}
        <div class="mx-4 mt-3 rounded-2xl border border-slate-800 bg-slate-950 p-4 text-slate-200 shadow-xl md:hidden">
            <nav class="flex flex-col space-y-2.5 text-xs font-semibold uppercase">
                <a href="#katalog-pintu" onclick={() => (mobileNavOpen = false)} class="rounded-lg px-3 py-2 hover:bg-slate-900">
                    Katalog Pintu
                </a>
                <a href="#alur-sewa" onclick={() => (mobileNavOpen = false)} class="rounded-lg px-3 py-2 hover:bg-slate-900">
                    Cara Menyewa
                </a>
                <a href="#kontak-lokasi" onclick={() => (mobileNavOpen = false)} class="rounded-lg px-3 py-2 hover:bg-slate-900">
                    Kontak & Survei
                </a>
            </nav>
        </div>
    {/if}
</header>