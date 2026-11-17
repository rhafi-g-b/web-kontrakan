<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';

    type PaymentStatusType = 'unpaid' | 'pending' | 'waiting_verification' | 'paid' | 'rejected';

    interface PaymentItem {
        id: number;
        billing_period: string;
        amount: number;
        status: PaymentStatusType;
        payment_method: 'transfer' | 'cash' | null;
        due_date: string | null;
        paid_at: string | null;
        has_proof: boolean;
        rejection_reason: string | null;
        unit_number: string;
        tenant_name: string;
        tenant_phone: string;
    }

    interface PaymentStats {
        total_count: number;
        paid_amount: number;
        unpaid_amount: number;
        waiting_count: number;
    }

    let {
        payments,
        selectedPeriod,
        stats,
    }: {
        payments: PaymentItem[];
        selectedPeriod: string;
        stats: PaymentStats;
    } = $props();

    let periodFilter = $state('');
    let isGenerating = $state(false);
    let isProcessing = $state(false);

    let cashConfirmPayment = $state<PaymentItem | null>(null);
    let reviewPayment = $state<PaymentItem | null>(null);
    let rejectionReason = $state('');
    let showRejectInput = $state(false);

    let deletingPayment = $state<PaymentItem | null>(null);
    let isDeleting = $state(false);

    $effect(() => {
        periodFilter = selectedPeriod;
    });

    function formatRupiah(amount: number): string {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    }

    function handlePeriodChange(): void {
        router.get('/admin/payments', { period: periodFilter }, { preserveState: true });
    }

    function generateMonthlyBills(): void {
        isGenerating = true;
        router.post(
            '/admin/payments/generate',
            { period: periodFilter },
            {
                onFinish: () => {
                    isGenerating = false;
                },
            }
        );
    }

    function confirmCashPayment(): void {
        if (!cashConfirmPayment) {
            return;
        }

        isProcessing = true;
        router.post(
            `/admin/payments/${cashConfirmPayment.id}/cash`,
            {},
            {
                onFinish: () => {
                    isProcessing = false;
                    cashConfirmPayment = null;
                },
            }
        );
    }

    function openReviewModal(payment: PaymentItem): void {
        reviewPayment = payment;
        rejectionReason = payment.rejection_reason ?? '';
        showRejectInput = false;
    }

    function approveTransfer(): void {
        if (!reviewPayment) {
            return;
        }

        isProcessing = true;
        router.post(
            `/admin/payments/${reviewPayment.id}/approve`,
            {},
            {
                onFinish: () => {
                    isProcessing = false;
                    reviewPayment = null;
                },
            }
        );
    }

    function rejectTransfer(event: SubmitEvent): void {
        event.preventDefault();
        if (!reviewPayment || !rejectionReason.trim()) {
            return;
        }

        isProcessing = true;
        router.post(
            `/admin/payments/${reviewPayment.id}/reject`,
            { rejection_reason: rejectionReason },
            {
                onFinish: () => {
                    isProcessing = false;
                    reviewPayment = null;
                },
            }
        );
    }

    function confirmDeletePayment(): void {
        if (!deletingPayment) {
            return;
        }

        isDeleting = true;
        router.delete(`/admin/payments/${deletingPayment.id}`, {
            onFinish: () => {
                isDeleting = false;
                deletingPayment = null;
            },
        });
    }
</script>

<AppHead title="Tagihan & Pembayaran - Panel Admin" />

<AdminLayout
    title="Tagihan & Validasi Pembayaran"
    subtitle="Kelola tagihan sewa bulanan, catat pembayaran tunai, dan validasi bukti transfer"
    activeMenu="payments"
