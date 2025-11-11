# Error Handling System Guide

This application now uses SweetAlert2 for comprehensive error handling throughout the system. All errors are displayed using beautiful, user-friendly modal dialogs.

## Features

### Global Error Handler Functions

The system provides four global functions that can be used anywhere in your JavaScript/Vue code:

#### `window.handleError(error, customMessage)`

Displays error messages using SweetAlert2.

**Parameters:**

-   `error` (Error/Object/String): The error object or error message
-   `customMessage` (String, optional): Custom message that overrides the default error message

**Examples:**

```javascript
// Basic error handling
try {
    await someAsyncOperation();
} catch (error) {
    window.handleError(error);
}

// With custom message
window.handleError(error, "Custom error message for the user");

// Direct string error
window.handleError("Something went wrong");
```

#### `window.handleSuccess(message, title)`

Displays success messages using SweetAlert2.

**Parameters:**

-   `message` (String): Success message to display
-   `title` (String, optional): Custom title (default: "Berhasil")

**Examples:**

```javascript
window.handleSuccess("Data saved successfully!");
window.handleSuccess("Operation completed!", "Success");
```

#### `window.handleConfirm(message, title, confirmText, cancelText)`

Displays confirmation dialog using SweetAlert2.

**Parameters:**

-   `message` (String): Confirmation message
-   `title` (String, optional): Dialog title (default: "Konfirmasi")
-   `confirmText` (String, optional): Confirm button text (default: "Ya")
-   `cancelText` (String, optional): Cancel button text (default: "Batal")

**Returns:** Promise that resolves to `true` if confirmed, `false` if cancelled

**Examples:**

```javascript
const confirmed = await window.handleConfirm(
    "Are you sure you want to delete this item?",
    "Confirm Delete",
    "Yes, Delete",
    "Cancel"
);

if (confirmed) {
    // Perform delete operation
    window.handleSuccess("Item deleted successfully!");
}
```

#### `window.handleLoading(message)`

Displays loading dialog using SweetAlert2.

**Parameters:**

-   `message` (String, optional): Loading message (default: "Memproses...")

**Returns:** SweetAlert instance with `close()` method

**Examples:**

```javascript
const loading = window.handleLoading("Processing your request...");

// Simulate async operation
setTimeout(() => {
    loading.close();
    window.handleSuccess("Operation completed!");
}, 2000);
```

## Automatic Error Handling

### HTTP Response Interceptor

The system automatically handles HTTP errors through axios interceptors:

-   **401 Unauthorized**: Shows "Sesi Anda telah berakhir. Silakan login kembali."
-   **403 Forbidden**: Shows "Anda tidak memiliki izin untuk melakukan aksi ini."
-   **404 Not Found**: Shows "Data yang Anda cari tidak tersedia."
-   **422 Validation**: Shows validation errors (handled locally in forms)
-   **429 Too Many Requests**: Shows "Silakan tunggu beberapa saat sebelum mencoba lagi."
-   **500 Server Error**: Shows "Terjadi kesalahan pada server. Silakan coba lagi nanti."
-   **503 Service Unavailable**: Shows "Sedang dalam pemeliharaan. Silakan coba lagi nanti."
-   **Network Errors**: Shows "Tidak dapat terhubung ke server. Periksa koneksi internet Anda."

### CSRF Token Handling

The system automatically handles CSRF token expiration by refreshing the page when a 419 status is encountered.

## Implementation in Components

### Vue Components

All Vue components now use the global error handlers:

```javascript
// In Vue setup
const deleteItem = async (item) => {
    const confirmed = await window.handleConfirm(
        `Delete ${item.name}?`,
        "Confirm Delete"
    );

    if (confirmed) {
        router.delete(route("items.destroy", item.id), {
            onSuccess: () => {
                window.handleSuccess("Item deleted successfully!");
            },
            onError: (errors) => {
                window.handleError(errors);
            },
        });
    }
};
```

### Form Handling

Forms use Inertia's built-in error handling with global error display:

