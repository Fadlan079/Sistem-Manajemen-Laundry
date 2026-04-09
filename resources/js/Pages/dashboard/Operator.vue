<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head title="Dashboard Operator - Hi Wash Laundry" />

    <div class="min-h-screen bg-gray-950 text-white font-sans">
        <!-- Sidebar -->
        <aside class="fixed top-0 left-0 h-full w-64 bg-gray-900 border-r border-gray-800 flex flex-col z-50">
            <div class="px-6 py-5 border-b border-gray-800">
                <div class="flex items-center gap-2">
                    <svg class="w-8 h-8 fill-blue-400" viewBox="0 0 24 24">
                        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z"/>
                    </svg>
                    <span class="font-bold text-lg tracking-tight">Hi Wash <span class="text-blue-400 font-light">Operator</span></span>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-1">
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-blue-600/20 text-blue-400 font-medium text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white text-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Proses Laundry
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white text-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Riwayat
                </a>
            </nav>

            <div class="px-4 py-4 border-t border-gray-800">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-gray-400 hover:bg-red-900/30 hover:text-red-400 text-sm transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Keluar
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold">Halo, {{ user?.name }} 👋</h1>
                    <p class="text-gray-400 text-sm mt-1">Panel operator laundry</p>
                </div>
                <span class="px-3 py-1 bg-blue-600/20 text-blue-400 text-xs font-semibold rounded-full uppercase tracking-wider">Operator</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
                    <p class="text-gray-400 text-sm">Antrian Cuci</p>
                    <p class="text-3xl font-bold mt-1">0</p>
                </div>
                <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
                    <p class="text-gray-400 text-sm">Sedang Diproses</p>
                    <p class="text-3xl font-bold mt-1">0</p>
                </div>
                <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
                    <p class="text-gray-400 text-sm">Selesai Hari Ini</p>
                    <p class="text-3xl font-bold mt-1">0</p>
                </div>
            </div>

            <div class="bg-gray-900 rounded-2xl border border-gray-800 p-8 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                <p class="text-sm">Antrian pesanan akan tampil di sini</p>
            </div>
        </main>
    </div>
</template>
