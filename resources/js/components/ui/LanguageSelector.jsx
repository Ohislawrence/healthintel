import React from 'react';
import api from '../../lib/api';

const FLAG_MAP = { en: '🇬🇧', pcm: '🇳🇬', yo: '🇳🇬', ha: '🇳🇬', ig: '🇳🇬' };
let cachedLangs = null;
async function fetchLanguages() {
    if (cachedLangs) return cachedLangs;
    try { const r = await api.get('/languages'); cachedLangs = r; return r; } catch { return { en: { label: 'English', native: 'English' } }; }
}

export default function LanguageSelector({ value = 'en', onChange }) {
    const [langs, setLangs] = React.useState(null);
    React.useEffect(() => { fetchLanguages().then(setLangs); }, []);

    if (!langs) return null;
    const entries = Object.entries(langs);

    return (
        <div className="flex items-center gap-1.5">
            <span className="text-xs text-neutral-400">🌐</span>
            <select
                value={value}
                onChange={e => onChange?.(e.target.value)}
                className="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-neutral-700 focus:outline-none focus:border-teal-300"
            >
                {entries.map(([code, info]) => (
                    <option key={code} value={code}>{FLAG_MAP[code] || '🌐'} {info.native || info.label}</option>
                ))}
            </select>
        </div>
    );
}