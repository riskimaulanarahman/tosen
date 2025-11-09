<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { getNavItems, type NavItem } from "@/router/navRoutes";
import Button from "@/Components/ui/Button.vue";
import { useThemeStore } from "@/stores/ThemeStore";

const page = usePage();
const isSidebarOpen = ref(false);
const isSidebarCollapsed = ref(false);

// Theme store
const themeStore = useThemeStore();

// Get current path from page URL
const currentPath = computed(() => {
    return (page.props.url as string)?.split("?")[0] || "";
});

// Navigation items with active state
const navigationItems = computed(() => {
    return getNavItems(currentPath.value);
});

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const toggleCollapse = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    // Save preference to localStorage
    if (typeof window !== "undefined") {
        localStorage.setItem(
            "sidebar-collapsed",
            String(isSidebarCollapsed.value)
        );
    }
};

const closeSidebar = () => {
    isSidebarOpen.value = false;
};

// Load sidebar state from localStorage
onMounted(() => {
    if (typeof window !== "undefined") {
        const saved = localStorage.getItem("sidebar-collapsed");
        if (saved) {
            isSidebarCollapsed.value = saved === "true";
        }
    }
});

// Close sidebar on route change
watch(
    () => page.props.url,
    () => {
        closeSidebar();
    }
);

// Close sidebar on escape key
const handleEscape = (event: KeyboardEvent) => {
    if (event.key === "Escape") {
        closeSidebar();
    }
};

onMounted(() => {
    document.addEventListener("keydown", handleEscape);
});

onUnmounted(() => {
    document.removeEventListener("keydown", handleEscape);
});
</script>

