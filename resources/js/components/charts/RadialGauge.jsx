import React from 'react';
import { bucket, COLORS, LABELS } from './status';

// ── Geometry helpers ──────────────────────────────────────────────
// Angle is measured in degrees from the top (12 o'clock), increasing
// clockwise. A top semi-circle spans -90° (left) → +90° (right).
function polarToCartesian(cx, cy, r, angleDeg) {
    const rad = ((angleDeg - 90) * Math.PI) / 180;
    return {
        x: cx + r * Math.cos(rad),
        y: cy + r * Math.sin(rad),
    };
}

function arcPath(cx, cy, r, startDeg, endDeg) {
    const start = polarToCartesian(cx, cy, r, startDeg);
    const end = polarToCartesian(cx, cy, r, endDeg);
    const largeArc = endDeg - startDeg <= 180 ? 0 : 1;
    return `M ${start.x.toFixed(2)} ${start.y.toFixed(2)} A ${r} ${r} 0 ${largeArc} 1 ${end.x.toFixed(2)} ${end.y.toFixed(2)}`;
}

const DEG = (frac) => -90 + 180 * clamp01(frac);
const clamp01 = (n) => Math.max(0, Math.min(1, n));

/**
 * Build the colored zone segments + needle fraction for a chart value.
 */
function buildGauge(item) {
    const rangeLow = num(item.range_low);
    const rangeHigh = num(item.range_high);
    const criticalLow = num(item.critical_low);
    const criticalHigh = num(item.critical_high);
    const value = num(item.value);

    // If we have no range at all, render an empty "unknown" gauge.
    if (rangeLow === null || rangeHigh === null || rangeHigh <= rangeLow) {
        return { segments: [{ key: 'unknown', left: 0, right: 1, color: COLORS.unknown }], needle: null };
    }

    const pad = rangeHigh - rangeLow;
    const hasCritLow = criticalLow !== null;
    const hasCritHigh = criticalHigh !== null;

    // Domain spans critical bounds when present, otherwise pads one band on each side.
    const lo = hasCritLow ? criticalLow : rangeLow - pad;
    const hi = hasCritHigh ? criticalHigh : rangeHigh + pad;
    const span = hi - lo;

    const frac = (boundary) => (boundary - lo) / span;

    const segments = [];
    if (hasCritLow) {
        segments.push({ key: 'critical_low', left: 0, right: frac(criticalLow), color: COLORS.critical_low });
    }
    segments.push({ key: 'low', left: frac(hasCritLow ? criticalLow : lo), right: frac(rangeLow), color: COLORS.low });
    segments.push({ key: 'normal', left: frac(rangeLow), right: frac(rangeHigh), color: COLORS.normal });
    segments.push({ key: 'high', left: frac(rangeHigh), right: frac(hasCritHigh ? criticalHigh : hi), color: COLORS.high });
    if (hasCritHigh) {
        segments.push({ key: 'critical_high', left: frac(criticalHigh), right: 1, color: COLORS.critical_high });
    }

    const needle = clamp01((value - lo) / span);
    return { segments, needle };
}

function num(v) {
    if (v === null || v === undefined || v === '' || isNaN(Number(v))) return null;
    return Number(v);
}

export default function RadialGauge({ item, width = 200, height = 130 }) {
    const cx = width / 2;
    const cy = height - 14;
    const radius = Math.min(width / 2 - 12, height - 24);

    const { segments, needle } = buildGauge(item);
    const statusBucket = bucket(item.status);
    const statusColor = COLORS[statusBucket] || COLORS.unknown;
    const statusLabel = LABELS[statusBucket] || LABELS.unknown;

    // Needle position
    let needlePoint = null;
    if (needle !== null) {
        const r = radius - 8;
        needlePoint = polarToCartesian(cx, cy, r, DEG(needle));
    }

    return (
        <div className="flex flex-col items-center">
            <svg width={width} height={height} viewBox={`0 0 ${width} ${height}`} role="img" aria-label={`${item.test_name} gauge`}>
                {/* Colored zone arcs */}
                {segments.map((seg) => (
                    <path
                        key={seg.key}
                        d={arcPath(cx, cy, radius, DEG(seg.left), DEG(seg.right))}
                        fill="none"
                        stroke={seg.color}
                        strokeWidth={12}
                        strokeLinecap="butt"
                    />
                ))}

                {/* Needle */}
                {needlePoint && (
                    <g>
                        <line
                            x1={cx}
                            y1={cy}
                            x2={needlePoint.x}
                            y2={needlePoint.y}
                            stroke="#1F2937"
                            strokeWidth={3}
                            strokeLinecap="round"
                        />
                        <circle cx={cx} cy={cy} r={5} fill="#1F2937" />
                    </g>
                )}

                {/* Value text */}
                <text x={cx} y={cy - radius + 26} textAnchor="middle" className="fill-current" style={{ fontSize: 22, fontWeight: 800, fill: '#111827' }}>
                    {item.display_value != null ? item.display_value : item.value}
                    {item.unit ? <tspan style={{ fontSize: 12, fontWeight: 600, fill: '#9CA3AF' }}> {item.unit}</tspan> : null}
                </text>
            </svg>

            <div className="flex items-center gap-2 mt-1">
                <span className="inline-block w-2.5 h-2.5 rounded-full" style={{ backgroundColor: statusColor }} />
                <span className="text-xs font-semibold" style={{ color: statusColor }}>{statusLabel}</span>
            </div>
        </div>
    );
}