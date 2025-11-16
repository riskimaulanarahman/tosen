# Overtime Calculation Fix - Dashboard.vue:346 Error

## Problem

Error: `Dashboard.vue:346 Uncaught (in promise) TypeError: outlet.value?.calculateOvertime is not a function`

## Root Cause

The Vue component was trying to call `outlet.value?.calculateOvertime(new Date(now))` but this method doesn't exist on the frontend. The `calculateOvertime` method is a PHP backend method in the `Outlet` model that needs to be called server-side.

## Solution

Implemented a server-side pre-calculation approach:

### 1. Updated DashboardController

-   **File**: `app/Http/Controllers/DashboardController.php`
-   **Changes**: Added current overtime calculation in the `employeeDashboard()` method
-   **Code**:

```php
// Calculate current overtime for checkout validation
$currentOvertimeMinutes = $outlet->calculateOvertime(now());
$outlet->current_overtime_minutes = $currentOvertimeMinutes;
```

### 2. Updated AttendanceController

-   **File**: `app/Http/Controllers/AttendanceController.php`
-   **Changes**: Added current overtime calculation in the `status()` method
-   **Code**:

```php
// Calculate current overtime for checkout validation
$currentOvertimeMinutes = $outlet->calculateOvertime(now());
$outlet->current_overtime_minutes = $currentOvertimeMinutes;
```

### 3. Updated Dashboard.vue

-   **File**: `resources/js/Pages/Dashboard.vue`
-   **Changes**: Replaced method call with pre-calculated value
-   **Before**:

```javascript
const overtimeMinutes = outlet.value?.calculateOvertime(new Date(now)) || 0;
```

-   **After**:

```javascript
const overtimeMinutes = outlet.value?.current_overtime_minutes || 0;
```

## Benefits

1. **Eliminates Frontend-Backend Method Calls**: No more calling PHP methods from JavaScript
2. **Improved Performance**: Overtime is calculated once server-side instead of multiple times client-side
3. **Better Error Handling**: No more runtime errors from missing methods
4. **Consistent Data**: All overtime calculations happen in one place (backend)
5. **Maintainability**: Easier to debug and modify overtime logic

## Testing

-   ✅ PHP syntax validation passed
-   ✅ Laravel routes working correctly
-   ✅ Vite development server running without errors
-   ✅ All modified files compile successfully

## Files Modified

1. `app/Http/Controllers/DashboardController.php`
2. `app/Http/Controllers/AttendanceController.php`
3. `resources/js/Pages/Dashboard.vue`

## Impact

This fix resolves the runtime error and ensures that overtime calculations work properly for checkout validation in the employee dashboard.
