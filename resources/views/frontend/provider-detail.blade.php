@extends('layouts.frontend')

@section('title', $provider->name . ' — ' . ucfirst($provider->type) . ' in ' . $provider->city . ', ' . $provider->state . ' | HealthIntel')
@section('description', Str::limit(strip_tags($provider->bio ?? ($provider->name . ' is a ' . $provider->type . ' located in ' . $provider->city . ', ' . $provider->state . '. Find contact details, hours, services and reviews on HealthIntel.')), 155))

@php
    $typeLabel = ucfirst($provider->type);
    $locationLabel = collect([$provider->city, $provider->state, $provider->country])->filter()->join(', ');
    $phone = $provider->phone;
    $whatsappRaw = preg_replace('/[^0-9]/', '', (string) ($provider->whatsapp ?: $provider->phone));
    $whatsapp = $whatsappRaw ? (str_starts_with($whatsappRaw, '0') ? '234' . substr($whatsappRaw, 1) : $whatsappRaw) : null;
@endphp

@section('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "MedicalBusiness",
    "name": "{{ $provider->name }}",
    "description": "{{ Str::limit(strip_tags($provider->bio ?? ''), 200) }}",
    "url": "{{ url()->current() }}",
    "telephone": "{{ $phone }}",
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ $provider->address }}",
        "addressLocality": "{{ $provider->city }}",
        "addressRegion": "{{ $provider->state }}",
        "addressCountry": "{{ $provider->country ?? 'NG' }}"
    },
    @if($provider->latitude && $provider->longitude)
    "geo": {
        "@type": "GeoCoordinates",
        "latitude": {{ $provider->latitude }},
        "longitude": {{ $provider->longitude }}
    },
    @endif
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "{{ $ratingAvg }}",
        "reviewCount": "{{ $ratingCount }}"
    }
}
</script>
@endsection

