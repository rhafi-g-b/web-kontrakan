<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';

    interface DashboardStats {
        total_units: number;
        occupied_units: number;
        available_units: number;
        maintenance_units: number;
        paid_revenue: number;
        unpaid_revenue: number;
        waiting_payments_count: number;
        active_complaints_count: number;
    }

    interface PendingVerificationItem {
        id: number;
        unit_number: string;
        tenant_name: string;
        amount: number;
        billing_period: string;
    }

    interface ActiveComplaintItem {
        id: number;
        title: string;
        status: 'pending' | 'in_progress';
        unit_number: string;
        tenant_name: string;
        created_at: string;
    }

    let {
        currentPeriod,
        stats,
        pendingVerifications,
        activeComplaints,
    }: {
        currentPeriod: string;
        stats: DashboardStats;
        pendingVerifications: PendingVerificationItem[];
        activeComplaints: ActiveComplaintItem[];
    } = $props();

    function formatRupiah(amount: number): string {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    }
</script>

<AppHead title="Ringkasan Kontrakan - Panel Admin" />

<AdminLayout
    title="Ringkasan Kontrakan Ibu"
    subtitle="Pantau kondisi pintu kontrakan, pemasukan sewa, dan keluhan penghuni secara langsung"
    activeMenu="dashboard"
>
    {#snippet headerAction()}
        <a
            href="/admin/units"
            class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-center text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700 sm:w-auto sm:text-sm"
        >
            Kelola Data Pintu
        </a>
    {/snippet}

    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Keterisian Pintu</p>
            <p class="mt-2 text-xl font-bold text-slate-900 sm:text-2xl">
                {stats.occupied_units} <span class="text-sm font-medium text-slate-400 sm:text-base">/ {stats.total_units} Pintu</span>
            </p>
            <p class="mt-1.5 text-xs font-medium text-emerald-600">
                {stats.available_units} pintu kosong siap disewa
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pemasukan Lunas ({currentPeriod})</p>
            <p class="mt-2 text-xl font-bold text-blue-600 sm:text-2xl">{formatRupiah(stats.paid_revenue)}</p>
            <p class="mt-1.5 text-xs font-medium text-slate-500">
                Belum terbayar: {formatRupiah(stats.unpaid_revenue)}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Menunggu Validasi Transfer</p>
            <p class="mt-2 text-xl font-bold text-amber-600 sm:text-2xl">{stats.waiting_payments_count} Bukti</p>
            <a href="/admin/payments" class="mt-1.5 inline-block text-xs font-semibold text-blue-600 hover:underline">
                Periksa di menu Pembayaran &rarr;
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Keluhan Belum Selesai</p>
            <p class="mt-2 text-xl font-bold text-rose-600 sm:text-2xl">{stats.active_complaints_count} Laporan</p>
            <a href="/admin/complaints" class="mt-1.5 inline-block text-xs font-semibold text-blue-600 hover:underline">
                Lihat Laporan Kerusakan &rarr;
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 lg:gap-6">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-6">
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 sm:text-base">Bukti Transfer Menunggu Validasi</h2>
                    <p class="text-xs text-slate-500">Pembayaran transfer terbaru yang perlu Ibu periksa</p>
                </div>
                <a href="/admin/payments" class="shrink-0 text-xs font-semibold text-blue-600 hover:underline">
                    Semua Tagihan
                </a>
            </div>

            {#if pendingVerifications.length === 0}
                <div class="py-8 text-center sm:py-10">
                    <p class="text-sm font-semibold text-slate-700">Tidak Ada Antrean Validasi Transfer</p>
                    <p class="mt-1 text-xs text-slate-400">Semua bukti transfer dari penghuni sudah Ibu periksa.</p>
                </div>
            {:else}
                <div class="mt-3 divide-y divide-slate-100">
                    {#each pendingVerifications as item}
                        <div class="flex flex-col gap-2.5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-900">{item.unit_number} — {item.tenant_name}</p>
                                <p class="text-xs text-slate-500">Periode {item.billing_period} · <span class="font-semibold text-blue-600">{formatRupiah(item.amount)}</span></p>
                            </div>
                            <a
                                href="/admin/payments?period={item.billing_period}"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                            >
                                Periksa Bukti
                            </a>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-6">
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 sm:text-base">Laporan Kerusakan Aktif</h2>
                    <p class="text-xs text-slate-500">Keluhan fasilitas dari penghuni yang sedang ditangani</p>
                </div>
                <a href="/admin/complaints" class="shrink-0 text-xs font-semibold text-blue-600 hover:underline">
                    Semua Laporan
                </a>
            </div>

            {#if activeComplaints.length === 0}
                <div class="py-8 text-center sm:py-10">
                    <p class="text-sm font-semibold text-slate-700">Tidak Ada Keluhan Kerusakan Aktif</p>
                    <p class="mt-1 text-xs text-slate-400">Seluruh fasilitas kontrakan dalam keadaan baik.</p>
                </div>
            {:else}
                <div class="mt-3 divide-y divide-slate-100">
                    {#each activeComplaints as complaint}
                        <div class="flex items-start justify-between gap-3 py-3.5 sm:items-center">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900">{complaint.unit_number} — {complaint.title}</p>
                                <p class="text-xs text-slate-500">{complaint.tenant_name} · {complaint.created_at}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {complaint.status === 'in_progress' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700'}">
                                {complaint.status === 'in_progress' ? 'Diperbaiki' : 'Menunggu'}
                            </span>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>
    </div>
</AdminLayout>