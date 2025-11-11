import axios from "axios";
import { router } from "@inertiajs/vue3";
import Swal from "sweetalert2";

window.axios = axios;
window.Swal = Swal;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Global error handler utility
window.handleError = (error, customMessage = null) => {
    console.error("Error caught:", error);

    let title = "Terjadi Kesalahan";
    let message = "Silakan coba lagi atau hubungi administrator.";

    if (customMessage) {
        message = customMessage;
    } else if (error?.response?.data?.message) {
        message = error.response.data.message;
    } else if (error?.response?.data?.error) {
        message = error.response.data.error;
    } else if (error?.message) {
        message = error.message;
    } else if (typeof error === "string") {
        message = error;
    }

    // Handle validation errors
    if (error?.response?.data?.errors) {
        const errors = error.response.data.errors;
        const firstError = Object.values(errors)[0];
        if (Array.isArray(firstError) && firstError.length > 0) {
            message = firstError[0];
        }
    }

    // Handle specific HTTP status codes
    if (error?.response?.status) {
        switch (error.response.status) {
            case 401:
                title = "Tidak Terotentikasi";
                message = "Sesi Anda telah berakhir. Silakan login kembali.";
                break;
            case 403:
                title = "Akses Ditolak";
                message = "Anda tidak memiliki izin untuk melakukan aksi ini.";
                break;
            case 404:
                title = "Data Tidak Ditemukan";
                message = "Data yang Anda cari tidak tersedia.";
                break;
            case 422:
                title = "Validasi Gagal";
                break;
            case 429:
                title = "Terlalu Banyak Permintaan";
                message = "Silakan tunggu beberapa saat sebelum mencoba lagi.";
                break;
            case 500:
                title = "Kesalahan Server";
                message =
                    "Terjadi kesalahan pada server. Silakan coba lagi nanti.";
                break;
            case 503:
                title = "Servis Tidak Tersedia";
                message = "Sedang dalam pemeliharaan. Silakan coba lagi nanti.";
                break;
        }
    }

    // Handle network errors
    if (
        error?.code === "NETWORK_ERROR" ||
        error?.message?.includes("Network Error")
    ) {
        title = "Koneksi Gagal";
        message =
            "Tidak dapat terhubung ke server. Periksa koneksi internet Anda.";
    }

    // Show SweetAlert error notification
    Swal.fire({
        icon: "error",
        title: title,
        text: message,
        confirmButtonText: "OK",
        confirmButtonColor: "#dc2626",
        timer: 5000,
        timerProgressBar: true,
        backdrop: true,
        allowOutsideClick: false,
    });
};

// Global success handler utility
window.handleSuccess = (message, title = "Berhasil") => {
    Swal.fire({
        icon: "success",
        title: title,
        text: message,
        confirmButtonText: "OK",
        confirmButtonColor: "#16a34a",
        timer: 3000,
        timerProgressBar: true,
        backdrop: true,
        allowOutsideClick: false,
    });
};

// Global confirmation dialog utility
window.handleConfirm = async (
    message,
    title = "Konfirmasi",
    confirmText = "Ya",
    cancelText = "Batal"
) => {
    const result = await Swal.fire({
        title: title,
        text: message,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        confirmButtonColor: "#dc2626",
        cancelButtonColor: "#6b7280",
        backdrop: true,
        allowOutsideClick: false,
    });
    return result.isConfirmed;
};

// Global loading utility
window.handleLoading = (message = "Memproses...") => {
    return Swal.fire({
        title: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });
};

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

// Add response interceptor to handle CSRF token expiration and global error handling
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 419) {
            // CSRF token expired, refresh the page
            console.warn("CSRF token expired, refreshing page...");
            window.location.reload();
        } else if (error.response && error.response.status >= 400) {
            // For all other HTTP errors, show them using global error handler
            // But don't handle validation errors (422) globally as they're usually handled locally
            if (error.response.status !== 422) {
                handleError(error);
            }
        } else if (!error.response) {
            // Network errors or other client-side errors
            handleError(error);
        }
        return Promise.reject(error);
    }
);
