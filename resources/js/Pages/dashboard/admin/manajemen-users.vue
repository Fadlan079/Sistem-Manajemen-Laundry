<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    LinearScale, 
    PointElement, 
    CategoryScale,
    Filler,
    ArcElement,
    BarElement
} from 'chart.js';

ChartJS.register(
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    LinearScale, 
    PointElement, 
    CategoryScale,
    Filler,
    ArcElement,
    BarElement
);

const user = computed(() => usePage().props.auth.user);

// State for collapsible chart
const isChartExpanded = ref(true);

// Load preference from local storage
onMounted(() => {
    const savedState = localStorage.getItem('manajemen-users-chart-expanded');
    if (savedState !== null) {
        isChartExpanded.value = savedState === 'true';
    }
});

// Save preference on change
watch(isChartExpanded, (newValue) => {
    localStorage.setItem('manajemen-users-chart-expanded', newValue.toString());
});

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

/** CHART CONFIGURATIONS **/

// 1. Distribusi Role User (Doughnut)
const chartDistributionData = {
    labels: ['Admin', 'Operator', 'Kurir', 'Pelanggan'],
    datasets: [{
        data: [4, 18, 12, 118],
        backgroundColor: ['#e11d48', '#059669', '#d97706', '#475569'], // Tailwind colors
        borderWidth: 0,
        hoverOffset: 4
    }]
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } },
        tooltip: {
            backgroundColor: '#0A0A0B',
            titleFont: { family: 'Space Mono', size: 10 },
            bodyFont: { family: 'Inter', size: 12, weight: 'bold' },
            padding: 12,
            cornerRadius: 4,
            displayColors: true
        }
    },
    cutout: '70%'
};

// 2. Pertumbuhan User (Line)
const chartGrowthData = {
    labels: ['M1', 'M2', 'M3', 'M4', 'M5', 'M6', 'M7', 'M8'],
    datasets: [
        {
            label: 'User Baru',
            data: [12, 19, 15, 28, 22, 35, 30, 45],
            borderColor: '#5B4CF3', // primary
            backgroundColor: 'rgba(91, 76, 243, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#5B4CF3',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
        }
    ]
};

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0A0A0B',
            titleFont: { family: 'Space Mono', size: 10 },
            bodyFont: { family: 'Inter', size: 12, weight: 'bold' },
            padding: 12,
            cornerRadius: 4,
            displayColors: false
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.03)' },
            border: { display: false },
            ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' }
        },
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' }
        }
    }
};

// 3. Verifikasi User (Doughnut)
const chartVerificationData = {
    labels: ['Aktif', 'Non-Aktif'],
    datasets: [{
        data: [140, 12],
        backgroundColor: ['#059669', '#cbd5e1'], // Emerald and Slate
        borderWidth: 0,
        hoverOffset: 4
    }]
};

// 4. Growth per Role (Bar - Stacked)
const chartGrowthPerRoleData = {
    labels: ['M1', 'M2', 'M3', 'M4'],
    datasets: [
        { label: 'Admin', data: [1, 0, 1, 2], backgroundColor: '#e11d48', borderRadius: 4 },
        { label: 'Operator', data: [3, 5, 2, 8], backgroundColor: '#059669', borderRadius: 4 },
        { label: 'Kurir', data: [2, 3, 4, 3], backgroundColor: '#d97706', borderRadius: 4 },
    ]
};

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } },
        tooltip: {
            backgroundColor: '#0A0A0B',
            titleFont: { family: 'Space Mono', size: 10 },
            bodyFont: { family: 'Inter', size: 12, weight: 'bold' },
            padding: 12,
            cornerRadius: 4
        }
    },
    scales: {
        x: { 
            stacked: true, 
            grid: { display: false },
            border: { display: false },
            ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' }
        },
        y: { 
            stacked: true, 
            beginAtZero: true, 
            grid: { color: 'rgba(0,0,0,0.03)' },
            border: { display: false },
            ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' }
        }
    }
};

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

            <!-- Chart Section (Collapsible) -->
            <div class="bg-surface border border-border rounded-sm shadow-sm overflow-hidden">
                <button 
                    @click="isChartExpanded = !isChartExpanded"
                    class="w-full flex items-center justify-between px-6 py-4 hover:bg-container/50 transition-colors group"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-text">Visual Analitik - Pertumbuhan User</h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-[9px] font-mono text-muted uppercase tracking-widest italic">Live Trend Analysis</span>
                        </div>
                        <i :class="['fa-solid transition-transform duration-300', isChartExpanded ? 'fa-chevron-up' : 'fa-chevron-down text-primary']"></i>
                    </div>
                </button>

                <!-- Change max-h strategy here: max-h-[2000px] ensures it fits safely on mobile grids -->
                <transition 
                    enter-active-class="transition-all duration-500 ease-out"
                    leave-active-class="transition-all duration-300 ease-in"
                    enter-from-class="max-h-0 opacity-0"
                    enter-to-class="max-h-[2000px] opacity-100"
                    leave-from-class="max-h-[2000px] opacity-100"
                    leave-to-class="max-h-0 opacity-0"
                >
                    <div v-show="isChartExpanded" class="p-6 border-t border-dashed border-border overflow-hidden">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-12">
                            <!-- 1. Distribusi Role -->
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Distribusi Role</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Doughnut :data="chartDistributionData" :options="doughnutOptions" />
                                </div>
                            </div>

                            <!-- 2. Pertumbuhan Global -->
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Trend Registrasi Global</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Line :data="chartGrowthData" :options="lineOptions" />
                                </div>
                            </div>

                            <!-- 3. Status Verifikasi -->
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Status Verifikasi</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Doughnut :data="chartVerificationData" :options="doughnutOptions" />
                                </div>
                            </div>

                            <!-- 4. Growth per Role -->
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Registrasi per Role</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Bar :data="chartGrowthPerRoleData" :options="barOptions" />
                                </div>
                            </div>
                        </div>

                    </div>
                </transition>
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