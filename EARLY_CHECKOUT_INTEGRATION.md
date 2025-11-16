# Early Checkout Modal Integration Guide

## Overview

The `EarlyCheckoutModal.vue` component is designed to integrate with the existing attendance system to handle early checkout scenarios when employees attempt to check out before their scheduled end time.

## Component Features

### 1. Mandatory Justification

-   **Required Field**: Textarea for early checkout justification
-   **Validation**: Minimum 10 characters, maximum 200 characters
-   **Placeholder**: "Silakan berikan justifikasi untuk early checkout (wajib diisi)"
-   **Language**: Indonesian text as requested

### 2. Visual Design

-   **Responsive Design**: Works on mobile and desktop
-   **Theme Matching**: Uses existing UI theme colors and styles
-   **Warning Message**: Clear indication that early checkout requires justification
-   **Loading States**: Proper loading indicators during submission

### 3. State Management Integration

-   **Shared State**: Integrates with `attendanceState.js` for consistent state
-   **Form Reset**: Automatically resets form when modal closes
-   **Error Handling**: User-friendly error messages for validation failures

## Integration Example

### Step 1: Import the Component

```vue
<script setup>
import EarlyCheckoutModal from "@/Components/EarlyCheckoutModal.vue";
import { useAttendanceState } from "@/utils/attendanceState";

const { checkoutRemarks, resetCheckoutRemarks } = useAttendanceState();

const showEarlyCheckoutModal = ref(false);
</script>
```

### Step 2: Use in Template

```vue
<template>
    <!-- Your existing template -->

    <!-- Add Early Checkout Modal -->
    <EarlyCheckoutModal
        :show="showEarlyCheckoutModal"
        :isLoading="isProcessing"
        @close="handleEarlyCheckoutClose"
        @submit="handleEarlyCheckoutSubmit"
    />
</template>
```

### Step 3: Handle Events

```javascript
const handleEarlyCheckoutSubmit = async (data) => {
    try {
        // Process checkout with justification
        await processCheckoutWithJustification(data.justification);

        // Close modal
        showEarlyCheckoutModal.value = false;

        // Reset remarks after successful checkout
        resetCheckoutRemarks();
    } catch (error) {
        console.error("Checkout failed:", error);
        // Handle error appropriately
    }
};

const handleEarlyCheckoutClose = () => {
    showEarlyCheckoutModal.value = false;
};
```

## Backend Integration

### API Endpoint

The component expects to work with existing backend endpoints:

-   **Checkout**: `POST /attendance/checkout`
-   **Parameter**: `checkout_remarks` (sent along with other checkout data)

### Expected Request Format

```javascript
const formData = new FormData();
formData.append("latitude", position.latitude);
formData.append("longitude", position.longitude);
formData.append("selfie", imageBlob);
formData.append("checkout_remarks", justification); // Early checkout justification
```

## Triggering Logic

### When to Show Early Checkout Modal

The modal should be triggered when:

1. **User attempts checkout** before scheduled end time
2. **Work duration** is less than the minimum required hours
3. **Early checkout policy** is enabled for the outlet

### Example Detection Logic

```javascript
const shouldShowEarlyCheckoutModal = computed(() => {
    if (!todayAttendance.value || todayAttendance.value.check_out_time) {
        return false;
    }

    const workDurationMinutes = calculateWorkDuration(
        todayAttendance.value.check_in_time
    );
    const minimumWorkDuration = outlet.value?.early_checkout_threshold || 240; // 4 hours default

    return workDurationMinutes < minimumWorkDuration;
});
```

## Integration with Existing Dashboard

To integrate with the existing Dashboard.vue:

### 1. Add Import

```javascript
import EarlyCheckoutModal from "@/Components/EarlyCheckoutModal.vue";
```

### 2. Add Modal State

```javascript
const showEarlyCheckoutModal = ref(false);
```

### 3. Update Checkout Logic

```javascript
const checkOut = () => {
    // Check if early checkout
    if (shouldShowEarlyCheckoutModal.value) {
        showEarlyCheckoutModal.value = true;
    } else {
        // Proceed with normal checkout flow
        proceedWithCheckout();
    }
};
```

### 4. Add Modal to Template

```vue
<EarlyCheckoutModal
    :show="showEarlyCheckoutModal"
    :isLoading="isProcessing"
    @close="() => (showEarlyCheckoutModal = false)"
    @submit="handleEarlyCheckoutSubmit"
/>
```

## Styling Integration

The component uses Tailwind CSS classes that match the existing theme:

-   **Colors**: Uses `yellow-600`, `gray-900`, etc. for consistency
-   **Spacing**: Follows existing spacing patterns
-   **Typography**: Uses existing text sizes and weights
-   **Responsive**: Fully responsive design

## Testing

### Manual Testing

1. Open Dashboard as an employee
2. Check in at normal time
3. Attempt to check out early (before 4 hours of work)
4. Verify modal appears with correct warning message
5. Fill justification with less than 10 characters
6. Verify validation error appears
7. Fill valid justification (10+ characters)
8. Submit and verify checkout completes

### Automated Testing

```javascript
// Test the modal behavior
window.testEarlyCheckout = () => {
    // Simulate early checkout scenario
    const testJustification = "Ada urusan penting di luar kantor";

    // Trigger modal
    showEarlyCheckoutModal.value = true;

    // Simulate form submission
    setTimeout(() => {
        handleEarlyCheckoutSubmit({
            justification: testJustification,
            timestamp: new Date().toISOString(),
        });
    }, 1000);
};
```

## Backend Validation

The backend should validate:

1. **Justification presence**: Ensure `checkout_remarks` is not empty when required
2. **Character limits**: Validate minimum and maximum character counts
3. **Business rules**: Apply early checkout policies based on outlet settings

### Example Backend Validation

```php
// In AttendanceController.php checkout method
$rules = [
    'latitude' => 'required|numeric|between:-90,90',
    'longitude' => 'required|numeric|between:-180,180',
    'selfie' => 'required|file|image|mimes:jpeg,jpg,png|max:2048',
    'checkout_remarks' => [
        'required_if:work_duration,' . $earlyCheckoutThreshold,
        'string',
        'min:10',
        'max:500'
    ]
];
```

## Benefits

### For Employees

-   **Clear Justification Process**: Easy to understand when early checkout is required
-   **User-Friendly Interface**: Indonesian language and clear instructions
-   **Proper Validation**: Real-time feedback on form validation
-   **Consistent Experience**: Matches existing UI patterns

### For Management

-   **Audit Trail**: Justifications are stored with attendance records
-   **Policy Enforcement**: Ensures early checkouts are properly justified
-   **Reporting**: Data available for attendance analytics

## Troubleshooting

### Modal Not Appearing

1. Check `shouldShowEarlyCheckoutModal` computed property
2. Verify work duration calculation logic
3. Ensure outlet early checkout settings are loaded

### Validation Not Working

1. Check `isFormValid` computed property
2. Verify minimum character validation (10 characters)
3. Check error message display logic

### Submission Failing

1. Verify backend endpoint is receiving `checkout_remarks`
2. Check network connectivity
3. Review browser console for JavaScript errors

This integration provides a complete solution for handling early checkout scenarios with proper justification requirements while maintaining consistency with the existing attendance system.
