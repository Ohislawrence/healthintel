import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import usePartnerAuthStore from '../../stores/partnerAuthStore';

export default function PartnerLogin() {
  const [accessCode, setAccessCode] = useState('');
  const { login, loading, error, clearError } = usePartnerAuthStore();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    clearError();
    if (!accessCode.trim()) return;
    const ok = await login(accessCode.trim());
    if (ok) {
      navigate('/partner/dashboard', { replace: true });
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="flex justify-center mb-4">
            <span className="w-12 h-12 rounded-xl bg-teal-600 flex items-center justify-center">
              <span className="w-4 h-4 rounded-sm bg-white" />
            </span>
          </div>
          <h1 className="text-2xl font-bold text-gray-900">Partner Portal</h1>
          <p className="mt-2 text-sm text-gray-500">
            Enter your access code to view your dashboard
          </p>
        </div>

        <form onSubmit={handleSubmit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
              {error}
            </div>
          )}

          <div>
            <label htmlFor="accessCode" className="block text-sm font-medium text-gray-700 mb-1">
              Access Code
            </label>
            <input
              id="accessCode"
              type="text"
              value={accessCode}
              onChange={(e) => setAccessCode(e.target.value)}
              placeholder="Enter your access code"
              className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition"
              required
              autoFocus
            />
          </div>

          <button
            type="submit"
            disabled={loading || !accessCode.trim()}
            className="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {loading ? (
              <span className="flex items-center justify-center gap-2">
                <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                Verifying...
              </span>
            ) : (
              'Sign in'
            )}
          </button>

          <p className="text-xs text-gray-400 text-center">
            If you don't have an access code, contact{' '}
            <a href="mailto:partnerships@healthintel.app" className="text-teal-600 hover:underline">
              partnerships@healthintel.app
            </a>
          </p>
        </form>
      </div>
    </div>
  );
}