# Early Checkout Modal Troubleshooting Guide

## Overview

This guide provides detailed troubleshooting steps for when the Early Checkout Justification Modal fails to appear during early checkout attempts in the Vue.js attendance system.

## Common Issues & Solutions

### 1. Modal Not Appearing

#### Issue: Modal doesn't show when user tries to check out early

#### Debugging Steps:

**Step 1: Verify Modal Trigger Condition**

```javascript
// In Dashboard.vue, add console logging
const checkOut = () => {
    console.log("DEBUG: checkOut called");
    console.log("DEBUG: todayAttendance:", todayAttendance.value);
    console.log("DEBUG: checkoutValidation:", checkoutValidation.value);

    // Check if early checkout is detected
    const requiresEarlyCheckout =
        checkoutValidation.value.requiresEarlyCheckoutRemarks;
    const requiresOvertime = checkoutValidation.value.requiresOvertimeRemarks;
    const shouldShowModal = requiresEarlyCheckout || requiresOvertime;

    console.log("DEBUG: shouldShowModal:", shouldShowModal);

    if (shouldShowModal) {
        showCheckoutRemarksModal.value = true;
        console.log("DEBUG: Early checkout modal should show");
    } else {
        proceedWithCheckout();
        console.log("DEBUG: Proceeding with normal checkout");
    }
};
```

**Step 2: Check attendanceState.js Validation Logic**

```javascript
// In attendanceState.js, add debugging
const checkoutValidation = computed(() => {
    if (
        !outlet.value ||
        !todayAttendance.value ||
        todayAttendance.value.check_out_time
    ) {
        console.log("DEBUG: No outlet or already checked out");
        return {
            requiresEarlyCheckoutRemarks: false,
            requiresOvertimeRemarks: false,
        };
    }

    const checkinTime = todayAttendance.value.check_in_time;
    const now = new Date();
    const workDurationMinutes = checkinTime
        ? Math.floor((now - new Date(checkinTime)) / (1000 * 60))
        : 0;
    const overtimeMinutes =
        outlet.value?.operational_status?.status === "closed"
            ? 0
            : outlet.value?.current_overtime_minutes || 0;

    console.log("DEBUG: workDurationMinutes:", workDurationMinutes);
    console.log("DEBUG: overtimeMinutes:", overtimeMinutes);

    const config = outlet.value?.overtime_config || {};
    const earlyCheckoutConfig = config.early_checkout || {};
    const overtimeConfig = config.overtime || {};

    const requiresEarlyCheckoutRemarks =
        (earlyCheckoutConfig.enabled &&
            earlyCheckoutConfig.mandatory_remarks &&
            workDurationMinutes < earlyCheckoutConfig.threshold_minutes) ||
        false;

    const requiresOvertimeRemarks =
        (overtimeConfig.mandatory_remarks &&
            overtimeMinutes >= overtimeConfig.threshold_minutes) ||
        false;

    console.log(
        "DEBUG: requiresEarlyCheckoutRemarks:",
        requiresEarlyCheckoutRemarks
    );
    console.log("DEBUG: requiresOvertimeRemarks:", requiresOvertimeRemarks);

    return {
        requiresEarlyCheckoutRemarks,
        requiresOvertimeRemarks,
        workDurationMinutes,
        overtimeMinutes,
        earlyCheckoutThreshold: earlyCheckoutConfig.threshold_minutes || 240,
        overtimeThreshold: overtimeConfig.threshold_minutes || 60,
    };
});
```

**Step 3: Verify Outlet Configuration**

```javascript
// Check if outlet has proper overtime configuration
const checkOutletConfig = () => {
    console.log("DEBUG: Outlet config:", outlet.value);
    console.log("DEBUG: Overtime config:", outlet.value?.overtime_config);

    if (!outlet.value?.overtime_config) {
        console.error("ERROR: Outlet missing overtime_config");
        return false;
    }

    const earlyCheckoutConfig = outlet.value.overtime_config.early_checkout;
    if (!earlyCheckoutConfig) {
        console.error("ERROR: Outlet missing early_checkout config");
        return false;
    }

    console.log("DEBUG: Early checkout enabled:", earlyCheckoutConfig.enabled);
    console.log(
        "DEBUG: Early checkout threshold:",
        earlyCheckoutConfig.threshold_minutes
    );

    return true;
};
```

#### Common Causes & Solutions:

**Cause 1: Outlet Configuration Missing**

-   **Problem**: Outlet doesn't have `overtime_config` or `early_checkout` settings
-   **Solution**: Check outlet configuration in database

```sql
SELECT id, name, overtime_config FROM outlets WHERE id = [current_outlet_id];
```

**Cause 2: Work Duration Calculation Issue**

-   **Problem**: Work duration calculation is incorrect
-   **Solution**: Verify time zone and calculation logic

```javascript
// Add timezone consideration
const now = new Date();
const checkinTime = new Date(todayAttendance.value.check_in_time);
const workDurationMinutes = Math.floor(
    (now.getTime() - checkinTime.getTime()) / (1000 * 60)
);
```

**Cause 3: State Not Reactive**

