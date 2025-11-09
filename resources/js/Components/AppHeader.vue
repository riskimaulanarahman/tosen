<script setup>
import { ref } from "vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import ThemeToggle from "./ThemeToggle.vue";

const page = usePage();
const isProfileMenuOpen = ref(false);

// Props
const props = defineProps({
    toggleSidebar: {
        type: Function,
        required: false,
    },
});

const toggleProfileMenu = () => {
    isProfileMenuOpen.value = !isProfileMenuOpen.value;
};

const closeProfileMenu = () => {
    isProfileMenuOpen.value = false;
};

const logout = () => {
    router.post("/logout");
};

// Close profile menu when clicking outside
document.addEventListener("click", (e) => {
    if (!e.target.closest(".profile-menu")) {
        isProfileMenuOpen.value = false;
    }
});
</script>

<template>
    <header class="bg-surface-0 border-b border-border">
        <div class="flex justify-between items-center p-4">
            <!-- Bagian Kiri: Hamburger Menu + Breadcrumb -->
            <div class="flex items-center space-x-4">
                <!-- Mobile Menu Toggle -->
                <button
                    v-if="props.toggleSidebar"
                    @click="props.toggleSidebar"
                    class="lg:hidden p-2 text-text-2 hover:bg-surface-1 rounded-lg transition-colors duration-200"
                >
                    <svg
                        class="w-6 h-6"
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

                <div class="flex-1">
                    <!-- Breadcrumb atau judul halaman bisa ditaruh di sini -->
                </div>
            </div>

            <!-- Bagian Kanan -->
            <div class="flex items-center space-x-4">
                <!-- Theme Toggle -->
                <ThemeToggle />

                <!-- User Dropdown -->
                <div class="relative profile-menu">
                    <button
                        @click="toggleProfileMenu"
                        class="flex items-center space-x-3 p-2 text-text-2 hover:bg-surface-1 rounded-lg transition-colors duration-200"
                    >
                        <!-- User Avatar -->
                        <div
                            class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center"
                        >
                            <span class="text-sm font-medium text-white">
                                {{
                                    page.props.auth?.user?.name
                                        ?.charAt(0)
                                        ?.toUpperCase() || "U"
                                }}
                            </span>
                        </div>
                        <!-- User Name (hidden on mobile) -->
                        <div class="hidden sm:block text-left">
                            <p class="text-sm font-medium text-text">
                                {{ page.props.auth?.user?.name || "User" }}
                            </p>
                            <p class="text-xs text-muted">
                                {{
                                    page.props.auth?.user?.email ||
                                    "user@example.com"
                                }}
                            </p>
                        </div>
                        <!-- Dropdown Icon -->
                        <svg
                            class="w-4 h-4 text-muted"
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

                    <!-- Dropdown Menu -->
                    <div
                        v-if="isProfileMenuOpen"
                        class="absolute right-0 top-full mt-2 w-48 bg-surface-0 border border-border rounded-lg shadow-lg z-50"
                    >
                        <Link
                            href="/profile"
                            class="flex items-center px-4 py-3 text-sm text-text-2 hover:bg-surface-1 transition-colors duration-200"
                        >
                            <!-- User icon -->
                            <svg
                                class="w-5 h-5 mr-3 text-muted"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            Profile
                        </Link>

                        <Link
                            href="/settings"
                            class="flex items-center px-4 py-3 text-sm text-text-2 hover:bg-surface-1 transition-colors duration-200"
                        >
                            <!-- Settings icon -->
                            <svg
                                class="w-5 h-5 mr-3 text-muted"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            Settings
                        </Link>

                        <hr class="border-border" />

                        <button
                            @click="logout"
                            class="flex items-center w-full px-4 py-3 text-sm text-error hover:bg-surface-1 transition-colors duration-200"
                        >
                            <!-- Logout icon -->
                            <svg
                                class="w-5 h-5 mr-3 text-error"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                />
                            </svg>
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
