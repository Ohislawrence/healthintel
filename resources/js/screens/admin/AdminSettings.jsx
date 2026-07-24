import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

const groupLabels = {
    general: 'App Settings',
    credits: 'Credit System',
    api: 'API Configuration',
    features: 'Feature Toggles',
    payment: 'Payment Gateway',
};

const groupIcons = {
    general: '⚙',
    credits: '◆',
    api: '⚡',
    features: '🔧',
    payment: '💳',
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

    // Payment Gateway — separate query
    const { data: gatewayData, isLoading: gatewayLoading, refetch: refetchGateway } = useQuery({
        queryKey: ['admin-payment-gateway'],
        queryFn: () => api.get('/admin/settings/payment-gateway'),
    });
    const gatewayInfo = gatewayData?.data || {};
    const activeGateway = gatewayInfo.active_gateway || 'paystack';
    const paystackConfigured = gatewayInfo.gateways?.paystack?.configured || false;
    const flutterwaveConfigured = gatewayInfo.gateways?.flutterwave?.configured || false;

    const gatewayMutation = useMutation({
        mutationFn: (gateway) => api.post('/admin/settings/payment-gateway', { gateway }),
        onSuccess: (res) => {
            setMessage({ type: 'success', text: res?.message || `Switched to ${res.data?.gateway}` });
            refetchGateway();
            setTimeout(() => setMessage(null), 3000);
        },
        onError: (err) => {
            setMessage({ type: 'error', text: err?.message || 'Failed to switch gateway' });
            setTimeout(() => setMessage(null), 3000);
        },
    });

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

    // .env Key Editor — for editing API keys directly
    const { data: envKeysData, isLoading: envKeysLoading, refetch: refetchEnvKeys } = useQuery({
        queryKey: ['admin-env-keys'],
        queryFn: () => api.get('/admin/settings/env-keys'),
    });
    const envKeys = envKeysData?.data?.keys || [];

    const [editingEnvKey, setEditingEnvKey] = useState(null);
    const [envKeyValue, setEnvKeyValue] = useState('');

    const envKeyMutation = useMutation({
        mutationFn: ({ key, value }) => api.post('/admin/settings/env-keys', { key, value }),
        onSuccess: (res) => {
            setMessage({ type: 'success', text: res?.message || 'Key saved!' });
            setEditingEnvKey(null);
            refetchEnvKeys();
            refetchGateway(); // refresh gateway cards too
            setTimeout(() => setMessage(null), 3000);
        },
        onError: (err) => {
            setMessage({ type: 'error', text: err?.message || 'Failed to save key' });
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
    // Always show payment gateway tab even if no settings records exist for it
    if (!groupKeys.includes('payment')) {
        groupKeys.push('payment');
    }
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

            {/* Payment Gateway Section — dedicated UI when "payment" tab is active */}
            {activeGroup === 'payment' && (
                <div className="card p-6 space-y-5">
                    <div>
                        <h3 className="text-base font-bold text-neutral-900 mb-1">Active Payment Processor</h3>
                        <p className="text-sm text-neutral-500">Choose which provider processes credit purchases. Changes take effect immediately for all new transactions.</p>
                    </div>

                    {gatewayLoading ? (
                        <div className="skeleton h-20 rounded-lg" />
                    ) : (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {/* Paystack */}
                            <button
                                onClick={() => gatewayMutation.mutate('paystack')}
                                disabled={gatewayMutation.isPending || !paystackConfigured}
                                className={`relative rounded-xl border-2 p-4 text-left transition-all ${
                                    activeGateway === 'paystack'
                                        ? 'border-teal-600 bg-teal-50 shadow-md'
                                        : !paystackConfigured
                                            ? 'border-neutral-200 bg-neutral-50 opacity-50 cursor-not-allowed'
                                            : 'border-neutral-200 bg-white hover:border-teal-300 hover:bg-teal-50/30'
                                }`}
                            >
                                <div className="flex items-center gap-3 mb-2">
                                    <span className="text-2xl">🇳🇬</span>
                                    <div>
                                        <p className="font-bold text-neutral-900">Paystack</p>
                                        <p className="text-xs text-neutral-400">paystack.com</p>
                                    </div>
                                    {activeGateway === 'paystack' && (
                                        <span className="ml-auto bg-teal-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">Active</span>
                                    )}
                                </div>
                                <p className="text-xs text-neutral-500">Nigerian payment gateway. PCI-DSS Level 1 certified.</p>
                                {!paystackConfigured && (
                                    <p className="text-xs text-amber-600 mt-2 font-medium">⚠️ Not configured — add PAYSTACK_SECRET_KEY to .env</p>
                                )}
                                {gatewayMutation.isPending && gatewayMutation.variables === 'paystack' && (
                                    <div className="mt-2 flex items-center gap-2 text-xs text-teal-700 font-medium">
                                        <span className="animate-spin">⟳</span> Switching...
                                    </div>
                                )}
                            </button>

                            {/* Flutterwave */}
                            <button
                                onClick={() => gatewayMutation.mutate('flutterwave')}
                                disabled={gatewayMutation.isPending || !flutterwaveConfigured}
                                className={`relative rounded-xl border-2 p-4 text-left transition-all ${
                                    activeGateway === 'flutterwave'
                                        ? 'border-teal-600 bg-teal-50 shadow-md'
                                        : !flutterwaveConfigured
                                            ? 'border-neutral-200 bg-neutral-50 opacity-50 cursor-not-allowed'
                                            : 'border-neutral-200 bg-white hover:border-teal-300 hover:bg-teal-50/30'
                                }`}
                            >
                                <div className="flex items-center gap-3 mb-2">
                                    <span className="text-2xl">🌍</span>
                                    <div>
                                        <p className="font-bold text-neutral-900">Flutterwave</p>
                                        <p className="text-xs text-neutral-400">flutterwave.com</p>
                                    </div>
                                    {activeGateway === 'flutterwave' && (
                                        <span className="ml-auto bg-teal-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">Active</span>
                                    )}
                                </div>
                                <p className="text-xs text-neutral-500">Pan-African payment gateway. Supports cards, bank transfers, USSD.</p>
                                {!flutterwaveConfigured && (
                                    <p className="text-xs text-amber-600 mt-2 font-medium">⚠️ Not configured — add FLUTTERWAVE_SECRET_KEY to .env</p>
                                )}
                                {gatewayMutation.isPending && gatewayMutation.variables === 'flutterwave' && (
                                    <div className="mt-2 flex items-center gap-2 text-xs text-teal-700 font-medium">
                                        <span className="animate-spin">⟳</span> Switching...
                                    </div>
                                )}
                            </button>
                        </div>
                    )}

                    <div className="bg-neutral-50 rounded-lg p-4 text-xs text-neutral-500 space-y-1">
                        <p><strong>How it works:</strong> Selecting a provider changes which payment gateway is used for all new credit purchases. Existing transactions are not affected.</p>
                        <p><strong>Configuration:</strong> Set API keys in your <code className="bg-neutral-200 px-1 rounded">.env</code> file — PAYSTACK_SECRET_KEY and FLUTTERWAVE_SECRET_KEY. Unconfigured providers are disabled.</p>
                        <p><strong>Webhook URLs:</strong> Update your webhook URLs in the provider dashboard:</p>
                        <ul className="list-disc list-inside ml-2 mt-1 space-y-0.5">
                            <li>Paystack: <code className="bg-neutral-200 px-1 rounded">https://healthintel.app/api/payment/webhook</code></li>
                            <li>Flutterwave: <code className="bg-neutral-200 px-1 rounded">https://healthintel.app/api/payment/webhook/flutterwave</code></li>
                        </ul>
                    </div>
                </div>
            )}

            {/* ── .env Key Editor (visible when payment tab selected) ── */}
            {activeGroup === 'payment' && (
                <div className="card p-6 space-y-4">
                    <div>
                        <h3 className="text-base font-bold text-neutral-900 mb-1">API Keys</h3>
                        <p className="text-sm text-neutral-500">Manage API keys directly in your <code className="bg-neutral-200 px-1 rounded">.env</code> file. Keys are masked for security.</p>
                    </div>

                    {envKeysLoading ? (
                        <div className="skeleton h-20 rounded-lg" />
                    ) : (
                        <div className="space-y-3">
                            {/* Group by provider */}
                            {['Paystack', 'Flutterwave', 'AI / DeepSeek', 'Communication'].map(providerGroup => {
                                const providerKeys = envKeys.filter(k => k.group === providerGroup);
                                if (providerKeys.length === 0) return null;
                                return (
                                    <div key={providerGroup} className="space-y-2">
                                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider">{providerGroup}</p>
                                        {providerKeys.map(pk => (
                                            <div key={pk.key} className="flex items-center justify-between bg-neutral-50 rounded-lg px-3 py-2 gap-3">
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-semibold text-neutral-700 truncate">{pk.label}</p>
                                                    <p className="text-xs text-neutral-400 font-mono">
                                                        {pk.isSet ? pk.value : <span className="text-amber-500">(not set)</span>}
                                                    </p>
                                                </div>
                                                {editingEnvKey === pk.key ? (
                                                    <div className="flex items-center gap-2 shrink-0">
                                                        <input
                                                            type="text"
                                                            value={envKeyValue}
                                                            onChange={(e) => setEnvKeyValue(e.target.value)}
                                                            className="input-base w-48 text-sm font-mono"
                                                            placeholder="sk_live_..."
                                                            autoFocus
                                                        />
                                                        <button
                                                            onClick={() => envKeyMutation.mutate({ key: pk.key, value: envKeyValue })}
                                                            disabled={envKeyMutation.isPending}
                                                            className="btn btn-primary text-xs px-3 py-2"
                                                        >✓</button>
                                                        <button
                                                            onClick={() => setEditingEnvKey(null)}
                                                            className="text-neutral-400 hover:text-neutral-600 px-1"
                                                        >×</button>
                                                    </div>
                                                ) : (
                                                    <button
                                                        onClick={() => {
                                                            setEditingEnvKey(pk.key);
                                                            setEnvKeyValue('');
                                                        }}
                                                        className="text-xs font-semibold text-teal-600 hover:text-teal-800 transition-colors shrink-0"
                                                    >
                                                        {pk.isSet ? 'Edit' : 'Add Key'}
                                                    </button>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700">
                        ⚠️ <strong>Security note:</strong> API keys are sensitive credentials. Keys saved here are written directly to the <code className="bg-amber-100 px-1 rounded">.env</code> file on your server. Only use this if you trust your admin users. Keys take effect on the next request.
                    </div>
                </div>
            )}

            {isLoading ? (
                <div className="card p-8 text-center">
                    <div className="skeleton h-6 w-48 mx-auto rounded" />
                </div>
            ) : activeGroup === 'payment' ? (
                /* Payment tab shows its own UI above — hide the empty settings list */
                <div className="card p-6 text-center">
                    <span className="text-3xl block mb-2">💳</span>
                    <p className="text-sm text-neutral-500">Configure your payment gateway settings in the <code className="bg-neutral-200 px-1 rounded">.env</code> file, then select the active provider above.</p>
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