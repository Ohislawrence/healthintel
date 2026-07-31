import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../lib/api';

export default function AdminPartnershipDetail() {
    const { id } = useParams();
    const isEdit = Boolean(id);
    const navigate = useNavigate();

    const [form, setForm] = useState({
        provider_id: '', plan_tier: 'pilot', pricing_model: 'per_report',
        rate_per_report: '20000', monthly_allowance: '500', overage_rate: '',
        white_label: false, brand_logo_url: '', brand_primary_color: '#0E6B5C',
        brand_contact_info: '', contract_start: '', contract_end: '',
        status: 'active', ndpa_agreement_signed: false,
    });
    const [providers, setProviders] = useState([]);
    const [fetching, setFetching] = useState(isEdit);
    const [saving, setSaving] = useState(false);
    const [stats, setStats] = useState(null);
    const [dailyCounts, setDailyCounts] = useState([]);
    const [recentInterpretations, setRecentInterpretations] = useState([]);
    const [health, setHealth] = useState(null);
    const [invoices, setInvoices] = useState([]);
    const [activeTab, setActiveTab] = useState('monitor');

    useEffect(function() {
        api.get('/admin/providers', { params: { page: 1, per_page: 500 } }).then(function(res) {
            setProviders(res.data || []);
        }).catch(function() {});
    }, []);

    useEffect(function() {
        if (isEdit) {
            setFetching(true);
            api.get('/admin/partnerships/' + id).then(function(res) {
                var p = res.data.partnership;
                setForm({
                    provider_id: p.provider_id || '',
                    plan_tier: p.plan_tier || 'pilot',
                    pricing_model: p.pricing_model || 'per_report',
                    rate_per_report: String(p.rate_per_report || ''),
                    monthly_allowance: String(p.monthly_allowance || ''),
                    overage_rate: String(p.overage_rate || ''),
                    white_label: p.white_label || false,
                    brand_logo_url: p.brand_logo_url || '',
                    brand_primary_color: p.brand_primary_color || '#0E6B5C',
                    brand_contact_info: p.brand_contact_info || '',
                    contract_start: p.contract_start || '',
                    contract_end: p.contract_end || '',
                    status: p.status || 'active',
                    ndpa_agreement_signed: p.ndpa_agreement_signed || false,
                });
                setRecentInterpretations(res.data.recent_interpretations || []);
                setStats({ monthly_count: p.monthly_count, estimated_bill: p.estimated_bill, active_since: p.created_at });
            }).catch(function() {
                alert('Failed to load partnership');
                navigate('/admin/partnerships');
            }).finally(function() { setFetching(false); });
        }
    }, [id]);

    useEffect(function() {
        if (!isEdit || activeTab !== 'monitor') return;
        loadMonitoringData();
    }, [isEdit, activeTab]);

    function loadMonitoringData() {
        Promise.all([
            api.get('/admin/partnerships/' + id + '/stats'),
            api.get('/partner-health'),
            api.get('/admin/partnerships/' + id + '/invoices'),
        ]).then(function(results) {
            var statsRes = results[0];
            var healthRes = results[1];
            var invoiceRes = results[2];

            var s = (statsRes.data && statsRes.data.stats) || statsRes.data || {};
            setDailyCounts((statsRes.data && statsRes.data.daily_counts) || []);
            setStats(function(prev) {
                var merged = {};
                for (var k in prev) { merged[k] = prev[k]; }
                for (var k2 in s) { merged[k2] = s[k2]; }
                return merged;
            });

            var allHealth = (healthRes.data && healthRes.data.partners) || [];
            var thisHealth = null;
            for (var i = 0; i < allHealth.length; i++) {
                if (allHealth[i].id === parseInt(id)) { thisHealth = allHealth[i].health; break; }
            }
            setHealth(thisHealth);
            setInvoices(invoiceRes.data || []);
        }).catch(function(err) {
            console.error('Failed to load monitoring data', err);
        });
    }

    function handleSubmit(e) {
        e.preventDefault();
        if (!form.provider_id) { alert('Please select a provider'); return; }
        setSaving(true);
        var payload = Object.assign({}, form, {
            provider_id: parseInt(form.provider_id),
            rate_per_report: parseInt(form.rate_per_report) || 0,
            monthly_allowance: form.monthly_allowance ? parseInt(form.monthly_allowance) : null,
            overage_rate: form.overage_rate ? parseInt(form.overage_rate) : null,
        });

        var promise;
        if (isEdit) {
            promise = api.put('/admin/partnerships/' + id, payload);
        } else {
            promise = api.post('/admin/partnerships', payload);
        }
        promise.then(function() {
            navigate('/admin/partnerships');
        }).catch(function(err) {
            alert(err && err.message ? err.message : 'Failed to save');
        }).finally(function() { setSaving(false); });
    }

    if (fetching) {
        return React.createElement('div', { className: 'flex justify-center py-20' },
            React.createElement('div', { className: 'h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent' })
        );
    }

    var selectedProvider = null;
    for (var pi = 0; pi < providers.length; pi++) {
        if (providers[pi].id === parseInt(form.provider_id)) { selectedProvider = providers[pi]; break; }
    }
    var headingTitle = isEdit ? ('Partnership: ' + (selectedProvider ? selectedProvider.name : 'Loading...')) : 'New Lab Partnership';

    var statCards = null;
    if (stats && activeTab === 'monitor') {
        var healthBadge = null;
        if (health) {
            var hColor = 'bg-gray-100 text-gray-600';
            var hEmoji = '';
            if (health.status === 'healthy') { hColor = 'bg-green-100 text-green-700'; hEmoji = '\u2705'; }
            else if (health.status === 'at_risk') { hColor = 'bg-amber-100 text-amber-700'; hEmoji = '\u26A0\uFE0F'; }
            else { hColor = 'bg-red-100 text-red-700'; hEmoji = '\u274C'; }
            healthBadge = React.createElement('div', null,
                React.createElement('span', { className: 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ' + hColor },
                    hEmoji + ' ' + (health.status ? health.status.replace('_', ' ') : '')
                ),
                React.createElement('p', { className: 'text-xs text-gray-400 mt-1' }, 'Score: ' + health.score + '/100')
            );
        }
        var warningSection = null;
        if (health && health.warning_signals && health.warning_signals.length > 0) {
            var signalItems = health.warning_signals.map(function(s, i) {
                return React.createElement('li', { key: i, className: 'text-sm text-amber-700 flex items-start gap-2' },
                    React.createElement('span', { className: 'mt-0.5' }, '\u2022'), ' ' + s
                );
            });
            warningSection = React.createElement('div', { className: 'bg-amber-50 border border-amber-200 rounded-xl p-4' },
                React.createElement('h3', { className: 'text-sm font-semibold text-amber-800 mb-2' }, '\u26A0\uFE0F Warning Signals'),
                React.createElement('ul', { className: 'space-y-1' }, signalItems)
            );
        }
        var chartSection = null;
        if (dailyCounts.length > 0) {
            var maxCount = 1;
            dailyCounts.forEach(function(d) { if (d.count > maxCount) maxCount = d.count; });
            var bars = dailyCounts.map(function(d, i) {
                var pct = (d.count / maxCount) * 100;
                var h = Math.max(pct, 2);
                return React.createElement('div', { key: i, className: 'flex-1 flex flex-col items-center group relative', title: d.date + ': ' + d.count + ' reports' },
                    React.createElement('span', { className: 'text-[10px] text-gray-400 mb-1 opacity-0 group-hover:opacity-100 absolute -top-5' }, d.count),
                    React.createElement('div', { className: 'w-full bg-teal-500 rounded-t hover:bg-teal-600 transition-colors min-h-[2px]', style: { height: h + '%' } })
                );
            });
            chartSection = React.createElement('div', { className: 'bg-white rounded-xl border border-gray-200 p-6' },
                React.createElement('h3', { className: 'text-sm font-semibold text-gray-900 mb-4' }, 'Daily Interpretations (Last 30 Days)'),
                React.createElement('div', { className: 'flex items-end gap-1 h-32' }, bars),
                React.createElement('div', { className: 'flex justify-between mt-2 text-[10px] text-gray-400' },
                    React.createElement('span', null, dailyCounts[0].date),
                    React.createElement('span', null, dailyCounts[dailyCounts.length - 1].date)
                )
            );
        }
        var interpItems = recentInterpretations.slice(0, 15).map(function(interp) {
            var statusClass = 'bg-gray-50 text-gray-600';
            if (interp.status === 'completed') statusClass = 'bg-green-50 text-green-700';
            else if (interp.status === 'failed') statusClass = 'bg-red-50 text-red-700';
            return React.createElement('div', { key: interp.id, className: 'flex items-center justify-between border-b border-gray-50 pb-2 last:border-0' },
                React.createElement('div', null,
                    React.createElement('p', { className: 'text-sm font-medium text-gray-800' }, interp.test_name),
                    React.createElement('p', { className: 'text-xs text-gray-400' },
                        'Patient: ' + (interp.patient_identifier || '\u2014') + ' \u00A0|\u00A0' +
                        new Date(interp.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
                    )
                ),
                React.createElement('div', { className: 'text-right' },
                    React.createElement('span', { className: 'text-sm font-mono font-semibold text-gray-900' }, interp.value + ' ' + (interp.unit || '')),
                    React.createElement('span', { className: 'ml-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium ' + statusClass }, interp.status)
                )
            );
        });
        var invoiceItems = invoices.map(function(inv) {
            var invStatusClass = 'bg-yellow-50 text-yellow-700';
            if (inv.status === 'paid') invStatusClass = 'bg-green-50 text-green-700';
            else if (inv.status === 'overdue') invStatusClass = 'bg-red-50 text-red-700';
            return React.createElement('div', { key: inv.id, className: 'flex items-center justify-between border-b border-gray-50 pb-2 last:border-0' },
                React.createElement('div', null,
                    React.createElement('p', { className: 'text-sm font-medium text-gray-800' },
                        new Date(inv.period_start).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' })
                    ),
                    React.createElement('p', { className: 'text-xs text-gray-400' }, (inv.report_count || 0) + ' reports')
                ),
                React.createElement('div', { className: 'text-right' },
                    React.createElement('p', { className: 'text-sm font-semibold text-gray-900' }, '\u20A6' + ((inv.total_amount || 0) / 100).toLocaleString()),
                    React.createElement('span', { className: 'inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium ' + invStatusClass }, inv.status)
                )
            );
        });

        statCards = React.createElement('div', { className: 'space-y-6' },
            React.createElement('div', { className: 'grid grid-cols-2 lg:grid-cols-4 gap-4' },
                React.createElement('div', { className: 'bg-white rounded-xl border border-gray-200 p-4' },
                    React.createElement('p', { className: 'text-xs text-gray-500 uppercase tracking-wider' }, 'Reports This Month'),
                    React.createElement('p', { className: 'text-2xl font-bold text-gray-900' }, stats.this_month || stats.monthly_count || 0),
                    stats.last_month !== undefined ? React.createElement('p', { className: 'text-xs text-gray-400 mt-1' }, 'vs ' + stats.last_month + ' last month') : null
                ),
                React.createElement('div', { className: 'bg-white rounded-xl border border-gray-200 p-4' },
                    React.createElement('p', { className: 'text-xs text-gray-500 uppercase tracking-wider' }, 'Estimated Bill'),
                    React.createElement('p', { className: 'text-2xl font-bold text-gray-900' }, '\u20A6' + ((stats.estimated_bill || 0).toLocaleString()))
                ),
                React.createElement('div', { className: 'bg-white rounded-xl border border-gray-200 p-4' },
                    React.createElement('p', { className: 'text-xs text-gray-500 uppercase tracking-wider' }, 'Rate Per Report'),
                    React.createElement('p', { className: 'text-2xl font-bold text-gray-900' }, '\u20A6' + (((parseInt(form.rate_per_report) || 0) / 100).toLocaleString()))
                ),
                React.createElement('div', { className: 'bg-white rounded-xl border border-gray-200 p-4' },
                    React.createElement('p', { className: 'text-xs text-gray-500 uppercase tracking-wider' }, 'Partner Health'),
                    healthBadge || React.createElement('p', { className: 'text-sm text-gray-400' }, 'Loading...')
                )
            ),
            warningSection,
            chartSection || React.createElement('div', { className: 'bg-white rounded-xl border border-gray-200 p-6' },
                React.createElement('p', { className: 'text-sm text-gray-400' }, 'No data for the last 30 days.')
            ),
            React.createElement('div', { className: 'grid grid-cols-1 lg:grid-cols-2 gap-6' },
                React.createElement('div', { className: 'bg-white rounded-xl border border-gray-200 p-6' },
                    React.createElement('h3', { className: 'text-sm font-semibold text-gray-900 mb-4' }, 'Recent Interpretations'),
                    recentInterpretations.length > 0
                        ? React.createElement('div', { className: 'space-y-3 max-h-80 overflow-y-auto' }, interpItems)
                        : React.createElement('p', { className: 'text-sm text-gray-400' }, 'No interpretations yet.')
                ),
                React.createElement('div', { className: 'bg-white rounded-xl border border-gray-200 p-6' },
                    React.createElement('div', { className: 'flex items-center justify-between mb-4' },
                        React.createElement('h3', { className: 'text-sm font-semibold text-gray-900' }, 'Invoices'),
                        React.createElement('button', {
                            onClick: function() {
                                api.post('/admin/partnerships/' + id + '/invoices').then(function() { loadMonitoringData(); }).catch(function(err) {
                                    alert(err && err.message ? err.message : 'Failed to generate invoice');
                                });
                            },
                            className: 'text-xs font-medium text-teal-600 hover:text-teal-700 bg-teal-50 px-3 py-1.5 rounded-lg'
                        }, '+ Generate')
                    ),
                    invoices.length > 0
                        ? React.createElement('div', { className: 'space-y-3 max-h-80 overflow-y-auto' }, invoiceItems)
                        : React.createElement('p', { className: 'text-sm text-gray-400' }, 'No invoices yet.')
                )
            )
        );
    }

    var tabClass = function(key) {
        return 'px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors ' +
            (activeTab === key
                ? 'bg-white text-teal-700 border border-b-white border-gray-200 -mb-px'
                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50');
    };

    return React.createElement('div', null,
        React.createElement('div', { className: 'flex items-center justify-between mb-6' },
            React.createElement('h2', { className: 'text-2xl font-bold text-gray-900' }, headingTitle),
            React.createElement('button', { onClick: function() { navigate('/admin/partnerships'); }, className: 'text-sm text-gray-600 hover:text-teal-600' },
                '\u2190 Back to partnerships'
            )
        ),
        isEdit ? React.createElement('div', { className: 'mb-6' },
            React.createElement('div', { className: 'flex gap-1 border-b border-gray-200' },
                [
                    { key: 'monitor', label: '\uD83D\uDCCA Activity Monitor' },
                    { key: 'edit', label: '\u2699\uFE0F Settings' },
                ].map(function(tab) {
                    return React.createElement('button', {
                        key: tab.key,
                        onClick: function() { setActiveTab(tab.key); if (tab.key === 'monitor') loadMonitoringData(); },
                        className: tabClass(tab.key)
                    }, tab.label);
                })
            )
        ) : null,
        activeTab === 'monitor' && isEdit ? statCards : null,
        (activeTab === 'edit' || !isEdit) ? React.createElement('form', { onSubmit: handleSubmit, className: 'max-w-2xl space-y-6' },
            React.createElement('div', null,
                React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Provider *'),
                React.createElement('select', {
                    value: form.provider_id,
                    onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.provider_id = e.target.value; return f; }); },
                    className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none',
                    disabled: isEdit
                },
                    React.createElement('option', { value: '' }, 'Select a provider...'),
                    providers.map(function(p) {
                        var label = p.name + ' (' + p.type + (p.specialty ? ' \u2014 ' + p.specialty : '') + ')';
                        return React.createElement('option', { key: p.id, value: p.id }, label);
                    })
                )
            ),
            React.createElement('div', { className: 'grid grid-cols-2 gap-4' },
                React.createElement('div', null,
                    React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Plan Tier'),
                    React.createElement('select', {
                        value: form.plan_tier,
                        onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.plan_tier = e.target.value; return f; }); },
                        className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                    },
                        React.createElement('option', { value: 'pilot' }, 'Pilot'),
                        React.createElement('option', { value: 'standard' }, 'Standard'),
                        React.createElement('option', { value: 'premium' }, 'Premium')
                    )
                ),
                React.createElement('div', null,
                    React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Status'),
                    React.createElement('select', {
                        value: form.status,
                        onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.status = e.target.value; return f; }); },
                        className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                    },
                        React.createElement('option', { value: 'active' }, 'Active'),
                        React.createElement('option', { value: 'pilot' }, 'Pilot'),
                        React.createElement('option', { value: 'expired' }, 'Expired'),
                        React.createElement('option', { value: 'cancelled' }, 'Cancelled')
                    )
                )
            ),
            React.createElement('div', { className: 'bg-gray-50 rounded-xl p-5 space-y-4' },
                React.createElement('h3', { className: 'text-sm font-semibold text-gray-900' }, 'Pricing'),
                React.createElement('div', null,
                    React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Pricing Model'),
                    React.createElement('select', {
                        value: form.pricing_model,
                        onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.pricing_model = e.target.value; return f; }); },
                        className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                    },
                        React.createElement('option', { value: 'per_report' }, 'Per Report'),
                        React.createElement('option', { value: 'volume_tier' }, 'Volume Tier'),
                        React.createElement('option', { value: 'flat_monthly' }, 'Flat Monthly')
                    )
                ),
                React.createElement('div', { className: 'grid grid-cols-3 gap-4' },
                    React.createElement('div', null,
                        React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Rate/Report (kobo)'),
                        React.createElement('input', {
                            type: 'number', value: form.rate_per_report,
                            onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.rate_per_report = e.target.value; return f; }); },
                            className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                        })
                    ),
                    React.createElement('div', null,
                        React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Monthly Allowance'),
                        React.createElement('input', {
                            type: 'number', value: form.monthly_allowance,
                            onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.monthly_allowance = e.target.value; return f; }); },
                            className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                        })
                    ),
                    React.createElement('div', null,
                        React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Overage Rate (kobo)'),
                        React.createElement('input', {
                            type: 'number', value: form.overage_rate,
                            onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.overage_rate = e.target.value; return f; }); },
                            className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                        })
                    )
                )
            ),
            React.createElement('div', { className: 'bg-gray-50 rounded-xl p-5 space-y-4' },
                React.createElement('div', { className: 'flex items-center justify-between' },
                    React.createElement('h3', { className: 'text-sm font-semibold text-gray-900' }, 'White-Label Branding'),
                    React.createElement('label', { className: 'flex items-center gap-2 cursor-pointer' },
                        React.createElement('input', {
                            type: 'checkbox', checked: form.white_label,
                            onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.white_label = e.target.checked; return f; }); },
                            className: 'rounded border-gray-300 text-teal-600 focus:ring-teal-500'
                        }),
                        React.createElement('span', { className: 'text-sm text-gray-600' }, 'Enable')
                    )
                ),
                form.white_label ? React.createElement('div', { className: 'grid grid-cols-2 gap-4' },
                    React.createElement('div', null,
                        React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Brand Logo URL'),
                        React.createElement('input', {
                            type: 'text', value: form.brand_logo_url, placeholder: 'https://...',
                            onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.brand_logo_url = e.target.value; return f; }); },
                            className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                        })
                    ),
                    React.createElement('div', null,
                        React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Brand Color'),
                        React.createElement('div', { className: 'flex gap-2' },
                            React.createElement('input', {
                                type: 'color', value: form.brand_primary_color,
                                onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.brand_primary_color = e.target.value; return f; }); },
                                className: 'h-10 w-10 rounded border border-gray-300'
                            }),
                            React.createElement('input', {
                                type: 'text', value: form.brand_primary_color,
                                onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.brand_primary_color = e.target.value; return f; }); },
                                className: 'flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-mono focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                            })
                        )
                    ),
                    React.createElement('div', { className: 'col-span-2' },
                        React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Contact Info (shown on reports)'),
                        React.createElement('input', {
                            type: 'text', value: form.brand_contact_info, placeholder: 'Phone & email',
                            onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.brand_contact_info = e.target.value; return f; }); },
                            className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                        })
                    )
                ) : null
            ),
            React.createElement('div', { className: 'grid grid-cols-2 gap-4' },
                React.createElement('div', null,
                    React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Contract Start'),
                    React.createElement('input', {
                        type: 'date', value: form.contract_start,
                        onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.contract_start = e.target.value; return f; }); },
                        className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                    })
                ),
                React.createElement('div', null,
                    React.createElement('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, 'Contract End'),
                    React.createElement('input', {
                        type: 'date', value: form.contract_end,
                        onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.contract_end = e.target.value; return f; }); },
                        className: 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none'
                    })
                )
            ),
            React.createElement('div', null,
                React.createElement('label', { className: 'flex items-center gap-2 cursor-pointer' },
                    React.createElement('input', {
                        type: 'checkbox', checked: form.ndpa_agreement_signed,
                        onChange: function(e) { setForm(function(prev) { var f = Object.assign({}, prev); f.ndpa_agreement_signed = e.target.checked; return f; }); },
                        className: 'rounded border-gray-300 text-teal-600 focus:ring-teal-500'
                    }),
                    React.createElement('span', { className: 'text-sm font-medium text-gray-700' }, 'NDPA Data Processing Agreement Signed')
                )
            ),
            React.createElement('div', { className: 'flex items-center gap-3 pt-4' },
                React.createElement('button', {
                    type: 'submit', disabled: saving,
                    className: 'rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 transition-colors disabled:opacity-50'
                }, saving ? 'Saving...' : (isEdit ? 'Update Partnership' : 'Create Partnership')),
                React.createElement('button', {
                    type: 'button',
                    onClick: function() { navigate('/admin/partnerships'); },
                    className: 'rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors'
                }, 'Cancel')
            )
        ) : null
    );
}