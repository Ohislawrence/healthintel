import React, { useEffect, useState } from 'react';
import usePartnerAuthStore from '../../stores/partnerAuthStore';

export default function PartnerDashboard() {
  const { apiGet, provider } = usePartnerAuthStore();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadDashboard();
  }, []);

  const loadDashboard = async () => {
    try {
      setLoading(true);
      const data = await apiGet('/stats');
      setStats(data.data || data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-20">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm">
        {error}
      </div>
    );
  }

  const totalInterpretations = stats?.total_interpretations || 0;
  const thisMonthCount = stats?.this_month_count || 0;
  const completedCount = stats?.completed_count || 0;
  const totalCost = stats?.total_cost_naira || 0;
  const estimatedBill = stats?.estimated_bill || 0;
  const monthlyAllowance = stats?.monthly_allowance || 0;
  const ratePerReport = stats?.rate_per_report || 0;
  const recentInterpretations = stats?.recent_interpretations || [];

  // ROI + Delivery health state
  const [roi, setRoi] = useState(null);
  const [deliveryHealth, setDeliveryHealth] = useState(null);

  useEffect(() => {
    loadDashboard();
    apiGet('/roi').then(d => setRoi((d.data || d)?.roi)).catch(() => {});
    apiGet('/delivery-health').then(d => setDeliveryHealth((d.data || d)?.delivery_health)).catch(() => {});
  }, []);

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">Dashboard</h2>
        <p className="text-sm text-gray-500 mt-1">Welcome back, {provider?.name}</p>
      </div>

      {/* Stats cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl border border-gray-200 p-5">
          <p className="text-xs text-gray-500 uppercase tracking-wide">Total Interpretations</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{totalInterpretations}</p>
        </div>
        <div className="bg-white rounded-xl border border-gray-200 p-5">
          <p className="text-xs text-gray-500 uppercase tracking-wide">This Month</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{thisMonthCount}</p>
          {monthlyAllowance > 0 && (
            <p className="text-xs text-gray-400 mt-1">Allowance: {monthlyAllowance}</p>
          )}
        </div>
        <div className="bg-white rounded-xl border border-gray-200 p-5">
          <p className="text-xs text-gray-500 uppercase tracking-wide">Completed</p>
          <p className="text-2xl font-bold text-green-600 mt-1">{completedCount}</p>
        </div>
        <div className="bg-white rounded-xl border border-gray-200 p-5">
          <p className="text-xs text-gray-500 uppercase tracking-wide">Est. Monthly Bill</p>
          <p className="text-2xl font-bold text-amber-600 mt-1">N{estimatedBill.toLocaleString()}</p>
          {ratePerReport > 0 && (
            <p className="text-xs text-gray-400 mt-1">N{ratePerReport}/report</p>
          )}
        </div>
      </div>

      {/* Pricing summary */}
      <div className="bg-white rounded-xl border border-gray-200 p-5">
        <h3 className="text-sm font-semibold text-gray-700 mb-3">Plan Summary</h3>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
          <div>
            <span className="text-gray-500">Monthly Allowance</span>
            <p className="font-semibold">{monthlyAllowance} reports</p>
          </div>
          <div>
            <span className="text-gray-500">Rate per Report</span>
            <p className="font-semibold">N{ratePerReport}</p>
          </div>
          <div>
            <span className="text-gray-500">Used This Month</span>
            <p className="font-semibold">{thisMonthCount}</p>
          </div>
          <div>
            <span className="text-gray-500">Total Cost (Month)</span>
            <p className="font-semibold">N{totalCost.toLocaleString()}</p>
          </div>
        </div>
      </div>

      {/* ROI + Delivery Summary */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {roi && (
          <div className="bg-white rounded-xl border border-gray-200 p-5">
            <h3 className="text-sm font-semibold text-gray-700 mb-3">📊 ROI Snapshot ({roi.period_days}d)</h3>
            <div className="grid grid-cols-2 gap-2 text-xs">
              <div><span className="text-gray-400">Abnormal Rate:</span> <strong>{roi.abnormal_detection?.abnormal_rate ?? 0}%</strong></div>
              <div><span className="text-gray-400">Critical:</span> <strong className="text-red-600">{roi.abnormal_detection?.critical_count ?? 0}</strong></div>
              <div><span className="text-gray-400">Calls Prevented:</span> <strong>{roi.patient_communication?.estimated_calls_prevented ?? 0}</strong></div>
              <div><span className="text-gray-400">Re-test Rate:</span> <strong>{roi.patient_retention?.retest_rate ?? '0%'}</strong></div>
              <div><span className="text-gray-400">Avg TAT:</span> <strong>{Math.round(roi.efficiency?.avg_turnaround_minutes ?? 0)} min</strong></div>
            </div>
          </div>
        )}
        {deliveryHealth && (
          <div className="bg-white rounded-xl border border-gray-200 p-5">
            <h3 className="text-sm font-semibold text-gray-700 mb-3">📨 Delivery Health (This Month)</h3>
            <div className="grid grid-cols-2 gap-2 text-xs">
              <div><span className="text-gray-400">Delivery Rate:</span> <strong className="text-green-600">{deliveryHealth.this_month?.delivery_rate ?? '0%'}</strong></div>
              <div><span className="text-gray-400">Sent:</span> <strong>{deliveryHealth.this_month?.sent ?? 0}</strong></div>
              <div><span className="text-gray-400">Failed:</span> <strong className="text-red-600">{deliveryHealth.this_month?.failed ?? 0}</strong></div>
              <div><span className="text-gray-400">Pending Retry:</span> <strong className="text-amber-600">{deliveryHealth.this_month?.pending_retry ?? 0}</strong></div>
            </div>
          </div>
        )}
      </div>

      {/* Recent interpretations */}
      <div className="bg-white rounded-xl border border-gray-200">
        <div className="px-5 py-4 border-b border-gray-100">
          <h3 className="text-sm font-semibold text-gray-700">Recent Interpretations</h3>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-gray-400 uppercase">
                <th className="px-5 py-3">Patient</th>
                <th className="px-5 py-3">Test</th>
                <th className="px-5 py-3">Value</th>
                <th className="px-5 py-3">Status</th>
                <th className="px-5 py-3">Cost</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {recentInterpretations.map((i) => (
                <tr key={i.id} className="hover:bg-gray-50">
                  <td className="px-5 py-3 font-medium text-gray-900">
                    {i.patient_identifier || 'N/A'}
                  </td>
                  <td className="px-5 py-3 text-gray-600">{i.test_name || 'N/A'}</td>
                  <td className="px-5 py-3 text-gray-600">
                    {i.value} {i.unit || ''}
                  </td>
                  <td className="px-5 py-3">
                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                      i.status === 'completed' ? 'bg-green-50 text-green-600' :
                      i.status === 'pending' ? 'bg-yellow-50 text-yellow-600' :
                      'bg-gray-50 text-gray-500'
                    }`}>
                      {i.status}
                    </span>
                  </td>
                  <td className="px-5 py-3 text-gray-600">
                    N{((i.cost_to_partner || 0) / 100).toFixed(2)}
                  </td>
                </tr>
              ))}
              {recentInterpretations.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-5 py-8 text-center text-gray-400">
                    No interpretations yet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}