-   **Problem**: `todayAttendance` or `outlet` not properly reactive
-   **Solution**: Ensure proper state initialization

```javascript
// In Dashboard.vue onMounted
onMounted(() => {
    // Initialize state with props
    updateAttendance(props.today_attendance);
    updateOutlet(props.outlet);

    // Then fetch latest status
    fetchAttendanceStatus();
});
```

### 2. Modal Appears But Doesn't Submit

#### Debugging Steps:

**Step 1: Check Modal Component Props**

```javascript
// In EarlyCheckoutModal.vue, add prop logging
const props = defineProps({
    show: { type: Boolean, default: false },
    isLoading: { type: Boolean, default: false },
});

watch(
    () => props.show,
    (newVal) => {
        console.log("DEBUG: Modal show prop changed to:", newVal);
    },
    { immediate: true }
);
```

**Step 2: Verify Event Emission**

```javascript
// In EarlyCheckoutModal.vue, add event logging
const emit = defineEmits(["close", "submit"]);

const handleSubmit = async () => {
    console.log(
        "DEBUG: Form submitted with justification:",
        justification.value
    );

    emit("submit", {
        justification: justification.value.trim(),
        timestamp: new Date().toISOString(),
    });

    console.log("DEBUG: Submit event emitted");
};
```

**Step 3: Check Parent Event Handler**

```javascript
// In Dashboard.vue, verify event handler
const handleCheckoutRemarksConfirm = (data) => {
    console.log("DEBUG: Received checkout remarks confirmation:", data);

    // Update shared state
    checkoutRemarks.value = data.justification;

    // Proceed with checkout
    proceedWithCheckout();
};
```

#### Common Causes & Solutions:

**Cause 1: Event Handler Not Connected**

-   **Problem**: Modal emits event but parent doesn't listen
-   **Solution**: Verify event binding in parent component

```vue
<!-- Correct event binding -->
<EarlyCheckoutModal
    @close="handleCheckoutRemarksClose"
    @submit="handleCheckoutRemarksConfirm"
/>
```

**Cause 2: Form Validation Preventing Submission**

-   **Problem**: Validation logic incorrectly blocks submission
-   **Solution**: Check validation rules

```javascript
// Temporarily disable validation for testing
const isFormValid = computed(() => {
    // For testing: always return true
    return justification.value.trim().length >= 0;
});
```

### 3. Modal Z-Index Issues

#### Debugging Steps:

**Step 1: Check CSS Z-Index**

```css
/* Verify modal z-index */
.early-checkout-modal {
    z-index: 9999; /* Ensure highest z-index */
}

/* Check for conflicting z-index */
.other-modal {
    z-index: 1000; /* Lower than checkout modal */
}
```

**Step 2: Verify Modal Stack**

```javascript
// Check if multiple modals are open
const modalStack = ref([]);

const openModal = (modalId) => {
    modalStack.value.push(modalId);
    console.log("DEBUG: Modal stack:", modalStack.value);
};

const closeModal = (modalId) => {
    const index = modalStack.value.indexOf(modalId);
    if (index > -1) {
        modalStack.value.splice(index, 1);
    }
    console.log("DEBUG: Modal stack after close:", modalStack.value);
};
```

#### Common Causes & Solutions:

**Cause 1: CSS Specificity Issues**

-   **Problem**: Other CSS rules override modal styles
-   **Solution**: Use more specific selectors or !important

```css
.early-checkout-modal {
    z-index: 9999 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
}
```

**Cause 2: Portal/Teleport Issues**

-   **Problem**: Modal rendered in wrong DOM container
-   **Solution**: Use Vue's Teleport component

```vue
<template>
    <Teleport to="body">
        <div class="early-checkout-modal">
            <!-- Modal content -->
        </div>
    </Teleport>
</template>
```

### 4. State Synchronization Issues

#### Debugging Steps:

**Step 1: Check State Updates**

```javascript
// In attendanceState.js, add state logging
const updateAttendance = (attendanceData) => {
    console.log("DEBUG: Updating attendance state:", attendanceData);
    todayAttendance.value = attendanceData;
};

const updateOutlet = (outletData) => {
    console.log("DEBUG: Updating outlet state:", outletData);
    outlet.value = outletData;
};
```

**Step 2: Verify Computed Properties**

```javascript
// Add debugging to computed properties
const canCheckOut = computed(() => {
    const result =
        todayAttendance.value && !todayAttendance.value.check_out_time;
    console.log("DEBUG: canCheckOut computed:", {
        todayAttendance: todayAttendance.value,
        check_out_time: todayAttendance.value?.check_out_time,
        result: result,
    });
    return result;
});
```

#### Common Causes & Solutions:

**Cause 1: State Not Shared**

-   **Problem**: Different components using different state instances
-   **Solution**: Ensure single instance of attendanceState

```javascript
// Create singleton instance
import { useAttendanceState } from "@/utils/attendanceState";

// In main app, ensure single instance
const attendanceState = useAttendanceState();
```

**Cause 2: Async State Updates**

-   **Problem**: State updates not reflected immediately
-   **Solution**: Use nextTick for DOM updates

