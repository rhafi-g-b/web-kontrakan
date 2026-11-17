<script lang="ts">
    import { onMount } from 'svelte';
    import { page } from '@inertiajs/svelte';

    interface PublicUnitItem {
        id: number;
        unit_number: string;
        monthly_price: number;
        status: 'available' | 'occupied' | 'maintenance';
        description: string | null;
        photo_url: string | null;
    }

    interface HeroSlideItem {
        label: string;
        photo_url: string | null;
        bgClass: string;
    }

    interface SharedSiteSettings {
        site_name: string;
        hero_title: string;
        hero_subtitle: string;
        whatsapp_number: string;
    }

    let {
        units = [],
        adminPhone = '081312132075',
    }: {
        units?: PublicUnitItem[];
        adminPhone?: string;
    } = $props();

    let activeSlide = $state(0);

    const site = $derived(
        (page.props.siteSettings as SharedSiteSettings | undefined) ?? {
            site_name: 'Kontrakan Ibu',
            hero_title: 'Hunian sewa bulanan yang tenang, bersih, dan terawat.',
            hero_subtitle: 'Lingkungan nyaman untuk beristirahat dengan akses mudah dan pengelolaan langsung yang rapi.',
            whatsapp_number: adminPhone,
        }
    );

    const defaultSlides: HeroSlideItem[] = [
        {
            label: 'Tampak Depan & Lingkungan',
            photo_url: null,
            bgClass: 'bg-slate-900',
        },
        {
            label: 'Area Parkir & Teras Unit',
            photo_url: null,
            bgClass: 'bg-slate-950',
        },
        {
            label: 'Bagian Dalam Ruangan',
            photo_url: null,
            bgClass: 'bg-neutral-900',
        },
    ];

    const slides = $derived.by<HeroSlideItem[]>(() => {
        const uploaded = units.filter((u) => u.photo_url !== null && u.photo_url !== '');

        if (uploaded.length === 0) {
            return defaultSlides;
        }

        return uploaded.map((u) => ({
            label: u.unit_number,
            photo_url: u.photo_url,
            bgClass: 'bg-slate-950',
        }));
    });

    onMount(() => {
        const interval = setInterval(() => {
            if (slides.length > 0) {
                activeSlide = (activeSlide + 1) % slides.length;
            }
        }, 6000);

        return () => clearInterval(interval);
    });

    function buildWhatsAppUrl(): string {
        const phoneToUse = site.whatsapp_number || adminPhone;
        const cleaned = phoneToUse.replace(/[^0-9]/g, '');
        const internationalPhone = cleaned.startsWith('0') ? `62${cleaned.slice(1)}` : cleaned;
        const text = `Halo pengelola ${site.site_name}, saya ingin bertanya mengenai ketersediaan pintu kontrakan.`;
        return `https://wa.me/${internationalPhone}?text=${encodeURIComponent(text)}`;
    }
</script>

<section class="relative flex min-h-[85vh] items-end overflow-hidden bg-slate-950 pt-32 pb-12 sm:min-h-[88vh] sm:pb-16">
    <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden">
        {#each slides as slide, idx}
            <div
                class="absolute inset-0 transition-opacity duration-1000 ease-in-out {idx === activeSlide
                    ? 'opacity-100'
                    : 'opacity-0'}"
            >
                <div
                    class="relative h-full w-full {slide.bgClass} transition-transform duration-[8000ms] ease-out {idx === activeSlide
                        ? 'scale-105'
                        : 'scale-100'}"
                >
                    {#if slide.photo_url}
                        <img
                            src={slide.photo_url}
                            alt={slide.label}
                            class="h-full w-full object-cover"
                        />
                    {:else}
                        <div class="flex h-full w-full items-center justify-center p-6">
                            <span class="text-xs tracking-widest text-white/20 uppercase">
                                Placeholder Foto — {slide.label}
                            </span>
                        </div>
                    {/if}
                </div>
            </div>
        {/each}

        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/35 to-slate-950/20"></div>
    </div>

    <div class="relative z-10 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-10 lg:flex-row lg:items-end">
            <div class="max-w-2xl space-y-4">
                <p class="text-xs font-semibold tracking-widest text-slate-300 uppercase">
                    {site.site_name}
                </p>

                <h1 class="text-3xl leading-tight font-bold tracking-tight text-white sm:text-5xl">
                    {site.hero_title}
                </h1>

                {#if site.hero_subtitle}
                    <p class="max-w-xl text-sm leading-relaxed text-slate-300 sm:text-base">
                        {site.hero_subtitle}
                    </p>
                {/if}

                <div class="flex flex-wrap items-center gap-3 pt-3">
                    <a
                        href="#katalog-pintu"
                        class="rounded-xl bg-white px-5 py-3 text-xs font-bold text-slate-950 transition hover:bg-slate-200 sm:text-sm"
                    >
                        Lihat Daftar Pintu
                    </a>
                    <a
                        href={buildWhatsAppUrl()}
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-xl bg-white/10 px-5 py-3 text-xs font-semibold text-white backdrop-blur-xs transition hover:bg-white/20 sm:text-sm"
                    >
                        Hubungi Pengelola
                    </a>
                </div>
            </div>

            <div class="flex items-center justify-between gap-6 lg:justify-end">
                <span class="text-xs font-medium text-slate-300">
                    {slides[activeSlide]?.label}
                </span>

                <div class="flex items-center gap-2">
                    {#each slides as _, idx}
                        <button
                            type="button"
                            aria-label="Slide {idx + 1}"
                            onclick={() => (activeSlide = idx)}
                            class="h-1.5 cursor-pointer rounded-full transition-all duration-300 {idx === activeSlide
                                ? 'w-8 bg-white'
                                : 'w-2 bg-white/30 hover:bg-white/60'}"
                        ></button>
                    {/each}
                </div>
            </div>
        </div>
    </div>
</section>