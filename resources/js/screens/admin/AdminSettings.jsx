import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

const groupLabels = {
    general: 'App Settings',
    credits: 'Credit System',
    api: 'API Configuration',
    features: 'Feature Toggles',
};

const groupIcons = {
    general: '⚙',
    credits: '◆',
    api: '⚡',
    features: '🔧',
};

export default function AdminSettings() {
    const [editingKey, setEditingKey] = useState(null);
    const [editValue, setEditValue] = useState('');
    const [activeGroup, setActiveGroup] = useState('credits');
    const [message, setMessage] = useState(null);

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['admin-settings'],
        queryFn: () => api.get('/admin/settings'),
    });
    const groups = data?.data?.groups || {};

    const updateMutation = useMutation({
        mutationFn: ({ id, value }) => api.put(`/admin/settings/${id}`, { value }),
        onSuccess: (res) => {
            setMessage({ type: 'success', text: res?.message || 'Saved!' });
            setEditingKey(null);
            refetch();
            setTimeout(() => setMessage(null), 3000);
        },
        onError: (err) => {
            setMessage({ type: 'error', text: err?.message || 'Failed to save' });
            setTimeout(() => setMessage(null), 3000);
        },
    });

    const startEdit = (setting) => {
        setEditingKey(setting.key);
        setEditValue(setting.type === 'boolean' ? String(setting.value) : String(setting.value ?? ''));
    };

    const saveEdit = (setting) => {
        let value = editValue;
        if (setting.type === 'boolean') {
            value = editValue === '1' || editValue === 'true' ? '1' : '0';
        } else if (setting.type === 'integer') {
            value = parseInt(editValue, 10);
        } else if (setting.type === 'decimal') {
            value = parseFloat(editValue);
        }
        updateMutation.mutate({ id: setting.id, value });
    };

    const toggleBoolean = (setting) => {
        const newVal = setting.value ? '0' : '1';
        updateMutation.mutate({ id: setting.id, value: newVal });
    };

    const groupKeys = Object.keys(groups).filter(g => groups[g]?.length > 0);
    const currentGroup = groups[activeGroup] || [];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-neutral-900">Settings</h2>
            </div>

            {message && (
                <div className={`rounded-xl border px-4 py-3 text-sm font-medium ${
                    message.type === 'success'
                        ? 'bg-success-50 border-success-200 text-success-700'
                        : 'bg-danger-50 border-danger-200 text-danger-700'
                }`}>
                    {message.text}
                </div>
            )}

            {/* Group Tabs */}
            <div className="flex gap-2 flex-wrap">
                {groupKeys.map((group) => (
                    <button
                        key={group}
                        onClick={() => setActiveGroup(group)}
                        className={`flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition-all ${
                            activeGroup === group
                                ? 'bg-teal-700 text-white shadow-lg shadow-teal-200'
                                : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50'
                        }`}
                    >
                        <span>{groupIcons[group] || '⚙'}</span>
                        {groupLabels[group] || group}
                    </button>
                ))}
            </div>

            {isLoading ? (
                <div className="card p-8 text-center">
                    <div className="skeleton h-6 w-48 mx-auto rounded" />
                </div>
            ) : currentGroup.length === 0 ? (
                <div className="card p-8 text-center">
                    <span className="text-3xl block mb-3">⚙</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">No settings in this group</p>
                </div>
            ) : (
                <div className="card overflow-hidden">
                    {currentGroup.map((setting, index) => (
                        <div
                            key={setting.key}
                            className={`p-4 ${
                                index < currentGroup.length - 1 ? 'border-b border-neutral-100' : ''
                            }`}
                        >
                            <div className="flex items-center justify-between gap-4">
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-bold text-neutral-900">{setting.label}</p>
                                    <p className="text-xs text-neutral-400 mt-0.5">{setting.description || setting.key}</p>
                                </div>

                                {editingKey === setting.key ? (
                                    <div className="flex items-center gap-2 shrink-0">
                                        {setting.type === 'boolean' ? (
                                            <select
                                                value={editValue}
                                                onChange={(e) => setEditValue(e.target.value)}
                                                className="input-base w-24 text-sm"
                                            >
                                                <option value="1">Enabled</option>
                                                <option value="0">Disabled</option>
                                            </select>
                                        ) : setting.type === 'string' && setting.key.includes('_key') ? (
                                            <input
                                                type="text"
                                                value={editValue}
                                                onChange={(e) => setEditValue(e.target.value)}
                                                className="input-base w-64 text-sm"
                                                placeholder="Enter value..."
                                            />
                                        ) : setting.type === 'string' ? (
                                            <input
                                                type="text"
                                                value={editValue}
                                                onChange={(e) => setEditValue(e.target.value)}
                                                className="input-base w-40 text-sm"
                                            />
                                        ) : (
                                            <input
                                                type="number"
                                                value={editValue}
                                                onChange={(e) => setEditValue(e.target.value)}
                                                step={setting.type === 'decimal' ? '0.1' : '1'}
                                                className="input-base w-24 text-sm"
                                            />
                                        )}
                                        <button
                                            onClick={() => saveEdit(setting)}
                                            disabled={updateMutation.isPending}
                                            className="btn btn-primary text-xs px-3 py-2"
                                        >
                                            ✓
                                        </button>
                                        <button
                                            onClick={() => setEditingKey(null)}
                                            className="text-neutral-400 hover:text-neutral-600 px-1"
                                        >
                                            ×
                                        </button>
                                    </div>
                                ) : setting.type === 'boolean' ? (
                                    <button
                                        onClick={() => toggleBoolean(setting)}
                                        disabled={updateMutation.isPending}
                                        className={`shrink-0 w-12 h-7 rounded-full transition-all relative ${
                                            setting.value ? 'bg-teal-600' : 'bg-neutral-300'
                                        }`}
                                    >
                                        <span
                                            className={`absolute top-0.5 w-6 h-6 rounded-full bg-white shadow transition-all ${
                                                setting.value ? 'right-0.5' : 'left-0.5'
                                            }`}
                                        />
                                    </button>
                                ) : (
                                    <div className="flex items-center gap-2 shrink-0">
                                        <span className="text-sm font-bold text-teal-700">
                                            {setting.type === 'string'
                                                ? (setting.value || '(empty)')
                                                : String(setting.value)}
                                        </span>
                                        <button
                                            onClick={() => startEdit(setting)}
                                            className="text-xs font-semibold text-neutral-400 hover:text-teal-700 transition-colors"
                                        >
                                            Edit
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Legend */}
            <div className="text-xs text-neutral-400">
                <p>Changes take effect immediately. Sensitive keys (API keys) are stored securely.</p>
            </div>
        </div>
    );
}