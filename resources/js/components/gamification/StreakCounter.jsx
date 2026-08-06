import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function StreakCounter() {
    const { data } = useQuery({
        queryKey: ['gamification'],
        queryFn: () => api.get('/gamification'),
        staleTime: 1000 * 60 * 5,
    });
    const streaks = data?.data?.streaks || [];

    if (!streaks.length) return null;

    const daily = streaks.find(s => s.type === 'daily_tracker');
    const weekly = streaks.find(s => s.type === 'weekly_lab');

    return (
        <div className="card p-4">
            <h3 className="text-sm font-bold text-neutral-700 mb-3">🔥 Streaks</h3>
            <div className="space-y-3">
                {daily && (daily.current > 0 || daily.longest > 0) && (
                    <div className="flex items-center justify-between">
                        <div>
                            <span className="text-xs text-neutral-500">Daily tracking</span>
                            <p className="text-lg font-bold text-teal-700">{daily.current} day{daily.current !== 1 ? 's' : ''}</p>
                        </div>
                        <span className="text-xs text-neutral-400">Best: {daily.longest}</span>
                    </div>
                )}
                {weekly && (weekly.current > 0 || weekly.longest > 0) && (
                    <div className="flex items-center justify-between">
                        <div>
                            <span className="text-xs text-neutral-500">Lab checks</span>
                            <p className="text-lg font-bold text-teal-700">{weekly.current} week{weekly.current !== 1 ? 's' : ''}</p>
                        </div>
                        <span className="text-xs text-neutral-400">Best: {weekly.longest}</span>
                    </div>
                )}
                {(!daily || (daily.current === 0 && daily.longest === 0)) && (!weekly || (weekly.current === 0 && weekly.longest === 0)) && (
                    <p className="text-xs text-neutral-400">Start tracking to build streaks!</p>
                )}
            </div>
        </div>
    );
}