```javascript
const form = useForm({
    name: "",
    email: "",
});

const submit = () => {
    form.post(route("users.store"), {
        onSuccess: () => {
            window.handleSuccess("User created successfully!");
        },
        onError: (errors) => {
            window.handleError(errors);
        },
    });
};
```

## Testing the Error System

A test utility is available at `window.testErrorHandling` with the following methods:

```javascript
// Test different error types
window.testErrorHandling.testNetworkError(); // Network error
window.testErrorHandling.testUnauthorizedError(); // 401 error
window.testErrorHandling.testForbiddenError(); // 403 error
window.testErrorHandling.testNotFoundError(); // 404 error
window.testErrorHandling.testValidationError(); // 422 validation error
window.testErrorHandling.testServerError(); // 500 server error
window.testErrorHandling.testCustomError(); // Custom error message

// Test success messages
window.testErrorHandling.testSuccessMessage(); // Success dialog
window.testErrorHandling.testConfirmDialog(); // Confirmation dialog
window.testErrorHandling.testLoadingDialog(); // Loading dialog
```

## Best Practices

### 1. Always Use Global Handlers

```javascript
// ✅ Good
try {
    await operation();
} catch (error) {
    window.handleError(error);
}

// ❌ Avoid this
try {
    await operation();
} catch (error) {
    console.error(error); // User won't see this!
    alert(error.message); // Poor UX
}
```

### 2. Use Confirmation for Destructive Actions

```javascript
// ✅ Good
const confirmed = await window.handleConfirm(
    "Delete this item? This action cannot be undone.",
    "Confirm Delete"
);

if (confirmed) {
    // Perform destructive action
}

// ❌ Avoid this
if (confirm("Delete this item?")) {
    // Native confirm is ugly
    // Perform destructive action
}
```

### 3. Provide Context-Specific Messages

```javascript
// ✅ Good
window.handleError(error, "Failed to save user. Please try again.");

// ❌ Less helpful
window.handleError(error);
```

### 4. Handle Success Too

```javascript
// ✅ Good
window.handleSuccess("Profile updated successfully!");

// ❌ Silent success
// User might not know the operation succeeded
```

## Styling and Customization

The SweetAlert2 dialogs use consistent styling:

-   **Error**: Red theme with timer (5 seconds)
-   **Success**: Green theme with timer (3 seconds)
-   **Warning/Confirm**: Warning theme with custom buttons
-   **Loading**: Loading spinner with backdrop

All dialogs:

-   Have backdrop overlay
-   Prevent outside clicks
-   Use consistent color scheme
-   Auto-dismiss after timeout (except loading and confirm)

## File Structure

```
resources/js/
├── bootstrap.js                    # Global error handlers and axios setup
├── utils/
│   └── errorHandlerTest.js         # Test utility for error handling
└── Components/
    ├── CameraModal.vue            # Camera component with error handling
    └── [other components]         # All components updated with error handling
```

## Migration Notes

Previous error handling used inline alerts and basic message displays. The new system:

1. **Replaced** all `alert()` calls with `window.handleError()`
2. **Replaced** all `confirm()` calls with `window.handleConfirm()`
3. **Added** success messages using `window.handleSuccess()`
4. **Centralized** error handling in bootstrap.js
5. **Added** automatic HTTP error handling via axios interceptors

## Troubleshooting

### Common Issues

1. **SweetAlert not defined**: Ensure SweetAlert2 is installed and imported in bootstrap.js
2. **Global functions not available**: Make sure bootstrap.js is imported before other scripts
3. **Errors not showing**: Check browser console for JavaScript errors
4. **Style issues**: Ensure SweetAlert2 CSS is loaded properly

### Debug Mode

To debug error handling, open browser console and use:

```javascript
// Test basic error handling
window.testErrorHandling.testNetworkError();

// Check if global functions exist
console.log(typeof window.handleError); // should be 'function'
console.log(typeof window.handleSuccess); // should be 'function'
```

This comprehensive error handling system ensures all users receive clear, consistent, and helpful feedback for all operations in the application.
