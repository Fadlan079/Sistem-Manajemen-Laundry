<script setup>
import DashboardLayout from '@/Layouts/dashboard.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const services = ref([
    { id: 1, name: 'Cuci Kering', price: 7000, unit: 'Kg', duration: '2 Hari', category: 'Kiloan' },
    { id: 2, name: 'Cuci Setrika', price: 10000, unit: 'Kg', duration: '2 Hari', category: 'Kiloan' },
    { id: 3, name: 'Setrika Saja', price: 5000, unit: 'Kg', duration: '1 Hari', category: 'Kiloan' },
    { id: 4, name: 'Bedcover Besar', price: 35000, unit: 'Pcs', duration: '3 Hari', category: 'Satuan' },
    { id: 5, name: 'Jas / Blazer', price: 25000, unit: 'Pcs', duration: '3 Hari', category: 'Satuan' },
]);

// State untuk Edit
const isEditModalOpen = ref(false);
const editingService = ref(null);

const openEditModal = (service) => {
    editingService.value = { ...service };
    isEditModalOpen.value = true;
};

const saveService = () => {
    const index = services.value.findIndex(s => s.id === editingService.value.id);
    if (index !== -1) {
        services.value[index] = editingService.value;
    }
    isEditModalOpen.value = false;
};
</script>

<template>
    <Head title="Daftar Layanan" />

    <DashboardLayout title="layanan">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Layanan & Harga</h2>
                <span class="text-sm text-gray-500 italic">*Klik kartu untuk ubah harga</span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="service in services" :key="service.id" 
                     @click="openEditModal(service)"
                     class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 hover:border-indigo-400 cursor-pointer transition group relative">
                    
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-indigo-50 rounded-xl group-hover:bg-indigo-600 transition duration-300">
                            <svg class="w-6 h-6 text-indigo-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="px-3 py-1 bg-gray-100 text-gray-600 text-[10px] font-bold uppercase rounded-full tracking-widest">
                                {{ service.category }}
                            </span>
                            <span class="text-[10px] text-indigo-500 mt-2 opacity-0 group-hover:opacity-100 transition">Klik untuk Edit</span>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-800 mb-1">{{ service.name }}</h3>
                    <div class="flex items-baseline gap-1 mb-4">
                        <span class="text-2xl font-black text-indigo-600">Rp {{ service.price.toLocaleString('id-ID') }}</span>
                        <span class="text-sm text-gray-400">/ {{ service.unit }}</span>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-gray-500 border-t pt-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Estimasi: {{ service.duration }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isEditModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="isEditModalOpen = false"></div>
            
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800">Edit Harga Layanan</h3>
                    <p class="text-sm text-gray-500">{{ editingService.name }}</p>
                </div>

                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga per {{ editingService.unit }}</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">Rp</span>
                            <input v-model="editingService.price" type="number" 
                                   class="w-full pl-12 border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-3">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estimasi Durasi</label>
                        <input v-model="editingService.duration" type="text" 
                               class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-3"
                               placeholder="Contoh: 2 Hari">
                    </div>
                </div>

                <div class="p-6 bg-gray-50 flex justify-end gap-3">
                    <button @click="isEditModalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-600">
                        Batal
                    </button>
                    <button @click="saveService" class="px-8 py-3 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                        Perbarui Harga
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.animate-in {
    animation: animate-in 0.2s ease-out;
}
@keyframes animate-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>