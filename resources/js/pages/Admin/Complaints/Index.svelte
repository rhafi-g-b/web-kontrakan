<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';

    type ComplaintStatusType = 'pending' | 'in_progress' | 'resolved';

    interface ComplaintItem {
        id: number;
        title: string;
        description: string;
        status: ComplaintStatusType;
        admin_response: string | null;
        has_photo: boolean;
        created_at: string;
        resolved_at: string | null;
        unit_number: string;
        tenant_name: string;
        tenant_phone: string;
    }

    interface ComplaintStats {
        total: number;
        pending: number;
        in_progress: number;
        resolved: number;
    }

    let {
        complaints,
        stats,
    }: {
        complaints: ComplaintItem[];
        stats: ComplaintStats;
    } = $props();

    let managingComplaint = $state<ComplaintItem | null>(null);
    let selectedStatus = $state<ComplaintStatusType>('pending');
    let adminResponse = $state('');
    let isSubmitting = $state(false);

    let photoPreviewComplaint = $state<ComplaintItem | null>(null);
    let deletingComplaint = $state<ComplaintItem | null>(null);
    let isDeleting = $state(false);

    function openManageModal(item: ComplaintItem): void {
        managingComplaint = item;
        selectedStatus = item.status;
        adminResponse = item.admin_response ?? '';
    }

    function submitUpdate(event: SubmitEvent): void {
        event.preventDefault();
        if (!managingComplaint) {
            return;
        }

        isSubmitting = true;
        router.put(
            `/admin/complaints/${managingComplaint.id}`,
            {
                status: selectedStatus,
                admin_response: adminResponse,
            },
            {
                onFinish: () => {
                    isSubmitting = false;
                },
                onSuccess: () => {
                    managingComplaint = null;
                },
            }
        );
    }

    function confirmDeleteComplaint(): void {
        if (!deletingComplaint) {
            return;
        }

        isDeleting = true;
        router.delete(`/admin/complaints/${deletingComplaint.id}`, {
            onFinish: () => {
                isDeleting = false;
                deletingComplaint = null;
            },
        });
    }
</script>

<AppHead title="Laporan Kerusakan - Panel Admin" />

<AdminLayout
    title="Laporan Kerusakan Fasilitas"
    subtitle="Pantau dan tindak lanjuti keluhan kerusakan pintu kontrakan dari penghuni"
    activeMenu="complaints"
