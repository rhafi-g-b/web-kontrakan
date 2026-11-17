<script lang="ts">
    import type { Snippet } from 'svelte';
    import { router, page } from '@inertiajs/svelte';

    interface SharedSiteSettings {
        site_name: string;
        site_initial: string;
        logo_url: string | null;
    }

    let {
        title,
        subtitle = '',
        activeMenu = 'dashboard',
        headerAction,
        children,
    }: {
        title: string;
        subtitle?: string;
        activeMenu?: 'dashboard' | 'units' | 'leases' | 'payments' | 'complaints' | 'settings';
        headerAction?: Snippet;
        children: Snippet;
    } = $props();

    let mobileMenuOpen = $state(false);
    let toastMessage = $state<string | null>(null);
    let toastType = $state<'success' | 'error'>('success');

    const site = $derived(
        (page.props.siteSettings as SharedSiteSettings | undefined) ?? {
            site_name: 'Kontrakan Ibu',
            site_initial: 'K',
            logo_url: null,
        }
    );

    const navItems = [
        { key: 'dashboard', label: 'Ringkasan Kontrakan', href: '/admin/dashboard' },
        { key: 'units', label: 'Data Pintu Kontrakan', href: '/admin/units' },
        { key: 'leases', label: 'Penghuni & Kontrak', href: '/admin/leases' },
        { key: 'payments', label: 'Tagihan & Pembayaran', href: '/admin/payments' },
        { key: 'complaints', label: 'Laporan Kerusakan', href: '/admin/complaints' },
        { key: 'settings', label: 'Pengaturan Website', href: '/admin/settings' },
    ] as const;

    $effect(() => {
        const success = page.props.flash?.success as string | undefined;
        const error = page.props.flash?.error as string | undefined;

        if (success) {
            toastType = 'success';
            toastMessage = success;
            const timer = setTimeout(() => {
                toastMessage = null;
            }, 4000);
            return () => clearTimeout(timer);
        }

        if (error) {
            toastType = 'error';
            toastMessage = error;
            const timer = setTimeout(() => {
                toastMessage = null;
            }, 5000);
            return () => clearTimeout(timer);
        }
    });

    function logout(): void {
        router.post('/logout');
    }
</script>

