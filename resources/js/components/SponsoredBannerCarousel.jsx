import React, { useEffect, useState, useRef, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../lib/api';

const VIEWS_KEY = 'healthintel_banner_views';
const INTERVAL_MS = 7000;

/**
 * Track how many times the user has viewed each banner slug.
 * Returns a Map of slug → view count.
 */
function loadViewCounts() {
  try {
    const raw = localStorage.getItem(VIEWS_KEY);
    return raw ? new Map(JSON.parse(raw)) : new Map();
  } catch {
    return new Map();
  }
}

function saveViewCounts(map) {
  try {
    localStorage.setItem(VIEWS_KEY, JSON.stringify([...map]));
  } catch {
    // quota exceeded — silently ignore
  }
}

/**
 * Determine whether the affiliate/referral card should be injected.
 * True when the user has seen every available banner at least 2 times
 * AND there is at least 1 banner.
 */
function shouldShowAffiliate(banners, viewCounts) {
  if (banners.length === 0) return false;
  return banners.every((b) => (viewCounts.get(b.slug) ?? 0) >= 2);
}

export default function SponsoredBannerCarousel() {
  const [banners, setBanners] = useState([]);
  const [current, setCurrent] = useState(0);
  const [loading, setLoading] = useState(true);
  const [paused, setPaused] = useState(false);
  const [showAffiliate, setShowAffiliate] = useState(false);
  const timerRef = useRef(null);
  const viewCountsRef = useRef(loadViewCounts());

  // Fetch banners
  useEffect(() => {
    api
      .get('/providers/sponsored-banners')
      .then((res) => {
        const list = res?.data?.banners || res?.banners || [];
        setBanners(list);
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  // Increment view count for the currently visible banner
  useEffect(() => {
    const banner = banners[current];
    if (!banner) return;
    const counts = viewCountsRef.current;
    const slug = banner.slug || banner.id?.toString() || 'unknown';
    counts.set(slug, (counts.get(slug) ?? 0) + 1);
    saveViewCounts(counts);

    // Re-evaluate affiliate card eligibility
    setShowAffiliate(shouldShowAffiliate(banners, counts));
  }, [banners, current]);

  // Build the effective slides array (banners + optional affiliate card)
  const slides = React.useMemo(() => {
    if (banners.length === 0) return [];
    const list = [...banners];
    if (showAffiliate) {
      list.push({ _isAffiliate: true });
    }
    return list;
  }, [banners, showAffiliate]);

  const totalSlides = slides.length;

  const next = useCallback(() => {
    if (totalSlides === 0) return;
    setCurrent((prev) => (prev + 1) % totalSlides);
  }, [totalSlides]);

  // Auto-advance
  useEffect(() => {
    if (totalSlides <= 1 || paused) {
      if (timerRef.current) clearInterval(timerRef.current);
      return;
    }
    timerRef.current = setInterval(next, INTERVAL_MS);
    return () => clearInterval(timerRef.current);
  }, [totalSlides, next, paused]);

  // Pause on touch for mobile
  const handleTouchStart = () => setPaused(true);
  const handleTouchEnd = () => setPaused(false);

  if (loading || banners.length === 0) return null;

  // ── Single slide (no affiliate) → static display, no dots ──
  if (totalSlides === 1 && !slides[0]._isAffiliate) {
    const banner = slides[0];
    return (
      <div
        className="relative overflow-hidden rounded-xl"
        style={{ background: 'linear-gradient(135deg, #0A4E43 0%, #0E6B5C 50%, #0A4E43 100%)' }}
      >
        <BannerSlide banner={banner} />
      </div>
    );
  }

  const currentSlide = slides[current];

  return (
    <div
      className="relative overflow-hidden rounded-xl"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
      style={{ background: 'linear-gradient(135deg, #0A4E43 0%, #0E6B5C 50%, #0A4E43 100%)' }}
    >
      {/* Slide */}
      {currentSlide._isAffiliate ? (
        <AffiliateSlide />
      ) : (
        <BannerSlide banner={currentSlide} />
      )}

    </div>
  );
}

/**
 * Sponsored provider banner slide.
 */
function BannerSlide({ banner }) {
  return (
    <Link to={'/providers/' + banner.slug} className="block p-4 sm:p-5 hover:no-underline">
      <div className="flex items-center gap-4">
        {banner.banner_url ? (
          <img
            src={banner.banner_url}
            alt={banner.name}
            className="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover flex-shrink-0 border-2 border-white/20"
            onError={(e) => {
              e.target.style.display = 'none';
              const sibling = e.target.nextElementSibling;
              if (sibling) sibling.style.display = 'flex';
            }}
          />
        ) : null}
        <div
          className={
            'w-16 h-16 sm:w-20 sm:h-20 rounded-xl flex items-center justify-center flex-shrink-0 bg-white/15 border-2 border-white/20 ' +
            (banner.banner_url ? 'hidden' : '')
          }
          style={{ display: banner.banner_url ? 'none' : 'flex' }}
        >
          <span className="text-3xl">&#9877;</span>
        </div>

        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <span className="text-[10px] font-extrabold uppercase tracking-widest bg-white/20 rounded-md px-2 py-0.5 text-white">
              {banner.partner_status === 'affiliate' ? 'Partner' : 'Sponsored'}
            </span>
            {banner.type && <span className="text-[10px] font-semibold uppercase text-white/60">{banner.type}</span>}
          </div>
          <p className="text-sm sm:text-base font-bold text-white leading-tight truncate">{banner.name}</p>
          <p className="text-xs text-white/70 mt-1 line-clamp-1">
            {[banner.specialty, banner.city, banner.state].filter(Boolean).join(' \u00b7 ') || 'Healthcare provider near you'}
          </p>
          {banner.distance_km != null && (
            <p className="text-xs text-white/50 mt-0.5">{banner.distance_km.toFixed(1)} km away</p>
          )}
        </div>

        <div className="flex-shrink-0 hidden sm:block">
          <span className="inline-block px-4 py-2 bg-white/15 hover:bg-white/25 rounded-lg text-sm font-semibold text-white transition-colors">
            View &rarr;
          </span>
        </div>
      </div>
    </Link>
  );
}

/**
 * Affiliate / Referral inline slide.
 * Shown after the user has seen every sponsor banner 2+ times.
 */
function AffiliateSlide() {
  return (
    <Link to="/referral" className="block p-4 sm:p-5 hover:no-underline">
      <div className="flex items-center gap-4">
        {/* Icon */}
        <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-xl flex items-center justify-center flex-shrink-0 bg-white/15 border-2 border-white/20">
          <span className="text-3xl">👥</span>
        </div>

        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <span className="text-[10px] font-extrabold uppercase tracking-widest bg-white/20 rounded-md px-2 py-0.5 text-white">
              Invite & Earn
            </span>
            <span className="text-[10px] font-semibold uppercase text-white/60">Affiliate</span>
          </div>
          <p className="text-sm sm:text-base font-bold text-white leading-tight truncate">
            Refer friends, earn credits
          </p>
          <p className="text-xs text-white/70 mt-1 line-clamp-1">
            Share your link and earn credits when friends join HealthIntel
          </p>
        </div>

        <div className="flex-shrink-0 hidden sm:block">
          <span className="inline-block px-4 py-2 bg-white/15 hover:bg-white/25 rounded-lg text-sm font-semibold text-white transition-colors">
            Invite &rarr;
          </span>
        </div>
      </div>
    </Link>
  );
}