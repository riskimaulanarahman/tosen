/**
 * Enhanced Error Handler with Retry Mechanisms
 * Provides user-friendly error messages and automatic retry for common issues
 */

import Swal from "sweetalert2";

class EnhancedErrorHandler {
    constructor() {
        this.retryAttempts = new Map();
        this.maxRetries = 3;
        this.retryDelays = [2000, 3000, 5000]; // Progressive delays
    }

    /**
     * Handle errors with intelligent retry mechanism
     */
    async handleError(error, customMessage = null, context = {}) {
        console.error("Enhanced Error Handler:", error, context);

        const errorInfo = this.analyzeError(error);

        // Check if we should retry
        if (errorInfo.canRetry && this.shouldRetry(errorInfo.type, context)) {
            return this.handleRetry(errorInfo, context);
        }

        // Show appropriate error message
        return this.showErrorMessage(errorInfo, customMessage, context);
    }

    /**
     * Analyze error to determine type and retry possibility
     */
    analyzeError(error) {
        const errorInfo = {
            type: "unknown",
            canRetry: false,
            title: "Terjadi Kesalahan",
            message: "Silakan coba lagi atau hubungi administrator.",
            retryButton: false,
            retryText: "Coba Lagi",
            troubleshooting: [],
        };

        // Network errors
        if (this.isNetworkError(error)) {
            errorInfo.type = "network";
            errorInfo.canRetry = true;
            errorInfo.title = "Koneksi Gagal";
            errorInfo.message =
                "Tidak dapat terhubung ke server. Periksa koneksi internet Anda.";
            errorInfo.retryButton = true;
            errorInfo.troubleshooting = [
                "Periksa koneksi WiFi/data seluler Anda",
                "Coba refresh halaman",
                "Pastikan server dapat diakses",
            ];
        }

        // GPS/Location errors
        else if (this.isGpsError(error)) {
            errorInfo.type = "gps";
            errorInfo.canRetry = true;
            errorInfo.title = "Masalah Lokasi";
            errorInfo.message = "Tidak mendapatkan lokasi yang akurat.";
            errorInfo.retryButton = true;
            errorInfo.troubleshooting = [
                "Pastikan GPS/location services aktif",
                "Izinkan akses lokasi untuk browser ini",
                "Coba di lokasi dengan sinyal GPS lebih baik",
                "Refresh halaman dan coba lagi",
            ];
        }

        // Camera errors
        else if (this.isCameraError(error)) {
            errorInfo.type = "camera";
            errorInfo.canRetry = true;
            errorInfo.title = "Masalah Kamera";
            errorInfo.message = "Tidak dapat mengakses kamera.";
            errorInfo.retryButton = true;
            errorInfo.troubleshooting = [
                "Izinkan akses kamera untuk browser ini",
                "Tutup aplikasi lain yang menggunakan kamera",
                "Coba gunakan browser lain (Chrome, Firefox, Safari)",
                "Restart browser jika masalah berlanjut",
            ];
        }

        // Validation errors
        else if (this.isValidationError(error)) {
            errorInfo.type = "validation";
            errorInfo.canRetry = false;
            errorInfo.title = "Validasi Gagal";
            errorInfo.message = this.extractValidationMessage(error);
            errorInfo.retryButton = false;
        }

        // Permission errors
        else if (this.isPermissionError(error)) {
            errorInfo.type = "permission";
            errorInfo.canRetry = true;
            errorInfo.title = "Izin Diperlukan";
            errorInfo.message = "Izin diperlukan untuk melanjutkan.";
            errorInfo.retryButton = true;
            errorInfo.troubleshooting = [
                "Periksa pengaturan browser Anda",
                "Izinkan akses yang diperlukan",
                "Refresh halaman setelah mengizinkan",
            ];
        }

        // Server errors
        else if (this.isServerError(error)) {
            errorInfo.type = "server";
            errorInfo.canRetry = error.response?.status >= 500;
            errorInfo.title = "Kesalahan Server";
            errorInfo.message =
                "Terjadi kesalahan pada server. Silakan coba lagi nanti.";
            errorInfo.retryButton = error.response?.status >= 500;
        }

        // Custom error message from API
        if (error?.response?.data?.message) {
            errorInfo.message = error.response.data.message;
        }

        // Check for retry suggestion from backend
        if (error?.response?.data?.retry_suggested) {
            errorInfo.canRetry = true;
            errorInfo.retryButton = true;
        }

        // Error type from backend
        if (error?.response?.data?.error_type) {
            errorInfo.type = error.response.data.error_type;
        }

        return errorInfo;
    }

    /**
     * Check if error should be retried
     */
    shouldRetry(errorType, context) {
        const retryKey = `${errorType}_${context.action || "default"}`;
        const currentAttempts = this.retryAttempts.get(retryKey) || 0;

        return currentAttempts < this.maxRetries;
    }