<div class="flex min-h-screen bg-gray-50 text-slate-800">
    {#if toastMessage}
        <div class="fixed top-4 right-4 left-4 z-60 mx-auto flex max-w-sm items-center gap-3 rounded-2xl border bg-white p-4 shadow-xl transition-all sm:right-5 sm:left-auto {toastType === 'success' ? 'border-emerald-200' : 'border-rose-200'}">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white {toastType === 'success' ? 'bg-emerald-600' : 'bg-rose-600'}">
                {toastType === 'success' ? '✓' : '!'}
            </div>
            <div class="min-w-0 flex-1 pr-1">
                <p class="text-xs font-bold uppercase tracking-wider {toastType === 'success' ? 'text-emerald-700' : 'text-rose-700'}">
                    {toastType === 'success' ? 'Berhasil' : 'Perhatian'}
                </p>
                <p class="mt-0.5 break-words text-xs font-medium text-slate-700">{toastMessage}</p>
            </div>
            <button
                type="button"
                onclick={() => (toastMessage = null)}
                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-gray-100 hover:text-slate-600"
            >
                &#10005;
            </button>
        </div>
    {/if}

    <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col justify-between bg-slate-950 p-6 text-slate-300 lg:flex">
        <div class="space-y-7 overflow-y-auto">
            <div class="flex items-center gap-3 px-2">
                {#if site.logo_url}
                    <img
                        src={site.logo_url}
                        alt={site.site_name}
                        class="h-9 w-9 shrink-0 rounded-xl bg-white/10 object-contain p-1"
                    />
                {:else}
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 font-bold text-white shadow-xs">
                        {site.site_initial}
                    </div>
                {/if}
                <div class="min-w-0">
                    <p class="truncate text-base font-bold text-white">{site.site_name}</p>
                    <p class="text-xs text-slate-400">Panel Pengelola</p>
                </div>
            </div>

            <div>
                <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Menu Pengelola
                </p>
                <nav class="space-y-1 text-sm font-medium">
                    {#each navItems as item}
                        <a
                            href={item.href}
                            class="flex items-center justify-between rounded-xl px-3.5 py-2.5 transition {activeMenu === item.key
                                ? 'bg-blue-600 text-white shadow-xs'
                                : 'text-slate-400 hover:bg-slate-900 hover:text-white'}"
                        >
                            <span>{item.label}</span>
                        </a>
                    {/each}
                </nav>
            </div>

            <div class="border-t border-slate-800/80 pt-5">
                <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Halaman Publik
                </p>
                <a
                    href="/"
                    class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/70 px-3.5 py-2.5 text-xs font-semibold text-slate-300 transition hover:border-blue-500/50 hover:bg-slate-900 hover:text-white"
                >
                    <span>Lihat Katalog Depan</span>
                    <span class="text-blue-400">&rarr;</span>
                </a>
            </div>
        </div>

        <div class="border-t border-slate-800 pt-4">
            <div class="mb-3 px-2">
                <p class="truncate text-sm font-semibold text-white">{page.props.auth.user?.name}</p>
                <p class="text-xs text-slate-400">{page.props.auth.user?.phone}</p>
            </div>
            <button
                type="button"
                onclick={logout}
                class="w-full cursor-pointer rounded-xl border border-slate-800 bg-slate-900 px-4 py-2 text-xs font-semibold text-slate-300 transition hover:border-rose-600 hover:bg-rose-600 hover:text-white"
            >
                Keluar Akun
            </button>
        </div>
    </aside>

    {#if mobileMenuOpen}
        <div class="fixed inset-0 z-50 flex lg:hidden">
            <button
                type="button"
                aria-label="Tutup Menu"
                onclick={() => (mobileMenuOpen = false)}
                class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs"
            ></button>

            <div class="relative z-10 flex w-72 max-w-[80vw] flex-col justify-between overflow-y-auto bg-slate-950 p-6 text-slate-300 shadow-2xl">
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            {#if site.logo_url}
                                <img
                                    src={site.logo_url}
                                    alt={site.site_name}
                                    class="h-9 w-9 shrink-0 rounded-xl bg-white/10 object-contain p-1"
                                />
                            {:else}
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 font-bold text-white">
                                    {site.site_initial}
                                </div>
                            {/if}
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-white">{site.site_name}</p>
                                <p class="text-xs text-slate-400">Panel Pengelola</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onclick={() => (mobileMenuOpen = false)}
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-900 hover:text-white"
                        >
                            &#10005;
                        </button>
                    </div>

                    <div>
                        <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Menu Pengelola
                        </p>
                        <nav class="space-y-1 text-sm font-medium">
                            {#each navItems as item}
                                <a
                                    href={item.href}
                                    onclick={() => (mobileMenuOpen = false)}
                                    class="block rounded-xl px-3.5 py-2.5 transition {activeMenu === item.key
                                        ? 'bg-blue-600 text-white shadow-xs'
                                        : 'text-slate-400 hover:bg-slate-900 hover:text-white'}"
                                >
                                    {item.label}
                                </a>
                            {/each}
                        </nav>
                    </div>

                    <div class="border-t border-slate-800/80 pt-4">
                        <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Halaman Publik
                        </p>
                        <a
                            href="/"
                            onclick={() => (mobileMenuOpen = false)}
                            class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/70 px-3.5 py-2.5 text-xs font-semibold text-slate-300 transition hover:bg-slate-900 hover:text-white"
                        >
                            <span>Lihat Katalog Depan</span>
                            <span class="text-blue-400">&rarr;</span>
                        </a>
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-4">
                    <div class="mb-3 px-1">
                        <p class="truncate text-sm font-semibold text-white">{page.props.auth.user?.name}</p>
                        <p class="text-xs text-slate-400">{page.props.auth.user?.phone}</p>
                    </div>
                    <button
                        type="button"
                        onclick={logout}
                        class="w-full rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs"
                    >
                        Keluar Akun
                    </button>
                </div>
            </div>
        </div>
    {/if}

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 px-4 py-3.5 backdrop-blur-xs sm:px-6 sm:py-4 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3 sm:items-center">
                    <button
                        type="button"
                        onclick={() => (mobileMenuOpen = true)}
                        class="mt-0.5 shrink-0 rounded-xl border border-slate-200 p-2 text-slate-700 hover:bg-gray-50 sm:mt-0 lg:hidden"
                        aria-label="Buka Menu Navigasi"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="truncate text-base font-bold text-slate-900 sm:text-xl">{title}</h1>
                        {#if subtitle}
                            <p class="line-clamp-1 text-xs text-slate-500 sm:text-sm">{subtitle}</p>
                        {/if}
                    </div>
                </div>

                {#if headerAction}
                    <div class="flex w-full items-center justify-end gap-2 sm:w-auto">
                        {@render headerAction()}
                    </div>
                {/if}
            </div>
        </header>

        <main class="flex-1 space-y-5 p-4 sm:space-y-6 sm:p-6 lg:p-8">
            {@render children()}
        </main>
    </div>
</div>