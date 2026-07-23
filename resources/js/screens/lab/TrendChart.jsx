import React from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import Disclaimer from '../../components/ui/Disclaimer';

const flagColors = {
    normal: '#10B981',
    low: '#F59E0B',
    high: '#F59E0B',
    critical_low: '#EF4444',
    critical_high: '#EF4444',
};

const flagLabels = {
    normal: 'Normal',
    low: 'Low',
    high: 'High',
    critical_low: 'Critical Low',
    critical_high: 'Critical High',
};

export default function TrendChart() {
    const { testSlug } = useParams();

    const { data, isLoading } = useQuery({
        queryKey: ['trends', testSlug],
        queryFn: () => api.get(`/trends?test_slug=${testSlug}`),
        enabled: !!testSlug,
    });

    const trends = data?.data?.trends || [];

    // Reverse to show chronological order (oldest first)
    const sorted = [...trends].reverse();

    if (isLoading) {
        return (
            <div className="space-y-4 animate-pulse">
                <div className="h-8 w-48 rounded bg-gray-100" />
                <div className="h-64 rounded-xl bg-gray-100" />
            </div>
        );
    }

    if (sorted.length === 0) {
        return (
            <div className="py-20 text-center">
                <p className="text-gray-500">No trend data available for this test.</p>
                <Link to="/dashboard" className="text-teal-600 text-sm mt-2 inline-block">
                    Back to dashboard
                </Link>
            </div>
        );
    }

    const testName = sorted[0]?.test_name || testSlug;
    const unit = sorted[0]?.unit || '';
    const values = sorted.map((s) => s.value);
    const minVal = Math.min(...values) * 0.9;
    const maxVal = Math.max(...values) * 1.1;
    const range = maxVal - minVal || 1;

    const svgWidth = 600;
    const svgHeight = 300;
    const padding = { top: 20, right: 20, bottom: 40, left: 50 };
    const chartW = svgWidth - padding.left - padding.right;
    const chartH = svgHeight - padding.top - padding.bottom;

    const points = sorted.map((s, i) => {
        const x = padding.left + (i / Math.max(sorted.length - 1, 1)) * chartW;
        const y = padding.top + chartH - ((Number(s.value) - minVal) / range) * chartH;
        return { x, y, ...s };
    });

    const linePath = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-semibold text-gray-900">
                        {testName} Trend
                    </h2>
                    <p className="text-sm text-gray-500">
                        Historical values over time ({unit})
                    </p>
                </div>
                <Link to="/dashboard" className="text-sm text-teal-600">
                    Dashboard
                </Link>
            </div>

            {/* SVG chart */}
            <div className="rounded-xl border border-gray-200 bg-white p-6 overflow-x-auto">
                <svg viewBox={`0 0 ${svgWidth} ${svgHeight}`} className="w-full max-w-full">
                    {/* Y axis grid lines */}
                    {[0, 0.25, 0.5, 0.75, 1].map((pct) => {
                        const y = padding.top + chartH - pct * chartH;
                        const label = (minVal + pct * range).toFixed(unit.includes('%') ? 1 : 1);
                        return (
                            <g key={pct}>
                                <line
                                    x1={padding.left}
                                    y1={y}
                                    x2={svgWidth - padding.right}
                                    y2={y}
                                    stroke="#E5E7EB"
                                    strokeWidth="1"
                                />
                                <text
                                    x={padding.left - 8}
                                    y={y + 4}
                                    textAnchor="end"
                                    fontSize="10"
                                    fill="#9CA3AF"
                                >
                                    {label}
                                </text>
                            </g>
                        );
                    })}

                    {/* X axis labels */}
                    {points
                        .filter((_, i) => i % Math.ceil(points.length / 6) === 0 || i === points.length - 1)
                        .map((p) => (
                            <text
                                key={p.x}
                                x={p.x}
                                y={svgHeight - 8}
                                textAnchor="middle"
                                fontSize="10"
                                fill="#9CA3AF"
                            >
                                {p.date}
                            </text>
                        ))}

                    {/* Line */}
                    <path
                        d={linePath}
                        fill="none"
                        stroke="#0F766E"
                        strokeWidth="2.5"
                        strokeLinecap="round"
                    />

                    {/* Data points */}
                    {points.map((p, i) => (
                        <g key={i}>
                            <circle
                                cx={p.x}
                                cy={p.y}
                                r={p.flag.startsWith('critical') ? 6 : 4}
                                fill={flagColors[p.flag] || flagColors.normal}
                                stroke="#fff"
                                strokeWidth="2"
                            />
                            <title>
                                {`${p.date}: ${p.value} ${unit} (${flagLabels[p.flag]})`}
                            </title>
                        </g>
                    ))}
                </svg>
            </div>

            {/* Legend */}
            <div className="flex gap-4 flex-wrap">
                {Object.entries(flagLabels).map(([key, label]) => (
                    <div key={key} className="flex items-center gap-2">
                        <span
                            className="h-3 w-3 rounded-full inline-block"
                            style={{ backgroundColor: flagColors[key] }}
                        />
                        <span className="text-xs text-gray-600">{label}</span>
                    </div>
                ))}
            </div>

            {/* Data table */}
            <div className="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-600">Date</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-600">Value</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-600">Flag</th>
                        </tr>
                    </thead>
                    <tbody>
                        {[...trends].reverse().map((t, i) => (
                            <tr key={i} className="border-t border-gray-100">
                                <td className="px-4 py-2 text-gray-700">{t.date}</td>
                                <td className="px-4 py-2 text-gray-900 font-medium">
                                    {t.value} {t.unit}
                                </td>
                                <td className="px-4 py-2">
                                    <span
                                        className="inline-block rounded-full px-2 py-0.5 text-xs font-medium"
                                        style={{
                                            backgroundColor: flagColors[t.flag] + '20',
                                            color: flagColors[t.flag],
                                        }}
                                    >
                                        {flagLabels[t.flag] || t.flag}
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Disclaimer />
        </div>
    );
}
