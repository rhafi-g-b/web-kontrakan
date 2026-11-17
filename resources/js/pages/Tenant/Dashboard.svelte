<script lang="ts">
    import { router, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';

    interface LeaseInfo {
        id: number;
        status: string;
        start_date: string;
        end_date: string | null;
        unit_number: string;
        monthly_price: number;
        description: string | null;
    }

    interface TenantPaymentItem {
        id: number;
        billing_period: string;
        amount: number;
        status: 'unpaid' | 'pending' | 'waiting_verification' | 'paid' | 'rejected';
        payment_method: 'transfer' | 'cash' | null;
        due_date: string;
        paid_at: string | null;
        has_proof: boolean;
        rejection_reason: string | null;
    }

    interface TenantComplaintItem {
        id: number;
        title: string;
        description: string;
        status: 'pending' | 'in_progress' | 'resolved';
        admin_response: string | null;
        created_at: string;
    }

    let {
        lease,
        payments,
        complaints,
    }: {
        lease: LeaseInfo | null;
        payments: TenantPaymentItem[];
        complaints: TenantComplaintItem[];
    } = $props();

    let toastMessage = $state<string | null>(null);
    let toastType = $state<'success' | 'error'>('success');

    let uploadingPayment = $state<TenantPaymentItem | null>(null);
    let proofInput = $state<HTMLInputElement | null>(null);
    let isUploadingProof = $state(false);

    let isComplaintModalOpen = $state(false);
    let complaintTitle = $state('');
    let complaintDescription = $state('');
    let complaintPhotoInput = $state<HTMLInputElement | null>(null);
    let isSubmittingComplaint = $state(false);

    const errors = $derived((page.props.errors ?? {}) as Record<string, string>);

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

    function formatRupiah(amount: number): string {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    }

    function openUploadModal(payment: TenantPaymentItem): void {
        uploadingPayment = payment;
        if (proofInput) {
            proofInput.value = '';
        }
    }

    function submitProof(event: SubmitEvent): void {
        event.preventDefault();
        if (!uploadingPayment) {
            return;
        }

        const rawFile = proofInput?.files?.[0];
        if (!rawFile) {
            return;
        }

        isUploadingProof = true;
        const formData = new FormData();
        formData.append('proof_photo', rawFile);

        router.post(`/tenant/payments/${uploadingPayment.id}/proof`, formData, {
            onFinish: () => {
                isUploadingProof = false;
            },
            onSuccess: () => {
                uploadingPayment = null;
            },
        });
    }

    function openComplaintModal(): void {
        complaintTitle = '';
        complaintDescription = '';
        if (complaintPhotoInput) {
            complaintPhotoInput.value = '';
        }
        isComplaintModalOpen = true;
    }

    function submitComplaint(event: SubmitEvent): void {
        event.preventDefault();
        isSubmittingComplaint = true;

        const formData = new FormData();
        formData.append('title', complaintTitle);
        formData.append('description', complaintDescription);

        const rawPhoto = complaintPhotoInput?.files?.[0];
        if (rawPhoto) {
            formData.append('photo', rawPhoto);
        }

        router.post('/tenant/complaints', formData, {
            onFinish: () => {
                isSubmittingComplaint = false;
            },
            onSuccess: () => {
                isComplaintModalOpen = false;
            },
        });
    }

    function logout(): void {
        router.post('/logout');
    }
</script>

<AppHead title="Portal Penghuni - Kontrakan Ibu" />

<div class="min-h-screen bg-gray-50 text-slate-800">
    {#if toastMessage}
        <div class="fixed top-4 right-4 left-4 z-60 mx-auto flex max-w-sm items-center gap-3 rounded-2xl border bg-white p-4 shadow-xl sm:right-5 sm:left-auto {toastType === 'success' ? 'border-emerald-200' : 'border-rose-200'}">
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
                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-gray-100"
            >
                &#10005;
            </button>
        </div>
    {/if}

    <header class="sticky top-0 z-30 border-b border-slate-800 bg-slate-950 text-white shadow-xs">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 font-bold text-white">
                    K
                </div>
                <div>
                    <p class="text-sm font-bold sm:text-base">Portal Penghuni Kontrakan</p>
                    <p class="text-xs text-slate-400">{page.props.auth.user?.name} · {page.props.auth.user?.phone}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="/"
                    class="rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white"
                >
                    Halaman Depan
                </a>
                <button
                    type="button"
                    onclick={logout}
                    class="cursor-pointer rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300 transition hover:border-rose-600 hover:bg-rose-600 hover:text-white"
                >
                    Keluar
                </button>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
        {#if !lease}
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-2xs">
                <h1 class="text-lg font-bold text-slate-900">Belum Ada Kontrak Sewa Aktif</h1>
                <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                    Silakan hubungi Ibu Pengelola untuk mengaktifkan pintu kontrakan Anda.
                </p>
            </div>
        {:else}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs sm:p-6">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                            {lease.unit_number}
                        </span>
                        <h1 class="mt-2 text-lg font-bold text-slate-900 sm:text-xl">
                            Harga Sewa: <span class="text-blue-600">{formatRupiah(lease.monthly_price)}</span>/bulan
                        </h1>
                        <p class="mt-1 text-xs text-slate-500">
                            Mulai menghuni sejak <strong>{lease.start_date}</strong>
                        </p>
                        {#if lease.description}
                            <p class="mt-2 text-xs text-slate-600">{lease.description}</p>
                        {/if}
                    </div>

                    <button
                        type="button"
                        onclick={openComplaintModal}
                        class="w-full cursor-pointer rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700 sm:w-auto sm:text-sm"
                    >
                        + Lapor Kerusakan Fasilitas
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="border-b border-slate-100 p-4 sm:p-6">
                    <h2 class="text-sm font-bold text-slate-900 sm:text-base">Tagihan & Riwayat Pembayaran Sewa</h2>
                    <p class="text-xs text-slate-500">
                        Unggah foto bukti transfer pada tagihan yang belum lunas untuk divalidasi Ibu
                    </p>
                </div>

                {#if payments.length === 0}
                    <div class="p-8 text-center">
                        <p class="text-sm font-semibold text-slate-700">Belum Ada Tagihan</p>
                    </div>
                {:else}
                    <div class="divide-y divide-slate-100">
                        {#each payments as item}
                            <div class="flex flex-col justify-between gap-4 p-4 sm:flex-row sm:items-center sm:p-6">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900">Periode {item.billing_period}</span>
                                        {#if item.status === 'paid'}
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                                Lunas ({item.payment_method === 'cash' ? 'Tunai' : 'Transfer'})
                                            </span>
                                        {:else if item.status === 'waiting_verification' || item.status === 'pending'}
                                            <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                                Menunggu Validasi Ibu
                                            </span>
                                        {:else if item.status === 'rejected'}
                                            <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                                Bukti Ditolak — Unggah Ulang
                                            </span>
                                        {:else}
                                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                                Belum Dibayar
                                            </span>
                                        {/if}
                                    </div>

                                    <p class="text-base font-bold text-blue-600">{formatRupiah(item.amount)}</p>
                                    <p class="text-xs text-slate-500">Jatuh tempo: {item.due_date}</p>

                                    {#if item.rejection_reason}
                                        <p class="mt-1 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700">
                                            Catatan Ibu: {item.rejection_reason}
                                        </p>
                                    {/if}
                                </div>

                                <div class="flex items-center gap-2">
                                    {#if item.status === 'unpaid' || item.status === 'rejected'}
                                        <button
                                            type="button"
                                            onclick={() => openUploadModal(item)}
                                            class="w-full cursor-pointer rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700 sm:w-auto"
                                        >
                                            {item.status === 'rejected' ? 'Unggah Ulang Bukti' : 'Unggah Bukti Transfer'}
                                        </button>
                                    {:else if item.status === 'waiting_verification' || item.status === 'pending'}
                                        <button
                                            type="button"
                                            onclick={() => openUploadModal(item)}
                                            class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-gray-50 sm:w-auto"
                                        >
                                            Ganti Foto Bukti
                                        </button>
                                    {/if}
                                </div>
                            </div>
                        {/each}
                    </div>
                {/if}
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="border-b border-slate-100 p-4 sm:p-6">
                    <h2 class="text-sm font-bold text-slate-900 sm:text-base">Riwayat Laporan Kerusakan Anda</h2>
                    <p class="text-xs text-slate-500">Pantau status tindak lanjut perbaikan fasilitas dari Ibu Pengelola</p>
                </div>

                {#if complaints.length === 0}
                    <div class="p-8 text-center">
                        <p class="text-xs text-slate-500">Belum ada laporan kerusakan yang Anda kirimkan.</p>
                    </div>
                {:else}
                    <div class="divide-y divide-slate-100">
                        {#each complaints as c}
                            <div class="space-y-2 p-4 sm:p-6">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">{c.title}</h3>
                                        <p class="text-xs text-slate-400">Dilaporkan: {c.created_at}</p>
                                    </div>
                                    {#if c.status === 'resolved'}
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Selesai Diperbaiki
                                        </span>
                                    {:else if c.status === 'in_progress'}
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Sedang Diperbaiki
                                        </span>
                                    {:else}
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            Menunggu Tindakan
                                        </span>
                                    {/if}
                                </div>

                                <p class="text-xs leading-relaxed text-slate-600">{c.description}</p>

                                {#if c.admin_response}
                                    <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-3 text-xs text-slate-700">
                                        <span class="font-bold text-blue-700">Balasan Ibu Pengelola:</span> {c.admin_response}
                                    </div>
                                {/if}
                            </div>
                        {/each}
                    </div>
                {/if}
            </div>
        {/if}
    </main>

    {#if uploadingPayment}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Unggah Bukti Transfer</h3>
                        <p class="text-xs text-slate-500">
                            Periode {uploadingPayment.billing_period} · <strong class="text-blue-600">{formatRupiah(uploadingPayment.amount)}</strong>
                        </p>
                    </div>
                    <button
                        type="button"
                        onclick={() => (uploadingPayment = null)}
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-gray-100"
                    >
                        &#10005;
                    </button>
                </div>

                <form onsubmit={submitProof} class="mt-4 space-y-4">
                    <div>
                        <label for="proof_photo" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Pilih Foto Struk / Bukti Transfer (Maks 10MB)
                        </label>
                        <input
                            id="proof_photo"
                            type="file"
                            bind:this={proofInput}
                            required
                            accept="image/png,image/jpeg,image/webp"
                            class="mt-1.5 block w-full text-xs text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3.5 file:py-2 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                        />
                        {#if errors.proof_photo}
                            <p class="mt-1 text-xs font-medium text-rose-600">{errors.proof_photo}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                        <button
                            type="button"
                            onclick={() => (uploadingPayment = null)}
                            class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 sm:w-auto"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={isUploadingProof}
                            class="w-full cursor-pointer rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50 sm:w-auto"
                        >
                            {isUploadingProof ? 'Mengirim...' : 'Kirim Bukti Transfer'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    {/if}

    {#if isComplaintModalOpen}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Lapor Kerusakan Fasilitas</h3>
                        <p class="text-xs text-slate-500">Sampaikan keluhan fasilitas pintu kontrakan Anda kepada Ibu</p>
                    </div>
                    <button
                        type="button"
                        onclick={() => (isComplaintModalOpen = false)}
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-gray-100"
                    >
                        &#10005;
                    </button>
                </div>

                <form onsubmit={submitComplaint} class="mt-4 space-y-4">
                    <div>
                        <label for="complaint_title" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Bagian yang Rusak / Judul Keluhan
                        </label>
                        <input
                            id="complaint_title"
                            type="text"
                            bind:value={complaintTitle}
                            required
                            placeholder="Contoh: Keran kamar mandi patah / Atap dapur rembes"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                        />
                        {#if errors.title}
                            <p class="mt-1 text-xs font-medium text-rose-600">{errors.title}</p>
                        {/if}
                    </div>

                    <div>
                        <label for="complaint_description" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Keterangan Detail
                        </label>
                        <textarea
                            id="complaint_description"
                            rows="3"
                            bind:value={complaintDescription}
                            required
                            placeholder="Jelaskan kondisi kerusakan secara singkat agar Ibu bisa menyiapkan tukang yang sesuai."
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none"
                        ></textarea>
                        {#if errors.description}
                            <p class="mt-1 text-xs font-medium text-rose-600">{errors.description}</p>
                        {/if}
                    </div>

                    <div>
                        <label for="complaint_photo" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Foto Kerusakan (Opsional, Maks 10MB)
                        </label>
                        <input
                            id="complaint_photo"
                            type="file"
                            bind:this={complaintPhotoInput}
                            accept="image/png,image/jpeg,image/webp"
                            class="mt-1.5 block w-full text-xs text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3.5 file:py-2 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                        />
                        {#if errors.photo}
                            <p class="mt-1 text-xs font-medium text-rose-600">{errors.photo}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                        <button
                            type="button"
                            onclick={() => (isComplaintModalOpen = false)}
                            class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 sm:w-auto"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmittingComplaint}
                            class="w-full cursor-pointer rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50 sm:w-auto"
                        >
                            {isSubmittingComplaint ? 'Mengirim...' : 'Kirim Laporan'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    {/if}
</div>