```javascript
import { nextTick } from "vue";

const updateState = async (newState) => {
    todayAttendance.value = newState;
    await nextTick();
    console.log("DEBUG: State updated and DOM refreshed");
};
```

### 5. Browser Compatibility Issues

#### Debugging Steps:

**Step 1: Check Browser Console**

```javascript
// Add browser compatibility checks
const checkBrowserCompatibility = () => {
    console.log("DEBUG: User agent:", navigator.userAgent);
    console.log("DEBUG: Vue version:", window.Vue?.version);

    // Check for specific browser issues
    if (!window.fetch) {
        console.error("ERROR: Fetch API not supported");
    }

    if (!window.FormData) {
        console.error("ERROR: FormData not supported");
    }
};
```

**Step 2: Verify Event Listeners**

```javascript
// Check if events are properly attached
const verifyEventListeners = () => {
    const modal = document.querySelector(".early-checkout-modal");
    if (modal) {
        console.log("DEBUG: Modal element found:", modal);

        // Check for event listeners
        const hasClickListener = modal.onclick !== null;
        console.log("DEBUG: Has click listener:", hasClickListener);
    } else {
        console.error("ERROR: Modal element not found");
    }
};
```

## Comprehensive Testing Script

### Automated Testing

```javascript
// Create comprehensive test script
const runEarlyCheckoutTests = async () => {
    console.log("🧪 Starting Early Checkout Modal Tests...");

    // Test 1: Modal trigger conditions
    await testModalTriggerConditions();

    // Test 2: Modal visibility
    await testModalVisibility();

    // Test 3: Form submission
    await testFormSubmission();

    // Test 4: State synchronization
    await testStateSynchronization();

    console.log("✅ Early Checkout Modal Tests Completed");
};

const testModalTriggerConditions = async () => {
    console.log("Testing modal trigger conditions...");

    // Simulate early checkout scenario
    const mockAttendance = {
        check_in_time: new Date(Date.now() - 3 * 60 * 60 * 1000), // 3 hours ago
        check_out_time: null,
    };

    updateAttendance(mockAttendance);

    // Check if modal should show
    const shouldShow = checkoutValidation.value.requiresEarlyCheckoutRemarks;
    console.log("Expected: Modal should show =", shouldShow);

    if (shouldShow) {
        console.log("✅ Modal trigger condition working");
    } else {
        console.log("❌ Modal trigger condition failed");
    }
};
```

## Quick Fix Checklist

### Before Deploying to Production

-   [ ] Verify outlet has proper `overtime_config` in database
-   [ ] Test early checkout scenario with work duration < threshold
-   [ ] Test normal checkout scenario (no modal)
-   [ ] Verify modal appears on mobile devices
-   [ ] Check form validation with various character counts
-   [ ] Test modal close and reopen functionality
-   [ ] Verify state persistence across page refreshes
-   [ ] Check browser console for JavaScript errors
-   [ ] Test with different user roles and permissions

### Production Monitoring

```javascript
// Add production monitoring
window.addEventListener("error", (event) => {
    if (event.message.includes("EarlyCheckoutModal")) {
        console.error("Early Checkout Modal Error:", event.error);
        // Send error to monitoring service
        sendErrorToMonitoring({
            error: event.error,
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString(),
            url: window.location.href,
        });
    }
});
```

## Emergency Fixes

### Hotfix for Modal Not Showing

```javascript
// Add to Dashboard.vue as emergency fix
const emergencyCheckOut = () => {
    console.log("EMERGENCY: Forcing early checkout modal");

    // Force show modal regardless of validation
    showCheckoutRemarksModal.value = true;

    // Add emergency logging
    setTimeout(() => {
        console.log("EMERGENCY: Modal should be visible now");
        console.log(
            "EMERGENCY: showCheckoutRemarksModal.value:",
            showCheckoutRemarksModal.value
        );

        const modalElement = document.querySelector(".early-checkout-modal");
        if (modalElement) {
            console.log("EMERGENCY: Modal element found in DOM");
        } else {
            console.error("EMERGENCY: Modal element NOT found in DOM");
        }
    }, 100);
};
```

## Support Commands

### For Debugging in Production

```bash
# Check for JavaScript errors
grep -r "EarlyCheckoutModal" storage/logs/laravel.log

# Check outlet configuration
php artisan tinker --execute="
$outlet = App\Models\Outlet::find(1);
echo json_encode($outlet->overtime_config, JSON_PRETTY_PRINT);
"

# Test early checkout validation
php artisan tinker --execute="
$user = App\Models\User::find(1);
$attendance = App\Models\Attendance::where('user_id', $user->id)
    ->whereDate('created_at', now())
    ->first();

if ($attendance) {
    $workDuration = now()->diffInMinutes($attendance->check_in_time);
    echo 'Work duration: ' . $workDuration . ' minutes';
    echo 'Early checkout threshold: ' . config('attendance.early_checkout_threshold', 240);
    echo 'Requires remarks: ' . ($workDuration < 240 ? 'YES' : 'NO');
}
"
```

This troubleshooting guide provides systematic debugging approaches for identifying and resolving early checkout modal issues in production environments.
