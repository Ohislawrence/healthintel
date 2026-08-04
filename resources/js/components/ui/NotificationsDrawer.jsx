import React, { useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';

function timeAgo(dateStr) {
    const now = new Date();
    const date = new Date(dateStr);
    const seconds = Math.floor((now - date) / 1000);
    if (seconds < 60) return 'Just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 7) return `${days}d ago`;
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

export default function NotificationsDrawer({ open, onClose }) {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data, isLoading } = useQuery({
        queryKey: ['user-notifications'],
        queryFn: () => api.get('/notifications', { params: { per_page: 30 } }),
        enabled: open,
        staleTime: 1000 * 30,
    });

    const notifications = data?.data?.data || [];

    const markAllRead = useMutation({
        mutationFn: () => api.post('/notifications/mark-all-read'),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['user-notifications'] });
            queryClient.invalidateQueries({ queryKey: ['notifications-unread-count'] });
        },
    });

    const markRead = useMutation({
        mutationFn: (id) => api.post(`/notifications/${id}/read`),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['user-notifications'] });
            queryClient.invalidateQueries({ queryKey: ['notifications-unread-count'] });
        },
    });

    const handleClick = useCallback((notification) => {
        if (!notification.is_read) {
            markRead.mutate(notification.id);
        }
        if (notification.action_url) {
            onClose();
            navigate(notification.action_url);
        }
    }, [markRead, navigate, onClose]);

    // Close on Escape
    useEffect(() => {
        if (!open) return;
        const handler = (e) => {
            if (e.key === 'Escape') onClose();
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [open, onClose]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex justify-end">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/20 backdrop-blur-sm"
                onClick={onClose}
            />

            {/* Drawer panel */}
            <div className="relative w-full max-w-sm bg-white shadow-2xl h-full overflow-y-auto animate-slide-in-right">
                {/* Header */}
                <div className="sticky top-0 bg-white/95 backdrop-blur border-b border-neutral-100 px-4 py-3 flex items-center justify-between z-10">
                    <h3 className="text-base font-bold text-neutral-900">Notifications</h3>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => markAllRead.mutate()}
                            className="text-xs font-semibold text-teal-600 hover:text-teal-700"
                            disabled={notifications.every(n => n.is_read)}
                        >
                            Mark all read
                        </button>
                        <button
                            onClick={onClose}
                            className="text-neutral-400 hover:text-neutral-600 p-1"
                            aria-label="Close"
                        >
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Content */}
                <div className="p-4">
                    {isLoading && (
                        <div className="flex justify-center py-12">
                            <div className="h-6 w-6 animate-spin rounded-full border-2 border-teal-500 border-t-transparent" />
                        </div>
                    )}

                    {!isLoading && notifications.length === 0 && (
                        <div className="text-center py-12">
                            <div className="w-16 h-16 rounded-2xl bg-neutral-100 flex items-center justify-center text-2xl mx-auto mb-3">
                                🔔
                            </div>
                            <p className="text-sm font-bold text-neutral-900 mb-1">No notifications</p>
                            <p className="text-xs text-neutral-500">You're all caught up!</p>
                        </div>
                    )}

                    {!isLoading && notifications.map((n) => (
                        <button
                            key={n.id}
                            onClick={() => handleClick(n)}
                            className={`w-full flex items-start gap-3 p-3 rounded-xl text-left transition-colors mb-1 ${
                                n.is_read ? 'hover:bg-neutral-50' : 'bg-teal-50/50 hover:bg-teal-50'
                            }`}
                        >
                            {/* Unread dot */}
                            <div className="mt-1.5 shrink-0">
                                {!n.is_read && (
                                    <span className="block w-2 h-2 rounded-full bg-teal-500" />
                                )}
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className={`text-sm font-semibold ${n.is_read ? 'text-neutral-700' : 'text-neutral-900'}`}>
                                    {n.title}
                                </p>
                                {n.body && (
                                    <p className="text-xs text-neutral-500 mt-0.5 line-clamp-2">{n.body}</p>
                                )}
                                <p className="text-[10px] text-neutral-400 font-medium mt-1">
                                    {timeAgo(n.created_at)}
                                </p>
                            </div>
                            {n.action_url && (
                                <svg className="w-4 h-4 text-neutral-300 mt-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            )}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}