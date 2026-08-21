import React from 'react';
import { bucket, COLORS, LABELS } from './status';

const ORDER = ['normal', 'low', 'high', 'critical_low', 'critical_high', 'unknown'];

export default function DistributionDonut({ values, size = 150, thickness = 24 }) {
    // Tally counts by canonical bucket.
    const counts = { normal: 0, low: 0, high: 0, critical_low: 0, critical_high: 0, unknown: 0 };
    (values || []).forEach((v) => {
        const b = bucket(v.status);
        counts[b] = (counts[b] || 0) + 1;
    });

    const total = (values || []).length;
    const segments = ORDER
        .map((key) => ({ key, count: counts[key] || 0, color: COLORS[key], label: LABELS[key] }))
        .filter((seg) => seg.count > 0);

    // Build donut arcs.
    const radius = (size - thickness) / 2;
    const cx = size / 2;
    const cy = size / 2;
    const circumference = 2 * Math.PI * radius;

    let offset = 0;
    const arcs = segments.map((seg) => {
        const frac = total > 0 ? seg.count / total : 0;
        const len = frac * circumference;
        const dash = `${len} ${circumference - len}`;
        const dashOffset = -offset;
        offset += len;
        return { ...seg, frac, dash, dashOffset };
    });

    // Center label: dominant category or total.
    const dominant = segments.length > 0 ? segments.reduce((a, b) => (b.count > a.count ? b : a), segments[0]) : null;

    return (
        <div className="flex items-center gap-5">
            <div className="relative flex-shrink-0" style={{ width: size, height: size }}>
                <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`} role="img" aria-label="Results distribution">
                    <circle
                        cx={cx}
                        cy={cy}
                        r={radius}
                        fill="none"
                        stroke="#F3F4F6"
                        strokeWidth={thickness}
                    />
                    {arcs.map((arc) => (
                        <circle
                            key={arc.key}
                            cx={cx}
                            cy={cy}
                            r={radius}
                            fill="none"
                            stroke={arc.color}
                            strokeWidth={thickness}
                            strokeDasharray={arc.dash}
                            strokeDashoffset={arc.dashOffset}
                            strokeLinecap="butt"
                            transform={`rotate(-90 ${cx} ${cy})`}
                        />
                    ))}
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span className="text-2xl font-extrabold text-gray-900">{total}</span>
                    <span className="text-[10px] uppercase tracking-wide text-gray-400">tests</span>
                </div>
            </div>

            <div className="space-y-1.5">
                {segments.map((seg) => (
                    <div key={seg.key} className="flex items-center gap-2 text-xs text-gray-600">
                        <span className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ backgroundColor: seg.color }} />
                        <span className="flex-1">{seg.label}</span>
                        <span className="font-semibold text-gray-900">{seg.count}</span>
                    </div>
                ))}
                {segments.length === 0 && <p className="text-xs text-gray-400">No results to chart</p>}
            </div>
        </div>
    );
}