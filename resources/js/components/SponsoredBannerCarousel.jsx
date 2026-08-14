import React, { useEffect, useState, useRef, useCallback, useMemo } from 'react';
import { Link } from 'react-router-dom';
import api from '../lib/api';

const VIEWS_KEY = 'healthintel_banner_views';

// Display timing. Sponsored listings get more on-screen time than partners.
const PARTNER_INTERVAL_MS = 7000;
const SPONSORED_INTERVAL_MS = 12000;

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
        // Sponsored providers first, then affiliate partners.
        const sorted = [...list].sort((a, b) => {
          const order = (p) => (p.partner_status === 'sponsored' ? 0 : 1);
          return order(a) - order(b);
        });
        setBanners(sorted);
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  // Increment view count for the currently visible banner
  useEffect(() => {
    const banner = banners[current];
    if (!banner || banner._isAffiliate) return;
    const counts = viewCountsRef.current;
    const slug = banner.slug || banner.id?.toString() || 'unknown';
    counts.set(slug, (counts.get(slug) ?? 0) + 1);
    saveViewCounts(counts);

    // Re-evaluate affiliate card eligibility
    setShowAffiliate(shouldShowAffiliate(banners, counts));
  }, [banners, current]);

  // Build the effective slides array (banners + optional affiliate card)
  const slides = useMemo(() => {
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

  const goTo = (index) => {
    if (index >= 0 && index < totalSlides) setCurrent(index);
  };

  // Auto-advance with per-slide timing (sponsored get more display time)
  useEffect(() => {
    if (totalSlides <= 1 || paused) {
      if (timerRef.current) clearInterval(timerRef.current);
      return;
    }

    const slide = slides[current];
    const duration = slide?._isAffiliate || slide?.partner_status !== 'sponsored'
      ? PARTNER_INTERVAL_MS
      : SPONSORED_INTERVAL_MS;

    if (timerRef.current) clearInterval(timerRef.current);
    timerRef.current = setInterval(next, duration);
    return () => clearInterval(timerRef.current);
  }, [totalSlides, next, paused, current, slides]);

  // Pause on touch for mobile
  const handleTouchStart = () => setPaused(true);
  const handleTouchEnd = () => setPaused(false);

  if (loading || banners.length === 0) return null;

  // ── Single slide (no affiliate) → static display, no dots ──
  if (totalSlides === 1 && !slides[0]._isAffiliate) {
    const banner = slides[0];
    return <BannerCard banner={banner} />;
  }

  const currentSlide = slides[current];

  return (
    <div
      className="relative overflow-hidden rounded-xl"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
    >
      {/* Slide */}
      {currentSlide._isAffiliate ? (
        <AffiliateSlide />
      ) : (
        <BannerCard banner={currentSlide} />
      )}

      {/* Navigation dots */}
      {totalSlides > 1 && (
        <div className="absolute bottom-2 left-0 right-0 flex items-center justify-center gap-1.5 z-10">
          {slides.map((slide, i) => (
            <button
              key={i}
              onClick={() => goTo(i)}
              aria-label={`Go to slide ${i + 1}`}
              className={`h-1.5 rounded-full transition-all ${
                i === current ? 'w-5 bg-white' : 'w-1.5 bg-white/50 hover:bg-white/75'
              }`}
            />
          ))}
        </div>
      )}
    </div>
  );
}

/**
 * A sponsored/partner provider card. Uses the provider banner as the full
 * background image and the provider logo in the dedicated logo slot.
 */
function BannerCard({ banner }) {
  const hasBanner = Boolean(banner.banner_url);
  const hasLogo = Boolean(banner.logo_url);

  return (
    <Link to={'/providers/' + banner.slug} className="block relative hover:no-underline">
      {/* Banner background */}
      {hasBanner ? (
        <img
          src={banner.banner_url}
          alt=""
          aria-hidden="true"
          className="absolute inset-0 w-full h-full object-cover"
          onError={(e) => {
            e.currentTarget.style.display = 'none';
          }}
        />
      ) : null}

      {/* Dark overlay for legibility */}
      <div className="absolute inset-0 bg-gradient-to-r from-black/75 via-black/45 to-black/25" />

      {/* Content above the overlay */}
      <div className="relative p-4 sm:p-5">
        <div className="flex items-center gap-4">
          {/* Logo area */}
          {hasLogo ? (
            <img
              src={banner.logo_url}
              alt={banner.name}
              className="w-14 h-14 sm:w-16 sm:h-16 rounded-xl object-contain bg-white border-2 border-white/60 flex-shrink-0 shadow-sm"
              onError={(e) => {
                e.currentTarget.style.display = 'none';
                const sibling = e.currentTarget.nextElementSibling;
                if (sibling) sibling.style.display = 'flex';
              }}
            />
          ) : null}
          <div
            className="w-14 h-14 sm:w-16 sm:h-16 rounded-xl items-center justify-center flex-shrink-0 bg-white/15 border-2 border-white/40"
            style={{ display: hasLogo ? 'none' : 'flex' }}
          >
            <span className="text-2xl sm:text-3xl">⚕</span>
          </div>

          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2 mb-1">
              <span className="text-[10px] font-extrabold uppercase tracking-widest bg-white/25 rounded-md px-2 py-0.5 text-white backdrop-blur-sm">
                {banner.partner_status === 'affiliate' ? 'Partner' : 'Sponsored'}
              </span>
              {banner.type && <span className="text-[10px] font-semibold uppercase text-white/80">{banner.type}</span>}
            </div>
            <p className="text-sm sm:text-lg font-extrabold text-white leading-tight truncate drop-shadow">{banner.name}</p>
            <p className="text-xs text-white/85 mt-1 line-clamp-1 drop-shadow">
              {[banner.specialty, banner.city, banner.state].filter(Boolean).join(' \u00b7 ') || 'Healthcare provider near you'}
            </p>
            {banner.distance_km != null && (
              <p className="text-xs text-white/70 mt-0.5">{banner.distance_km.toFixed(1)} km away</p>
            )}
          </div>

          <div className="flex-shrink-0 hidden sm:block">
            <span className="inline-block px-4 py-2 bg-white/20 hover:bg-white/35 backdrop-blur-sm rounded-lg text-sm font-semibold text-white transition-colors">
              View &rarr;
            </span>
          </div>
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
    <div className="relative">
      <div className="absolute inset-0 bg-gradient-to-r from-[#0A4E43] via-[#0E6B5C] to-[#0A4E43]" />
      <Link to="/referral" className="relative block p-4 sm:p-5 hover:no-underline">
        <div className="flex items-center gap-4">
          {/* Icon */}
          <div className="w-14 h-14 sm:w-16 sm:h-16 rounded-xl flex items-center justify-center flex-shrink-0 bg-white/15 border-2 border-white/40">
            <span className="text-2xl sm:text-3xl">👥</span>
          </div>

          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2 mb-1">
              <span className="text-[10px] font-extrabold uppercase tracking-widest bg-white/25 rounded-md px-2 py-0.5 text-white backdrop-blur-sm">
                Invite & Earn
              </span>
              <span className="text-[10px] font-semibold uppercase text-white/80">Affiliate</span>
            </div>
            <p className="text-sm sm:text-base font-bold text-white leading-tight truncate">
              Refer friends, earn credits
            </p>
            <p className="text-xs text-white/85 mt-1 line-clamp-1">
              Share your link and earn credits when friends join HealthIntel
            </p>
          </div>

          <div className="flex-shrink-0 hidden sm:block">
            <span className="inline-block px-4 py-2 bg-white/20 hover:bg-white/35 backdrop-blur-sm rounded-lg text-sm font-semibold text-white transition-colors">
              Invite &rarr;
            </span>
          </div>
        </div>
      </Link>
    </div>
  );
}