# Fix Plan: Display Indonesian Day Names Instead of Numbers

## Issue Summary

The UI is showing work days as numbers (0, 1, 2, 3, 4, 5) instead of Indonesian day names (Senin, Selasa, etc.).

## Root Cause

The `getFormattedWorkDaysAttribute()` method in `app/Models/Outlet.php` has two issues:

1. It's using `array_keys($days)` instead of the actual day values
2. The mapping array uses English day names as keys instead of numeric values (1-7)

## Solution

### 1. Fix the getFormattedWorkDaysAttribute method in Outlet.php

Current implementation (lines 42-67):

```php
public function getFormattedWorkDaysAttribute()
{
    if (empty($this->work_days)) {
        return 'Tidak ada jadwal';
    }

    $dayNames = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];

    $days = array_filter($this->work_days, function ($day) {
        return !is_null($day);
    });

    $formattedDays = array_map(function ($day) use ($dayNames) {
        return $dayNames[$day] ?? $day;
    }, array_keys($days));

    return implode(', ', $formattedDays);
}
```

Fixed implementation should be:

```php
public function getFormattedWorkDaysAttribute()
{
    if (empty($this->work_days)) {
        return 'Tidak ada jadwal';
    }

    // Map numeric values (1-7) to Indonesian day names
    $dayNames = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu'
    ];

    // Filter out null values and get the actual day values
    $days = array_filter($this->work_days, function ($day) {
        return !is_null($day);
    });

    // Map the day values to Indonesian names
    $formattedDays = array_map(function ($day) use ($dayNames) {
        return $dayNames[$day] ?? $day;
    }, $days);

    return implode(', ', $formattedDays);
}
```

### 2. Check other places where days might be displayed

Search for other files that might need similar fixes:

-   Look for direct display of work_days array
-   Check if there are any JavaScript components that need updating
-   Verify any API responses that return raw day numbers

### 3. Test the changes

1. Check the OperationalStatus component to ensure it displays formatted work days
2. Verify outlet creation/editing pages still work correctly
3. Test any reports or views that show work days

## Files to Modify

1. `app/Models/Outlet.php` - Fix the getFormattedWorkDaysAttribute method

## Expected Outcome

After applying these changes, the UI will show:

-   "Senin, Selasa, Rabu, Kamis, Jumat" instead of "1, 2, 3, 4, 5"
-   "Senin, Selasa, Rabu, Kamis, Jumat, Sabtu" instead of "1, 2, 3, 4, 5, 6"
-   etc.
