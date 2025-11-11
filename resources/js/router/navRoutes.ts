export interface NavItem {
    name: string;
    href: string;
    icon: string;
    current?: boolean;
    children?: NavItem[];
    roles?: string[]; // Add roles property
}

// Owner routes - full access
const ownerRoutes: NavItem[] = [
    {
        name: "Dashboard",
        href: "/dashboard",
        icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
        roles: ["owner", "employee"],
    },
    {
        name: "Manajemen Outlet",
        href: "/outlets",
        icon: "M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4",
        roles: ["owner"],
    },
    {
        name: "Manajemen Karyawan",
        href: "/employees",
        icon: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z",
        roles: ["owner"],
    },
    // {
    //     name: "Manajemen Cuti",
    //     href: "/leave",
    //     icon: "M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002 2V7a2 2 0 00-2-2h-2M9 5a2 2 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1z",
    //     roles: ["owner", "employee"],
    // },
    // {
    //     name: "Manajemen Shift",
    //     href: "/shifts",
    //     icon: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",
    //     roles: ["owner"],
    //     children: [
    //         {
    //             name: "Daftar Shift",
    //             href: "/shifts",
    //             icon: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",
    //             roles: ["owner"],
    //         },
    //         {
    //             name: "Jadwal Shift",
    //             href: "/shifts/generate-schedule",
    //             icon: "M8 7V3m0 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
    //             roles: ["owner"],
    //         },
    //         {
    //             name: "Penugasan Shift",
    //             href: "/shifts/assign-employee",
    //             icon: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z",
    //             roles: ["owner"],
    //         },
    //         {
    //             name: "Statistik Shift",
    //             href: "/shifts/statistics",
    //             icon: "M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 01-1 1h-4a1 1 0 01-1 1h-4a1 1 0 01-1-1z",
    //             roles: ["owner"],
    //         },
    //     ],
    // },
    // {
    //     name: "Manajemen Penggajian",
    //     href: "/payroll",
    //     icon: "M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 01-1 1h-4a1 1 0 01-1 1h-4a1 1 0 01-1-1z",
    //     roles: ["owner"],
    //     children: [
    //         {
    //             name: "Daftar Payroll",
    //             href: "/payroll",
    //             icon: "M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 01-1 1h-4a1 1 0 01-1 1h-4a1 1 0 01-1-1z",
    //             roles: ["owner"],
    //         },
    //         {
    //             name: "Buat Payroll",
    //             href: "/payroll/create-period",
    //             icon: "M12 4v16a1 1 0 011-1h4a1 1 0 011-1v-9a1 1 0 011-1h-4a1 1 0 01-1-1z",
    //             roles: ["owner"],
    //         },
    //         {
    //             name: "Statistik Payroll",
    //             href: "/payroll/statistics",
    //             icon: "M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 01-1 1h-4a1 1 0 01-1 1h-4a1 1 0 01-1-1z",
    //             roles: ["owner"],
    //         },
    //     ],
    // },
    {
        name: "Laporan",
        href: "/reports",
        icon: "M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 01-1 1h-4a1 1 0 01-1 1h-4a1 1 0 01-1-1z",
        roles: ["owner"],
    },
];

// Employee routes - limited access
const employeeRoutes: NavItem[] = [
    {
        name: "Dashboard",
        href: "/dashboard",
        icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
        roles: ["owner", "employee"],
    },
    {
        name: "Absensi",
        href: "/attendance",
        icon: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",
        roles: ["employee"],
    },
    // {
    //     name: "Manajemen Cuti",
    //     href: "/leave",
    //     icon: "M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002 2V7a2 2 0 00-2-2h-2M9 5a2 2 0 001-1v-1m3-2V8a2 2 0 00-2 2v6a2 2 0 002 2zm6-4V7a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1z",
    //     roles: ["owner", "employee"],
    // },
    // {
    //     name: "Jadwal Saya",
    //     href: "/shifts/employee",
    //     icon: "M8 7V3m0 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011 1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
    //     roles: ["employee"],
    // },
];

export const getNavItems = (
    currentPath: string,
    userRole?: string
): NavItem[] => {
    const routes = userRole === "owner" ? ownerRoutes : employeeRoutes;

    return routes.map((item) => ({
        ...item,
        current: item.href === currentPath,
    }));
};
