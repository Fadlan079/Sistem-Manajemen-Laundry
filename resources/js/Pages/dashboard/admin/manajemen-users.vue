<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';

const user = computed(() => usePage().props.auth.user);

// Dummy Data Pengguna
const users = ref([
    { id: 1, name: 'Fadlan Rizqi', email: 'fadlan@hiwash.com', role: 'admin', status: 'Aktif', joined: '12 Jan 2024', avatar: null },
    { id: 2, name: 'Budi Santoso', email: 'budi.ops@hiwash.com', role: 'operator', status: 'Aktif', joined: '15 Jan 2024', avatar: null },
    { id: 3, name: 'Siti Aminah', email: 'siti.kurir@hiwash.com', role: 'kurir', status: 'Aktif', joined: '20 Jan 2024', avatar: null },
    { id: 4, name: 'Andi Wijaya', email: 'andi@gmail.com', role: 'pelanggan', status: 'Non-Aktif', joined: '02 Feb 2024', avatar: null },
    { id: 5, name: 'Rina Kartika', email: 'rina.ops@hiwash.com', role: 'operator', status: 'Aktif', joined: '10 Feb 2024', avatar: null },
]);

// Statistik Pengguna
const userStats = [
    { label: 'Total Pengguna', value: '152', icon: 'fa-users', color: 'text-blue-600', bg: 'bg-blue-500/10' },
    { label: 'Administrator', value: '4', icon: 'fa-user-shield', color: 'text-rose-600', bg: 'bg-rose-500/10' },
    { label: 'Operator Toko', value: '18', icon: 'fa-user-gear', color: 'text-emerald-600', bg: 'bg-emerald-500/10' },
    { label: 'Kurir Lapangan', value: '12', icon: 'fa-motorcycle', color: 'text-amber-600', bg: 'bg-amber-500/10' },
];

const getRoleClass = (role) => {
    switch (role) {
        case 'admin': return 'bg-rose-100 text-rose-700 border-rose-200';
        case 'operator': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'kurir': return 'bg-amber-100 text-amber-700 border-amber-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};
</script>

<template>
    <Head title="Manajemen User - Hi Wash" />

    <DashboardLayout title="Manajemen User">
        <div class="space-y-10 pb-20">
            <!-- Header Section -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h1 class="text-4xl font-black tracking-tighter text-text uppercase">
                        Manajemen <span class="text-muted font-medium">User</span>
                    </h1>
                    <p class="text-muted font-medium italic">Kelola hak akses dan informasi personil HiWash Laundry.</p>
                </div>
                
                <button class="bg-primary hover:bg-primary-hover text-white px-8 py-4 rounded-sm font-black uppercase tracking-widest text-xs shadow-lg transition-all transform hover:-translate-y-1 flex items-center gap-3">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Pengguna Baru
                </button>
            </header>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="(stat, index) in userStats" :key="index" 
                    class="bg-surface border border-border p-6 rounded-sm shadow-sm relative group hover:border-primary/50 transition-all overflow-hidden">
                    <div class="absolute -right-2 -bottom-2 opacity-5 group-hover:opacity-10 transition-opacity">
                        <i :class="['fa-solid', stat.icon]" class="text-6xl"></i>
                    </div>
                    
                    <div class="flex items-center gap-4 mb-4">
                        <div :class="[stat.bg, stat.color]" class="w-10 h-10 rounded-full flex items-center justify-center text-sm">
                            <i :class="['fa-solid', stat.icon]"></i>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted">{{ stat.label }}</span>
                    </div>

                    <h3 class="text-3xl font-black text-text tracking-tighter">{{ stat.value }}</h3>
                </div>
            </div>

            <!-- Main Table Section -->
            <div class="space-y-4">
                <div class="flex flex-col md:flex-row gap-4 justify-between items-end">
                    <div class="w-full md:max-w-md relative group">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-muted group-focus-within:text-primary transition-colors"></i>
                        <input type="text" placeholder="Cari nama, email, atau role..." 
                            class="w-full pl-12 pr-4 py-3 bg-white border border-border rounded-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all font-medium text-sm">
                    </div>

                    <div class="flex gap-2">
                        <button class="px-4 py-3 border border-border bg-surface text-muted hover:text-text rounded-sm transition flex items-center gap-2 text-xs font-bold uppercase tracking-widest">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>
                        <button class="px-4 py-3 border border-border bg-surface text-muted hover:text-text rounded-sm transition flex items-center gap-2 text-xs font-bold uppercase tracking-widest">
                            <i class="fa-solid fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-sm shadow-xl overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-container/50 border-b border-border">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Profil Pengguna</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Role</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Tanggal Gabung</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="u in users" :key="u.id" class="hover:bg-container/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold border border-primary/20">
                                            {{ u.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-text group-hover:text-primary transition-colors">{{ u.name }}</p>
                                            <p class="text-xs text-muted font-medium">{{ u.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getRoleClass(u.role)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border">
                                        {{ u.role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div :class="u.status === 'Aktif' ? 'bg-emerald-500' : 'bg-slate-300'" class="w-2 h-2 rounded-full"></div>
                                        <span class="text-xs font-bold uppercase tracking-tighter text-text">{{ u.status }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-muted">
                                    {{ u.joined }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button class="w-8 h-8 flex items-center justify-center rounded-sm border border-border text-muted hover:bg-text hover:text-white transition-all">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>
                                        <button class="w-8 h-8 flex items-center justify-center rounded-sm border border-border text-muted hover:bg-rose-600 hover:border-rose-600 hover:text-white transition-all">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between px-2 pt-4">
                    <p class="text-[10px] font-bold text-muted uppercase tracking-widest">Menampilkan 5 dari 152 pengguna</p>
                    <div class="flex gap-2">
                        <button disabled class="w-8 h-8 flex items-center justify-center border border-border text-muted opacity-50 cursor-not-allowed rounded-sm">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button class="w-8 h-8 flex items-center justify-center border border-border bg-primary text-white font-bold text-xs rounded-sm">1</button>
                        <button class="w-8 h-8 flex items-center justify-center border border-border hover:bg-container text-text font-bold text-xs rounded-sm transition">2</button>
                        <button class="w-8 h-8 flex items-center justify-center border border-border hover:bg-container text-text font-bold text-xs rounded-sm transition">3</button>
                        <button class="w-8 h-8 flex items-center justify-center border border-border text-muted hover:text-text rounded-sm">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.font-mono {
    font-family: 'Space Mono', monospace;
}
</style>