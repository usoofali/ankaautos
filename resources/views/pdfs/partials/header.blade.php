<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; margin: 24px; }
    .header { border-bottom: 3px solid #dc141e; padding-bottom: 12px; margin-bottom: 18px; }
    .header-table { width: 100%; border-collapse: collapse; }
    .header-logo { width: 120px; vertical-align: middle; }
    .header-logo img { max-height: 54px; width: auto; }
    .company-name { font-size: 18px; font-weight: bold; color: #0a2e5c; margin: 0; }
    .company-meta { font-size: 10px; color: #475569; margin-top: 4px; }
    .doc-title { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #0a2e5c; margin: 12px 0 4px; }
    .ref-badge { font-size: 10px; color: #dc141e; font-weight: bold; }
    .section { margin-bottom: 16px; }
    .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; background: #f1f5f9; padding: 6px 10px; border-left: 3px solid #0a2e5c; margin-bottom: 8px; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th, table.grid td { padding: 6px 8px; border: 1px solid #e2e8f0; vertical-align: top; }
    table.grid th { background: #f8fafc; font-weight: bold; color: #0a2e5c; text-align: left; }
    table.grid td.label { width: 28%; font-weight: bold; background: #f8fafc; color: #334155; }
    .num { text-align: right; }
    .total-row td { font-weight: bold; background: #f1f5f9; color: #0a2e5c; }
    .signatures { width: 100%; margin-top: 30px; border-collapse: collapse; }
    .signatures td { width: 50%; padding: 12px 16px 0 0; vertical-align: bottom; }
    .sig-line { border-top: 1px solid #334155; margin-top: 34px; padding-top: 4px; font-size: 10px; }
    .footer { margin-top: 28px; border-top: 1px solid #cbd5e1; padding-top: 8px; font-size: 9px; color: #64748b; text-align: center; }
</style>
<div class="header">
    <table class="header-table">
        <tr>
            @if(file_exists(public_path('logo-transparent.png')))
                <td class="header-logo">
                    <img src="{{ public_path('logo-transparent.png') }}" alt="{{ $business['company_name'] }}">
                </td>
            @endif
            <td>
                <p class="company-name">{{ $business['company_name'] }}</p>
                <p class="company-meta">{{ $business['address'] }} &bull; Tel: {{ $business['phone'] }} &bull; Email: {{ $business['email'] }}</p>
            </td>
        </tr>
    </table>
    <div class="doc-title">{{ $title }}</div>
    <div class="ref-badge">{{ $subtitle }}</div>
</div>
