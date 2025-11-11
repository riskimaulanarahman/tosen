/**
 * Error Handler Test Utility
 * This file can be used to test the global error handling system
 */

// Test function to simulate different types of errors
export const testErrorHandling = {
    // Test network error
    testNetworkError() {
        const error = new Error("Network connection failed");
        error.code = "NETWORK_ERROR";
        window.handleError(error);
    },

    // Test 401 error
    testUnauthorizedError() {
        const error = new Error("Unauthorized access");
        error.response = { status: 401, data: { message: "Token expired" } };
        window.handleError(error);
    },

    // Test 403 error
    testForbiddenError() {
        const error = new Error("Access forbidden");
        error.response = { status: 403, data: { message: "No permission" } };
        window.handleError(error);
    },

    // Test 404 error
    testNotFoundError() {
        const error = new Error("Resource not found");
        error.response = { status: 404, data: { message: "Data not found" } };
        window.handleError(error);
    },

    // Test 422 validation error
    testValidationError() {
        const error = new Error("Validation failed");
        error.response = {
            status: 422,
            data: {
                message: "The given data was invalid.",
                errors: {
                    email: ["The email field is required."],
                    name: ["The name field is required."],
                },
            },
        };
        window.handleError(error);
    },

    // Test 500 server error
    testServerError() {
        const error = new Error("Internal server error");
        error.response = {
            status: 500,
            data: { message: "Something went wrong" },
        };
        window.handleError(error);
    },

    // Test custom error message
    testCustomError() {
        const error = new Error("Some custom error");
        window.handleError(
            error,
            "This is a custom error message that should override the default one."
        );
    },

    // Test success message
    testSuccessMessage() {
        window.handleSuccess(
            "Operation completed successfully!",
            "Success Title"
        );
    },

    // Test confirmation dialog
    async testConfirmDialog() {
        const confirmed = await window.handleConfirm(
            "Are you sure you want to perform this action?",
            "Confirm Action",
            "Yes, I am sure",
            "Cancel"
        );

        if (confirmed) {
            window.handleSuccess("Action confirmed!", "Confirmed");
        } else {
            window.handleSuccess("Action cancelled!", "Cancelled");
        }
    },

    // Test loading dialog
    testLoadingDialog() {
        const loading = window.handleLoading("Processing your request...");

        // Simulate async operation
        setTimeout(() => {
            loading.close();
            window.handleSuccess("Operation completed!", "Done");
        }, 3000);
    },
};

// Make it available globally for testing in browser console
if (typeof window !== "undefined") {
    window.testErrorHandling = testErrorHandling;
}

// Export for use in components
export default testErrorHandling;
