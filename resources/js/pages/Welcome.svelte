<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PublicFooter from '@/components/public/PublicFooter.svelte';
    import PublicHero from '@/components/public/PublicHero.svelte';
    import PublicHowItWorks from '@/components/public/PublicHowItWorks.svelte';
    import PublicNavbar from '@/components/public/PublicNavbar.svelte';
    import PublicUnitCatalog from '@/components/public/PublicUnitCatalog.svelte';

    interface PublicUnitItem {
        id: number;
        unit_number: string;
        monthly_price: number;
        status: 'available' | 'occupied' | 'maintenance';
        description: string | null;
        photo_url: string | null;
    }

    interface SharedSiteSettings {
        site_name: string;
        site_tagline: string;
    }

    let {
        units = [],
        adminPhone = '081312132075',
    }: {
        units?: PublicUnitItem[];
        adminPhone?: string;
    } = $props();

    const site = $derived(
        (page.props.siteSettings as SharedSiteSettings | undefined) ?? {
            site_name: 'Kontrakan Ibu',
            site_tagline: 'Hunian Sewa Bulanan Terpadu',
        }
    );
</script>

<AppHead title="{site.site_name} — {site.site_tagline}" />

<div class="min-h-screen bg-[#f8fafc] text-slate-900 selection:bg-blue-600 selection:text-white">
    <PublicNavbar {adminPhone} />

    <PublicHero {adminPhone} />

    <PublicUnitCatalog {units} {adminPhone} />

    <PublicHowItWorks />

    <PublicFooter {adminPhone} />
</div>