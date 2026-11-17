<script lang="ts">
    import { router, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';

    type UnitStatus = 'available' | 'occupied' | 'maintenance';

    interface UnitItem {
        id: number;
        unit_number: string;
        monthly_price: number;
        status: UnitStatus;
        description: string | null;
        photo_url: string | null;
        active_tenant: {
            name: string;
            phone: string;
        } | null;
    }

    interface UnitStats {
        total: number;
        available: number;
        occupied: number;
        maintenance: number;
    }

    let {
        units,
        stats,
    }: {
        units: UnitItem[];
        stats: UnitStats;
    } = $props();

    let isModalOpen = $state(false);
    let isSubmitting = $state(false);
    let editingUnit = $state<UnitItem | null>(null);

    let unitNumber = $state('');
    let monthlyPrice = $state(1000000);
    let status = $state<UnitStatus>('available');
    let description = $state('');
    let photoInput = $state<HTMLInputElement | null>(null);

    let deletingUnit = $state<UnitItem | null>(null);
    let isDeleting = $state(false);

    const errors = $derived((page.props.errors ?? {}) as Record<string, string>);

    function formatRupiah(amount: number): string {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    }

    function openCreateModal(): void {
        editingUnit = null;
        unitNumber = '';
        monthlyPrice = 1000000;
        status = 'available';
        description = '';
        if (photoInput) {
            photoInput.value = '';
        }
        isModalOpen = true;
    }

    function openEditModal(unit: UnitItem): void {
        editingUnit = unit;
        unitNumber = unit.unit_number;
        monthlyPrice = unit.monthly_price;
        status = unit.status;
        description = unit.description ?? '';
        if (photoInput) {
            photoInput.value = '';
        }
        isModalOpen = true;
    }

    function closeModal(): void {
        isModalOpen = false;
        editingUnit = null;
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        isSubmitting = true;

        const formData = new FormData();
        formData.append('unit_number', unitNumber);
        formData.append('monthly_price', String(monthlyPrice));
        formData.append('status', status);
        formData.append('description', description);

        const rawFile = photoInput?.files?.[0];
        if (rawFile) {
            formData.append('photo', rawFile);
        }

        if (editingUnit !== null) {
            formData.append('_method', 'PUT');
            router.post(`/admin/units/${editingUnit.id}`, formData, {
                onFinish: () => {
                    isSubmitting = false;
                },
                onSuccess: () => closeModal(),
            });
            return;
        }

        router.post('/admin/units', formData, {
            onFinish: () => {
                isSubmitting = false;
            },
            onSuccess: () => closeModal(),
        });
    }

    function confirmDeleteUnit(): void {
        if (!deletingUnit) {
            return;
        }

        isDeleting = true;
        router.delete(`/admin/units/${deletingUnit.id}`, {
            onFinish: () => {
                isDeleting = false;
                deletingUnit = null;
            },
        });
    }
</script>

<AppHead title="Data Pintu Kontrakan - Panel Admin" />

<AdminLayout
    title="Data Pintu Kontrakan"
    subtitle="Kelola daftar pintu, harga sewa bulanan, dan status ketersediaan"
    activeMenu="units"
>
    {#snippet headerAction()}
        <button
            type="button"
            onclick={openCreateModal}
            class="w-full cursor-pointer rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700 sm:w-auto sm:text-sm"
        >
            + Tambah Pintu Baru
        </button>
    {/snippet}

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Pintu</p>
            <p class="mt-1.5 text-xl font-bold text-slate-900 sm:mt-2 sm:text-2xl">{stats.total} Pintu</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tersedia</p>
            <p class="mt-1.5 text-xl font-bold text-emerald-600 sm:mt-2 sm:text-2xl">{stats.available} Pintu</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sedang Terisi</p>
            <p class="mt-1.5 text-xl font-bold text-blue-600 sm:mt-2 sm:text-2xl">{stats.occupied} Pintu</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Perbaikan</p>
            <p class="mt-1.5 text-xl font-bold text-amber-600 sm:mt-2 sm:text-2xl">{stats.maintenance} Pintu</p>
        </div>
    </div>

    {#if units.length === 0}
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center sm:p-12">
            <p class="text-base font-bold text-slate-900">Belum Ada Data Pintu Kontrakan</p>
            <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                Tambahkan pintu kontrakan pertama Ibu untuk mulai mengelola penghuni dan tagihan.
            </p>
            <button
                type="button"
                onclick={openCreateModal}
                class="mt-5 cursor-pointer rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700 sm:text-sm"
            >
                + Tambah Pintu Pertama
            </button>
        </div>
    {:else}
        <div class="grid grid-cols-1 gap-4 sm:gap-5 md:grid-cols-2 xl:grid-cols-3">
            {#each units as unit}
                <div class="flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                    <div>
                        <div class="relative h-44 w-full bg-slate-100">
                            {#if unit.photo_url}
                                <img
                                    src={unit.photo_url}
                                    alt={unit.unit_number}
                                    class="h-full w-full object-cover"
                                />
                            {:else}
                                <div class="flex h-full items-center justify-center text-xs font-medium text-slate-400">
                                    Belum ada foto pintu
                                </div>
                            {/if}

                            <div class="absolute top-3 right-3">
                                {#if unit.status === 'available'}
                                    <span class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white shadow-xs">
                                        Tersedia
                                    </span>
                                {:else if unit.status === 'occupied'}
                                    <span class="rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white shadow-xs">
                                        Terisi
                                    </span>
                                {:else}
                                    <span class="rounded-full bg-amber-500 px-3 py-1 text-xs font-semibold text-white shadow-xs">
                                        Perbaikan
                                    </span>
                                {/if}
                            </div>
                        </div>

                        <div class="p-4 sm:p-5">
                            <div class="flex items-baseline justify-between gap-2">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">{unit.unit_number}</h3>
                                <p class="text-sm font-bold text-blue-600 sm:text-base">
                                    {formatRupiah(unit.monthly_price)}
                                    <span class="text-xs font-normal text-slate-500">/bln</span>
                                </p>
                            </div>

                            {#if unit.description}
                                <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-600">
                                    {unit.description}
                                </p>
                            {:else}
                                <p class="mt-2 text-xs italic text-slate-400">Tidak ada catatan fasilitas.</p>
                            {/if}

                            <div class="mt-4 rounded-xl bg-gray-50 p-3 text-xs">
                                {#if unit.active_tenant}
                                    <p class="font-semibold text-slate-700">Penghuni Saat Ini:</p>
                                    <p class="mt-0.5 font-bold text-slate-900">{unit.active_tenant.name}</p>
                                    <p class="text-slate-500">{unit.active_tenant.phone}</p>
                                {:else}
                                    <p class="font-medium text-slate-500">Belum ada penghuni aktif di pintu ini.</p>
                                {/if}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-gray-50/50 px-4 py-3 sm:px-5">
                        <button
                            type="button"
                            onclick={() => openEditModal(unit)}
                            class="flex-1 cursor-pointer rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 sm:flex-none"
                        >
                            Edit Pintu
                        </button>
                        {#if !unit.active_tenant}
                            <button
                                type="button"
                                onclick={() => (deletingUnit = unit)}
                                class="flex-1 cursor-pointer rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-600 hover:text-white sm:flex-none"
                            >
                                Hapus
                            </button>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    {/if}

    {#if isModalOpen}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h2 class="text-base font-bold text-slate-900 sm:text-lg">
                        {editingUnit ? `Edit ${editingUnit.unit_number}` : 'Tambah Pintu Kontrakan Baru'}
                    </h2>
                    <button
                        type="button"
                        onclick={closeModal}
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-gray-100 hover:text-slate-600"
                    >
                        &#10005;
                    </button>
                </div>

                <form onsubmit={submit} class="mt-4 space-y-4">
                    <div>
                        <label for="unit_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Nama / Nomor Pintu
                        </label>
                        <input
                            id="unit_number"
                            type="text"
                            bind:value={unitNumber}
                            required
                            placeholder="Contoh: Pintu 1 atau Pintu A1"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                        />
                        {#if errors.unit_number}
                            <p class="mt-1 text-xs font-medium text-rose-600">{errors.unit_number}</p>
                        {/if}
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="monthly_price" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Harga Sewa per Bulan (Rp)
                            </label>
                            <input
                                id="monthly_price"
                                type="number"
                                step="50000"
                                bind:value={monthlyPrice}
                                required
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                            />
                            {#if errors.monthly_price}
                                <p class="mt-1 text-xs font-medium text-rose-600">{errors.monthly_price}</p>
                            {/if}
                        </div>

                        <div>
                            <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Status Pintu
                            </label>
                            <select
                                id="status"
                                bind:value={status}
                                disabled={editingUnit?.active_tenant !== null && editingUnit?.active_tenant !== undefined}
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none disabled:bg-gray-100"
                            >
                                <option value="available">Tersedia (Kosong)</option>
                                <option value="occupied">Terisi</option>
                                <option value="maintenance">Sedang Perbaikan</option>
                            </select>
                            {#if errors.status}
                                <p class="mt-1 text-xs font-medium text-rose-600">{errors.status}</p>
                            {/if}
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Fasilitas & Keterangan
                        </label>
                        <textarea
                            id="description"
                            rows="3"
                            bind:value={description}
                            placeholder="Contoh: Listrik token 1300W, kamar mandi dalam, dapur, teras motor."
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                        ></textarea>
                        {#if errors.description}
                            <p class="mt-1 text-xs font-medium text-rose-600">{errors.description}</p>
                        {/if}
                    </div>

                    <div>
                        <label for="photo" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Foto Pintu Kontrakan (Opsional, Maks 10MB)
                        </label>
                        <input
                            id="photo"
                            type="file"
                            bind:this={photoInput}
                            accept="image/png,image/jpeg,image/webp"
                            class="mt-1.5 block w-full text-xs text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3.5 file:py-2 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                        />
                        {#if errors.photo}
                            <p class="mt-1 text-xs font-medium text-rose-600">{errors.photo}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                        <button
                            type="button"
                            onclick={closeModal}
                            class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-gray-50 sm:w-auto"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            class="w-full cursor-pointer rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700 disabled:opacity-50 sm:w-auto"
                        >
                            {isSubmitting ? 'Menyimpan...' : 'Simpan Data Pintu'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    {/if}

    {#if deletingUnit}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 font-bold text-rose-600">
                        !
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Hapus {deletingUnit.unit_number}?</h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Data pintu yang dihapus tidak dapat dikembalikan lagi.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick={() => (deletingUnit = null)}
                        class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-gray-50 sm:w-auto"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        disabled={isDeleting}
                        onclick={confirmDeleteUnit}
                        class="w-full cursor-pointer rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-50 sm:w-auto"
                    >
                        {isDeleting ? 'Menghapus...' : 'Ya, Hapus Pintu'}
                    </button>
                </div>
            </div>
        </div>
    {/if}
</AdminLayout>