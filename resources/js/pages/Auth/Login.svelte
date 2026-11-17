<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';

    const form = useForm({
        phone: '',
        password: '',
        remember: false,
    });

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        form.post('/login', {
            onFinish: () => form.reset('password'),
        });
    }
</script>

<AppHead title="Masuk ke Sistem Kontrakan" />

<div class="flex min-h-screen bg-gray-50">
    <div class="hidden lg:flex lg:w-5/12 lg:flex-col lg:justify-between bg-slate-950 p-12 text-white">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 font-bold text-white shadow-sm">
                K
            </div>
            <span class="text-xl font-bold tracking-tight text-white">Kontrakan Ibu</span>
        </div>

        <div class="max-w-md space-y-4">
            <span class="inline-flex items-center gap-2 rounded-full bg-blue-500/10 px-3.5 py-1.5 text-xs font-semibold text-blue-400 ring-1 ring-blue-500/20">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span>
                Sistem Manajemen Hunian
            </span>
            <h2 class="text-3xl font-bold leading-tight text-white">
                Kelola sewa, tagihan bulanan, dan kenyamanan penghuni dalam satu tempat.
            </h2>
            <p class="text-sm leading-relaxed text-slate-400">
                Pantau status ketersediaan pintu kontrakan, validasi bukti transfer, dan tanggapi laporan fasilitas dengan mudah dari HP maupun laptop.
            </p>
        </div>

        <div class="text-xs text-slate-500">
            &copy; {new Date().getFullYear()} Kontrakan Ibu. Seluruh hak cipta dilindungi.
        </div>
    </div>

    <div class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-16">
        <div class="w-full max-w-md rounded-2xl border border-slate-200/80 bg-white p-8 shadow-xs sm:p-10">
            <div class="mb-8">
                <div class="mb-4 flex items-center gap-2.5 lg:hidden">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 font-bold text-white">
                        K
                    </div>
                    <span class="text-lg font-bold text-slate-900">Kontrakan Ibu</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Selamat Datang Kembali</h1>
                <p class="mt-2 text-sm text-slate-600">
                    Masukkan nomor WhatsApp dan kata sandi akun Anda
                </p>
            </div>

            <form onsubmit={submit} class="space-y-5">
                <div>
                    <label for="phone" class="block text-sm font-semibold text-slate-700">
                        Nomor WhatsApp
                    </label>
                    <input
                        id="phone"
                        type="tel"
                        bind:value={form.phone}
                        required
                        autocomplete="tel"
                        placeholder="081312132075"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-gray-50/50 px-4 py-2.5 text-sm text-slate-900 transition focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    />
                    {#if form.errors.phone}
                        <p class="mt-1.5 text-xs font-medium text-rose-600">
                            {form.errors.phone}
                        </p>
                    {/if}
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700">
                        Kata Sandi
                    </label>
                    <input
                        id="password"
                        type="password"
                        bind:value={form.password}
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-gray-50/50 px-4 py-2.5 text-sm text-slate-900 transition focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    />
                    {#if form.errors.password}
                        <p class="mt-1.5 text-xs font-medium text-rose-600">
                            {form.errors.password}
                        </p>
                    {/if}
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm font-medium text-slate-600">
                        <input
                            type="checkbox"
                            bind:checked={form.remember}
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-600"
                        />
                        Ingat saya di perangkat ini
                    </label>
                </div>

                <button
                    type="submit"
                    disabled={form.processing}
                    class="w-full cursor-pointer rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-xs transition hover:bg-blue-700 focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {form.processing ? 'Memverifikasi...' : 'Masuk ke Dashboard'}
                </button>
            </form>

            <div class="mt-8 border-t border-slate-100 pt-6 text-center">
                <a href="/" class="text-xs font-semibold text-slate-500 transition hover:text-blue-600">
                    &larr; Kembali ke Halaman Katalog Kontrakan
                </a>
            </div>
        </div>
    </div>
</div>