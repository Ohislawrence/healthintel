<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HealthIntel — Understand your lab results, in plain language')</title>
    <meta name="description" content="@yield('description', 'Your lab results explained by AI, checked against real reference ranges. Check symptoms, find doctors, and compare insurance — all in one place.')">
    <link rel="canonical" href="{{ url()->current() }}">
    
    <!-- Open Graph / Twitter -->
    <meta property="og:title" content="@yield('title', 'HealthIntel — Understand your lab results, in plain language')">
    <meta property="og:description" content="@yield('description', 'Your lab results explained by AI, checked against real reference ranges.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="HealthIntel">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'HealthIntel — Understand your lab results, in plain language')">
    <meta name="twitter:description" content="@yield('description')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    
    @yield('structured_data')
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --ink: #10201B;
            --paper: #F4F6F3;
            --paper-raised: #FFFFFF;
            --primary: #0E6B5C;
            --primary-deep: #0A4E43;
            --primary-light: rgba(14,107,92,0.08);
            --primary-glow: rgba(14,107,92,0.15);
            --amber: #B9812E;
            --amber-soft: #F4E9D6;
            --brick: #A8432F;
            --line: #DCE3DE;
            --line-light: #E8EBE7;
            --text: #1B2622;
            --text-muted: #57645D;
            
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-pill: 999px;
            
            /* Layered, organic shadows for a premium feel */
            --shadow-card: 0 1px 2px rgba(16,32,27,0.04), 0 4px 12px rgba(16,32,27,0.03);
            --shadow-elevated: 0 12px 24px -8px rgba(14,107,92,0.08), 0 20px 40px -12px rgba(16,32,27,0.1);
            --shadow-button: 0 1px 2px rgba(14,107,92,0.1), 0 4px 12px rgba(14,107,92,0.15);
            --shadow-button-hover: 0 4px 8px rgba(14,107,92,0.15), 0 8px 24px rgba(14,107,92,0.2);
            
            --ease-out-expo: cubic-bezier(0.22, 1, 0.36, 1);
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: 150ms var(--ease-smooth);
            --transition-smooth: 350ms var(--ease-out-expo);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--paper);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
            position: relative;
        }

        /* Subtle premium paper texture overlay */
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.025'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 9999;
            opacity: 0.6;
        }

        h1, h2, h3, h4 {
            font-family: 'Fraunces', Georgia, 'Times New Roman', serif;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--ink);
            line-height: 1.15;
        }

        .mono { font-family: 'IBM Plex Mono', 'Courier New', monospace; }
        a { color: inherit; text-decoration: none; }
        img, svg { max-width: 100%; height: auto; display: block; }

        .wrap {
            max-width: 1160px;
            margin: 0 auto;
            padding: 0 20px;
        }
        @media (min-width: 768px) { .wrap { padding: 0 32px; } }
        @media (min-width: 1024px) { .wrap { padding: 0 40px; } }

        /* ── Navigation ── */
        .nav-header {
            border-bottom: 1px solid rgba(220, 227, 222, 0.6);
            background: rgba(244, 246, 243, 0.85);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            position: sticky;
            top: 0;
            z-index: 50;
            transition: box-shadow var(--transition-smooth);
        }
        .nav-header.scrolled {
            box-shadow: 0 4px 20px rgba(16, 32, 27, 0.04);
        }

        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }
        @media (min-width: 768px) { .nav-container { height: 72px; } }

        .logo {
            font-family: 'Fraunces', Georgia, serif;
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--ink);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
            transition: opacity var(--transition-fast);
        }
        .logo:hover { opacity: 0.8; }

        .logo-mark {
            width: 28px; height: 28px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(14, 107, 92, 0.25);
        }
        .logo-mark-inner {
            width: 10px; height: 10px;
            background: #fff;
            border-radius: 3px;
        }

        .nav-links {
            display: none;
            gap: 4px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        @media (min-width: 860px) { .nav-links { display: flex; } }

        .nav-links a {
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-weight: 500;
            transition: color var(--transition-fast), background var(--transition-fast), transform var(--transition-fast);
        }
        .nav-links a:hover { 
            color: var(--primary); 
            background: var(--primary-light); 
        }

        .nav-actions { display: flex; gap: 10px; align-items: center; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all var(--transition-smooth);
            white-space: nowrap;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            position: relative;
            overflow: hidden;
        }
        @media (min-width: 768px) { .btn { padding: 12px 22px; font-size: 0.9rem; } }

        .btn:hover { transform: translateY(-2px); }
        .btn:active { transform: translateY(0); }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: var(--shadow-button);
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity var(--transition-fast);
        }
        .btn-primary:hover { 
            background: var(--primary-deep); 
            box-shadow: var(--shadow-button-hover); 
        }
        .btn-primary:hover::after { opacity: 1; }

        .btn-ghost {
            color: var(--text);
            border-color: var(--line);
            background: var(--paper-raised);
        }
        .btn-ghost:hover { 
            border-color: var(--primary); 
            color: var(--primary); 
            background: var(--primary-light);
        }

        .btn-sm { padding: 7px 14px; font-size: 0.8rem; }
        @media (min-width: 768px) { .btn-sm { padding: 8px 16px; font-size: 0.85rem; } }

        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px; height: 40px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--paper-raised);
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            transition: background var(--transition-fast), border-color var(--transition-fast);
        }
        @media (min-width: 860px) { .mobile-menu-toggle { display: none; } }
        .mobile-menu-toggle:hover { background: var(--primary-light); border-color: var(--primary); }

        .mobile-menu-toggle span {
            display: block;
            width: 18px; height: 2px;
            background: var(--ink);
            border-radius: 2px;
            position: relative;
            transition: all var(--transition-smooth);
        }
        .mobile-menu-toggle span::before,
        .mobile-menu-toggle span::after {
            content: '';
            position: absolute;
            width: 18px; height: 2px;
            background: var(--ink);
            border-radius: 2px;
            transition: all var(--transition-smooth);
        }
        .mobile-menu-toggle span::before { top: -6px; }
        .mobile-menu-toggle span::after { top: 6px; }

        .mobile-menu-toggle.active span { background: transparent; }
        .mobile-menu-toggle.active span::before { top: 0; transform: rotate(45deg); }
        .mobile-menu-toggle.active span::after { top: 0; transform: rotate(-45deg); }

        /* Mobile menu overlay */
        .mobile-menu {
            position: fixed;
            top: 64px;
            left: 0; right: 0; bottom: 0;
            background: rgba(244, 246, 243, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 40;
            padding: 32px 24px;
            overflow-y: auto;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: opacity 0.3s var(--ease-out-expo), visibility 0.3s, transform 0.3s var(--ease-out-expo);
        }
        .mobile-menu.active { 
            opacity: 1; 
            visibility: visible; 
            transform: translateY(0); 
        }

        .mobile-menu a {
            display: block;
            padding: 16px;
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--ink);
            border-radius: var(--radius-sm);
            transition: background var(--transition-fast), transform var(--transition-fast);
            opacity: 0;
            transform: translateX(-10px);
        }
        .mobile-menu.active a {
            animation: slideInLeft 0.4s var(--ease-out-expo) forwards;
        }
        .mobile-menu a:hover { background: var(--primary-light); transform: translateX(4px); }
        .mobile-menu a:nth-child(1) { animation-delay: 50ms; }
        .mobile-menu a:nth-child(2) { animation-delay: 100ms; }
        .mobile-menu a:nth-child(3) { animation-delay: 150ms; }
        .mobile-menu a:nth-child(4) { animation-delay: 200ms; }
        .mobile-menu a:nth-child(5) { animation-delay: 250ms; }

        .mobile-menu .mobile-cta {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--line);
            opacity: 0;
        }
        .mobile-menu.active .mobile-cta {
            animation: fadeInUp 0.5s var(--ease-out-expo) 0.3s forwards;
        }
        .mobile-menu .mobile-cta .btn { width: 100%; text-align: center; justify-content: center; }

        /* ── Sections ── */
        .section { padding: 60px 0; }
        @media (min-width: 768px) { .section { padding: 80px 0; } }
        @media (min-width: 1024px) { .section { padding: 100px 0; } }

        .section-header {
            max-width: 640px;
            margin-bottom: 48px;
        }
        @media (min-width: 768px) { .section-header { margin-bottom: 64px; } }
        .section-header.center { margin-left: auto; margin-right: auto; text-align: center; }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary-deep);
            background: var(--primary-light);
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            margin-bottom: 20px;
            border: 1px solid rgba(14, 107, 92, 0.1);
        }

        .section-header h1 { font-size: clamp(2rem, 5vw, 2.8rem); margin-bottom: 16px; }
        .section-header h2 { font-size: clamp(1.6rem, 4vw, 2.4rem); margin-bottom: 16px; }
        .section-header p {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.7;
        }
        @media (min-width: 768px) { .section-header p { font-size: 1.1rem; } }

        /* ── Hero ── */
        .hero {
            padding: 48px 0 60px;
            overflow: hidden;
        }

        /* ── Reimagined Hero Additions ── */
