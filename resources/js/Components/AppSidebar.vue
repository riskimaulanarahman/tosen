<script setup>
import { ref, computed, watch } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import { useThemeStore } from "@/stores/ThemeStore";
import { getNavItems } from "@/router/navRoutes";

const page = usePage();
const themeStore = useThemeStore();

// State untuk tracking dropdown yang terbuka
const openItems = ref({});

// Get user role dari page props
const userRole = computed(() => page.props.auth?.user?.role);

// Computed URL untuk current page detection
const currentUrl = computed(() => {
    const inertiaUrl = page.props?.url;
    if (typeof inertiaUrl === "string" && inertiaUrl.length > 0) {
        return inertiaUrl.split("?")[0];
    }

    if (typeof window !== "undefined" && window.location) {
        return window.location.pathname || "";
    }

    return "";
});

// Data menu berdasarkan role menggunakan navRoutes
const menuItems = computed(() => {
    return getNavItems(currentUrl.value, userRole.value);
});

// Helper functions - didefinisikan sebelum digunakan
const isMenuItemActive = (href, urlOverride = null) => {
    const url = urlOverride || currentUrl.value;

    // Exact match for root paths
    if (url === href) {
        return true;
    }

    // For nested routes, check if it starts with href + '/' to avoid false positives
    if (url.startsWith(href + "/")) {
        return true;
    }

    return false;
};

const isChildActive = (children, urlOverride = null) => {
    if (!children || !Array.isArray(children)) return false;
    const url = urlOverride || currentUrl.value;

    return children.some((child) => {
        if (!child?.href) return false;

        // Exact match
        if (url === child.href) {
            return true;
        }

        // For nested routes like /outlets/1/edit, check if it matches /outlets pattern
        if (url.startsWith(child.href + "/")) {
            return true;
        }

        return false;
    });
};

// Initialize dropdown states based on current URL
const initializeDropdownStates = () => {
    const activeUrl = currentUrl.value;
    const items = menuItems.value || [];

    items.forEach((item) => {
        if (item.children) {
            const hasActiveChild = isChildActive(item.children, activeUrl);
            openItems.value[item.name] = hasActiveChild;
        }
    });
};

// Watch URL changes dan update dropdown states
watch(
    currentUrl,
    () => {
        initializeDropdownStates();
    },
    { immediate: true }
);

// Watch menuItems changes and reinitialize dropdown states
watch(
    menuItems,
    () => {
        initializeDropdownStates();
    },
    { immediate: true }
);

// Toggle dropdown untuk menu item
const toggleDropdown = (itemName) => {
    openItems.value[itemName] = !openItems.value[itemName];
};

// Computed class functions
const getMenuItemClass = (item) => {
    if (!item) return [];

    const isActive = item.children
        ? isChildActive(item.children)
        : isMenuItemActive(item.href);

    const isOpen = item.children && openItems.value[item.name];

    return [
        "w-full flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200",
        isActive
            ? "bg-surface-1 text-primary border-l-4 border-primary"
            : "text-text-3 hover:text-text hover:bg-surface-1",
        isOpen && !isActive && "bg-surface-1",
    ];
};

const getChildItemClass = (href) => {
    if (!href) return [];

    const isActive = isMenuItemActive(href);
    return [
        "w-full flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200",
        isActive
            ? "bg-surface-2 text-primary border-l-4 border-primary ml-2"
            : "text-muted hover:text-text hover:bg-surface-2 ml-2",
    ];
};
</script>

<template>
    <aside
        class="w-64 bg-surface-0 h-full overflow-y-auto border-r border-border"
    >
        <!-- Logo Section -->
        <div class="p-6 border-b border-border">
            <div class="flex items-center space-x-3">
                <div
                    class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center"
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
                <div>
                    <h1 class="text-xl font-bold text-text">TOSEN TOGA</h1>
                    <p class="text-xs text-muted">Presence</p>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="p-4 space-y-2">
            <template v-for="item in menuItems" :key="item.name">
                <!-- Menu Item with Children (Dropdown) -->
                <div v-if="item.children">
                    <button
                        @click="toggleDropdown(item.name)"
                        :class="getMenuItemClass(item)"
                    >
                        <!-- Icon -->
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        <span class="ml-3">{{ item.name }}</span>
                        <!-- Dropdown Arrow -->
                        <svg
                            class="w-4 h-4 ml-auto transition-transform duration-200"
                            :class="{
                                'rotate-180': openItems[item.name],
                            }"
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
                    </button>

                    <!-- Sub-menu Items -->
                    <div
                        v-show="openItems[item.name]"
                        class="mt-1 space-y-1 overflow-hidden transition-all duration-200"
                    >
                        <Link
                            v-for="child in item.children"
                            :key="child?.name || 'child-' + Math.random()"
                            :href="child?.href || '#'"
                            :class="getChildItemClass(child?.href)"
                        >
                            <svg
                                class="w-4 h-4 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="child?.icon || ''"
                                />
                            </svg>
                            <span class="ml-3">{{
                                child?.name || "Unknown"
                            }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Regular Menu Item (No Children) -->
                <Link v-else :href="item.href" :class="getMenuItemClass(item)">
                    <svg
                        class="w-5 h-5 flex-shrink-0"
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
                    <span class="ml-3">{{ item.name }}</span>
                </Link>
            </template>
        </nav>

        <!-- Footer Section -->
        <div
            class="absolute bottom-0 left-0 right-0 p-4 border-t border-border"
        >
            <div class="flex items-center justify-between">
                <button
                    @click="themeStore.toggleTheme"
                    class="flex items-center space-x-2 p-2 text-muted hover:text-text rounded-lg transition-colors duration-200"
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
                    <span class="text-xs">
                        {{ themeStore.isDark ? "Light" : "Dark" }}
                    </span>
                </button>

                <div class="text-xs text-muted">Version 1.0.0</div>
            </div>
        </div>
    </aside>
</template>

<style scoped>
/* Custom scrollbar untuk sidebar */
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
    background-color: var(--color-muted);
}
</style>
