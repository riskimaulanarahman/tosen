const DEFAULT_APP_NAME = "TOSEN-TOGA Presence";

export const getAppName = (): string => {
    const name = import.meta.env.VITE_APP_NAME;
    return typeof name === "string" && name.trim().length > 0
        ? name.trim()
        : DEFAULT_APP_NAME;
};

export const getAppInitials = (name: string = getAppName()): string => {
    const initials = name
        .split(/\s+/)
        .filter(Boolean)
        .map((segment) => segment.charAt(0).toUpperCase())
        .slice(0, 2)
        .join("");

    return initials || DEFAULT_APP_NAME.slice(0, 2).toUpperCase();
};

export const getCurrentYear = (): number => new Date().getFullYear();
