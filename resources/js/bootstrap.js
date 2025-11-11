import axios from "axios";
import { router } from "@inertiajs/vue3";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

const syncCsrfToken = (token) => {
    if (!token) {
        return;
    }

    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;

    let meta = document.head.querySelector('meta[name="csrf-token"]');
    if (!meta) {
        meta = document.createElement("meta");
        meta.name = "csrf-token";
        document.head.appendChild(meta);
    }

    meta.setAttribute("content", token);
};

// Seed the CSRF token from the server-rendered meta tag
const initialToken = document.head.querySelector('meta[name="csrf-token"]');
if (initialToken?.content) {
    syncCsrfToken(initialToken.content);
} else {
    console.error(
        "CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token"
    );
}

// Keep the CSRF token in sync after every Inertia navigation
router.on("navigate", (event) => {
    const token = event.detail?.page?.props?.csrf_token;
    if (token) {
        syncCsrfToken(token);
    }
});

// Add response interceptor to handle CSRF token expiration
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 419) {
            // CSRF token expired, refresh the page
            console.warn("CSRF token expired, refreshing page...");
            window.location.reload();
        }
        return Promise.reject(error);
    }
);
