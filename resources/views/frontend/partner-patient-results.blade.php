@extends('layouts.frontend')

@section('title', $provider->name . ' — Lab Results')
@section('description', 'View your lab results and AI-powered plain-language interpretations.')

@section('content')
<section style="padding: 60px 0;">
    <div class="wrap" style="max-width: 800px;">
        {{-- Provider Branding --}}
        <div style="text-align: center; margin-bottom: 40px;">
            @if($partnership->brand_logo_url && $partnership->white_label)
                <img src="{{ $partnership->brand_logo_url }}" style="max-height: 60px; margin-bottom: 16px;" alt="{{ $provider->name }}">
            @endif
            <h1 style="font-family: 'Fraunces', serif; font-size: clamp(1.5rem, 3vw, 2rem); color: var(--ink); margin-bottom: 8px;">
                {{ $provider->name }}
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Your Lab Results & AI-Powered Interpretations
            </p>
        </div>

        {{-- Lookup Form --}}
        @if(!$patientId)
            <div style="background: var(--paper-raised); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 32px; max-width: 480px; margin: 0 auto;">
                <h2 style="font-family: 'Fraunces', serif; font-size: 1.2rem; color: var(--ink); margin-bottom: 8px; text-align: center;">Find Your Results</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; margin-bottom: 24px;">
                    Enter your Patient ID or Barcode from your lab receipt to view your results.
                </p>
                <form method="GET" action="">
                    <input
                        type="text"
                        name="pid"
                        placeholder="e.g., PT001 or BC-2026-001"
                        required
                        style="width: 100%; padding: 14px 16px; border: 1px solid var(--line); border-radius: var(--radius-sm); font-size: 0.95rem; background: var(--paper); color: var(--text); font-family: 'Inter', sans-serif; margin-bottom: 16px;"
                    >
                    <button type="submit" class="btn btn-primary" style="width: 100%;">View Results</button>
                </form>
            </div>
        @else
            {{-- Results Header --}}
            <div style="background: {{ $partnership->brand_primary_color ?? '#0E6B5C' }}10; border: 1px solid {{ $partnership->brand_primary_color ?? '#0E6B5C' }}30; border-radius: var(--radius-md); padding: 16px 20px; margin-bottom: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Patient ID</div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--ink);">{{ $patientId }}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Tests</div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--ink);">{{ $interpretations->count() }} results</div>
                    </div>
                </div>
            </div>

            @if($interpretations->isEmpty())
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <p>No results found for this Patient ID. Please check and try again.</p>
                    <a href="?pid=" style="color: var(--primary); font-weight: 500; margin-top: 12px; display: inline-block;">← Search again</a>
                </div>
            @else
                {{-- Results List --}}
                @foreach($interpretations as $i)
                    <div style="background: var(--paper-raised); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 24px; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 12px;">
                            <div>
                                <h3 style="font-family: 'Fraunces', serif; font-size: 1.1rem; color: var(--ink); margin-bottom: 4px;">
                                    {{ $i->test_name }}
                                </h3>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    {{ \Carbon\Carbon::parse($i->created_at)->format('F j, Y') }}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: {{ $partnership->brand_primary_color ?? '#0E6B5C' }};">
                                    {{ $i->value }} <span style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">{{ $i->unit }}</span>
                                </div>
                                @php
                                    $status = 'unknown';
                                    if ($i->reference_range_low && $i->reference_range_high) {
                                        $v = (float)$i->value;
                                        $l = (float)$i->reference_range_low;
                                        $h = (float)$i->reference_range_high;
                                        if ($v < $l) $status = 'LOW';
                                        elseif ($v > $h) $status = 'HIGH';
                                        else $status = 'NORMAL';
                                    }
                                @endphp
                                <span class="badge" style="
                                    display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.68rem; font-weight: 700; margin-top: 4px;
                                    @if($status === 'NORMAL') background: #DCFCE7; color: #166534;
                                    @elseif($status === 'LOW') background: #FEF3C7; color: #92400E;
                                    @elseif($status === 'HIGH') background: #FEE2E2; color: #991B1B;
                                    @else background: #E5E7EB; color: #4B5563;
                                    @endif
                                ">{{ $status }}</span>
                            </div>
                        </div>

                        @if($i->reference_range_low && $i->reference_range_high)
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;">
                                Normal range: {{ $i->reference_range_low }} – {{ $i->reference_range_high }} {{ $i->unit }}
                            </div>
                        @endif

                        @if($i->interpretation_text)
                            <div style="background: {{ $partnership->brand_primary_color ?? '#0E6B5C' }}08; border-left: 3px solid {{ $partnership->brand_primary_color ?? '#0E6B5C' }}; padding: 12px 16px; border-radius: 0 6px 6px 0; font-size: 0.9rem; color: var(--text); line-height: 1.7;">
                                {{ $i->interpretation_text }}
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Disclaimer --}}
                <div style="margin-top: 24px; padding: 12px 16px; background: #FFF7ED; border: 1px solid #FFEDD5; border-radius: 8px; font-size: 0.75rem; color: #92400E; text-align: center;">
                    <strong>Medical Disclaimer:</strong> These interpretations are for informational purposes only. They do not constitute medical advice. Please consult your doctor for diagnosis and treatment decisions.
                </div>

                <div style="text-align: center; margin-top: 24px;">
                    <a href="?pid=" style="color: var(--primary); font-weight: 500;">← Look up another patient</a>
                </div>
            @endif
        @endif
    </div>
</section>
@endsection