<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';

// Data Dummy Pesanan
const orders = ref([
    { id: 'ORD-001', customer: 'Budi Santoso', type: 'Cuci Kering', weight: '5', price: '35000', status: 'Pending', date: '13 April 2026' },
    { id: 'ORD-002', customer: 'Siti Aminah', type: 'Cuci Setrika', weight: '3', price: '27000', status: 'Proses', date: '13 April 2026' },
    { id: 'ORD-003', customer: 'Agus Pratama', type: 'Bedcover', weight: '1', price: '45000', status: 'Selesai', date: '12 April 2026' },
]);

// State untuk Modal
const isEditModalOpen = ref(false);
const editingOrder = ref(null);

// Fungsi buka modal & isi data
const openEditModal = (order) => {
    editingOrder.value = { ...order }; // Copy data agar tidak merusak tabel utama sebelum simpan
    isEditModalOpen.value = true;
};

// Fungsi simpan
const saveEdit = () => {
    const index = orders.value.findIndex(o => o.id === editingOrder.value.id);
    if (index !== -1) {
        orders.value[index] = editingOrder.value;
    }
    isEditModalOpen.value = false;
};

const getStatusClass = (status) => {
    switch (status) {
        case 'Pending': return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'Proses': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'Selesai': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};
</script>

<template>
    <Head title="Pesanan Masuk" />
    <DashboardLayout title="Pesanan Masuk">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pesanan Masuk</h2>
        </template>

        <div class="max-w-7xl mx-auto pb-10">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-bold text-gray-700">Daftar Antrean</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 text-xs font-bold uppercase text-gray-500">
                                <th class="px-6 py-4 tracking-wider">ID</th>
                                <th class="px-6 py-4 tracking-wider">Pelanggan</th>
                                <th class="px-6 py-4 tracking-wider">Layanan</th>
                                <th class="px-6 py-4 tracking-wider text-center">Berat (Kg/Unit)</th>
                                <th class="px-6 py-4 tracking-wider">Total</th>
                                <th class="px-6 py-4 tracking-wider">Status</th>
                                <th class="px-6 py-4 tracking-wider text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50/80 transition">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600">{{ order.id }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-800">{{ order.customer }}</div>
                                    <div class="text-[10px] text-gray-400 uppercase tracking-widest">{{ order.date }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ order.type }}</td>
                                <td class="px-6 py-4 text-center text-gray-600">{{ order.weight }}</td>
                                <td class="px-6 py-4 font-bold text-gray-900">
                                    Rp {{ parseInt(order.price).toLocaleString('id-ID') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-3 py-1 rounded-full text-[11px] font-bold border', getStatusClass(order.status)]">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <button @click="openEditModal(order)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition border border-indigo-100 bg-white">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </button>
                                        <button class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition border border-emerald-100 bg-white">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="isEditModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="isEditModalOpen = false"></div>
            
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-xl font-bold text-gray-800">Edit Pesanan {{ editingOrder.id }}</h3>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pelanggan</label>
                        <input v-model="editingOrder.customer" type="text" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Layanan</label>
                            <select v-model="editingOrder.type" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                                <option>Cuci Kering</option>
                                <option>Cuci Setrika</option>
                                <option>Bedcover</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select v-model="editingOrder.status" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                                <option>Pending</option>
                                <option>Proses</option>
                                <option>Selesai</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Berat (Kg)</label>
                            <input v-model="editingOrder.weight" type="number" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total Harga (Rp)</label>
                            <input v-model="editingOrder.price" type="number" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-gray-50 flex justify-end gap-3">
                    <button @click="isEditModalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition">
                        Batal
                    </button>
                    <button @click="saveEdit" class="px-6 py-2 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 shadow-md transition">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.animate-in {
    animation-name: animate-in;
    animation-duration: 0.2s;
    animation-timing-function: ease-out;
}
@keyframes animate-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>