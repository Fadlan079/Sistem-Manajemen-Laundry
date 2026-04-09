<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);

defineProps({
    title: {
        type: String,
        default: 'Admin Console'
    }
});
</script>

<template>
    <header class="h-20 flex items-center justify-between px-8 bg-surface border-b border-border sticky top-0 z-40">
        <!-- Title area -->
        <div class="flex items-center">
            <h1 class="text-xl font-bold text-primary">{{ title }}</h1>
        </div>

        <!-- Search Bar -->
        <div class="flex-1 max-w-xl px-12">
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted transition group-focus-within:text-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    placeholder="Search orders, customers, or services..." 
                    class="block w-full pl-10 pr-3 py-2.5 border border-border rounded-full bg-container text-sm placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary focus:bg-white transition-all shadow-sm"
                >
            </div>
        </div>

        <!-- Right Side: Actions & Profile -->
        <div class="flex items-center gap-6">
            <button class="relative p-2 text-muted hover:text-primary transition rounded-full hover:bg-container">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <div class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 border-2 border-white"></div>
            </button>
            <button class="flex items-center gap-2 text-muted hover:text-primary transition font-medium text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Support
            </button>
            
            <div class="h-8 w-px bg-border"></div>

            <button class="flex items-center gap-3 group focus:outline-none">
                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold border-2 border-transparent group-hover:border-primary/20 transition overflow-hidden">
                    <img v-if="user?.profile_photo_url" :src="user.profile_photo_url" alt="Profile" class="w-full h-full object-cover">
                    <span v-else>{{ user?.name ? user.name.charAt(0).toUpperCase() : 'A' }}</span>
                </div>
            </button>
        </div>
    </header>
</template>
