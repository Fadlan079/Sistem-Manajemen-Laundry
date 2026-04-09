<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/dashboard.vue';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head title="Dashboard Admin - Hi Wash Laundry" />

    <DashboardLayout title="Overview">
        <!-- Main Content -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-text tracking-tight mb-2">Welcome back, {{ user?.name || 'Admin' }} 👋</h1>
            <p class="text-muted text-sm">Here's an overview of what's happening at Hi Wash Laundry today.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <div class="bg-surface rounded-2xl p-6 shadow-sm border border-border/50 flex flex-col justify-between group hover:shadow-md transition">
                <div class="flex justify-between items-start mb-4">
                     <p class="text-muted text-sm font-medium">Total Pesanan</p>
                     <div class="p-2 bg-primary/10 text-primary rounded-xl group-hover:scale-110 transition-transform">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                     </div>
                </div>
                <div>
                    <h3 class="text-3xl font-bold text-text">0</h3>
                    <p class="text-xs text-muted mt-2 flex items-center gap-1"><span class="text-primary font-medium">0%</span> dari bulan lalu</p>
                </div>
            </div>
            
            <div class="bg-surface rounded-2xl p-6 shadow-sm border border-border/50 flex flex-col justify-between group hover:shadow-md transition">
                <div class="flex justify-between items-start mb-4">
                     <p class="text-muted text-sm font-medium">Total Pengguna</p>
                     <div class="p-2 bg-blue-50 text-blue-600 rounded-xl group-hover:scale-110 transition-transform">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                     </div>
                </div>
                <div>
                    <h3 class="text-3xl font-bold text-text">0</h3>
                    <p class="text-xs text-muted mt-2 flex items-center gap-1"><span class="text-primary font-medium">0%</span> dari bulan lalu</p>
                </div>
            </div>
            
            <div class="bg-surface rounded-2xl p-6 shadow-sm border border-border/50 flex flex-col justify-between group hover:shadow-md transition">
                <div class="flex justify-between items-start mb-4">
                     <p class="text-muted text-sm font-medium">Pesanan Hari Ini</p>
                     <div class="p-2 bg-secondary/20 text-yellow-600 rounded-xl group-hover:scale-110 transition-transform">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                     </div>
                </div>
                <div>
                    <h3 class="text-3xl font-bold text-text">0</h3>
                    <p class="text-xs text-muted mt-2 flex items-center gap-1">belum ada data baru</p>
                </div>
            </div>
            
            <div class="bg-surface rounded-2xl p-6 shadow-sm border border-border/50 flex flex-col justify-between group hover:shadow-md transition">
                <div class="flex justify-between items-start mb-4">
                     <p class="text-muted text-sm font-medium">Pendapatan</p>
                     <div class="p-2 bg-green-50 text-green-600 rounded-xl group-hover:scale-110 transition-transform">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                     </div>
                </div>
                <div>
                    <h3 class="text-3xl font-bold text-text">Rp 0</h3>
                    <p class="text-xs text-muted mt-2 flex items-center gap-1">belum ada transaksi</p>
                </div>
            </div>
        </div>

        <!-- Placeholder table area -->
        <div class="bg-surface rounded-2xl border border-border/50 shadow-sm p-12 text-center text-muted flex flex-col items-center justify-center min-h-[300px]">
            <div class="w-16 h-16 bg-container rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-primary/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-text mb-1">No Orders Yet</h3>
            <p class="text-sm max-w-sm mb-6">You don't have any orders right now. Data pesanan akan tampil di sini saat pelanggan membuat order baru.</p>
            <button class="bg-primary hover:bg-primary-hover text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                Create First Order
            </button>
        </div>
    </DashboardLayout>
</template>
