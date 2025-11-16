import { ref, computed } from "vue";

const todayAttendance = ref(null);
const outlet = ref(null);
const isLoading = ref(false);
const checkoutRemarks = ref("");

// Computed properties for consistent state
const canCheckIn = computed(() => {
    return !todayAttendance.value || todayAttendance.value.check_out_time;
});

const canCheckOut = computed(() => {
    return todayAttendance.value && !todayAttendance.value.check_out_time;
});

const isCheckedIn = computed(() => {
    return todayAttendance.value && !todayAttendance.value.check_out_time;
});

// Validation for checkout remarks
const checkoutValidation = computed(() => {
    if (
        !outlet.value ||
        !todayAttendance.value ||
        todayAttendance.value.check_out_time
    ) {
        return {
            requiresEarlyCheckoutRemarks: false,
            requiresOvertimeRemarks: false,
            workDurationMinutes: 0,
            overtimeMinutes: 0,
            earlyCheckoutThreshold: 240,
            overtimeThreshold: 60,
            earlyCheckoutRules: { min: 10, max: 300 },
            overtimeRules: { min: 10, max: 500 },
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

    const config = outlet.value?.overtime_config || {};
    const earlyCheckoutConfig = config.early_checkout || {};
    const overtimeConfig = config.overtime || {};

    return {
        requiresEarlyCheckoutRemarks:
            (earlyCheckoutConfig.enabled &&
                earlyCheckoutConfig.mandatory_remarks &&
                workDurationMinutes < earlyCheckoutConfig.threshold_minutes) ||
            false,
        requiresOvertimeRemarks:
            (overtimeConfig.mandatory_remarks &&
                overtimeMinutes >= overtimeConfig.threshold_minutes) ||
            false,
        workDurationMinutes,
        overtimeMinutes,
        earlyCheckoutThreshold: earlyCheckoutConfig.threshold_minutes || 240,
        overtimeThreshold: overtimeConfig.threshold_minutes || 60,
        earlyCheckoutRules: {
            min: earlyCheckoutConfig.remarks_min_length || 10,
            max: earlyCheckoutConfig.remarks_max_length || 300,
        },
        overtimeRules: {
            min: overtimeConfig.remarks_min_length || 10,
            max: overtimeConfig.remarks_max_length || 500,
        },
    };
});

export function useAttendanceState() {
    const updateAttendance = (attendanceData) => {
        todayAttendance.value = attendanceData;
    };

    const updateOutlet = (outletData) => {
        outlet.value = outletData;
    };

    const setLoading = (loading) => {
        isLoading.value = loading;
    };

    const resetCheckoutRemarks = () => {
        checkoutRemarks.value = "";
    };

    const fetchAttendanceStatus = async () => {
        console.log("DEBUG: fetchAttendanceStatus called");
        setLoading(true);
        try {
            const url = route("attendance.status");
            console.log("DEBUG: Fetching from URL:", url);
            const response = await fetch(url);
            console.log("DEBUG: Response received:", response);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            console.log("DEBUG: Data received:", data);

            updateAttendance(data.today_attendance);
            updateOutlet(data.outlet);
            console.log("DEBUG: State updated successfully");
            console.log(
                "DEBUG: Updated todayAttendance:",
                todayAttendance.value
            );
            console.log("DEBUG: Updated outlet:", outlet.value);
        } catch (error) {
            console.error("Error fetching attendance status:", error);
            console.error("DEBUG: Error details:", {
                message: error.message,
                stack: error.stack,
                name: error.name,
            });
            // Re-throw error so the caller can handle it
            throw error;
        } finally {
            console.log("DEBUG: Setting loading to false");
            setLoading(false);
        }
    };

    return {
        // State
        todayAttendance,
        outlet,
        isLoading,
        checkoutRemarks,

        // Computed
        canCheckIn,
        canCheckOut,
        isCheckedIn,
        checkoutValidation,

        // Methods
        updateAttendance,
        updateOutlet,
        setLoading,
        resetCheckoutRemarks,
        fetchAttendanceStatus,
    };
}
