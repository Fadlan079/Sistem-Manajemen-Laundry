<script setup>
import { computed, ref, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);

defineProps({
    title: {
        type: String,
        default: 'Admin Console'
    }
});

defineEmits(['toggle-sidebar']);

// Global Search Logic
const searchStr = ref(page.props.filters?.search || '');

watch(() => page.props.filters?.search, (newVal) => {
    if (newVal !== undefined && newVal !== searchStr.value) {
        searchStr.value = newVal || '';
    }
});

let timeout;
watch(searchStr, (value) => {
    // Prevent redundant requests if it matches current URL state
    if (value === (page.props.filters?.search || '')) return;

    clearTimeout(timeout);
    timeout = setTimeout(() => {
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set('search', value);
        } else {
            url.searchParams.delete('search');
        }
        router.get(url.pathname + url.search, {}, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});
</script>

<template>
    <header class="h-20 flex items-center justify-between px-4 lg:px-6 bg-[#E30613] border-b-4 border-[#FFE800] sticky top-0 z-40 shadow-lg transition-all">
        <div class="flex items-center lg:w-0">
            <!-- Hamburger Menu Button (Mobile Only) -->
            <button 
                @click="$emit('toggle-sidebar')"
                class="lg:hidden p-2 rounded-lg hover:bg-white/10 text-white transition"
                aria-label="Toggle Sidebar"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Search Bar (Full Width Adaptive) -->
        <div class="hidden sm:flex flex-1 px-4 lg:px-10">
            <div class="relative group w-full max-w-3xl">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-white/50 transition group-focus-within:text-[#FFE800]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    v-model="searchStr"
                    placeholder="Cari data di seluruh sistem HiWash..." 
                    class="block w-full pl-10 pr-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-[#FFE800]/50 focus:border-[#FFE800] focus:bg-white/20 transition-all shadow-inner"
                >
            </div>
        </div>

        <!-- Right Side: Actions & Profile -->
        <div class="flex items-center gap-3 lg:gap-6">
            <button class="relative p-2 text-white/80 hover:text-[#FFE800] transition rounded-xl hover:bg-white/10 hidden xs:flex">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <div class="absolute top-2 right-2 w-2 h-2 rounded-full bg-[#FFE800] border-2 border-[#E30613]"></div>
            </button>
            
            <div class="h-8 w-px bg-white/10 hidden sm:block"></div>

            <button class="flex items-center gap-2 group focus:outline-none">
                <div class="w-9 h-9 rounded-lg bg-white text-[#E30613] flex items-center justify-center font-black border-2 border-transparent group-hover:border-[#FFE800] transition overflow-hidden shadow-md">
                    <img v-if="user?.profile_photo_url" :src="user.profile_photo_url" alt="Profile" class="w-full h-full object-cover">
                    <span v-else>{{ user?.name ? user.name.charAt(0).toUpperCase() : 'A' }}</span>
                </div>
                <div class="hidden xl:flex flex-col items-start leading-none gap-0.5">
                    <span class="text-xs font-black text-white uppercase tracking-tight">{{ user?.name || 'Administrator' }}</span>
                    <span class="text-[9px] text-[#FFE800] font-black uppercase tracking-widest">{{ user?.role || 'Admin' }}</span>
                </div>
            </button>
        </div>
    </header>
</template>
