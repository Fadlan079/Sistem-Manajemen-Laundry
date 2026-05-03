<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Topbar from '@/Components/Topbar.vue';
import { usePushNotifications } from '@/Composables/usePushNotifications.js';

defineProps({
    title: {
        type: String,
        default: 'Admin Console'
    }
});

const isSidebarOpen = ref(false);
const isSidebarCollapsed = ref(localStorage.getItem('sidebarCollapsed') === 'true');

const toggleCollapse = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem('sidebarCollapsed', isSidebarCollapsed.value);
};

// Close sidebar on navigation (mobile)
watch(() => usePage().url, () => {
    isSidebarOpen.value = false;
});

// Register service worker & auto-subscribe if permission already granted
const { init: initPush, isSupported, isSubscribed, permissionStatus, subscribe } = usePushNotifications();

onMounted(async () => {
    if (isSupported.value) {
        await initPush();
        // Prompt for permission after a short delay if not yet decided
        if (Notification.permission === 'default') {
            setTimeout(() => subscribe(), 3000);
        }
    }
});
</script>

<template>
    <div class="min-h-screen flex bg-container font-sans text-text relative flex-col lg:flex-row overflow-x-hidden">
        <!-- Backdrop for mobile -->
        <transition 
            enter-active-class="transition-opacity ease-linear duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="isSidebarOpen" 
                class="fixed inset-0 bg-black/50 z-[45] lg:hidden backdrop-blur-sm"
                @click="isSidebarOpen = false"
            ></div>
        </transition>

        <!-- Sidebar -->
        <Sidebar 
            :is-open="isSidebarOpen" 
            :is-collapsed="isSidebarCollapsed"
            @update:is-open="val => isSidebarOpen = val"
            @toggle-collapse="toggleCollapse"
        />

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out min-w-0"
             :class="[isSidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64']">
            
            <!-- Topbar (Sticky) -->
            <Topbar :title="title" @toggle-sidebar="isSidebarOpen = !isSidebarOpen" />

            <!-- Page Content -->
            <main class="flex-1 p-4 md:p-8 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