>
    {#snippet headerAction()}
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
            <input
                type="month"
                bind:value={periodFilter}
                onchange={handlePeriodChange}
                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-800 focus:border-blue-600 focus:outline-none sm:w-auto sm:text-sm"
            />
            <button
                type="button"
                disabled={isGenerating}
                onclick={generateMonthlyBills}
                class="w-full cursor-pointer rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700 disabled:opacity-50 sm:w-auto sm:text-sm"
            >
                {isGenerating ? 'Memproses...' : '+ Terbitkan Tagihan Bulan Ini'}
            </button>
        </div>
    {/snippet}

    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total ({periodFilter})</p>
            <p class="mt-1.5 text-xl font-bold text-slate-900 sm:mt-2 sm:text-2xl">{stats.total_count} Pintu</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Lunas Masuk</p>
            <p class="mt-1.5 text-lg font-bold text-emerald-600 sm:mt-2 sm:text-2xl">{formatRupiah(stats.paid_amount)}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Perlu Validasi</p>
            <p class="mt-1.5 text-xl font-bold text-amber-600 sm:mt-2 sm:text-2xl">{stats.waiting_count} Transfer</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Belum Bayar</p>
            <p class="mt-1.5 text-lg font-bold text-rose-600 sm:mt-2 sm:text-2xl">{formatRupiah(stats.unpaid_amount)}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
        <div class="border-b border-slate-100 p-4 sm:p-6">
            <h2 class="text-sm font-bold text-slate-900 sm:text-base">Daftar Tagihan Periode {periodFilter}</h2>
            <p class="text-xs text-slate-500">
                Jika penghuni membayar uang tunai langsung ke Ibu, klik tombol "Catat Tunai"
            </p>
        </div>

        {#if payments.length === 0}
            <div class="p-8 text-center sm:p-12">
                <p class="text-sm font-bold text-slate-900">Belum Ada Tagihan pada Periode {periodFilter}</p>
                <p class="mt-1 text-xs text-slate-500">
                    Klik tombol "+ Terbitkan Tagihan Bulan Ini" di atas untuk membuat tagihan seluruh penghuni aktif secara otomatis.
                </p>
            </div>
        {:else}
            <div class="divide-y divide-slate-100 md:hidden">
                {#each payments as item}
                    <div class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-blue-600">{item.unit_number}</span>
                                <h3 class="text-sm font-bold text-slate-900">{item.tenant_name}</h3>
                                <p class="text-xs text-slate-500">{item.tenant_phone}</p>
                            </div>
                            {#if item.status === 'paid'}
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    Lunas ({item.payment_method === 'cash' ? 'Tunai' : 'Transfer'})
                                </span>
                            {:else if item.status === 'waiting_verification' || item.status === 'pending'}
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                    Menunggu Validasi
                                </span>
                            {:else if item.status === 'rejected'}
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                    Bukti Ditolak
                                </span>
                            {:else}
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    Belum Bayar
                                </span>
                            {/if}
                        </div>

                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2 text-xs">
                            <div>
                                <span class="text-slate-500">Nominal:</span>
                                <span class="font-bold text-blue-600"> {formatRupiah(item.amount)}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Tempo:</span>
                                <span class="font-semibold text-slate-800"> {item.due_date ?? '-'}</span>
                            </div>
                        </div>

                        {#if item.rejection_reason}
                            <p class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs text-rose-600">
                                Alasan ditolak: {item.rejection_reason}
                            </p>
                        {/if}

                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            {#if item.has_proof}
                                <button
                                    type="button"
                                    onclick={() => openReviewModal(item)}
                                    class="flex-1 cursor-pointer rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white"
                                >
                                    {item.status === 'waiting_verification' || item.status === 'pending' ? 'Periksa Bukti' : 'Lihat Bukti'}
                                </button>
                            {/if}

                            {#if item.status !== 'paid'}
                                <button
                                    type="button"
                                    onclick={() => (cashConfirmPayment = item)}
                                    class="flex-1 cursor-pointer rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700"
                                >
                                    Catat Tunai
                                </button>
                            {/if}

                            <button
                                type="button"
                                onclick={() => (deletingPayment = item)}
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
                            <th class="px-6 py-3.5">Penghuni</th>
                            <th class="px-6 py-3.5">Nominal</th>
                            <th class="px-6 py-3.5">Jatuh Tempo</th>
                            <th class="px-6 py-3.5">Status & Metode</th>
                            <th class="px-6 py-3.5 text-right">Aksi Ibu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {#each payments as item}
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-6 py-4 font-bold text-slate-900">{item.unit_number}</td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{item.tenant_name}</div>
                                    <div class="text-xs text-slate-500">{item.tenant_phone}</div>
                                </td>
                                <td class="px-6 py-4 font-bold text-blue-600">{formatRupiah(item.amount)}</td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    <div>{item.due_date ?? '-'}</div>
                                    {#if item.paid_at}
                                        <div class="font-medium text-emerald-600">Dibayar: {item.paid_at}</div>
                                    {/if}
                                </td>
                                <td class="px-6 py-4">
                                    {#if item.status === 'paid'}
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Lunas ({item.payment_method === 'cash' ? 'Tunai' : 'Transfer'})
                                        </span>
                                    {:else if item.status === 'waiting_verification' || item.status === 'pending'}
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Menunggu Validasi Transfer
                                        </span>
                                    {:else if item.status === 'rejected'}
                                        <div>
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                                Bukti Ditolak
                                            </span>
                                            {#if item.rejection_reason}
                                                <p class="mt-1 text-xs text-rose-600">Alasan: {item.rejection_reason}</p>
                                            {/if}
                                        </div>
                                    {:else}
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            Belum Bayar
                                        </span>
                                    {/if}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {#if item.has_proof}
                                            <button
                                                type="button"
                                                onclick={() => openReviewModal(item)}
                                                class="cursor-pointer rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700"
                                            >
                                                {item.status === 'waiting_verification' || item.status === 'pending' ? 'Periksa Bukti Transfer' : 'Lihat Bukti'}
                                            </button>
                                        {/if}

                                        {#if item.status !== 'paid'}
                                            <button
                                                type="button"
                                                onclick={() => (cashConfirmPayment = item)}
                                                class="cursor-pointer rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-gray-50"
                                            >
                                                Catat Tunai
                                            </button>
                                        {/if}

                                        <button
                                            type="button"
                                            onclick={() => (deletingPayment = item)}
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

    {#if cashConfirmPayment}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <h3 class="text-base font-bold text-slate-900">
                    Catat Pembayaran Tunai — {cashConfirmPayment.unit_number}
                </h3>
                <p class="mt-2 text-xs leading-relaxed text-slate-600">
                    Apakah Ibu sudah menerima uang tunai sebesar <strong>{formatRupiah(cashConfirmPayment.amount)}</strong> dari <strong>{cashConfirmPayment.tenant_name}</strong> untuk periode <strong>{cashConfirmPayment.billing_period}</strong>?
                </p>

                <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick={() => (cashConfirmPayment = null)}
                        class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-gray-50 sm:w-auto"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        disabled={isProcessing}
                        onclick={confirmCashPayment}
                        class="w-full cursor-pointer rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50 sm:w-auto"
                    >
                        {isProcessing ? 'Menyimpan...' : 'Ya, Tandai Lunas (Tunai)'}
                    </button>
                </div>
            </div>
        </div>
    {/if}

    {#if reviewPayment}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-xs">
            <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Bukti Transfer — {reviewPayment.unit_number} ({reviewPayment.tenant_name})
                        </h3>
                        <p class="text-xs text-slate-500">
                            Nominal Tagihan: <strong class="text-blue-600">{formatRupiah(reviewPayment.amount)}</strong> · Periode {reviewPayment.billing_period}
                        </p>
                    </div>
                    <button
                        type="button"
                        onclick={() => (reviewPayment = null)}
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-gray-100"
                    >
                        &#10005;
                    </button>
                </div>

                <div class="mt-4 overflow-hidden rounded-xl bg-slate-100">
                    <img
                        src="/admin/payments/{reviewPayment.id}/proof"
                        alt="Bukti Transfer {reviewPayment.tenant_name}"
                        class="max-h-96 w-full object-contain"
                    />
                </div>

                {#if showRejectInput}
                    <form onsubmit={rejectTransfer} class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                        <label for="rejection_reason" class="block text-xs font-semibold text-slate-700">
                            Alasan Penolakan Bukti Transfer
                        </label>
                        <input
                            id="rejection_reason"
                            type="text"
                            bind:value={rejectionReason}
                            required
                            placeholder="Contoh: Foto buram atau nominal transfer belum sesuai"
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-600 focus:outline-none"
                        />
                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                onclick={() => (showRejectInput = false)}
                                class="w-full cursor-pointer rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 sm:w-auto"
                            >
                                Kembali
                            </button>
                            <button
                                type="submit"
                                disabled={isProcessing}
                                class="w-full cursor-pointer rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700 disabled:opacity-50 sm:w-auto"
                            >
                                Konfirmasi Tolak
                            </button>
                        </div>
                    </form>
                {:else if reviewPayment.status !== 'paid'}
                    <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                        <button
                            type="button"
                            onclick={() => (showRejectInput = true)}
                            class="w-full cursor-pointer rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-semibold text-rose-700 hover:bg-rose-600 hover:text-white sm:w-auto"
                        >
                            Tolak Bukti
                        </button>
                        <button
                            type="button"
                            disabled={isProcessing}
                            onclick={approveTransfer}
                            class="w-full cursor-pointer rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-50 sm:w-auto"
                        >
                            {isProcessing ? 'Memproses...' : 'Setujui & Tandai Lunas'}
                        </button>
                    </div>
                {/if}
            </div>
        </div>
    {/if}

    {#if deletingPayment}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 font-bold text-rose-600">
                        !
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Hapus Tagihan {deletingPayment.unit_number}?
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {deletingPayment.tenant_name} · Periode {deletingPayment.billing_period}
                        </p>
                    </div>
                </div>

                <p class="mt-3 text-xs leading-relaxed text-slate-600">
                    Tagihan sebesar <strong>{formatRupiah(deletingPayment.amount)}</strong> ini beserta bukti pembayarannya (jika ada) akan dihapus permanen.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick={() => (deletingPayment = null)}
                        class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-gray-50 sm:w-auto"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        disabled={isDeleting}
                        onclick={confirmDeletePayment}
                        class="w-full cursor-pointer rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-50 sm:w-auto"
                    >
                        {isDeleting ? 'Menghapus...' : 'Ya, Hapus Tagihan'}
                    </button>
                </div>
            </div>
        </div>
    {/if}
</AdminLayout>