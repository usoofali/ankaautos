<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; margin: 22px 26px; }
    .header { border-bottom: 3px solid #dc141e; padding-bottom: 14px; margin-bottom: 18px; }
    .header-table { width: 100%; border-collapse: collapse; }
    .header-logo { width: 170px; padding-right: 14px; vertical-align: middle; }
    .header-logo img { max-height: 78px; max-width: 160px; width: auto; display: block; }
    .header-brand { vertical-align: middle; }
    .company-name { font-size: 26px; font-weight: bold; color: #0a2e5c; letter-spacing: 0.6px; margin: 0; line-height: 1.15; }
    .company-tagline { font-size: 10.5px; font-weight: bold; color: #dc141e; text-transform: uppercase; letter-spacing: 1px; margin: 3px 0 4px; }
    .company-meta { font-size: 10px; color: #475569; margin: 0; }
    .doc-banner { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    .doc-banner-table { width: 100%; border-collapse: collapse; }
    .doc-title { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6px; color: #0a2e5c; margin: 0 0 3px; }
    .ref-badge { font-size: 10px; color: #dc141e; font-weight: bold; }
    .status-stamp { display: inline-block; padding: 5px 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; border: 2px solid #0a2e5c; color: #0a2e5c; border-radius: 4px; }
    .status-stamp.paid { border-color: #059669; color: #059669; background: #ecfdf5; }
    .status-stamp.partial { border-color: #d97706; color: #b45309; background: #fffbeb; }
    .status-stamp.cancelled { border-color: #dc141e; color: #dc141e; background: #fef2f2; }
    .section { margin-bottom: 16px; }
    .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; background: #f1f5f9; padding: 6px 10px; border-left: 3px solid #0a2e5c; margin-bottom: 8px; color: #0a2e5c; }
    .amount-highlight { border: 2px solid #0a2e5c; background: #f8fafc; padding: 12px 14px; margin-bottom: 16px; }
    .amount-highlight-table { width: 100%; border-collapse: collapse; }
    .amount-highlight-label { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; }
    .amount-highlight-value { font-size: 20px; font-weight: bold; color: #0a2e5c; margin-top: 2px; }
    .amount-highlight-meta { text-align: right; font-size: 10px; color: #334155; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th, table.grid td { padding: 6px 8px; border: 1px solid #cbd5e1; vertical-align: top; }
    table.grid th { background: #f1f5f9; font-weight: bold; color: #0a2e5c; text-align: left; }
    table.grid td.label { width: 26%; font-weight: bold; background: #f8fafc; color: #334155; }
    .num { text-align: right; }
    .total-row td { font-weight: bold; background: #eef2f7; color: #0a2e5c; }
    .grand-total-row td { font-weight: bold; font-size: 12px; background: #0a2e5c; color: #ffffff; }
    .terms-box { border: 1px solid #cbd5e1; padding: 10px; background: #ffffff; margin-top: 6px; }
    .signatures { width: 100%; margin-top: 28px; border-collapse: collapse; }
    .signatures td { width: 50%; padding: 12px 18px 0 0; vertical-align: bottom; }
    .sig-line { border-top: 1px solid #334155; margin-top: 34px; padding-top: 4px; font-size: 10px; }
    .footer { margin-top: 26px; border-top: 1px solid #cbd5e1; padding-top: 8px; font-size: 9px; color: #64748b; text-align: center; }
</style>
<div class="header">
    <table class="header-table">
        <tr>
            @if(file_exists(public_path('logo-transparent.png')))
                <td class="header-logo">
                    <img src="{{ public_path('logo-transparent.png') }}" alt="{{ $business['company_name'] }}">
                </td>
            @endif
            <td class="header-brand">
                <p class="company-name">{{ strtoupper($business['company_name']) }}</p>
                @if(!empty($business['tagline']))
                    <p class="company-tagline">{{ $business['tagline'] }}</p>
                @endif
                <p class="company-meta">
                    {{ $business['address'] }} &bull; Tel: {{ $business['phone'] }} &bull; Email: {{ $business['email'] }}
                </p>
            </td>
        </tr>
    </table>
    <div class="doc-banner">
        <table class="doc-banner-table">
            <tr>
                <td>
                    <div class="doc-title">{{ $title }}</div>
                    <div class="ref-badge">{{ $subtitle }}</div>
                </td>
                @if(!empty($stampLabel))
                    <td style="text-align: right; vertical-align: middle;">
                        <span class="status-stamp {{ $stampClass ?? '' }}">{{ $stampLabel }}</span>
                    </td>
                @endif
            </tr>
        </table>
    </div>
</div>