.text-gradient {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-deep) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--primary-deep);
    background: var(--primary-light);
    padding: 6px 14px;
    border-radius: var(--radius-pill);
    margin-bottom: 24px;
    border: 1px solid rgba(14, 107, 92, 0.15);
}
.hero-badge svg { color: var(--primary); }

.trust-cluster {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 8px;
}

.trust-note {
    font-size: 0.82rem;
    color: var(--text-muted);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--paper-raised);
    padding: 8px 14px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--line-light);
    box-shadow: var(--shadow-card);
    font-weight: 500;
}

.trust-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--primary);
    flex-shrink: 0;
    box-shadow: 0 0 0 3px var(--primary-light);
}

/* Hero Visual Container */
.hero-visual {
    position: relative;
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    perspective: 1000px;
}

/* Background: Confusing Lab Report */
.lab-report-bg {
    position: absolute;
    top: 20px;
    right: -20px;
    width: 85%;
    height: 100%;
    background: var(--paper-raised);
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    padding: 24px;
    transform: rotate(3deg) translateZ(-50px);
    opacity: 0.6;
    filter: blur(2px);
    z-index: 1;
}

.report-line {
    height: 8px;
    background: var(--line-light);
    border-radius: 4px;
    margin-bottom: 16px;
}
.report-line:nth-child(2) { margin-top: 32px; }