>
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Laporan</p>
            <p class="mt-1.5 text-xl font-bold text-slate-900 sm:mt-2 sm:text-2xl">{stats.total} Laporan</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Menunggu</p>
            <p class="mt-1.5 text-xl font-bold text-rose-600 sm:mt-2 sm:text-2xl">{stats.pending} Laporan</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Diperbaiki</p>
            <p class="mt-1.5 text-xl font-bold text-amber-600 sm:mt-2 sm:text-2xl">{stats.in_progress} Laporan</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Selesai</p>
            <p class="mt-1.5 text-xl font-bold text-emerald-600 sm:mt-2 sm:text-2xl">{stats.resolved} Laporan</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
        <div class="border-b border-slate-100 p-4 sm:p-6">
            <h2 class="text-sm font-bold text-slate-900 sm:text-base">Daftar Keluhan & Laporan Kerusakan</h2>
            <p class="text-xs text-slate-500">
                Klik tombol "Tindak Lanjuti" untuk mengubah status perbaikan dan membalas pesan penghuni
            </p>
        </div>

        {#if complaints.length === 0}
            <div class="p-8 text-center sm:p-12">
                <p class="text-sm font-bold text-slate-900">Belum Ada Laporan Kerusakan</p>
                <p class="mt-1 text-xs text-slate-500">
                    Saat ini seluruh fasilitas pintu kontrakan dalam kondisi aman dan belum ada keluhan dari penghuni.
                </p>
            </div>
        {:else}
            <div class="divide-y divide-slate-100 md:hidden">
                {#each complaints as item}
                    <div class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-blue-600">{item.unit_number}</span>
                                <h3 class="text-sm font-bold text-slate-900">{item.title}</h3>
                                <p class="text-xs text-slate-500">{item.tenant_name} ({item.tenant_phone})</p>
                            </div>
                            {#if item.status === 'resolved'}
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    Selesai
                                </span>
                            {:else if item.status === 'in_progress'}
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                    Diperbaiki
                                </span>
                            {:else}
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                    Menunggu
                                </span>
                            {/if}
                        </div>

                        <p class="text-xs leading-relaxed text-slate-600">{item.description}</p>

                        {#if item.admin_response}
                            <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-2.5 text-xs text-slate-700">
                                <span class="font-bold text-blue-700">Tanggapan Ibu:</span> {item.admin_response}
                            </div>
                        {/if}

                        <div class="text-xs text-slate-400">Dilaporkan: {item.created_at}</div>

                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            {#if item.has_photo}
                                <button
                                    type="button"
                                    onclick={() => (photoPreviewComplaint = item)}
                                    class="flex-1 cursor-pointer rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700"
                                >
                                    Lihat Foto
                                </button>
                            {/if}

                            <button
                                type="button"
                                onclick={() => openManageModal(item)}
                                class="flex-1 cursor-pointer rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white"
                            >
                                Tindak Lanjuti
                            </button>

                            <button
                                type="button"
                                onclick={() => (deletingComplaint = item)}
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
                            <th class="px-6 py-3.5">Pintu & Penghuni</th>
                            <th class="px-6 py-3.5">Keluhan Kerusakan</th>
                            <th class="px-6 py-3.5">Waktu Lapor</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Aksi Ibu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {#each complaints as item}
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900">{item.unit_number}</div>
                                    <div class="text-xs font-medium text-slate-700">{item.tenant_name}</div>
                                    <div class="text-xs text-slate-500">{item.tenant_phone}</div>
                                </td>
                                <td class="max-w-md px-6 py-4">
                                    <div class="font-bold text-slate-900">{item.title}</div>
                                    <p class="mt-0.5 text-xs leading-relaxed text-slate-600">{item.description}</p>
                                    {#if item.admin_response}
                                        <div class="mt-2 rounded-xl border border-blue-100 bg-blue-50/60 p-2.5 text-xs text-slate-700">
                                            <span class="font-bold text-blue-700">Tanggapan Ibu:</span> {item.admin_response}
                                        </div>
                                    {/if}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    <div>Dilaporkan: {item.created_at}</div>
                                    {#if item.resolved_at}
                                        <div class="mt-0.5 font-medium text-emerald-600">Selesai: {item.resolved_at}</div>
                                    {/if}
                                </td>
                                <td class="px-6 py-4">
                                    {#if item.status === 'resolved'}
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Selesai Diperbaiki
                                        </span>
                                    {:else if item.status === 'in_progress'}
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Sedang Diperbaiki
                                        </span>
                                    {:else}
                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                            Menunggu Tindakan
                                        </span>
                                    {/if}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {#if item.has_photo}
                                            <button
                                                type="button"
                                                onclick={() => (photoPreviewComplaint = item)}
                                                class="cursor-pointer rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-gray-50"
                                            >
                                                Lihat Foto
                                            </button>
                                        {/if}

                                        <button
                                            type="button"
                                            onclick={() => openManageModal(item)}
                                            class="cursor-pointer rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700"
                                        >
                                            Tindak Lanjuti
                                        </button>

                                        <button
                                            type="button"
                                            onclick={() => (deletingComplaint = item)}
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

    {#if managingComplaint}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Tindak Lanjuti Laporan — {managingComplaint.unit_number}
                        </h3>
                        <p class="text-xs text-slate-500">Pelapor: {managingComplaint.tenant_name}</p>
                    </div>
                    <button
                        type="button"
                        onclick={() => (managingComplaint = null)}
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-gray-100"
                    >
                        &#10005;
                    </button>
                </div>

                <div class="mt-4 rounded-xl bg-gray-50 p-3.5 text-xs">
                    <p class="font-bold text-slate-900">{managingComplaint.title}</p>
                    <p class="mt-1 text-slate-600">{managingComplaint.description}</p>
                </div>

                <form onsubmit={submitUpdate} class="mt-4 space-y-4">
                    <div>
                        <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Status Pengerjaan
                        </label>
                        <select
                            id="status"
                            bind:value={selectedStatus}
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                        >
                            <option value="pending">Menunggu Tindakan</option>
                            <option value="in_progress">Sedang Diperbaiki Tukang</option>
                            <option value="resolved">Selesai Diperbaiki</option>
                        </select>
                    </div>

                    <div>
                        <label for="admin_response" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Catatan / Balasan untuk Penghuni (Opsional)
                        </label>
                        <textarea
                            id="admin_response"
                            rows="3"
                            bind:value={adminResponse}
                            placeholder="Contoh: Besok pagi jam 09.00 tukang ledeng akan datang memperbaiki keran."
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                        <button
                            type="button"
                            onclick={() => (managingComplaint = null)}
                            class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-gray-50 sm:w-auto"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            class="w-full cursor-pointer rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700 disabled:opacity-50 sm:w-auto"
                        >
                            {isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    {/if}

    {#if photoPreviewComplaint}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-xs">
            <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Foto Kerusakan — {photoPreviewComplaint.unit_number}
                        </h3>
                        <p class="text-xs text-slate-500">{photoPreviewComplaint.title}</p>
                    </div>
                    <button
                        type="button"
                        onclick={() => (photoPreviewComplaint = null)}
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-gray-100"
                    >
                        &#10005;
                    </button>
                </div>
                <div class="mt-4 overflow-hidden rounded-xl bg-slate-100">
                    <img
                        src="/admin/complaints/{photoPreviewComplaint.id}/photo"
                        alt="Foto Kerusakan {photoPreviewComplaint.title}"
                        class="max-h-96 w-full object-contain"
                    />
                </div>
            </div>
        </div>
    {/if}

    {#if deletingComplaint}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 font-bold text-rose-600">
                        !
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Hapus Laporan {deletingComplaint.unit_number}?
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-500">{deletingComplaint.title}</p>
                    </div>
                </div>

                <p class="mt-3 text-xs leading-relaxed text-slate-600">
                    Data laporan kerusakan ini beserta foto lampirannya akan dihapus permanen.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick={() => (deletingComplaint = null)}
                        class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-gray-50 sm:w-auto"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        disabled={isDeleting}
                        onclick={confirmDeleteComplaint}
                        class="w-full cursor-pointer rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-50 sm:w-auto"
                    >
                        {isDeleting ? 'Menghapus...' : 'Ya, Hapus Laporan'}
                    </button>
                </div>
            </div>
        </div>
    {/if}
</AdminLayout>