import React from 'react';
import { bucket, COLORS, LABELS } from './status';

function num(v) {
    if (v === null || v === undefined || v === '' || isNaN(Number(v))) return null;
    return Number(v);
}

const clamp01 = (n) => Math.max(0, Math.min(1, n));

/**
 * A compact horizontal reference-range bar with a marker for the value,
 * plus zone coloring. Good as a lighter companion to the radial gauge.
 */
export default function RangeBar({ item }) {
    const rangeLow = num(item.range_low);
    const rangeHigh = num(item.range_high);
    const criticalLow = num(item.critical_low);
    const criticalHigh = num(item.critical_high);
    const value = num(item.value);
    const statusBucket = bucket(item.status);

    if (rangeLow === null || rangeHigh === null || rangeHigh <= rangeLow) {
        return (
            <div className="flex items-center justify-between text-xs">
                <span className="text-gray-500">{item.test_name}</span>
                <span className="font-semibold text-gray-400">{LABELS.unknown}</span>
            </div>
        );
    }

    const pad = rangeHigh - rangeLow;
    const hasCritLow = criticalLow !== null;
    const hasCritHigh = criticalHigh !== null;
    const lo = hasCritLow ? criticalLow : rangeLow - pad;
    const hi = hasCritHigh ? criticalHigh : rangeHigh + pad;
    const span = hi - lo;
    const frac = (b) => (b - lo) / span;

    const zones = [];
    if (hasCritLow) zones.push({ left: 0, right: frac(criticalLow), color: COLORS.critical_low });
    zones.push({ left: frac(hasCritLow ? criticalLow : lo), right: frac(rangeLow), color: COLORS.low });
    zones.push({ left: frac(rangeLow), right: frac(rangeHigh), color: COLORS.normal });
    zones.push({ left: frac(rangeHigh), right: frac(hasCritHigh ? criticalHigh : hi), color: COLORS.high });
    if (hasCritHigh) zones.push({ left: frac(criticalHigh), right: 1, color: COLORS.critical_high });

    const marker = clamp01((num(item.percent) ?? (value - lo) / span));

    return (
        <div className="space-y-1.5">
            <div className="flex items-end justify-between">
                <span className="text-sm font-semibold text-gray-900">{item.test_name}</span>
                <span className="text-xs" style={{ color: COLORS[statusBucket] }}>
                    <strong className="text-sm">{item.display_value != null ? item.display_value : item.value}</strong>
                    {item.unit ? ` ${item.unit}` : ''} · {LABELS[statusBucket]}
                </span>
            </div>
            <div className="relative h-3 rounded-full overflow-hidden bg-gray-100">
                {zones.map((z, i) => (
                    <span
                        key={i}
                        className="absolute top-0 bottom-0"
                        style={{ left: `${z.left * 100}%`, width: `${(z.right - z.left) * 100}%`, backgroundColor: z.color }}
                    />
                ))}
                {/* Value marker */}
                <span
                    className="absolute top-1/2 w-3 h-3 rounded-full bg-white ring-2 ring-gray-800 -translate-x-1/2 -translate-y-1/2"
                    style={{ left: `${marker * 100}%` }}
                />
            </div>
            <div className="flex justify-between text-[10px] text-gray-400">
                <span>{hasCritLow ? criticalLow : lo.toFixed(1)}</span>
                <span className="text-gray-500">{rangeLow}–{rangeHigh}</span>
                <span>{hasCritHigh ? criticalHigh : hi.toFixed(1)}</span>
            </div>
        </div>
    );
}