.report-blur-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, rgba(244,246,243,0.4), rgba(244,246,243,0.9));
    border-radius: var(--radius-md);
}

/* Foreground: Clear Insight Card */
.insight-card {
    position: relative;
    background: var(--paper-raised);
    border: 1px solid var(--line-light);
    border-radius: var(--radius-lg);
    padding: 28px;
    box-shadow: var(--shadow-elevated), 0 0 0 1px rgba(255,255,255,0.5) inset;
    z-index: 2;
    animation: float 6s ease-in-out infinite;
    backdrop-filter: blur(10px);
}

.insight-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.insight-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-muted);
    margin-bottom: 4px;
}

.insight-date {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.insight-value-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 16px;
}

.insight-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--ink);
    line-height: 1;
}
.insight-value .unit {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-muted);
    margin-left: 4px;
}

.insight-range {
    font-size: 0.85rem;
    color: var(--text-muted);
    background: var(--paper);
    padding: 4px 10px;
    border-radius: var(--radius-sm);
}

.range-track-wrapper {
    margin-bottom: 24px;
}

.plain-language-box {
    background: rgba(14, 107, 92, 0.04);
    border: 1px solid rgba(14, 107, 92, 0.1);
    border-radius: var(--radius-md);
    padding: 16px;
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.plain-language-icon {
    font-size: 1.2rem;
    line-height: 1.4;
    flex-shrink: 0;
}

.plain-language-text {
    font-size: 0.88rem;
    color: var(--text);
    line-height: 1.6;
}
.plain-language-text strong {
    color: var(--primary-deep);
    font-weight: 600;
}

.insight-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.72rem;
    color: var(--text-muted);
    font-weight: 500;
    padding-top: 16px;
    border-top: 1px dashed var(--line);
}

/* Mobile Adjustments for Hero Visual */
@media (max-width: 1023px) {
    .hero-visual {
        margin-top: 40px;
        max-width: 400px;
    }
    .lab-report-bg {
        right: -10px;
        top: 15px;
        transform: rotate(2deg) scale(0.95);
    }
}

