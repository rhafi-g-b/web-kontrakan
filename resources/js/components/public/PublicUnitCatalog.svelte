<script lang="ts">
    interface PublicUnitItem {
        id: number;
        unit_number: string;
        monthly_price: number;
        status: 'available' | 'occupied' | 'maintenance';
        description: string | null;
        photo_url: string | null;
    }

    let {
        units = [],
        adminPhone = '081312132075',
    }: {
        units?: PublicUnitItem[];
        adminPhone?: string;
    } = $props();

    let selectedFilter = $state<'all' | 'available' | 'occupied'>('all');

    const availableCount = $derived(units.filter((u) => u.status === 'available').length);

    const filteredUnits = $derived(
        units.filter((u) => {
            if (selectedFilter === 'available') return u.status === 'available';
            if (selectedFilter === 'occupied') return u.status !== 'available';
            return true;
        })
    );

    function formatRupiah(amount: number): string {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    }

    function buildWhatsAppUrl(unitNumber: string): string {
        const cleaned = adminPhone.replace(/[^0-9]/g, '');
        const internationalPhone = cleaned.startsWith('0') ? `62${cleaned.slice(1)}` : cleaned;
        const text = `Halo pengelola Kontrakan Ibu, saya melihat informasi di website dan tertarik menanyakan ketersediaan sewa untuk *${unitNumber}*. Apakah masih bisa disurvei?`;
        return `https://wa.me/${internationalPhone}?text=${encodeURIComponent(text)}`;
    }
</script>

