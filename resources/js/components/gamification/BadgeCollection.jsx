import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

const BADGE_EMOJIS = {
    first_steps: '🦶', tracker: '📊', insight_seeker: '🔬', trend_watcher: '📈', referral_hero: '🦸',
};

export default function BadgeCollection() {
    const { data } = useQuery({
        queryKey: ['gamification'],
        queryFn: () => api.get('/gamification'),
        staleTime: 1000 * 60 * 5,
    });
    const badges = data?.data?.badges || [];

    if (!badges.length) return null;

    return (
        <div className="card p-4">
            <h3 className="text-sm font-bold text-neutral-700 mb-3">🏆 Badges</h3>
            <div className="flex flex-wrap gap-2">
                {badges.map((b) => (
                    <div key={b.key} className={`flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-medium border transition-all ${b.earned ? 'bg-teal-50 border-teal-300 text-teal-800' : 'bg-gray-50 border-gray-200 text-gray-400 opacity-60'}`} title={b.earned ? `Earned ${b.earned_at ? new Date(b.earned_at).toLocaleDateString() : ''}` : b.desc}>
                        <span className="text-sm">{b.emoji || BADGE_EMOJIS[b.key] || '🏅'}</span>
                        <span>{b.name}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}