import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';

const RATING_LABELS = { 1: 'Poor', 2: 'Fair', 3: 'Good', 4: 'Very Good', 5: 'Excellent' };

export default function ProviderDetail() {
    const { slug } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data, isLoading } = useQuery({
        queryKey: ['provider', slug],
        queryFn: () => api.get(`/providers/${slug}`),
    });
    const provider = data?.data?.provider || {};

    const { data: reviewsData } = useQuery({
        queryKey: ['provider-reviews', slug],
        queryFn: () => api.get(`/providers/${slug}/reviews`),
    });
    const ratingAvg = reviewsData?.data?.rating_avg ?? 0;
    const reviewCount = reviewsData?.data?.review_count ?? 0;
    const reviews = reviewsData?.data?.reviews || [];

    const clickOutMutation = useMutation({
        mutationFn: (action) => api.post(`/providers/${slug}/click-out`, { action }),
    });

    const trackClick = (action) => {
        clickOutMutation.mutate(action, { onError: () => {} });
    };

    // ── Favorite toggle ──
    const [isFavorited, setIsFavorited] = useState(false);
    const { data: favoriteData } = useQuery({
        queryKey: ['provider-favorite', slug],
        queryFn: () => api.get(`/providers/${slug}/favorite`),
        retry: false,
        onError: () => {},
    });
    const favStatus = favoriteData?.data?.is_favorited ?? false;

    const favoriteMutation = useMutation({
        mutationFn: () => api.post(`/providers/${slug}/favorite`),
        onSuccess: (res) => {
            setIsFavorited(res?.data?.is_favorited ?? !favStatus);
            queryClient.invalidateQueries(['provider-favorite', slug]);
            queryClient.invalidateQueries(['provider-favorites']);
        },
        onError: () => {},
    });

    const toggleFavorite = () => favoriteMutation.mutate();

    // ── Share ──
    const [shareMessage, setShareMessage] = useState('');
    const shareProvider = async () => {
        const url = window.location.href;
        const text = `${provider.name} — ${provider.type || 'Healthcare Provider'}`;
        if (navigator.share) {
            try {
                await navigator.share({ title: provider.name, text, url });
            } catch {}
        } else if (navigator.clipboard) {
            try {
                await navigator.clipboard.writeText(url);
                setShareMessage('Link copied!');
                setTimeout(() => setShareMessage(''), 2000);
            } catch {}
        }
    };

    // ── Appointment booking ──
    const [showBooking, setShowBooking] = useState(false);
    const [booking, setBooking] = useState({ appointment_date: '', appointment_time: '', notes: '' });
    const [bookingError, setBookingError] = useState('');
    const [bookingSuccess, setBookingSuccess] = useState('');

    const bookMutation = useMutation({
        mutationFn: (payload) => api.post('/appointments', payload),
        onSuccess: () => {
            queryClient.invalidateQueries(['appointments']);
            setBookingSuccess('Appointment booked — check your tracker.');
            setShowBooking(false);
            setBooking({ appointment_date: '', appointment_time: '', notes: '' });
        },
        onError: (err) => setBookingError(err?.message || 'Failed to book appointment'),
    });

    const submitBooking = () => {
        if (!booking.appointment_date) { setBookingError('Please pick a date.'); return; }
        setBookingError('');
        setBookingSuccess('');
        bookMutation.mutate({
            title: `Visit to ${provider.name}`,
            appointment_date: booking.appointment_date,
            appointment_time: booking.appointment_time || undefined,
            notes: booking.notes.trim() || undefined,
            provider_id: provider.id,
            reminder_enabled: true,
            reminder_minutes_before: 30,
        });
    };

    // ── Review submission ──
    const [review, setReview] = useState({ rating: 5, title: '', body: '' });
    const [reviewError, setReviewError] = useState('');
    const reviewMutation = useMutation({
        mutationFn: (payload) => api.post(`/providers/${slug}/reviews`, payload),
        onSuccess: () => {
            queryClient.invalidateQueries(['provider-reviews', slug]);
            setReview({ rating: 5, title: '', body: '' });
        },
        onError: (err) => setReviewError(err?.message || 'Failed to submit review'),
    });

    const submitReview = () => {
        if (!review.body.trim()) { setReviewError('Please write a short review.'); return; }
        setReviewError('');
        reviewMutation.mutate({ rating: review.rating, title: review.title.trim() || undefined, body: review.body.trim() });
    };

    // ── WhatsApp ──
    const getWhatsAppUrl = () => {
        const raw = (provider.whatsapp || provider.phone || '').replace(/[^0-9]/g, '');
        if (!raw) return null;
        // Convert leading 0 to Nigerian international format
        const intl = raw.startsWith('0') ? '234' + raw.slice(1) : raw;
        return `https://wa.me/${intl}`;
    };

    // ── Directions ──
    const getDirectionsUrl = () => {
        const loc = provider.locations?.find((l) => l.latitude && l.longitude);
        const lat = provider.latitude ?? loc?.latitude;
        const lng = provider.longitude ?? loc?.longitude;
        if (lat && lng) {
            return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
        }
        const query = [provider.address, provider.city, provider.state, provider.country].filter(Boolean).join(' ');
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
    };

    if (isLoading) {
        return (
            <div className="space-y-4 max-w-lg mx-auto">
                <div className="skeleton h-8 w-48 rounded" />
                <div className="skeleton h-64 w-full rounded-xl" />
            </div>
        );
    }

    const locationLabel = [provider.city, provider.state, provider.country]
        .filter(Boolean)
        .join(', ');

    return (
        <div className="space-y-5 max-w-lg mx-auto">
            <button onClick={() => navigate('/directory')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to Directory</button>

            {/* Banner */}
            {provider.banner_url && (
                <div className="rounded-2xl overflow-hidden border border-neutral-100 bg-white">
                    <img
                        src={provider.banner_url}
                        alt={`${provider.name} banner`}
                        className="w-full h-32 sm:h-40 object-cover"
                        onError={(e) => { e.currentTarget.style.display = 'none'; }}
                    />
                </div>
            )}

            <div>
                <div className="flex items-center gap-3 mb-3">
                    {provider.logo_url ? (
                        <img src={provider.logo_url} alt={provider.name} className="w-14 h-14 rounded-2xl object-contain bg-white border border-neutral-100" onError={(e) => { e.currentTarget.style.display = 'none'; }} />
                    ) : (
                        <span className="w-14 h-14 rounded-2xl bg-teal-50 flex items-center justify-center text-2xl text-teal-600">⚕</span>
                    )}
                    <div>
                        <p className="text-xl font-extrabold text-neutral-900 tracking-tight">{provider.name}</p>
                        <p className="text-sm text-neutral-500 capitalize">{provider.type || 'Healthcare Provider'}</p>
                    </div>
                </div>
                {(provider.is_sponsored || provider.is_verified || ratingAvg > 0) && (
                    <div className="flex items-center gap-2 mb-1 flex-wrap">
                        {provider.is_sponsored && (
                            <span className="badge badge-warning text-[10px]">Sponsored Listing</span>
                        )}
                        {provider.is_verified && (
                            <span className="badge badge-success text-[10px]">✓ Verified</span>
                        )}
                        {ratingAvg > 0 && (
                            <span className="badge badge-warning text-[10px]">★ {ratingAvg} ({reviewCount})</span>
                        )}
                        {provider.is_open_now === true && (
                            <span className="badge badge-success text-[10px]">Open now</span>
                        )}
                        {provider.is_open_now === false && (
                            <span className="badge bg-neutral-200 text-neutral-600 text-[10px]">Closed</span>
                        )}
                    </div>
                )}
            </div>

            {/* Details */}
            <div className="card p-4 space-y-3">
                {provider.specialty && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Specialty</span>
                        <span className="text-sm font-semibold text-neutral-900">{provider.specialty}</span>
                    </div>
                )}
                {provider.address && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Address</span>
                        <span className="text-sm font-semibold text-neutral-900">{provider.address}</span>
                    </div>
                )}
                {locationLabel && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Location</span>
                        <span className="text-sm font-semibold text-neutral-900">{locationLabel}</span>
                    </div>
                )}
                {provider.phone && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Phone</span>
                        <a href={`tel:${provider.phone}`} onClick={() => trackClick('call')} className="text-sm font-semibold text-teal-700 hover:text-teal-800">{provider.phone}</a>
                    </div>
                )}
                {provider.email && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Email</span>
                        <a href={`mailto:${provider.email}`} className="text-sm font-semibold text-teal-700 hover:text-teal-800">{provider.email}</a>
                    </div>
                )}
                {provider.website && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Website</span>
                        <a href={provider.website} target="_blank" rel="noopener noreferrer" onClick={() => trackClick('website')} className="text-sm font-semibold text-teal-700 hover:text-teal-800">
                            Visit Site ↗
                        </a>
                    </div>
                )}
            </div>

            {/* Quick actions */}
            <div className="grid grid-cols-2 gap-3">
                {provider.phone && (
                    <a
                        href={`tel:${provider.phone}`}
                        onClick={() => trackClick('call')}
                        className="rounded-xl bg-teal-600 text-white text-sm font-semibold py-3 text-center hover:bg-teal-700 transition-colors"
                    >
                        📞 Call Now
                    </a>
                )}
                <a
                    href={getDirectionsUrl()}
                    target="_blank"
                    rel="noopener noreferrer"
                    onClick={() => trackClick('directions')}
                    className="rounded-xl bg-white border border-teal-600 text-teal-700 text-sm font-semibold py-3 text-center hover:bg-teal-50 transition-colors"
                >
                    🗺 Directions
                </a>
                {provider.website && (
                    <a
                        href={provider.website}
                        target="_blank"
                        rel="noopener noreferrer"
                        onClick={() => trackClick('website')}
                        className="rounded-xl bg-white border border-teal-600 text-teal-700 text-sm font-semibold py-3 text-center hover:bg-teal-50 transition-colors col-span-2"
                    >
                        🌐 Visit Website
                    </a>
                )}
                {getWhatsAppUrl() && (
                    <a
                        href={getWhatsAppUrl()}
                        target="_blank"
                        rel="noopener noreferrer"
                        onClick={() => trackClick('call')}
                        className="rounded-xl bg-green-600 text-white text-sm font-semibold py-3 text-center hover:bg-green-700 transition-colors"
                    >
                        💬 WhatsApp
                    </a>
                )}
                <button
                    onClick={toggleFavorite}
                    disabled={favoriteMutation.isPending}
                    className={`rounded-xl text-sm font-semibold py-3 text-center transition-colors col-span-2 ${favStatus || isFavorited ? 'bg-amber-500 text-white hover:bg-amber-600' : 'bg-white border border-amber-500 text-amber-600 hover:bg-amber-50'}`}
                >
                    {favStatus || isFavorited ? '★ Saved' : '☆ Save Provider'}
                </button>
                <button
                    onClick={shareProvider}
                    className="rounded-xl bg-white border border-neutral-300 text-neutral-700 text-sm font-semibold py-3 text-center hover:bg-neutral-50 transition-colors col-span-2"
                >
                    {shareMessage || '🔗 Share'}
                </button>
                <button
                    onClick={() => setShowBooking(!showBooking)}
                    className="rounded-xl bg-indigo-600 text-white text-sm font-semibold py-3 text-center hover:bg-indigo-700 transition-colors col-span-2"
                >
                    📅 Book Appointment
                </button>
            </div>

            {/* Booking form */}
            {showBooking && (
                <div className="card p-4 space-y-3">
                    <p className="text-sm font-bold text-neutral-900">Book an appointment</p>
                    {bookingError && <p className="text-xs text-red-600 font-semibold">{bookingError}</p>}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Date *</p>
                            <input type="date" value={booking.appointment_date} onChange={(e) => setBooking({ ...booking, appointment_date: e.target.value })} className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-3 py-2.5 text-sm font-semibold outline-none" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Time</p>
                            <input type="time" value={booking.appointment_time} onChange={(e) => setBooking({ ...booking, appointment_time: e.target.value })} className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-3 py-2.5 text-sm font-semibold outline-none" />
                        </div>
                    </div>
                    <div>
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Notes</p>
                        <input type="text" value={booking.notes} onChange={(e) => setBooking({ ...booking, notes: e.target.value })} placeholder="Reason for visit, doctor's name..." className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-3 py-2.5 text-sm outline-none" />
                    </div>
                    <button onClick={submitBooking} disabled={bookMutation.isPending} className="btn w-full bg-indigo-600 hover:bg-indigo-700 text-white">
                        {bookMutation.isPending ? 'Booking…' : 'Confirm Booking'}
                    </button>
                </div>
            )}

            {/* Locations */}
            {provider.locations?.length > 0 && (
                <div className="card p-4 space-y-3">
                    <div className="flex items-center justify-between">
                        <p className="text-sm font-bold text-neutral-900">Locations</p>
                        <span className="text-xs text-neutral-400">{provider.locations.length} branch{provider.locations.length > 1 ? 'es' : ''}</span>
                    </div>
                    {provider.locations.map((loc, i) => (
                        <div key={loc.id ?? i} className={`rounded-xl p-3 ${loc.is_primary ? 'bg-teal-50 border border-teal-200' : 'bg-neutral-50 border border-neutral-100'}`}>
                            <div className="flex items-center gap-2 mb-1">
                                <p className="text-sm font-bold text-neutral-900">{loc.name || `Branch ${i + 1}`}</p>
                                {loc.is_primary && <span className="badge badge-success text-[9px]">Primary</span>}
                            </div>
                            <p className="text-xs text-neutral-500">
                                {[loc.address, loc.city, loc.state, loc.country].filter(Boolean).join(', ')}
                            </p>
                            {loc.phone && (
                                <a
                                    href={`tel:${loc.phone}`}
                                    onClick={() => trackClick('call')}
                                    className="text-xs font-semibold text-teal-700 hover:text-teal-800 mt-1 inline-block"
                                >
                                    {loc.phone}
                                </a>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* Opening hours */}
            {provider.opening_hours && Object.keys(provider.opening_hours).length > 0 && (
                <div className="card p-4">
                    <p className="text-sm font-bold text-neutral-900 mb-2">Opening Hours</p>
                    <div className="space-y-1">
                        {['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'].map((d) => {
                            const slot = provider.opening_hours[d];
                            const label = d.charAt(0).toUpperCase() + d.slice(1);
                            const text = slot && slot.open && slot.close ? `${slot.open} – ${slot.close}` : 'Closed';
                            return (
                                <div key={d} className="flex items-center justify-between text-sm">
                                    <span className="text-neutral-500">{label}</span>
                                    <span className={`font-semibold ${text === 'Closed' ? 'text-neutral-400' : 'text-neutral-900'}`}>{text}</span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Services / tests offered */}
            {provider.services?.length > 0 && (
                <div className="card p-4">
                    <p className="text-sm font-bold text-neutral-900 mb-2">Services & Tests</p>
                    <div className="flex flex-wrap gap-2">
                        {provider.services.map((s, i) => (
                            <span key={i} className="badge bg-teal-50 text-teal-700">
                                {typeof s === 'string' ? s : (s.name || s.label || JSON.stringify(s))}
                            </span>
                        ))}
                    </div>
                </div>
            )}

            {/* Gallery */}
            {provider.gallery?.length > 0 && (
                <div className="card p-4">
                    <p className="text-sm font-bold text-neutral-900 mb-2">Gallery</p>
                    <div className="grid grid-cols-3 gap-2">
                        {provider.gallery.map((img, i) => (
                            <img
                                key={i}
                                src={typeof img === 'string' ? img : (img.url || img.src)}
                                alt={`${provider.name} photo ${i + 1}`}
                                className="w-full h-20 object-cover rounded-lg border border-neutral-100"
                                onError={(e) => { e.currentTarget.style.display = 'none'; }}
                            />
                        ))}
                    </div>
                </div>
            )}

            {provider.bio && (
                <div className="card p-4">
                    <p className="text-sm font-bold text-neutral-900 mb-2">About</p>
                    <p className="text-sm text-neutral-700 leading-relaxed">{provider.bio}</p>
                </div>
            )}

            {provider.insurance_plans?.length > 0 && (
                <div className="card p-4">
                    <p className="text-sm font-bold text-neutral-900 mb-2">Insurances Accepted</p>
                    <div className="flex flex-wrap gap-2">
                        {provider.insurance_plans.map((ins, i) => (
                            <span key={i} className="badge badge-success">{typeof ins === 'string' ? ins : (ins.name || ins.plan || JSON.stringify(ins))}</span>
                        ))}
                    </div>
                </div>
            )}

            {/* Reviews */}
            <div className="card p-4 space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-bold text-neutral-900">Reviews</p>
                        <p className="text-xs text-neutral-500 mt-0.5">
                            {reviewCount > 0 ? `${ratingAvg} average from ${reviewCount} review${reviewCount > 1 ? 's' : ''}` : 'No reviews yet'}
                        </p>
                    </div>
                </div>

                {/* Review form */}
                <div className="border-t border-neutral-100 pt-3 space-y-3">
                    <p className="text-xs font-bold text-neutral-500 uppercase tracking-wider">Write a review</p>
                    {reviewError && <p className="text-xs text-red-600 font-semibold">{reviewError}</p>}
                    <div className="flex items-center gap-1">
                        {[1, 2, 3, 4, 5].map((star) => (
                            <button key={star} type="button" onClick={() => setReview({ ...review, rating: star })} className={`text-xl ${star <= review.rating ? 'text-amber-400' : 'text-neutral-300'}`}>★</button>
                        ))}
                        <span className="text-xs text-neutral-500 ml-2">{RATING_LABELS[review.rating]}</span>
                    </div>
                    <input type="text" value={review.title} onChange={(e) => setReview({ ...review, title: e.target.value })} placeholder="Title (optional)" className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-3 py-2.5 text-sm outline-none" />
                    <textarea value={review.body} onChange={(e) => setReview({ ...review, body: e.target.value })} rows={3} placeholder="Share your experience..." className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-3 py-2.5 text-sm outline-none resize-none" />
                    <button onClick={submitReview} disabled={reviewMutation.isPending} className="btn w-full bg-teal-600 hover:bg-teal-700 text-white text-sm">
                        {reviewMutation.isPending ? 'Submitting…' : 'Submit Review'}
                    </button>
                </div>

                {/* Review list */}
                {reviews.length > 0 && (
                    <div className="space-y-3">
                        {reviews.map((r) => (
                            <div key={r.id} className="rounded-xl bg-neutral-50 border border-neutral-100 p-3">
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-xs font-bold text-neutral-900">{r.user?.name || 'Anonymous'}</span>
                                    <span className="text-xs text-amber-500">{'★'.repeat(r.rating)}</span>
                                </div>
                                {r.title && <p className="text-sm font-semibold text-neutral-800">{r.title}</p>}
                                <p className="text-sm text-neutral-600">{r.body}</p>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}