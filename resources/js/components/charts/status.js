// Shared status styles/colors for lab-result charts.

// Canonical statuses emitted by ReferenceRangeService::classify().
export const STATUS = {
    NORMAL: 'normal',
    LOW: 'abnormal_low',
    HIGH: 'abnormal_high',
    CRITICAL_LOW: 'critical_low',
    CRITICAL_HIGH: 'critical_high',
    UNKNOWN: 'unknown',
};

// Map any status variant (including old-style 'low'/'high') to a canonical bucket.
export function bucket(status) {
    switch (status) {
        case 'critical_low':
            return 'critical_low';
        case 'critical_high':
            return 'critical_high';
        case 'abnormal_low':
        case 'low':
            return 'low';
        case 'abnormal_high':
        case 'high':
            return 'high';
        case 'normal':
            return 'normal';
        default:
            return 'unknown';
    }
}

export const COLORS = {
    normal: '#22C55E',
    low: '#F59E0B',
    high: '#F59E0B',
    critical_low: '#EF4444',
    critical_high: '#EF4444',
    unknown: '#9CA3AF',
};

export const LABELS = {
    normal: 'Normal',
    low: 'Low',
    high: 'High',
    critical_low: 'Critical Low',
    critical_high: 'Critical High',
    unknown: 'Unknown',
};