<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head title="Dashboard Admin - Hi Wash Laundry" />

    <div class="min-h-screen bg-gray-950 text-white font-sans">
        <!-- Sidebar -->
        <aside class="fixed top-0 left-0 h-full w-64 bg-gray-900 border-r border-gray-800 flex flex-col z-50">
            <div class="px-6 py-5 border-b border-gray-800">
                <div class="flex items-center gap-2">
                    <svg class="w-8 h-8 fill-red-500" viewBox="0 0 24 24">
                        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z"/>
                    </svg>
                    <span class="font-bold text-lg tracking-tight">Hi Wash <span class="text-red-400 font-light">Admin</span></span>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-1">
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-red-600/20 text-red-400 font-medium text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white text-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Manajemen User
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white text-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Semua Pesanan
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white text-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Pengaturan
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
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold">Selamat datang, {{ user?.name }} 👋</h1>
                    <p class="text-gray-400 text-sm mt-1">Panel administrasi Hi Wash Laundry</p>
                </div>
                <span class="px-3 py-1 bg-red-600/20 text-red-400 text-xs font-semibold rounded-full uppercase tracking-wider">Admin</span>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
                    <p class="text-gray-400 text-sm">Total Pesanan</p>
                    <p class="text-3xl font-bold mt-1">0</p>
                    <p class="text-xs text-gray-500 mt-2">belum ada data</p>
                </div>
                <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
                    <p class="text-gray-400 text-sm">Total Pengguna</p>
                    <p class="text-3xl font-bold mt-1">0</p>
                    <p class="text-xs text-gray-500 mt-2">belum ada data</p>
                </div>
                <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
                    <p class="text-gray-400 text-sm">Pesanan Hari Ini</p>
                    <p class="text-3xl font-bold mt-1">0</p>
                    <p class="text-xs text-gray-500 mt-2">belum ada data</p>
                </div>
                <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
                    <p class="text-gray-400 text-sm">Pendapatan</p>
                    <p class="text-3xl font-bold mt-1">Rp 0</p>
                    <p class="text-xs text-gray-500 mt-2">belum ada data</p>
                </div>
            </div>

            <!-- Placeholder table area -->
            <div class="bg-gray-900 rounded-2xl border border-gray-800 p-8 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-sm">Data pesanan akan tampil di sini</p>
            </div>
        </main>
    </div>
</template>