<main id="katalog-pintu" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
    <div class="flex flex-col justify-between gap-6 border-b border-slate-200 pb-8 md:flex-row md:items-end">
        <div>
            <p class="text-xs font-bold tracking-widest text-blue-600 uppercase">Daftar Unit Real-Time</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                Ketersediaan Pintu Kontrakan
            </h2>
            <p class="mt-1.5 text-xs text-slate-500 sm:text-sm">
                Data di bawah ini terhubung langsung dengan sistem pengelola. Pilih pintu yang berstatus <strong class="text-emerald-700">Tersedia</strong> untuk memesan.
            </p>
        </div>

        <div class="inline-flex self-start rounded-xl border border-slate-200 bg-white p-1 shadow-2xs">
            <button
                type="button"
                onclick={() => (selectedFilter = 'all')}
                class="cursor-pointer rounded-lg px-3.5 py-2 text-xs font-bold transition {selectedFilter === 'all'
                    ? 'bg-slate-900 text-white shadow-2xs'
                    : 'text-slate-600 hover:text-slate-900'}"
            >
                Semua ({units.length})
            </button>
            <button
                type="button"
                onclick={() => (selectedFilter = 'available')}
                class="cursor-pointer rounded-lg px-3.5 py-2 text-xs font-bold transition {selectedFilter === 'available'
                    ? 'bg-emerald-600 text-white shadow-2xs'
                    : 'text-slate-600 hover:text-slate-900'}"
            >
                Kosong ({availableCount})
            </button>
            <button
                type="button"
                onclick={() => (selectedFilter = 'occupied')}
                class="cursor-pointer rounded-lg px-3.5 py-2 text-xs font-bold transition {selectedFilter === 'occupied'
                    ? 'bg-slate-900 text-white shadow-2xs'
                    : 'text-slate-600 hover:text-slate-900'}"
            >
                Terisi ({units.length - availableCount})
            </button>
        </div>
    </div>

    {#if filteredUnits.length === 0}
        <div class="mt-10 rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-base font-bold text-slate-900">Belum Ada Data Pintu pada Kategori Ini</p>
            <p class="mt-1 text-xs text-slate-500">
                Silakan ubah filter tampilan di atas atau tambahkan pintu baru melalui Panel Admin.
            </p>
            {#if selectedFilter !== 'all'}
                <button
                    type="button"
                    onclick={() => (selectedFilter = 'all')}
                    class="mt-4 cursor-pointer rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white"
                >
                    Tampilkan Semua Pintu
                </button>
            {/if}
        </div>
    {:else}
        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {#each filteredUnits as unit (unit.id)}
                <article class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs transition duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl">
                    <div>
                        <div class="relative h-56 w-full overflow-hidden bg-slate-900">
                            {#if unit.photo_url}
                                <img
                                    src={unit.photo_url}
                                    alt="Foto {unit.unit_number}"
                                    class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-105"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/25 to-transparent"></div>
                            {:else}
                                <div
                                    class="absolute inset-0 opacity-15"
                                    style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.4) 1px, transparent 0); background-size: 24px 24px;"
                                ></div>

                                <div class="flex h-full flex-col items-center justify-center p-6 text-center transition duration-500 group-hover:scale-105">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-dashed border-slate-700 bg-slate-800/70 text-slate-400">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9.5L12 4l9 5.5v9a2 2 0 01-2 2H5a2 2 0 01-2-2v-9z" />
                                        </svg>
                                    </div>
                                    <span class="mt-2.5 text-xs font-bold tracking-wider text-slate-300 uppercase">
                                        Placeholder Foto {unit.unit_number}
                                    </span>
                                    <span class="mt-0.5 text-[11px] text-slate-500">
                                        Belum ada foto yang diunggah
                                    </span>
                                </div>
                            {/if}

                            <div class="absolute top-3.5 left-3.5">
                                <span class="rounded-lg bg-slate-950/80 px-3 py-1 text-xs font-bold text-white backdrop-blur-xs">
                                    {unit.unit_number}
                                </span>
                            </div>

                            <div class="absolute top-3.5 right-3.5">
                                {#if unit.status === 'available'}
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white shadow-sm">
                                        <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                        Tersedia
                                    </span>
                                {:else if unit.status === 'occupied'}
                                    <span class="rounded-full bg-slate-800/90 px-3 py-1 text-xs font-semibold text-slate-200 backdrop-blur-xs">
                                        Sudah Terisi
                                    </span>
                                {:else}
                                    <span class="rounded-full bg-amber-500 px-3 py-1 text-xs font-bold text-white shadow-sm">
                                        Perbaikan
                                    </span>
                                {/if}
                            </div>

                            <div class="absolute right-3.5 bottom-3 left-3.5 flex items-baseline justify-between border-t border-white/15 pt-2.5 text-white">
                                <span class="text-xs font-medium text-slate-200">Tarif Sewa</span>
                                <p class="text-lg font-extrabold tracking-tight text-white drop-shadow-xs">
                                    {formatRupiah(unit.monthly_price)}
                                    <span class="text-xs font-normal text-slate-300">/bln</span>
                                </p>
                            </div>
                        </div>

                        <div class="p-5">
                            <p class="text-xs font-semibold tracking-wider text-slate-400 uppercase">
                                Fasilitas & Keterangan Unit
                            </p>
                            <p class="mt-2 line-clamp-3 text-xs leading-relaxed text-slate-600 sm:text-sm">
                                {unit.description && unit.description.trim() !== ''
                                    ? unit.description
                                    : 'Ruang utama lega, kamar mandi dalam bersih, dapur, listrik token mandiri, dan area parkir motor aman.'}
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-4">
                        {#if unit.status === 'available'}
                            <a
                                href={buildWhatsAppUrl(unit.unit_number)}
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-blue-700"
                            >
                                <span>Tanya & Pesan {unit.unit_number} via WA</span>
                                <span>&rarr;</span>
                            </a>
                        {:else}
                            <button
                                type="button"
                                disabled
                                class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-center text-xs font-semibold text-slate-400"
                            >
                                Saat Ini Sedang Dihuni
                            </button>
                        {/if}
                    </div>
                </article>
            {/each}
        </div>
    {/if}
</main>