<template>
    <div
        class="min-h-screen bg-gradient-to-br from-canvas via-surface-0 to-canvas flex"
    >
        <!-- Sidebar Overlay for mobile -->
        <Transition
            enter-active-class="transition-opacity duration-normal"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-normal"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isSidebarOpen"
                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
                @click="closeSidebar"
            />
        </Transition>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed lg:static inset-y-0 left-0 z-50 transform transition-all duration-spring flex flex-col',
                isSidebarOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0',
                isSidebarCollapsed ? 'lg:w-sidebar-collapsed' : 'lg:w-sidebar',
                'w-sidebar lg:block',
            ]"
        >
            <!-- Sidebar Background -->
            <div
                class="absolute inset-0 bg-gradient-to-b from-surface-1 via-surface-2 to-surface-1 border-r border-border/50 backdrop-blur-xl"
            >
                <!-- Gradient overlay -->
                <div
                    class="absolute inset-0 bg-gradient-to-br from-primary-500/5 via-transparent to-accent-500/5"
                ></div>
            </div>

            <!-- Sidebar Content -->
            <div class="relative z-10 flex flex-col h-full">
                <!-- Sidebar Header -->
                <div
                    class="flex items-center justify-between h-16 px-4 border-b border-border/30"
                >
                    <!-- Logo -->
                    <Link
                        href="/dashboard"
                        class="flex items-center space-x-3 group"
                    >
                        <div class="relative">
                            <div
                                class="w-10 h-10 bg-gradient-to-br from-primary-500 to-accent-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-primary-500/25 group-hover:shadow-xl transition-all duration-spring transform group-hover:scale-110"
                            >
                                <svg
                                    class="w-6 h-6 text-white"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"
                                    />
                                </svg>
                            </div>
                            <!-- Glow effect -->
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-primary-500/20 to-accent-500/20 rounded-xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-normal"
                            ></div>
                        </div>
                        <span
                            :class="[
                                'text-xl font-bold bg-gradient-to-r from-text to-text-2 bg-clip-text text-transparent transition-all duration-spring',
                                isSidebarCollapsed
                                    ? 'lg:opacity-0 lg:invisible lg:w-0'
                                    : 'lg:opacity-100 lg:visible',
                            ]"
                        >
                            Absensi
                        </span>
                    </Link>

                    <!-- Toggle Button -->
                    <button
                        :class="[
                            'p-2 rounded-xl text-text-muted hover:bg-surface-3 hover:text-text transition-all duration-fast group',
                            'hidden lg:flex',
                        ]"
                        @click="toggleCollapse"
                        title="Toggle sidebar"
                    >
                        <div class="relative">
                            <svg
                                class="w-5 h-5 transition-transform duration-spring group-hover:rotate-180"
                                :class="isSidebarCollapsed ? 'rotate-180' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                            <!-- Hover glow -->
                            <div
                                class="absolute inset-0 bg-primary-500/20 rounded-lg blur-md opacity-0 group-hover:opacity-100 transition-opacity duration-fast"
                            ></div>
                        </div>
                    </button>

                    <!-- Mobile Close Button -->
                    <button
                        class="p-2 rounded-xl text-text-muted hover:bg-surface-3 hover:text-text transition-all duration-fast lg:hidden"
                        @click="closeSidebar"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <Link
                        v-for="item in navigationItems"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'group relative flex items-center px-3 py-2.5 rounded-xl transition-all duration-spring',
                            item.current
                                ? 'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/25'
                                : 'text-text hover:bg-surface-3 hover:text-text hover:shadow-md',
                        ]"
                    >
                        <!-- Active indicator -->
                        <div
                            v-if="item.current"
                            class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-white rounded-r-full"
                        ></div>

                        <!-- Icon -->
                        <div class="relative">
                            <svg
                                :class="[
                                    'w-5 h-5 flex-shrink-0 transition-all duration-spring',
                                    isSidebarCollapsed ? 'lg:mr-0' : 'lg:mr-3',
                                    item.current
                                        ? 'text-white'
                                        : 'text-text-muted group-hover:text-text',
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="item.icon"
                                />
                            </svg>
                            <!-- Icon glow for active state -->
                            <div
                                v-if="item.current"
                                class="absolute inset-0 bg-white/20 rounded-lg blur-md"
                            ></div>
                        </div>

                        <!-- Label -->
                        <span
                            :class="[
                                'font-medium transition-all duration-spring',
                                isSidebarCollapsed
                                    ? 'lg:opacity-0 lg:invisible lg:w-0'
                                    : 'lg:opacity-100 lg:visible',
                            ]"
                        >
                            {{ item.name }}
                        </span>

                        <!-- Hover effect -->
                        <div
                            v-if="!item.current"
                            class="absolute inset-0 bg-gradient-to-r from-primary-500/5 to-accent-500/5 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-fast"
                        ></div>
                    </Link>
                </nav>

                <!-- Sidebar Footer -->
                <div class="p-4 border-t border-border/30">
                    <div
                        :class="[
                            'flex items-center p-2 rounded-xl bg-surface-3/50 backdrop-blur-sm group transition-all duration-spring cursor-pointer hover:bg-surface-3',
                            isSidebarCollapsed
                                ? 'lg:justify-center lg:space-x-0'
                                : 'lg:space-x-3',
                        ]"
                    >
                        <!-- User Avatar -->
                        <div class="relative">
                            <div
                                class="w-10 h-10 bg-gradient-to-br from-primary-500 to-accent-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg shadow-primary-500/25"
                            >
                                <span class="text-sm font-bold text-white">
                                    {{
                                        $page.props.auth.user.name
                                            ?.charAt(0)
                                            ?.toUpperCase() || "U"
                                    }}
                                </span>
                            </div>
                            <!-- Avatar glow -->
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-primary-500/30 to-accent-500/30 rounded-full blur-lg group-hover:opacity-100 opacity-0 transition-opacity duration-fast"
                            ></div>
                        </div>

                        <!-- User Info -->
                        <div
                            :class="[
                                'flex-1 min-w-0',
                                isSidebarCollapsed
                                    ? 'lg:opacity-0 lg:invisible lg:w-0'
                                    : 'lg:opacity-100 lg:visible',
                            ]"
                        >
                            <p class="text-sm font-semibold text-text truncate">
                                {{ $page.props.auth.user.name }}
                            </p>
                            <p class="text-xs text-text-muted truncate">
                                {{ $page.props.auth.user.email }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Bar -->
            <header
                class="bg-surface-1/80 backdrop-blur-xl border-b border-border/50 h-16 flex items-center justify-between px-4 lg:px-6 shadow-lg shadow-black/10"
            >
                <!-- Mobile Menu Button -->
                <button
                    class="p-2 rounded-xl text-text-muted hover:bg-surface-2 hover:text-text transition-all duration-spring lg:hidden"
                    @click="toggleSidebar"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>

                <!-- Page Title (Mobile) -->
                <div class="lg:hidden">
                    <h1 class="text-lg font-semibold text-text">
                        {{
                            navigationItems.find((item) => item.current)
                                ?.name || "Dashboard"
                        }}
                    </h1>
                </div>

                <!-- Right Side -->
                <div class="flex items-center space-x-3">
                    <!-- Theme Toggle -->
                    <Button
                        @click="themeStore.toggleTheme"
                        variant="ghost"
                        size="sm"
                        icon
                        :title="
                            themeStore.isDark
                                ? 'Switch to light mode'
                                : 'Switch to dark mode'
                        "
                    >
                        <!-- Sun icon for dark mode -->
                        <svg
                            v-if="themeStore.isDark"
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                        <!-- Moon icon for light mode -->
                        <svg
                            v-else
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                            />
                        </svg>
                    </Button>

                    <!-- Notifications -->
                    <Button variant="ghost" size="sm" icon class="relative">
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                            />
                        </svg>
                        <span
                            class="absolute top-1 right-1 w-2 h-2 bg-gradient-to-r from-error-500 to-error-600 rounded-full shadow-lg shadow-error-500/50"
                        ></span>
                    </Button>

                    <!-- User Menu -->
                    <div class="relative">
                        <Button variant="ghost" size="sm" class="group">
                            <div class="flex items-center space-x-2">
                                <div class="relative">
                                    <div
                                        class="w-8 h-8 bg-gradient-to-br from-primary-500 to-accent-600 rounded-full flex items-center justify-center shadow-md shadow-primary-500/25 group-hover:shadow-lg transition-shadow duration-spring"
                                    >
                                        <span
                                            class="text-xs font-bold text-white"
                                        >
                                            {{
                                                $page.props.auth.user.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase() || "U"
                                            }}
                                        </span>
                                    </div>
                                    <!-- Avatar glow on hover -->
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-primary-500/30 to-accent-500/30 rounded-full blur-md opacity-0 group-hover:opacity-100 transition-opacity duration-fast"
                                    ></div>
                                </div>
                                <svg
                                    class="w-4 h-4 text-text-muted transition-transform duration-spring group-hover:rotate-180"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </Button>

                        <!-- Dropdown Menu (simplified) -->
                        <div
                            class="absolute right-0 top-full mt-2 w-48 bg-surface-1 border border-border/50 rounded-xl shadow-xl shadow-black/20 backdrop-blur-xl hidden"
                        >
                            <Link
                                href="/profile"
                                class="block px-4 py-2 text-text hover:bg-surface-2 transition-colors duration-fast rounded-t-xl"
                            >
                                Profile
                            </Link>
                            <Link
                                href="/settings"
                                class="block px-4 py-2 text-text hover:bg-surface-2 transition-colors duration-fast"
                            >
                                Settings
                            </Link>
                            <hr class="border-border/50" />
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                class="block w-full text-left px-4 py-2 text-error hover:bg-error/10 transition-colors duration-fast rounded-b-xl"
                            >
                                Logout
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <!-- Page Header -->
                <div
                    v-if="$slots.header"
                    class="bg-gradient-to-r from-surface-1 via-surface-2/50 to-surface-1 border-b border-border/30 px-4 py-4 lg:px-6 lg:py-6 backdrop-blur-sm"
                >
                    <slot name="header" />
                </div>

                <!-- Page Content -->
                <div class="p-4 lg:p-6">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for sidebar */
aside {
    scrollbar-width: thin;
    scrollbar-color: var(--color-border) transparent;
}

aside::-webkit-scrollbar {
    width: 6px;
}

aside::-webkit-scrollbar-track {
    background: transparent;
}

aside::-webkit-scrollbar-thumb {
    background-color: var(--color-border);
    border-radius: 3px;
}

aside::-webkit-scrollbar-thumb:hover {
    background-color: var(--color-text-muted);
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color,
        text-decoration-color, fill, stroke, opacity, box-shadow, transform,
        filter, backdrop-filter;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
