<script lang="ts">
    import { router, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';

    interface AvailableUnit {
        id: number;
        unit_number: string;
        monthly_price: number;
    }

    interface LeaseItem {
        id: number;
        status: 'active' | 'ended';
        start_date: string;
        end_date: string | null;
        has_ktp: boolean;
        unit: {
            id: number;
            unit_number: string;
            monthly_price: number;
        };
        tenant: {
            id: number;
            name: string;
            phone: string;
        };
    }

    interface LeaseStats {
        active: number;
        ended: number;
        available_units: number;
    }

    let {
        leases,
        availableUnits,
        stats,
    }: {
        leases: LeaseItem[];
        availableUnits: AvailableUnit[];
        stats: LeaseStats;
    } = $props();

    let isCreateModalOpen = $state(false);
    let isSubmitting = $state(false);
    let ktpPreviewLease = $state<LeaseItem | null>(null);

    let terminatingLease = $state<LeaseItem | null>(null);
    let isTerminating = $state(false);

    let deletingLease = $state<LeaseItem | null>(null);
    let isDeleting = $state(false);

    let unitId = $state<number>(0);
    let name = $state('');
    let phone = $state('');
    let password = $state('');
    let startDate = $state(new Date().toISOString().slice(0, 10));
    let ktpInput = $state<HTMLInputElement | null>(null);

    const errors = $derived((page.props.errors ?? {}) as Record<string, string>);

    function formatRupiah(amount: number): string {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    }

    function openCreateModal(): void {
        unitId = availableUnits[0]?.id ?? 0;
        name = '';
        phone = '';
        password = '';
        startDate = new Date().toISOString().slice(0, 10);
        if (ktpInput) {
            ktpInput.value = '';
        }
        isCreateModalOpen = true;
    }

    function submitCreate(event: SubmitEvent): void {
        event.preventDefault();
        isSubmitting = true;

        const formData = new FormData();
        formData.append('unit_id', String(unitId));
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('password', password);
        formData.append('start_date', startDate);

        const rawKtp = ktpInput?.files?.[0];
        if (rawKtp) {
            formData.append('ktp_photo', rawKtp);
        }

        router.post('/admin/leases', formData, {
            onFinish: () => {
                isSubmitting = false;
            },
            onSuccess: () => {
                isCreateModalOpen = false;
            },
        });
    }

    function confirmTerminate(): void {
        if (!terminatingLease) {
            return;
        }

        isTerminating = true;
        router.post(
            `/admin/leases/${terminatingLease.id}/terminate`,
            {},
            {
                onFinish: () => {
                    isTerminating = false;
                    terminatingLease = null;
                },
            }
        );
    }

    function confirmDeleteLease(): void {
        if (!deletingLease) {
            return;
        }

        isDeleting = true;
        router.delete(`/admin/leases/${deletingLease.id}`, {
            onFinish: () => {
                isDeleting = false;
                deletingLease = null;
            },
        });
    }
</script>

<AppHead title="Penghuni & Kontrak Sewa - Panel Admin" />

<AdminLayout
    title="Penghuni & Kontrak Sewa"
    subtitle="Daftarkan penghuni baru, lihat KTP, dan kelola masa sewa pintu kontrakan"
    activeMenu="leases"
>
    {#snippet headerAction()}
        <button
            type="button"
            onclick={openCreateModal}
            disabled={availableUnits.length === 0}
            class="w-full cursor-pointer rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto sm:text-sm"
        >
            + Daftarkan Penghuni Baru
        </button>
    {/snippet}

    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3 sm:gap-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Kontrak Sewa Aktif</p>
            <p class="mt-1.5 text-xl font-bold text-blue-600 sm:mt-2 sm:text-2xl">{stats.active} Penghuni</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pintu Siap Dihuni</p>
            <p class="mt-1.5 text-xl font-bold text-emerald-600 sm:mt-2 sm:text-2xl">{stats.available_units} Pintu Kosong</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Riwayat Sewa Selesai</p>
            <p class="mt-1.5 text-xl font-bold text-slate-700 sm:mt-2 sm:text-2xl">{stats.ended} Kontrak</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
        <div class="border-b border-slate-100 p-4 sm:p-6">
            <h2 class="text-sm font-bold text-slate-900 sm:text-base">Daftar Penghuni & Kontrak Sewa</h2>
            <p class="text-xs text-slate-500">Akun login penghuni otomatis menggunakan Nomor WhatsApp yang terdaftar</p>
        </div>

        {#if leases.length === 0}
            <div class="p-8 text-center sm:p-12">
                <p class="text-sm font-bold text-slate-900">Belum Ada Data Penghuni</p>
                <p class="mt-1 text-xs text-slate-500">
                    Klik tombol "+ Daftarkan Penghuni Baru" di atas untuk mengisi pintu kontrakan yang kosong.
                </p>
            </div>
        {:else}
            <div class="divide-y divide-slate-100 md:hidden">
                {#each leases as lease}
                    <div class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-blue-600">{lease.unit.unit_number}</span>
                                <h3 class="text-sm font-bold text-slate-900">{lease.tenant.name}</h3>
                                <p class="text-xs text-slate-500">{lease.tenant.phone}</p>
                            </div>
                            {#if lease.status === 'active'}
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    Aktif Menyewa
                                </span>
                            {:else}
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    Selesai
                                </span>
                            {/if}
                        </div>

                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2 text-xs">
                            <div>
                                <span class="text-slate-500">Harga:</span>
                                <span class="font-bold text-slate-800"> {formatRupiah(lease.unit.monthly_price)}/bln</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Masuk:</span>
                                <span class="font-semibold text-slate-800"> {lease.start_date}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            {#if lease.has_ktp}
                                <button
                                    type="button"
                                    onclick={() => (ktpPreviewLease = lease)}
                                    class="flex-1 cursor-pointer rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700"
                                >
                                    Lihat KTP
                                </button>
                            {/if}

                            {#if lease.status === 'active'}
                                <button
                                    type="button"
                                    onclick={() => (terminatingLease = lease)}
                                    class="flex-1 cursor-pointer rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800"
                                >
                                    Akhiri Sewa
                                </button>
                            {/if}

                            <button
                                type="button"
                                onclick={() => (deletingLease = lease)}
                                class="cursor-pointer rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-semibold text-rose-700"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                {/each}
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-gray-50/70 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-6 py-3.5">Pintu</th>
                            <th class="px-6 py-3.5">Nama Penghuni & WA</th>
                            <th class="px-6 py-3.5">Harga Sewa</th>
                            <th class="px-6 py-3.5">Masa Sewa</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {#each leases as lease}
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-6 py-4 font-bold text-slate-900">{lease.unit.unit_number}</td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{lease.tenant.name}</div>
                                    <div class="text-xs text-slate-500">{lease.tenant.phone}</div>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    {formatRupiah(lease.unit.monthly_price)}/bln
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    <div>Masuk: <span class="font-semibold text-slate-800">{lease.start_date}</span></div>
                                    {#if lease.end_date}
                                        <div>Keluar: <span class="font-semibold text-slate-600">{lease.end_date}</span></div>
                                    {/if}
                                </td>
                                <td class="px-6 py-4">
                                    {#if lease.status === 'active'}
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Aktif Menyewa
                                        </span>
                                    {:else}
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            Selesai
                                        </span>
                                    {/if}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {#if lease.has_ktp}
                                            <button
                                                type="button"
                                                onclick={() => (ktpPreviewLease = lease)}
                                                class="cursor-pointer rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-gray-50"
                                            >
                                                Lihat KTP
                                            </button>
                                        {/if}

                                        {#if lease.status === 'active'}
                                            <button
                                                type="button"
                                                onclick={() => (terminatingLease = lease)}
                                                class="cursor-pointer rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 transition hover:bg-amber-500 hover:text-white"
                                            >
                                                Akhiri Sewa
                                            </button>
                                        {/if}

                                        <button
                                            type="button"
                                            onclick={() => (deletingLease = lease)}
                                            class="cursor-pointer rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-600 hover:text-white"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        {/if}
    </div>

    {#if isCreateModalOpen}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 sm:text-lg">Daftarkan Penghuni Baru</h2>
                        <p class="text-xs text-slate-500">Sekaligus membuat akun login penghuni & tagihan bulan pertama</p>
                    </div>
                    <button
                        type="button"
                        onclick={() => (isCreateModalOpen = false)}
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-gray-100 hover:text-slate-600"
                    >
                        &#10005;
                    </button>
                </div>

                <form onsubmit={submitCreate} class="mt-4 space-y-4">
                    <div>
                        <label for="unit_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Pilih Pintu Kontrakan yang Kosong
                        </label>
                        <select
                            id="unit_id"
                            bind:value={unitId}
                            required
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                        >
                            {#each availableUnits as u}
                                <option value={u.id}>{u.unit_number} — {formatRupiah(u.monthly_price)}/bln</option>
                            {/each}
                        </select>
                        {#if errors.unit_id}
                            <p class="mt-1 text-xs font-medium text-rose-600">{errors.unit_id}</p>
                        {/if}
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Nama Lengkap Penghuni
                            </label>
                            <input
                                id="name"
                                type="text"
                                bind:value={name}
                                required
                                placeholder="Contoh: Budi Santoso"
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                            />
                            {#if errors.name}
                                <p class="mt-1 text-xs font-medium text-rose-600">{errors.name}</p>
                            {/if}
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Nomor WhatsApp (Untuk Login)
                            </label>
                            <input
                                id="phone"
                                type="tel"
                                bind:value={phone}
                                required
                                placeholder="081234567890"
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                            />
                            {#if errors.phone}
                                <p class="mt-1 text-xs font-medium text-rose-600">{errors.phone}</p>
                            {/if}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Kata Sandi Awal Penghuni
                            </label>
                            <input
                                id="password"
                                type="text"
                                bind:value={password}
                                required
                                placeholder="Minimal 6 karakter"
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                            />
                            {#if errors.password}
                                <p class="mt-1 text-xs font-medium text-rose-600">{errors.password}</p>
                            {/if}
                        </div>

                        <div>
                            <label for="start_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Tanggal Mulai Sewa
                            </label>
                            <input
                                id="start_date"
                                type="date"
                                bind:value={startDate}
                                required
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                            />
                            {#if errors.start_date}
                                <p class="mt-1 text-xs font-medium text-rose-600">{errors.start_date}</p>
                            {/if}
                        </div>
                    </div>

                    <div>
                        <label for="ktp_photo" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Foto KTP Penghuni (Wajib, Tersimpan Aman/Privat)
                        </label>
                        <input
                            id="ktp_photo"
                            type="file"
                            bind:this={ktpInput}
                            required
                            accept="image/png,image/jpeg,image/webp"
                            class="mt-1.5 block w-full text-xs text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3.5 file:py-2 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                        />
                        {#if errors.ktp_photo}
                            <p class="mt-1 text-xs font-medium text-rose-600">{errors.ktp_photo}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                        <button
                            type="button"
                            onclick={() => (isCreateModalOpen = false)}
                            class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-gray-50 sm:w-auto"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            class="w-full cursor-pointer rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700 disabled:opacity-50 sm:w-auto"
                        >
                            {isSubmitting ? 'Menyimpan...' : 'Simpan & Aktifkan Sewa'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    {/if}

    {#if ktpPreviewLease}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-xs">
            <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Foto KTP — {ktpPreviewLease.tenant.name}</h3>
                        <p class="text-xs text-slate-500">{ktpPreviewLease.unit.unit_number} ({ktpPreviewLease.tenant.phone})</p>
                    </div>
                    <button
                        type="button"
                        onclick={() => (ktpPreviewLease = null)}
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-gray-100"
                    >
                        &#10005;
                    </button>
                </div>
                <div class="mt-4 overflow-hidden rounded-xl bg-slate-100">
                    <img
                        src="/admin/leases/{ktpPreviewLease.id}/ktp"
                        alt="KTP {ktpPreviewLease.tenant.name}"
                        class="max-h-96 w-full object-contain"
                    />
                </div>
            </div>
        </div>
    {/if}

    {#if terminatingLease}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <h3 class="text-base font-bold text-slate-900">
                    Akhiri Sewa {terminatingLease.tenant.name} ({terminatingLease.unit.unit_number})?
                </h3>
                <p class="mt-2 text-xs leading-relaxed text-slate-600">
                    Status <strong>{terminatingLease.unit.unit_number}</strong> akan otomatis kembali menjadi <strong>Tersedia (Kosong)</strong> dan akun penghuni ini akan dinonaktifkan, namun riwayat datanya tetap tersimpan.
                </p>
                <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick={() => (terminatingLease = null)}
                        class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-gray-50 sm:w-auto"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        disabled={isTerminating}
                        onclick={confirmTerminate}
                        class="w-full cursor-pointer rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-amber-700 disabled:opacity-50 sm:w-auto"
                    >
                        {isTerminating ? 'Memproses...' : 'Ya, Akhiri Kontrak'}
                    </button>
                </div>
            </div>
        </div>
    {/if}

    {#if deletingLease}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 font-bold text-rose-600">
                        !
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Hapus Penghuni {deletingLease.tenant.name}?
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {deletingLease.unit.unit_number} · {deletingLease.tenant.phone}
                        </p>
                    </div>
                </div>

                <p class="mt-3 text-xs leading-relaxed text-slate-600">
                    Seluruh data penghuni ini, termasuk <strong>akun login, foto KTP, kontrak sewa, dan semua tagihan terkait</strong> akan dihapus permanen. Status pintu akan kembali menjadi <strong>Tersedia</strong>.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick={() => (deletingLease = null)}
                        class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-gray-50 sm:w-auto"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        disabled={isDeleting}
                        onclick={confirmDeleteLease}
                        class="w-full cursor-pointer rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-50 sm:w-auto"
                    >
                        {isDeleting ? 'Menghapus...' : 'Ya, Hapus Permanen'}
                    </button>
                </div>
            </div>
        </div>
    {/if}
</AdminLayout>