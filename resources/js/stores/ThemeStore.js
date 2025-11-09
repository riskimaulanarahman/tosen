import { defineStore } from "pinia";
import { ref, watchEffect } from "vue";

export const useThemeStore = defineStore("theme", () => {
    // State
    const isDark = ref(false);

    // Actions
    const toggleTheme = () => {
        isDark.value = !isDark.value;
    };

    const initTheme = () => {
        // Check localStorage first
        const storedTheme = localStorage.getItem("theme");

        if (storedTheme !== null) {
            // Use stored theme preference
            isDark.value = storedTheme === "dark";
        } else {
            // Default to light mode, but still check system preference as fallback
            const prefersDark = window.matchMedia(
                "(prefers-color-scheme: dark)"
            ).matches;
            isDark.value = false; // Default to light mode
        }
    };

    // Watch for changes to isDark and update DOM and localStorage
    watchEffect(() => {
        // Update DOM class
        const htmlElement = document.documentElement;
        if (isDark.value) {
            htmlElement.classList.add("dark");
        } else {
            htmlElement.classList.remove("dark");
        }

        // Save to localStorage
        localStorage.setItem("theme", isDark.value ? "dark" : "light");
    });

    // Initialize theme when store is created
    initTheme();

    return {
        isDark,
        toggleTheme,
        initTheme,
    };
});
