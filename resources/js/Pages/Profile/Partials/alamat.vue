<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    addresses: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['back']);

const viewMode = ref('list'); // 'list' or 'form'
const editingAddress = ref(null);

const form = useForm({
    label: '',
    address: '',
    is_default: false,
});

const openAddForm = () => {
    editingAddress.value = null;
    form.reset();
    form.clearErrors();
    viewMode.value = 'form';
};

const openEditForm = (address) => {
    editingAddress.value = address;
    form.label = address.label;
    form.address = address.address;
    form.is_default = address.is_default;
    form.clearErrors();
    viewMode.value = 'form';
};

const cancelForm = () => {
    viewMode.value = 'list';
    editingAddress.value = null;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (editingAddress.value) {
        form.put(route('profile.addresses.update', editingAddress.value.id), {
            preserveScroll: true,
            onSuccess: () => cancelForm(),
        });
    } else {
        form.post(route('profile.addresses.store'), {
            preserveScroll: true,
            onSuccess: () => cancelForm(),
        });
    }
};

const deleteForm = useForm({});
const deleteAddress = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus alamat ini?')) {
        deleteForm.delete(route('profile.addresses.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <div class="flex flex-col">

        <!-- View: List -->
        <div v-if="viewMode === 'list'" class="flex-1 space-y-4 pb-32">
            <!-- Existing Addresses -->
            <div v-if="addresses.length > 0" class="space-y-4">
                <div v-for="address in addresses" :key="address.id" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3 flex-1">
                            <div class="w-10 h-10 bg-red-50 text-primary rounded-xl flex items-center justify-center text-lg shrink-0">
                                <i :class="address.label.toLowerCase().includes('rumah') ? 'fas fa-home' : (address.label.toLowerCase().includes('kantor') ? 'fas fa-building' : 'fas fa-map-marker-alt')"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-black text-gray-900">{{ address.label }}</h3>
                                    <span v-if="address.is_default" class="text-[9px] bg-primary/10 text-primary px-2 py-0.5 rounded-full font-bold uppercase tracking-widest">Utama</span>
                                </div>
                                <p class="text-xs text-gray-500 leading-relaxed font-medium">{{ address.address }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end gap-2 mt-4 pt-4 border-t border-gray-50 relative z-10">
                        <button @click="openEditForm(address)" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-primary transition-colors px-3 py-2 bg-gray-50 hover:bg-red-50 rounded-lg">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                        <button @click="deleteAddress(address.id)" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-red-600 transition-colors px-3 py-2 bg-gray-50 hover:bg-red-50 rounded-lg">
                            <i class="fas fa-trash-alt mr-1"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12 px-6">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mx-auto mb-4">
                    <i class="fas fa-map-marked-alt text-3xl"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900 mb-2">Belum Ada Alamat</h3>
                <p class="text-xs text-gray-500 font-medium">Anda belum menyimpan alamat penjemputan apapun.</p>
            </div>

            <!-- Fixed Add Button (Only if < 3) -->
            <div v-if="addresses.length < 3" class="fixed bottom-0 left-0 right-0 bg-white sm:relative pt-4 pb-6 sm:pb-0 px-6 sm:px-0 border-t rounded-t-3xl border-gray-100 sm:border-0 z-50 shadow-[0_-20px_40px_-10px_rgba(0,0,0,0.12)] sm:shadow-none">
                <div class="max-w-xl mx-auto">
                    <button @click="openAddForm" class="w-full bg-primary hover:bg-black text-white py-4 sm:py-5 rounded-2xl text-sm font-black uppercase tracking-widest shadow-xl shadow-primary/20 transition-all active:scale-[0.98] flex items-center justify-center gap-3">
                        <i class="fas fa-plus"></i> Tambah Alamat Baru
                    </button>
                    <p class="text-[10px] text-center text-gray-400 font-bold uppercase tracking-widest mt-3">
                        {{ addresses.length }} dari 3 alamat tersimpan
                    </p>
                </div>
            </div>
            
            <div v-else class="fixed bottom-0 left-0 right-0 bg-gray-50 sm:relative pt-4 pb-6 sm:pb-0 px-6 sm:px-0 border-t rounded-t-3xl border-gray-100 sm:border-0 z-50">
                <div class="max-w-xl mx-auto text-center py-2">
                    <p class="text-xs text-gray-500 font-bold"><i class="fas fa-info-circle text-primary mr-1"></i> Batas maksimal 3 alamat telah tercapai.</p>
                </div>
            </div>
        </div>

        <!-- View: Form -->
        <form v-else @submit.prevent="submitForm" class="flex-1 space-y-8 pb-32">
            <!-- Label -->
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-red-50 text-primary rounded-lg flex items-center justify-center text-sm shadow-sm">
                        <i class="fas fa-tag"></i>
                    </div>
                    <label class="text-sm font-black text-gray-800 tracking-tight">Label Alamat</label>
                </div>
                <div class="relative">
                    <input
                        type="text"
                        v-model="form.label"
                        placeholder="Contoh: Rumah, Kantor, Kosan"
                        class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all placeholder:text-gray-300 shadow-sm"
                        required
                        autofocus
                    >
                </div>
                <div v-if="form.errors.label" class="text-[10px] text-primary font-bold mt-2 ml-1 uppercase tracking-tight">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ form.errors.label }}
                </div>
            </div>

            <!-- Detail Alamat -->
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-red-50 text-primary rounded-lg flex items-center justify-center text-sm shadow-sm">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <label class="text-sm font-black text-gray-800 tracking-tight">Detail Alamat</label>
                </div>
                <div class="relative">
                    <textarea
                        v-model="form.address"
                        rows="4"
                        placeholder="Nama jalan, nomor rumah, RT/RW, patokan"
                        class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all placeholder:text-gray-300 shadow-sm resize-none"
                        required
                    ></textarea>
                </div>
                <div v-if="form.errors.address" class="text-[10px] text-primary font-bold mt-2 ml-1 uppercase tracking-tight">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ form.errors.address }}
                </div>
            </div>
            
            <!-- Default Checkbox -->
            <div class="flex items-center gap-3 p-4 bg-gray-50 border border-gray-100 rounded-2xl">
                <input 
                    type="checkbox" 
                    id="is_default" 
                    v-model="form.is_default"
                    class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary"
                >
                <label for="is_default" class="text-xs font-bold text-gray-700">Jadikan sebagai alamat utama</label>
            </div>
            
            <!-- Fixed Actions -->
            <div class="fixed bottom-0 left-0 right-0 bg-white sm:relative pt-4 pb-6 sm:pb-0 px-6 sm:px-0 border-t rounded-t-3xl border-gray-100 sm:border-0 z-50 shadow-[0_-20px_40px_-10px_rgba(0,0,0,0.12)] sm:shadow-none">
                <div class="max-w-xl mx-auto flex flex-col gap-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-primary hover:bg-black text-white py-4 sm:py-5 rounded-2xl text-sm font-black uppercase tracking-widest shadow-xl shadow-primary/20 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-3"
                    >
                        <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                        <span>Simpan Alamat</span>
                    </button>

                    <button
                        type="button"
                        @click="cancelForm"
                        class="w-full text-primary hover:text-black py-2 text-sm font-black uppercase tracking-widest transition-colors"
                    >
                        Batal
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<style scoped>
.text-primary {
    color: #E30613;
}
.bg-primary {
    background-color: #E30613;
}
.shadow-primary\/20 {
    --tw-shadow-color: rgba(227, 6, 19, 0.2);
    --tw-shadow: var(--tw-shadow-lookup, 0 0 #0000), 0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -4px var(--tw-shadow-color);
    box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
}
</style>