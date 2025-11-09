import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],
    darkMode: "class",

    theme: {
        extend: {
            fontFamily: {
                sans: ["Inter", ...defaultTheme.fontFamily.sans],
                display: ["Inter", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Modern Primary Colors
                primary: {
                    50: "var(--color-primary-50)",
                    100: "var(--color-primary-100)",
                    200: "var(--color-primary-200)",
                    300: "var(--color-primary-300)",
                    400: "var(--color-primary-400)",
                    500: "var(--color-primary-500)",
                    600: "var(--color-primary-600)",
                    700: "var(--color-primary-700)",
                    800: "var(--color-primary-800)",
                    900: "var(--color-primary-900)",
                    DEFAULT: "var(--color-primary)",
                },
                // Modern Accent Colors
                accent: {
                    400: "var(--color-accent-400)",
                    500: "var(--color-accent-500)",
                    600: "var(--color-accent-600)",
                    DEFAULT: "var(--color-accent)",
                },
                // Modern Surface Colors
                canvas: "var(--color-canvas)",
                surface: {
                    0: "var(--color-surface-0)",
                    1: "var(--color-surface-1)",
                    2: "var(--color-surface-2)",
                    3: "var(--color-surface-3)",
                },
                border: "var(--color-border)",
                // Modern Text Colors
                text: {
                    DEFAULT: "var(--color-text)",
                    2: "var(--color-text-2)",
                    3: "var(--color-text-3)",
                    muted: "var(--color-muted)",
                },
                // Modern Status Colors
                success: {
                    50: "var(--color-success-50)",
                    100: "var(--color-success-100)",
                    500: "var(--color-success-500)",
                    600: "var(--color-success-600)",
                    DEFAULT: "var(--color-success)",
                },
                info: {
                    50: "var(--color-info-50)",
                    100: "var(--color-info-100)",
                    500: "var(--color-info-500)",
                    600: "var(--color-info-600)",
                    DEFAULT: "var(--color-info)",
                },
                warning: {
                    50: "var(--color-warning-50)",
                    100: "var(--color-warning-100)",
                    500: "var(--color-warning-500)",
                    600: "var(--color-warning-600)",
                    DEFAULT: "var(--color-warning)",
                },
                error: {
                    50: "var(--color-error-50)",
                    100: "var(--color-error-100)",
                    500: "var(--color-error-500)",
                    600: "var(--color-error-600)",
                    DEFAULT: "var(--color-error)",
                },
            },
            borderRadius: {
                xs: "var(--radius-xs)",
                sm: "var(--radius-sm)",
                md: "var(--radius-md)",
                lg: "var(--radius-lg)",
                xl: "var(--radius-xl)",
                "2xl": "var(--radius-2xl)",
                "3xl": "var(--radius-3xl)",
                full: "var(--radius-full)",
            },
            boxShadow: {
                xs: "var(--shadow-xs)",
                sm: "var(--shadow-sm)",
                md: "var(--shadow-md)",
                lg: "var(--shadow-lg)",
                xl: "var(--shadow-xl)",
                "2xl": "var(--shadow-2xl)",
                glow: "var(--shadow-glow)",
                "glow-accent": "var(--shadow-glow-accent)",
            },
            spacing: {
                0: "var(--space-0)",
                1: "var(--space-1)",
                2: "var(--space-2)",
                3: "var(--space-3)",
                4: "var(--space-4)",
                5: "var(--space-5)",
                6: "var(--space-6)",
                8: "var(--space-8)",
                10: "var(--space-10)",
                12: "var(--space-12)",
                16: "var(--space-16)",
                20: "var(--space-20)",
                24: "var(--space-24)",
            },
            transitionDuration: {
                fast: "var(--transition-fast)",
                normal: "var(--transition-normal)",
                slow: "var(--transition-slow)",
                spring: "var(--transition-spring)",
            },
            transitionTimingFunction: {
                "bounce-in": "cubic-bezier(0.68, -0.55, 0.265, 1.55)",
                "smooth-out": "cubic-bezier(0.4, 0, 0.2, 1)",
            },
            minWidth: {
                sidebar: "var(--sidebar-width)",
                "sidebar-collapsed": "var(--sidebar-collapsed-width)",
            },
            backgroundImage: {
                "gradient-primary": "var(--gradient-primary)",
                "gradient-accent": "var(--gradient-accent)",
                "gradient-success": "var(--gradient-success)",
                "gradient-dark": "var(--gradient-dark)",
            },
            animation: {
                "fade-in": "fadeIn 0.5s ease-in-out",
                "slide-up": "slideUp 0.3s ease-out",
                "bounce-in":
                    "bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55)",
                "pulse-glow": "pulseGlow 2s ease-in-out infinite",
            },
            keyframes: {
                fadeIn: {
                    "0%": { opacity: "0" },
                    "100%": { opacity: "1" },
                },
                slideUp: {
                    "0%": { transform: "translateY(20px)", opacity: "0" },
                    "100%": { transform: "translateY(0)", opacity: "1" },
                },
                bounceIn: {
                    "0%": { transform: "scale(0.3)", opacity: "0" },
                    "50%": { transform: "scale(1.05)" },
                    "70%": { transform: "scale(0.9)" },
                    "100%": { transform: "scale(1)", opacity: "1" },
                },
                pulseGlow: {
                    "0%, 100%": {
                        boxShadow: "0 0 20px rgba(59, 130, 246, 0.15)",
                    },
                    "50%": { boxShadow: "0 0 30px rgba(59, 130, 246, 0.3)" },
                },
            },
        },
    },

    plugins: [forms],
};
