import React, { useEffect, useState, useRef, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../lib/api';

export default function SponsoredBannerCarousel() {
  const [banners, setBanners] = useState([]);
  const [current, setCurrent] = useState(0);
  const [loading, setLoading] = useState(true);
  const [paused, setPaused] = useState(false);
  const timerRef = useRef(null);

  useEffect(() => {
    api.get('/providers/sponsored-banners')
      .then(res => {
        setBanners(res?.data?.banners || res?.banners || []);
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  const next = useCallback(() => {
    if (banners.length === 0) return;
    setCurrent(prev => (prev + 1) % banners.length);
  }, [banners.length]);

  const prev = useCallback(() => {
    if (banners.length === 0) return;
    setCurrent(prev => (prev - 1 + banners.length) % banners.length);
  }, [banners.length]);

  useEffect(() => {
    if (banners.length <= 1 || paused) {
      if (timerRef.current) clearInterval(timerRef.current);
      return;
    }
    timerRef.current = setInterval(next, 4000);
    return () => clearInterval(timerRef.current);
  }, [banners.length, next, paused]);

  if (loading || banners.length === 0) return null;

  const banner = banners[current];

  return (
    <div
      className="relative overflow-hidden rounded-xl"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      style={{ background: 'linear-gradient(135deg, #0A4E43 0%, #0E6B5C 50%, #0A4E43 100%)' }}
    >
      {banners.length > 1 && (
        <div className="absolute top-3 left-1/2 -translate-x-1/2 z-10 flex gap-1.5">
          {banners.map((_, i) => (
            <button
              key={i}
              onClick={() => setCurrent(i)}
              className={'w-2 h-2 rounded-full transition-all duration-300 ' + (i === current ? 'bg-white w-6' : 'bg-white/40 hover:bg-white/70')}
              aria-label={'Banner ' + (i + 1)}
            />
          ))}
        </div>
      )}

      <Link
        to={'/providers/' + banner.slug}
        className="block p-4 sm:p-5 hover:no-underline"
      >
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
            className={'w-16 h-16 sm:w-20 sm:h-20 rounded-xl flex items-center justify-center flex-shrink-0 bg-white/15 border-2 border-white/20 ' + (banner.banner_url ? 'hidden' : '')}
            style={{ display: banner.banner_url ? 'none' : 'flex' }}
          >
            <span className="text-3xl">&#9877;</span>
          </div>

          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2 mb-1">
              <span className="text-[10px] font-extrabold uppercase tracking-widest bg-white/20 rounded-md px-2 py-0.5 text-white">Sponsored</span>
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
    </div>
  );
}