    /**
     * Handle retry with progressive delay
     */
    async handleRetry(errorInfo, context) {
        const retryKey = `${errorInfo.type}_${context.action || "default"}`;
        const currentAttempts = this.retryAttempts.get(retryKey) || 0;
        const delay =
            this.retryDelays[
                Math.min(currentAttempts, this.retryDelays.length - 1)
            ];

        // Increment retry counter
        this.retryAttempts.set(retryKey, currentAttempts + 1);

        // Show retry message
        const result = await Swal.fire({
            title: "Mencoba Kembali...",
            html: `
                <div class="text-center">
                    <div class="mb-4">
                        <svg class="animate-spin h-12 w-12 text-orange-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <p class="text-gray-600">Percobaan ${
                        currentAttempts + 1
                    } dari ${this.maxRetries}</p>
                    <p class="text-sm text-gray-500">${errorInfo.message}</p>
                </div>
            `,
            timer: delay,
            timerProgressBar: true,
            showConfirmButton: false,
            allowOutsideClick: false,
        });

        // Check if user cancelled or timer completed
        if (result.dismiss === "timer") {
            // Auto-retry after delay
            if (context.retryCallback) {
                return context.retryCallback();
            }
        } else {
            // User cancelled, reset retry counter
            this.retryAttempts.delete(retryKey);
        }

        return false;
    }

    /**
     * Show error message with troubleshooting tips
     */
    async showErrorMessage(errorInfo, customMessage, context) {
        let html = `
            <div class="text-left">
                <p class="text-gray-700 mb-4">${
                    customMessage || errorInfo.message
                }</p>
        `;

        // Add troubleshooting tips if available
        if (errorInfo.troubleshooting.length > 0) {
            html += `
                <div class="bg-orange-50 border-l-4 border-orange-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-orange-700 font-medium mb-2">Tips troubleshooting:</p>
                            <ul class="text-sm text-orange-600 space-y-1">
            `;

            errorInfo.troubleshooting.forEach((tip) => {
                html += `<li>• ${tip}</li>`;
            });

            html += `
                            </ul>
                        </div>
                    </div>
                </div>
            `;
        }

        html += "</div>";

        const result = await Swal.fire({
            icon: "error",
            title: errorInfo.title,
            html: html,
            confirmButtonText: "Tutup",
            confirmButtonColor: "#dc2626",
            showCancelButton: errorInfo.retryButton,
            cancelButtonText: errorInfo.retryText,
            cancelButtonColor: "#16a34a",
            backdrop: true,
            allowOutsideClick: false,
        });

        // Reset retry counter on manual retry
        if (result.isDismissed && errorInfo.retryButton) {
            const retryKey = `${errorInfo.type}_${context.action || "default"}`;
            this.retryAttempts.delete(retryKey);

            if (context.retryCallback) {
                return context.retryCallback();
            }
        }

        return false;
    }

    /**
     * Error type detection methods
     */
    isNetworkError(error) {
        return (
            error?.code === "NETWORK_ERROR" ||
            error?.message?.includes("Network Error") ||
            error?.message?.includes("ERR_NETWORK") ||
            !error?.response
        );
    }

    isGpsError(error) {
        return (
            error?.response?.data?.error_type === "gps_validation" ||
            error?.message?.includes("location") ||
            error?.message?.includes("GPS") ||
            error?.code === "POSITION_UNAVAILABLE" ||
            error?.code === "TIMEOUT"
        );
    }

    isCameraError(error) {
        return (
            error?.message?.includes("camera") ||
            error?.message?.includes("Camera") ||
            error?.name === "NotAllowedError" ||
            error?.name === "NotFoundError" ||
            error?.name === "NotReadableError"
        );
    }

    isValidationError(error) {
        return (
            error?.response?.status === 422 ||
            error?.response?.data?.error_type === "selfie_validation"
        );
    }

    isPermissionError(error) {
        return (
            error?.response?.status === 403 ||
            error?.name === "NotAllowedError" ||
            error?.name === "PermissionDeniedError"
        );
    }

    isServerError(error) {
        return error?.response?.status >= 500;
    }

    extractValidationMessage(error) {
        if (error?.response?.data?.errors) {
            const errors = error.response.data.errors;
            const firstError = Object.values(errors)[0];
            if (Array.isArray(firstError) && firstError.length > 0) {
                return firstError[0];
            }
        }
        return "Data yang dimasukkan tidak valid.";
    }

    /**
     * Reset retry counters (call after successful operation)
     */
    resetRetries(action = null) {
        if (action) {
            // Reset specific action
            for (const [key] of this.retryAttempts) {
                if (key.includes(action)) {
                    this.retryAttempts.delete(key);
                }
            }
        } else {
            // Reset all
            this.retryAttempts.clear();
        }
    }
}

// Create global instance
const enhancedErrorHandler = new EnhancedErrorHandler();

// Make it available globally
window.enhancedErrorHandler = enhancedErrorHandler;

// Export for use in components
export default enhancedErrorHandler;