@media (max-width: 480px) {
    .hero-visual {
        max-width: 100%;
    }
    .lab-report-bg {
        width: 90%;
        right: -5px;
        transform: rotate(1.5deg) scale(0.9);
    }
    .insight-card {
        padding: 20px;
    }
    .insight-value {
        font-size: 1.75rem;
    }
}

        @media (min-width: 768px) { .hero { padding: 80px 0 100px; } }
        @media (min-width: 1024px) { .hero { padding: 100px 0 120px; } }

        .hero-grid {
            display: grid;
            gap: 48px;
            align-items: center;
        }
        @media (min-width: 1024px) {
            .hero-grid { grid-template-columns: 1.1fr 0.9fr; gap: 80px; }
        }

        .hero-content h1 { 
            font-size: clamp(2.2rem, 6vw, 3.6rem); 
            line-height: 1.08; 
            margin-bottom: 20px; 
            background: linear-gradient(135deg, var(--ink) 0%, var(--primary-deep) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-content .lead {
            font-size: 1.05rem;
            color: var(--text-muted);
            max-width: 520px;
            margin-bottom: 32px;
            line-height: 1.7;
        }
        @media (min-width: 768px) { .hero-content .lead { font-size: 1.15rem; margin-bottom: 40px; } }

        .hero-ctas {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 28px;
        }
        @media (min-width: 480px) { .hero-ctas { flex-direction: row; } }
        .hero-ctas .btn { width: 100%; text-align: center; }
        @media (min-width: 480px) { .hero-ctas .btn { width: auto; } }

        .trust-note {
            font-size: 0.82rem;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--paper-raised);
            padding: 8px 16px;
            border-radius: var(--radius-pill);
            border: 1px solid var(--line-light);
            box-shadow: var(--shadow-card);
        }
        .trust-note::before {
            content: '';
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--primary);
            flex-shrink: 0;
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        /* ── Range Card (Floating) ── */
        .range-card {
            background: var(--paper-raised);
            border: 1px solid var(--line-light);
            border-radius: var(--radius-lg);
            padding: 28px;
            box-shadow: var(--shadow-elevated);
            width: 100%;
            animation: float 6s ease-in-out infinite;
        }
        @media (min-width: 768px) { .range-card { padding: 36px; } }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .range-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        .range-card-header .panel-name {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }
        .status-chip {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: var(--radius-pill);
            background: var(--primary-light);
            color: var(--primary-deep);
            white-space: nowrap;
            border: 1px solid rgba(14, 107, 92, 0.15);
        }
        .status-chip.warn { background: var(--amber-soft); color: var(--amber); border-color: rgba(185, 129, 46, 0.2); }

        .range-item + .range-item { margin-top: 28px; }

        .range-item-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 12px;
            gap: 12px;
        }
        .range-item-header .name { font-size: 0.9rem; font-weight: 600; color: var(--ink); }
        .range-item-header .value { font-family: 'IBM Plex Mono', monospace; font-size: 0.9rem; font-weight: 600; color: var(--ink); white-space: nowrap; }
        @media (min-width: 768px) { 
            .range-item-header .name, .range-item-header .value { font-size: 0.95rem; } 
        }

        .range-track {
            position: relative;
            height: 10px;
            border-radius: var(--radius-pill);
            /* More realistic medical reference range gradient */
            background: linear-gradient(90deg, 
                var(--amber-soft) 0%, 
                var(--amber-soft) 15%, 
                rgba(14,107,92,0.15) 30%, 
                rgba(14,107,92,0.15) 70%, 
                var(--amber-soft) 85%, 
                var(--amber-soft) 100%);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .range-marker {
            position: absolute;
            top: 50%;
            width: 18px; height: 18px;
            border-radius: 50%;
            background: var(--primary);
            border: 3px solid var(--paper-raised);
            box-shadow: 0 0 0 1px var(--primary), 0 4px 12px rgba(14, 107, 92, 0.3);
            transform: translate(-50%, -50%);
            transition: left 1s var(--ease-out-expo);
            animation: markerPulse 3s ease-in-out infinite;
        }
        .range-marker.warn { 
            background: var(--amber); 
            box-shadow: 0 0 0 1px var(--amber), 0 4px 12px rgba(185, 129, 46, 0.3);
            animation: markerPulseWarn 3s ease-in-out infinite; 
        }

        .range-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.65rem;
            color: var(--text-muted);
            margin-top: 8px;
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 500;
        }

        .range-note {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px dashed var(--line);
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ── Steps ── */
        .steps-grid {
            display: grid;
            gap: 32px;
        }
        @media (min-width: 600px) { .steps-grid { grid-template-columns: repeat(3, 1fr); gap: 24px; } }
        @media (min-width: 1024px) { .steps-grid { gap: 48px; } }

        .step-card { padding: 24px 0; }
        .step-number {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .step-number::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--line), transparent);
        }
        .step-card:last-child .step-number::after { display: none; }
        .step-card h3 { font-size: 1.15rem; margin-bottom: 10px; }
        .step-card p { color: var(--text-muted); font-size: 0.92rem; line-height: 1.65; }

        /* ── Features Grid ── */
        .features-wrapper {
            background: var(--paper-raised);
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }
        .features-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1px;
            background: var(--line);
        }
        @media (min-width: 480px) { .features-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 860px) { 
            .features-grid { 
                grid-template-columns: repeat(4, 1fr); 
                border: 1px solid var(--line); 
                border-radius: var(--radius-md); 
                overflow: hidden; 
            } 
        }

        .feature-card {
            background: var(--paper-raised);
            padding: 32px 24px;
            transition: background var(--transition-fast);
        }
        .feature-card:hover { background: rgba(14, 107, 92, 0.02); }

        .feature-dot {
            width: 40px; height: 6px;
            border-radius: var(--radius-pill);
            background: var(--amber-soft);
            position: relative;
            margin-bottom: 24px;
        }
        .feature-dot::after {
            content: '';
            position: absolute;
            top: -4px;
            width: 14px; height: 14px;
            border-radius: 50%;
            background: var(--primary);
            border: 3px solid var(--paper-raised);
            box-shadow: 0 2px 8px rgba(14, 107, 92, 0.2);
        }
        .feature-card:nth-child(1) .feature-dot::after { left: 70%; }
        .feature-card:nth-child(2) .feature-dot::after { left: 30%; }
        .feature-card:nth-child(3) .feature-dot::after { left: 50%; }
        .feature-card:nth-child(4) .feature-dot::after { left: 20%; background: var(--amber); }

        .feature-card h3 { font-size: 1.05rem; margin-bottom: 8px; }
        .feature-card p { font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; }

        /* ── Trust ── */
        .trust-grid {
            display: grid;
            gap: 40px;
            align-items: center;
        }
        @media (min-width: 860px) { .trust-grid { grid-template-columns: 1fr 1fr; gap: 64px; } }

        .trust-copy h2 { font-size: clamp(1.5rem, 3.5vw, 2.2rem); margin-bottom: 16px; }
        .trust-copy p { color: var(--text-muted); margin-bottom: 16px; font-size: 1rem; line-height: 1.7; }

        .trust-disclaimer {
            background: var(--ink);
            color: #C9D6D1;
            border-radius: var(--radius-lg);
            padding: 32px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.78rem;
            line-height: 1.8;
            box-shadow: 0 20px 40px rgba(16, 32, 27, 0.15);
        }
        @media (min-width: 768px) { .trust-disclaimer { padding: 40px; font-size: 0.84rem; } }

        .trust-disclaimer .border-accent {
            border-left: 3px solid var(--amber);
            padding-left: 16px;
        }

        /* ── Credits ── */
        .credits-wrapper {
            background: linear-gradient(135deg, var(--primary-deep) 0%, var(--ink) 100%);
            border-radius: var(--radius-lg);
            padding: 40px 28px;
            color: #fff;
            box-shadow: var(--shadow-elevated);
        }
        @media (min-width: 768px) { .credits-wrapper { padding: 56px 48px; } }
        @media (min-width: 1024px) { .credits-wrapper { padding: 72px 64px; } }

        .credits-grid {
            display: grid;
            gap: 40px;
        }
        @media (min-width: 860px) { .credits-grid { grid-template-columns: 1.1fr 0.9fr; gap: 56px; align-items: center; } }

        .credits-copy h2 { color: #fff; font-size: clamp(1.5rem, 3.5vw, 2.2rem); margin-bottom: 16px; }
        .credits-copy p { color: rgba(255,255,255,0.8); margin-bottom: 24px; font-size: 1rem; line-height: 1.7; }

        .credit-tags { display: flex; flex-wrap: wrap; gap: 10px; }
        .credit-tag {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.72rem;
            background: rgba(255,255,255,0.1);
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            color: rgba(255,255,255,0.9);
            border: 1px solid rgba(255,255,255,0.1);
            transition: background var(--transition-fast);
        }
        .credit-tag:hover { background: rgba(255,255,255,0.2); }

        .credits-table {
            background: rgba(255,255,255,0.06);
            border-radius: var(--radius-md);
            padding: 20px 24px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        @media (min-width: 768px) { .credits-table { padding: 28px 32px; } }

        .credits-table-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            gap: 12px;
        }
        .credits-table-row:last-child { border-bottom: none; }
        .credits-table-row .cost {
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 600;
            color: var(--amber-soft);
            white-space: nowrap;
        }

        /* ── CTA ── */
        .cta-section { text-align: center; padding: 80px 0 56px; }
        @media (min-width: 768px) { .cta-section { padding: 112px 0 72px; } }
        .cta-section h2 { font-size: clamp(1.6rem, 5vw, 2.6rem); margin-bottom: 16px; }
        .cta-section p { color: var(--text-muted); margin-bottom: 32px; font-size: 1.05rem; max-width: 500px; margin-left: auto; margin-right: auto; }

        /* ── Page sections (generic) ── */
        .page-section { max-width: 720px; }
        .page-section h2 { font-size: 1.25rem; margin-bottom: 12px; }
        .page-section p { color: var(--text-muted); font-size: 0.95rem; line-height: 1.75; margin-bottom: 32px; }

        .page-section .value-grid {
            display: grid;
            gap: 1px;
            background: var(--line);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-top: 8px;
            border: 1px solid var(--line);
        }
        @media (min-width: 480px) { .page-section .value-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 860px) { .page-section .value-grid { grid-template-columns: repeat(4, 1fr); } }

        .page-section .value-item {
            background: var(--paper-raised);
            padding: 28px 24px;
            transition: background var(--transition-fast);
        }
        .page-section .value-item:hover { background: rgba(14, 107, 92, 0.02); }
        .page-section .value-item h3 { font-size: 0.95rem; margin-bottom: 6px; }
        .page-section .value-item p { font-size: 0.85rem; margin-bottom: 0; }

        /* ── Form ── */
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--ink); margin-bottom: 8px; }
        .form-group input, .form-group textarea {
            display: block;
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            background: var(--paper-raised);
            color: var(--text);
            transition: all var(--transition-fast);
            -webkit-appearance: none;
        }
        .form-group input:hover, .form-group textarea:hover { border-color: #b0bfb5; }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }
        .form-group textarea { resize: vertical; min-height: 120px; }

        /* ── Pricing cards ── */
        .pricing-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 40px;
        }
        @media (min-width: 480px) { .pricing-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 860px) { .pricing-grid { grid-template-columns: repeat(4, 1fr); } }

        .pricing-card {
            background: var(--paper-raised);
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 32px 24px;
            text-align: center;
            transition: all var(--transition-smooth);
        }
        .pricing-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-elevated);
            transform: translateY(-4px);
        }

        .pricing-amount {
            font-family: 'Fraunces', serif;
            font-size: 2.4rem;
            font-weight: 600;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 4px;
        }
        .pricing-label { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 16px; font-weight: 500; }
        .pricing-price {
            font-family: 'Fraunces', serif;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 24px;
        }
        .pricing-card .btn { width: 100%; justify-content: center; }

        /* ── Footer ── */
        .site-footer {
            border-top: 1px solid var(--line);
            padding: 32px 0;
            background: var(--paper);
        }
        @media (min-width: 768px) { .site-footer { padding: 40px 0; } }

        .footer-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        @media (min-width: 640px) {
            .footer-container { flex-direction: row; justify-content: space-between; align-items: center; text-align: left; }
        }

        /* ── Utility ── */
        .sr-only {
            position: absolute; width: 1px; height: 1px;
            padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0,0,0,0);
            border: 0;
        }
        .text-center { text-align: center; }
        .mt-8 { margin-top: 32px; }
        .mt-12 { margin-top: 48px; }
        .mt-16 { margin-top: 64px; }
        @media (min-width: 768px) {
            .mt-8 { margin-top: 40px; }
            .mt-12 { margin-top: 56px; }
            .mt-16 { margin-top: 72px; }
        }

        /* ── Focus ── */
        :focus-visible { 
            outline: 2px solid var(--primary); 
            outline-offset: 3px; 
            border-radius: 4px; 
        }

        /* ── Animations ── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes markerPulse {
            0%, 100% { box-shadow: 0 0 0 1px var(--primary), 0 4px 12px rgba(14, 107, 92, 0.3); }
            50% { box-shadow: 0 0 0 1px var(--primary), 0 4px 12px rgba(14, 107, 92, 0.3), 0 0 0 8px rgba(14, 107, 92, 0.1); }
        }
        @keyframes markerPulseWarn {
            0%, 100% { box-shadow: 0 0 0 1px var(--amber), 0 4px 12px rgba(185, 129, 46, 0.3); }
            50% { box-shadow: 0 0 0 1px var(--amber), 0 4px 12px rgba(185, 129, 46, 0.3), 0 0 0 8px rgba(185, 129, 46, 0.1); }
        }

        /* Scroll-triggered animation base states */
        .anim-fade-up, .anim-fade-in, .anim-slide-left, .anim-scale-in {
            opacity: 0;
        }

        .anim-fade-up.visible { animation: fadeInUp 0.8s var(--ease-out-expo) forwards; }
        .anim-fade-in.visible { animation: fadeIn 0.6s var(--ease-smooth) forwards; }
        .anim-slide-left.visible { animation: slideInLeft 0.8s var(--ease-out-expo) forwards; }
        .anim-scale-in.visible { animation: scaleIn 0.7s var(--ease-out-expo) forwards; }

        /* Staggered delays for grid children (applied via JS) */
        .stagger-children > * {
            opacity: 0;
            transform: translateY(20px);
        }
        .stagger-children > *.visible {
            animation: fadeInUp 0.7s var(--ease-out-expo) forwards;
        }

        /* Nav reveal */
        .nav-header { animation: fadeIn 0.6s var(--ease-smooth); }

        /* ── Reduced Motion ── */
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            .anim-fade-up, .anim-fade-in, .anim-slide-left, .anim-scale-in,
            .stagger-children > * { 
                opacity: 1; 
                transform: none !important; 
                animation: none !important; 
            }
            .range-card { animation: none; }
            .range-marker { animation: none; }
        }
    </style>