@section('content')
<section class="section" style="padding-bottom:0">
    <div class="wrap">
        <div class="page-section" style="margin:0 auto">
            <a href="/directory" style="color:var(--text-muted);font-size:0.85rem;font-weight:600">‹ Back to Provider Directory</a>

            <div style="display:flex;align-items:center;gap:16px;margin:24px 0 8px;flex-wrap:wrap">
                <div style="width:64px;height:64px;border-radius:16px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.8rem;overflow:hidden">
                    @if($provider->logo_url)
                        <img src="{{ $provider->logo_url }}" alt="{{ $provider->name }}" style="width:100%;height:100%;object-fit:contain;border-radius:16px" onerror="this.style.display='none'">
                    @else
                        ⚕
                    @endif
                </div>
                <div>
                    <h1 style="font-size:1.8rem;margin-bottom:4px">{{ $provider->name }}</h1>
                    <p style="color:var(--text-muted);margin:0;text-transform:capitalize">{{ $typeLabel }}</p>
                </div>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:8px;margin:12px 0 24px">
                @if($provider->is_verified)
                    <span style="background:var(--success-50, #F0FDF4);color:var(--success-700, #15803D);padding:4px 12px;border-radius:999px;font-size:0.75rem;font-weight:700">✓ Verified</span>
                @endif
                @if($ratingCount > 0)
                    <span style="background:var(--amber-soft, #F4E9D6);color:var(--amber, #B9812E);padding:4px 12px;border-radius:999px;font-size:0.75rem;font-weight:700">★ {{ $ratingAvg }} ({{ $ratingCount }})</span>
                @endif
                @if($provider->is_open_now === true)
                    <span style="background:var(--success-50, #F0FDF4);color:var(--success-700, #15803D);padding:4px 12px;border-radius:999px;font-size:0.75rem;font-weight:700">Open now</span>
                @elseif($provider->is_open_now === false)
                    <span style="background:var(--line-light, #E8EBE7);color:var(--text-muted);padding:4px 12px;border-radius:999px;font-size:0.75rem;font-weight:700">Closed</span>
                @endif
            </div>

            {{-- Actions --}}
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:32px">
                @if($phone)
                    <a href="tel:{{ $phone }}" class="btn btn-primary btn-sm">📞 Call Now</a>
                @endif
                @if($whatsapp)
                    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm">💬 WhatsApp</a>
                @endif
                @if($provider->website)
                    <a href="{{ $provider->website }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm">🌐 Visit Website</a>
                @endif
                <a href="/directory" class="btn btn-ghost btn-sm">Find more providers</a>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="page-section" style="margin:0 auto">
            {{-- Details --}}
            <div style="background:var(--paper-raised);border:1px solid var(--line);border-radius:var(--radius-md);padding:28px;margin-bottom:24px">
                <h2 style="font-size:1.2rem;margin-bottom:16px">Details</h2>
                <dl style="margin:0">
                    @if($provider->specialty)
                        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line-light)">
                            <dt style="color:var(--text-muted);font-weight:500">Specialty</dt>
                            <dd style="margin:0;font-weight:600">{{ $provider->specialty }}</dd>
                        </div>
                    @endif
                    @if($locationLabel)
                        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line-light)">
                            <dt style="color:var(--text-muted);font-weight:500">Location</dt>
                            <dd style="margin:0;font-weight:600">{{ $locationLabel }}</dd>
                        </div>
                    @endif
                    @if($provider->address)
                        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line-light)">
                            <dt style="color:var(--text-muted);font-weight:500">Address</dt>
                            <dd style="margin:0;font-weight:600;text-align:right">{{ $provider->address }}</dd>
                        </div>
                    @endif
                    @if($provider->email)
                        <div style="display:flex;justify-content:space-between;padding:10px 0">
                            <dt style="color:var(--text-muted);font-weight:500">Email</dt>
                            <dd style="margin:0;font-weight:600"><a href="mailto:{{ $provider->email }}" style="color:var(--primary)">{{ $provider->email }}</a></dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Opening hours --}}
            @if($provider->opening_hours && count($provider->opening_hours))
                <div style="background:var(--paper-raised);border:1px solid var(--line);border-radius:var(--radius-md);padding:28px;margin-bottom:24px">
                    <h2 style="font-size:1.2rem;margin-bottom:16px">Opening Hours</h2>
                    <div style="font-size:0.9rem">
                        @foreach(['mon','tue','wed','thu','fri','sat','sun'] as $day)
                            @php
                                $slot = $provider->opening_hours[$day] ?? null;
                                $text = $slot && !empty($slot['open']) && !empty($slot['close']) ? $slot['open'].' – '.$slot['close'] : 'Closed';
                            @endphp
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line-light)">
                                <span style="color:var(--text-muted)">{{ ucfirst($day) }}</span>
                                <span style="font-weight:600">{{ $text }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Services --}}
            @if($provider->services && count($provider->services))
                <div style="background:var(--paper-raised);border:1px solid var(--line);border-radius:var(--radius-md);padding:28px;margin-bottom:24px">
                    <h2 style="font-size:1.2rem;margin-bottom:16px">Services & Tests</h2>
                    <div style="display:flex;flex-wrap:wrap;gap:8px">
                        @foreach($provider->services as $service)
                            <span style="background:var(--primary-light);color:var(--primary-deep);padding:6px 12px;border-radius:999px;font-size:0.8rem;font-weight:600">{{ is_string($service) ? $service : ($service['name'] ?? ($service['label'] ?? '')) }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- About --}}
            @if($provider->bio)
                <div style="background:var(--paper-raised);border:1px solid var(--line);border-radius:var(--radius-md);padding:28px;margin-bottom:24px">
                    <h2 style="font-size:1.2rem;margin-bottom:16px">About</h2>
                    <p style="color:var(--text-muted);line-height:1.75;margin:0">{{ $provider->bio }}</p>
                </div>
            @endif

            {{-- Reviews --}}
            <div style="background:var(--paper-raised);border:1px solid var(--line);border-radius:var(--radius-md);padding:28px">
                <h2 style="font-size:1.2rem;margin-bottom:16px">Reviews</h2>
                @if($reviews->isEmpty())
                    <p style="color:var(--text-muted);margin:0">No reviews yet. Be the first to share your experience.</p>
                @else
                    <div style="display:flex;flex-direction:column;gap:16px">
                        @foreach($reviews as $review)
                            <div style="background:var(--paper);border:1px solid var(--line-light);border-radius:var(--radius-sm);padding:16px">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                                    <strong>{{ $review->user?->name ?? 'Anonymous' }}</strong>
                                    <span style="color:var(--amber, #B9812E)">{{ str_repeat('★', $review->rating) }}</span>
                                </div>
                                @if($review->title)
                                    <p style="font-weight:600;margin:0 0 4px">{{ $review->title }}</p>
                                @endif
                                <p style="color:var(--text-muted);margin:0">{{ $review->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- CTA --}}
            <div style="text-align:center;margin-top:32px">
                <p style="color:var(--text-muted);margin-bottom:16px">Want to take action on your health? Review your results and find trusted providers.</p>
                <a href="/dashboard" class="btn btn-primary">Go to your dashboard</a>
            </div>
        </div>
    </div>
</section>
@endsection