<template>
    <div class="min-h-screen bg-[#EDF2F4] flex">

        <!-- Mobile backdrop -->
        <Transition name="fade">
            <div
                v-if="isMobile && sidebarOpen"
                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
                @click="sidebarOpen = false"
            />
        </Transition>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col bg-[#2B2D42] transition-all duration-300 overflow-hidden',
                isMobile
                    ? (sidebarOpen ? 'w-72 translate-x-0 shadow-2xl' : 'w-72 -translate-x-full')
                    : (sidebarOpen ? 'w-64' : 'w-16'),
            ]"
        >
            <!-- Logo / Brand -->
            <div class="flex items-center gap-3 px-3 py-4 border-b border-white/10 flex-shrink-0">
                <AppLogo :size="40" class="flex-shrink-0" />
                <span v-show="sidebarOpen || isMobile" class="text-white font-semibold text-sm truncate">
                    Staff Portal
                </span>
                <!-- Close button on mobile -->
                <button
                    v-if="isMobile"
                    @click="sidebarOpen = false"
                    class="ml-auto text-[#8D99AE] hover:text-white p-1 rounded-md hover:bg-white/10 transition-colors"
                >
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-2">
                <NavGroup label="Operations" :collapsed="!sidebarOpen && !isMobile">
                    <NavItem :href="route('dashboard')" routeName="dashboard" icon="HomeIcon"                  label="Dashboard"  :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem href="/jobs"                                      icon="ClipboardDocumentListIcon" label="Live Board" :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem href="/schedule"                                  icon="CalendarIcon"              label="Schedule"   :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem href="/calendar"                                  icon="CalendarDaysIcon"          label="Calendar"   :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem href="/attendance"                                icon="ClockIcon"                 label="Attendance" :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem href="/leave"                                     icon="CalendarDaysIcon"          label="Leave"      :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem href="/my-qr"                                    icon="QrCodeIcon"                label="My QR Code" :collapsed="!sidebarOpen && !isMobile" />
                </NavGroup>

                <NavGroup v-if="isSiteHead" label="Field" :collapsed="!sidebarOpen && !isMobile">
                    <NavItem href="/qr-scanner" icon="CameraIcon" label="QR Scanner" :collapsed="!sidebarOpen && !isMobile" />
                </NavGroup>

                <NavGroup v-if="isManager" label="Management" :collapsed="!sidebarOpen && !isMobile">
                    <NavItem href="/projects" icon="FolderIcon"  label="Projects" :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem href="/staff"    icon="UsersIcon"   label="Staff"    :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem href="/vans"     icon="TruckIcon"   label="Vans"     :collapsed="!sidebarOpen && !isMobile" />
                </NavGroup>

                <NavGroup v-if="isAdmin || isManager" label="Admin" :collapsed="!sidebarOpen && !isMobile">
                    <NavItem v-if="isAdmin" :href="route('businesses.index')" routeName="businesses.index" icon="BuildingOfficeIcon"      label="Businesses" :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem :href="route('reports')"                         routeName="reports"           icon="ChartBarIcon"            label="Reports"    :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem :href="route('audit-log')"                       routeName="audit-log"         icon="ClipboardDocumentIcon"  label="Audit Log"  :collapsed="!sidebarOpen && !isMobile" />
                    <NavItem v-if="isAdmin" :href="route('settings')"         routeName="settings"          icon="Cog6ToothIcon"           label="Settings"   :collapsed="!sidebarOpen && !isMobile" />
                </NavGroup>
            </nav>

            <!-- Sidebar footer: logout -->
            <div class="border-t border-white/10 p-2 flex-shrink-0">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-white/10 transition-colors text-[#8D99AE] hover:text-white group relative"
                >
                    <ArrowRightOnRectangleIcon class="w-5 h-5 flex-shrink-0" />
                    <span v-show="sidebarOpen || isMobile" class="text-sm">Log out</span>
                    <div
                        v-if="!sidebarOpen && !isMobile"
                        class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50"
                    >
                        Log out
                    </div>
                </Link>
            </div>
        </aside>

        <!-- Main content area -->
        <div
            :class="[
                'flex-1 flex flex-col transition-all duration-300 min-w-0',
                isMobile ? 'ml-0' : (sidebarOpen ? 'ml-64' : 'ml-16'),
            ]"
        >
            <!-- Top bar -->
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200 flex items-center gap-3 px-4 h-14">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="text-gray-500 hover:text-[#2B2D42] p-1.5 rounded-md hover:bg-gray-100 flex-shrink-0"
                >
                    <Bars3Icon class="w-5 h-5" />
                </button>

                <h1 class="text-sm font-semibold text-[#2B2D42] flex-1 truncate">{{ title }}</h1>

                <div class="flex items-center gap-2 text-xs text-[#8D99AE] flex-shrink-0">
                    <span class="hidden lg:inline">{{ formattedDate }}</span>

                    <!-- Notification bell -->
                    <div class="relative" ref="notifRef">
                        <button
                            @click="notifOpen = !notifOpen"
                            class="relative p-1.5 rounded-md hover:bg-gray-100 transition-colors text-gray-500 hover:text-[#2B2D42]"
                        >
                            <BellIcon class="w-5 h-5" />
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-[#EF233C] text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none"
                            >{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
                        </button>

                        <Transition name="dropdown">
                            <div
                                v-if="notifOpen"
                                class="absolute right-0 mt-1 w-80 bg-white rounded-xl shadow-lg border border-gray-200 z-50 overflow-hidden"
                            >
                                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-semibold text-gray-800">Notifications</p>
                                    <button
                                        v-if="unreadCount > 0"
                                        @click="markAllRead"
                                        class="text-xs text-[#EF233C] hover:underline"
                                    >Mark all read</button>
                                </div>
                                <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                                    <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-xs text-gray-400">
                                        No new notifications
                                    </div>
                                    <a
                                        v-for="n in notifications"
                                        :key="n.id"
                                        :href="n.url ?? '#'"
                                        @click.prevent="openNotif(n)"
                                        class="flex gap-3 px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer"
                                    >
                                        <div class="w-2 h-2 rounded-full bg-[#EF233C] flex-shrink-0 mt-1.5" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-gray-800">{{ n.title }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ n.message }}</p>
                                            <p class="text-[10px] text-gray-400 mt-1">{{ n.created_at }}</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <Link
                        :href="route('profile.edit')"
                        class="flex items-center gap-2 hover:bg-gray-100 rounded-lg px-2 py-1 transition-colors"
                    >
                        <img
                            :src="user.avatar_url"
                            :alt="user.name"
                            class="w-7 h-7 rounded-full object-cover flex-shrink-0"
                        />
                        <span class="hidden sm:inline font-medium text-[#2B2D42] max-w-24 truncate">{{ user.name }}</span>
                    </Link>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-4 sm:p-6">
                <Transition name="page" mode="out-in">
                    <div :key="page.component">
                        <slot />
                    </div>
                </Transition>
            </main>
        </div>

        <ToastContainer />
        <TempPasswordModal />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { usePermission } from '@/Composables/usePermission';
import NavGroup from '@/Components/Layout/NavGroup.vue';
import NavItem from '@/Components/Layout/NavItem.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import TempPasswordModal from '@/Components/TempPasswordModal.vue';
import AppLogo from '@/Components/AppLogo.vue';
import { Bars3Icon, ArrowRightOnRectangleIcon, XMarkIcon, BellIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: { type: String, default: '' },
});

const page = usePage();
const { isAdmin, isManager, isSiteHead } = usePermission();

const isMobile   = ref(window.innerWidth < 768);
const sidebarOpen = ref(window.innerWidth >= 768);

function onResize() {
    isMobile.value = window.innerWidth < 768;
    if (isMobile.value) sidebarOpen.value = false;
    else if (!sidebarOpen.value) sidebarOpen.value = true;
}

onMounted(() => {
    window.addEventListener('resize', onResize);
    document.addEventListener('click', onClickOutside);
});
onUnmounted(() => {
    window.removeEventListener('resize', onResize);
    document.removeEventListener('click', onClickOutside);
});

// Close drawer on navigation (mobile)
watch(() => page.url, () => {
    if (isMobile.value) sidebarOpen.value = false;
});

const user             = computed(() => page.props.auth.user);
const pendingApprovals = computed(() => page.props.pendingApprovals ?? 0);
const notifications    = computed(() => page.props.notifications ?? []);
const unreadCount      = computed(() => page.props.unreadCount ?? 0);

const notifOpen = ref(false);
const notifRef  = ref(null);

function markAllRead() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true, onSuccess: () => { notifOpen.value = false; } });
}

function openNotif(n) {
    router.post(route('notifications.read', n.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            notifOpen.value = false;
            if (n.url) router.visit(n.url);
        },
    });
}

function onClickOutside(e) {
    if (notifRef.value && !notifRef.value.contains(e.target)) notifOpen.value = false;
}

const formattedDate = computed(() =>
    new Intl.DateTimeFormat('en-GB', {
        weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
    }).format(new Date())
);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

.dropdown-enter-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.dropdown-leave-active { transition: opacity 0.1s ease, transform 0.1s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px); }

.page-enter-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.page-leave-active { transition: opacity 0.12s ease, transform 0.12s ease; }
.page-enter-from   { opacity: 0; transform: translateY(6px); }
.page-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