</head>
<body>
    <header class="nav-header" id="navHeader">
        <div class="wrap nav-container">
            <a href="{{ route('home') }}" class="logo" aria-label="HealthIntel home">
                <span class="logo-mark" aria-hidden="true"><span class="logo-mark-inner"></span></span>
                HealthIntel
            </a>

            <nav class="nav-links" aria-label="Main navigation">
                <a href="{{ route('features') }}">Features</a>
                <a href="{{ route('how-it-works') }}">How it works</a>
                <a href="{{ route('pricing') }}">Pricing</a>
                <a href="{{ route('about') }}">About</a>
                <a href="{{ route('contact') }}">Contact</a>
            </nav>

            <div class="nav-actions">
                @auth
                <a href="/dashboard" class="btn btn-primary btn-sm">Dashboard</a>
                @else
                <a href="/login" class="btn btn-ghost btn-sm">Log in</a>
                <a href="/register" class="btn btn-primary btn-sm">Sign up free</a>
                @endauth
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu" aria-expanded="false">
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <div class="mobile-menu" id="mobileMenu" role="dialog" aria-label="Mobile navigation">
        <a href="{{ route('features') }}">Features</a>
        <a href="{{ route('how-it-works') }}">How it works</a>
        <a href="{{ route('pricing') }}">Pricing</a>
        <a href="{{ route('about') }}">About</a>
        <a href="{{ route('contact') }}">Contact</a>
        
        <div class="mobile-cta">
            @auth
            <a href="/dashboard" class="btn btn-primary" style="font-weight:600;">Dashboard</a>
            @else
            <a href="/login" class="btn btn-ghost">Log in</a>
            <a href="/register" class="btn btn-primary">Sign up free</a>
            @endauth
        </div>
    </div>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="wrap" style="display:flex;flex-direction:column;gap:20px">
            <div class="footer-container" style="border-bottom:1px solid var(--line);padding-bottom:20px">
                <span>&copy; {{ date('Y') }} HealthIntel. All rights reserved.</span>
                <nav style="display:flex;gap:24px;font-size:0.82rem;flex-wrap:wrap;justify-content:center">
                    <a href="{{ route('privacy') }}" style="color:var(--text-muted);text-decoration:underline;text-underline-offset:4px;text-decoration-color:var(--line);transition:color 0.2s" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Privacy Policy</a>
                    <a href="{{ route('terms') }}" style="color:var(--text-muted);text-decoration:underline;text-underline-offset:4px;text-decoration-color:var(--line);transition:color 0.2s" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Terms of Service</a>
                    <a href="{{ route('contact') }}" style="color:var(--text-muted);text-decoration:underline;text-underline-offset:4px;text-decoration-color:var(--line);transition:color 0.2s" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Contact</a>
                </nav>
            </div>
            <p style="font-size:0.78rem;color:var(--text-muted);line-height:1.7;max-width:800px;margin:0 auto;text-align:center">
                <strong>Medical Disclaimer:</strong> HealthIntel provides general health information for educational purposes only. It is not a diagnosis and does not replace advice from a licensed healthcare professional. Always consult a doctor about your lab results or medical condition.
            </p>
        </div>
    </footer>

    {{-- Floating Play Store Badge --}}
    <a href="https://play.google.com/store/apps/details?id=ng.healthintel.mobile" target="_blank" rel="noopener noreferrer" class="play-store-float anim-scale-in" aria-label="Download HealthIntel on Google Play">
        <span class="play-store-icon">▶</span>
        <span class="play-store-text">
            <span class="play-store-label">GET IT ON</span>
            <span class="play-store-name">Google Play</span>
        </span>
    </a>

    <style>
        .play-store-float {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 30;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #000;
            color: #fff;
            border-radius: 14px;
            padding: 12px 22px 12px 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2), 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s var(--ease-out-expo), box-shadow 0.3s var(--ease-out-expo);
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .play-store-float:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 16px 48px rgba(0,0,0,0.25), 0 4px 12px rgba(0,0,0,0.15);
        }
        .play-store-float:active {
            transform: translateY(-1px) scale(0.98);
        }
        .play-store-icon {
            font-size: 1.5rem;
            line-height: 1;
        }
        .play-store-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }
        .play-store-label {
            font-size: 0.55rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            opacity: 0.7;
            font-weight: 600;
        }
        .play-store-name {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        @media (max-width: 600px) {
            .play-store-float {
                bottom: 20px;
                right: 20px;
                padding: 10px 18px 10px 14px;
                border-radius: 12px;
            }
            .play-store-name { font-size: 0.9rem; }
            .play-store-icon { font-size: 1.3rem; }
        }
    </style>

    <script>
        // ── Mobile Menu Logic ──
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        
        function toggleMobileMenu() {
            const isActive = mobileMenu.classList.toggle('active');
            mobileMenuToggle.classList.toggle('active');
            mobileMenuToggle.setAttribute('aria-expanded', isActive);
            document.body.style.overflow = isActive ? 'hidden' : '';
        }

        mobileMenuToggle.addEventListener('click', toggleMobileMenu);

        // Close mobile menu when clicking a link
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                if (mobileMenu.classList.contains('active')) {
                    toggleMobileMenu();
                }
            });
        });

        // Close mobile menu on resize if desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 860 && mobileMenu.classList.contains('active')) {
                toggleMobileMenu();
            }
        });

        // ── Nav Scroll Shadow ──
        const navHeader = document.getElementById('navHeader');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 10) {
                navHeader.classList.add('scrolled');
            } else {
                navHeader.classList.remove('scrolled');
            }
        }, { passive: true });

        // ── Enhanced Intersection Observer for Animations ──
        const animObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Handle staggered children
                    if (entry.target.classList.contains('stagger-children')) {
                        const children = entry.target.children;
                        Array.from(children).forEach((child, index) => {
                            child.style.animationDelay = `${index * 100}ms`;
                            child.classList.add('visible');
                        });
                    } else {
                        entry.target.classList.add('visible');
                    }
                    animObserver.unobserve(entry.target);
                }
            });
        }, { 
            threshold: 0.1, 
            rootMargin: '0px 0px -40px 0px' 
        });

        // Observe all animation targets
        document.querySelectorAll('.anim-fade-up, .anim-fade-in, .anim-slide-left, .anim-scale-in, .stagger-children')
            .forEach(el => animObserver.observe(el));

        // Trigger play store badge animation after a slight delay
        setTimeout(() => {
            document.querySelector('.play-store-float')?.classList.add('visible');
        }, 1500);
    </script>
</body>
</html>