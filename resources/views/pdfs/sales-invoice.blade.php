<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Invoice - INV-{{ $sale->reference_number }}</title>
</head>
<body>
    @php
        $watermarkLabel = match ($sale->status->value) {
            'cancelled' => 'CANCELLED',
            'completed' => 'PAID IN FULL',
            default => $sale->isFullyPaid() ? 'PAID IN FULL' : 'BALANCE DUE',
        };
        $watermarkColor = match ($sale->status->value) {
            'cancelled' => '#dc141e',
            'completed' => '#059669',
            default => $sale->isFullyPaid() ? '#059669' : '#d97706',
        };
    @endphp

    @include('pdfs.partials.header', [
        'business' => $business,
        'title' => 'Sales Invoice',
        'subtitle' => "Invoice No: INV-{$sale->reference_number} | Sale Ref: {$sale->reference_number} | Issue Date: {$sale->confirmed_at->format('d M Y')}",
    ])

    {{-- Single-page compact overrides & diagonal status watermark --}}
    <style>
        @page { size: A4 portrait; margin: 18px 24px; }
        body { margin: 0; font-size: 9.8px; line-height: 1.32; }
        .header { padding-bottom: 9px; margin-bottom: 10px; }
        .header-logo { width: 155px; padding-right: 12px; }
        .header-logo img { max-height: 68px; max-width: 148px; }
        .company-name { font-size: 24px; }
        .company-tagline { font-size: 9.5px; margin: 2px 0 3px; }
        .company-meta { font-size: 9px; }
        .doc-banner { margin-top: 8px; padding-top: 6px; }
        .doc-title { font-size: 13.5px; margin-bottom: 2px; }
        .ref-badge { font-size: 9.5px; }
        .section { margin-bottom: 9px; }
        .section-title { font-size: 9.8px; padding: 4px 8px; margin-bottom: 5px; }
        table.grid th, table.grid td { padding: 4px 6px; }
        table.grid td.label { width: 22%; }
        .grand-total-row td { font-size: 10.5px; }
        .terms-box { padding: 6px 8px; margin-top: 0; font-size: 9.2px; }
        .signatures { margin-top: 14px; }
        .signatures td { padding: 4px 16px 0 0; }
        .sig-line { margin-top: 24px; padding-top: 3px; font-size: 9.2px; }
        .footer { margin-top: 12px; padding-top: 6px; font-size: 8.5px; }
        .invoice-watermark {
            position: fixed;
            top: 40%;
            left: -5%;
            width: 110%;
            text-align: center;
            z-index: -1000;
            opacity: 0.12;
            transform: rotate(-30deg);
        }
        .invoice-watermark-box {
            display: inline-block;
            border: 5px solid {{ $watermarkColor }};
            color: {{ $watermarkColor }};
            font-size: 56px;
            font-weight: bold;
            letter-spacing: 5px;
            text-transform: uppercase;
            padding: 6px 26px;
            border-radius: 8px;
        }
        table.split-summary { width: 100%; border-collapse: collapse; }
        table.split-summary > tbody > tr > td { vertical-align: top; border: none; padding: 0; }
    </style>

    <div class="invoice-watermark">
        <span class="invoice-watermark-box">{{ $watermarkLabel }}</span>
    </div>

    <div class="section">
        <div class="section-title">1. Bill To &amp; Invoice Details</div>
        <table class="grid">
            <tr>
                <td class="label">Customer</td>
                <td><strong>{{ $sale->customer?->name }}</strong> ({{ $sale->customer?->reference_number }})</td>
                <td class="label">Invoice No.</td>
                <td><strong>INV-{{ $sale->reference_number }}</strong></td>
            </tr>
            <tr>
                <td class="label">Phone / Email</td>
                <td>{{ $sale->customer?->phone }}{{ $sale->customer?->email ? ' · '.$sale->customer->email : '' }}</td>
                <td class="label">Sale Date</td>
                <td>{{ $sale->confirmed_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="label">Address</td>
                <td>{{ $sale->customer?->address ?: '—' }}</td>
                <td class="label">Payment Plan</td>
                <td>
                    {{ $sale->is_installment ? 'Installment Plan' : 'Full Payment' }}
                    @if($sale->reservation)
                        &bull; From {{ $sale->reservation->reference_number }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Vehicle &amp; Itemized Charges</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th>Description</th>
                    <th class="num" style="width: 24%;">Amount (&#8358;)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        <strong>{{ $sale->vehicle?->displayName() }}</strong>
                        <span style="font-size: 8.8px; color: #475569;">
                            &nbsp;|&nbsp; Ref: {{ $sale->vehicle?->reference_number }}
                            &bull; Colour: {{ $sale->vehicle?->exterior_colour }}
                            &bull; VIN/Chassis: {{ $sale->vehicle?->vin ?: $sale->vehicle?->chassis_number ?: '—' }}
                            @if($sale->vehicle?->mileage)
                                &bull; {{ number_format($sale->vehicle->mileage) }} km
                            @endif
                        </span>
                    </td>
                    <td class="num">&#8358;{{ number_format((float) $sale->list_price, 2) }}</td>
                </tr>
                @if((float) $sale->discount_amount > 0)
                    <tr>
                        <td>—</td>
                        <td>
                            Approved Sale Discount
                            @if($sale->discounts->isNotEmpty() && $sale->discounts->first()?->reason)
                                <span style="font-size: 8.8px; color: #475569;">({{ $sale->discounts->first()->reason }})</span>
                            @endif
                        </td>
                        <td class="num" style="color: #dc141e;">- &#8358;{{ number_format((float) $sale->discount_amount, 2) }}</td>
                    </tr>
                @endif
                @foreach($sale->charges as $charge)
                    <tr>
                        <td>+</td>
                        <td>
                            Service Charge: {{ $charge->displayLabel() }}
                            @if($charge->notes)
                                <span style="font-size: 8.8px; color: #475569;">({{ $charge->notes }})</span>
                            @endif
                        </td>
                        <td class="num">&#8358;{{ number_format((float) $charge->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">Total Agreed Vehicle Sale Price (Invoice Total)</td>
                    <td class="num">&#8358;{{ number_format((float) $sale->final_price, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. Consolidated Payment History (All Payments Applied)</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 21%;">Receipt Ref</th>
                    <th style="width: 13%;">Date</th>
                    <th style="width: 17%;">Type</th>
                    <th style="width: 24%;">Method &amp; Ref</th>
                    <th class="num" style="width: 20%;">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allPayments as $idx => $p)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $p->reference_number }}</strong></td>
                        <td>{{ $p->paid_at->format('d/m/Y') }}</td>
                        <td>{{ $p->payment_type->label() }}</td>
                        <td>
                            {{ $p->paymentMethod?->name ?? '—' }}
                            @if($p->external_reference)
                                <span style="font-size: 8.5px; color: #475569;">({{ $p->external_reference }})</span>
                            @endif
                        </td>
                        <td class="num">&#8358;{{ number_format((float) $p->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b;">No payments recorded yet.</td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    <td colspan="5">Total Payments Received to Date ({{ count($allPayments) }} {{ count($allPayments) === 1 ? 'payment' : 'payments' }})</td>
                    <td class="num">&#8358;{{ number_format((float) $sale->amount_paid, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section" style="margin-bottom: 6px;">
        <table class="split-summary">
            <tbody>
                <tr>
                    <td style="width: 48%; padding-right: 12px;">
                        <div class="terms-box">
                            <strong>Payment &amp; Terms Note:</strong><br>
                            @if($sale->is_installment && $sale->installment_terms)
                                {{ $sale->installment_terms }}
                            @else
                                This consolidated invoice covers all payments received toward Sale {{ $sale->reference_number }}. Individual transaction receipts are issued per payment.
                            @endif
                        </div>
                    </td>
                    <td style="width: 52%;">
                        <table class="grid">
                            <tr>
                                <td class="label" style="width: 56%;">Total Invoice Amount</td>
                                <td class="num">&#8358;{{ number_format((float) $sale->final_price, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="label" style="width: 56%;">Less: Total Payments Received</td>
                                <td class="num" style="color: #059669;">- &#8358;{{ number_format((float) $sale->amount_paid, 2) }}</td>
                            </tr>
                            <tr class="grand-total-row">
                                <td>Outstanding Balance Due</td>
                                <td class="num">&#8358;{{ number_format((float) $sale->balance_due, 2) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">
                    <strong>Buyer Acknowledgment &amp; Date</strong> &mdash; {{ $sale->customer?->name }}
                </div>
            </td>
            <td>
                <div class="sig-line">
                    <strong>For {{ $business['company_name'] }}</strong> &mdash; {{ $sale->salesOfficer?->name ?? 'Authorized Staff' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Consolidated Invoice INV-{{ $sale->reference_number }} &bull; {{ $business['receipt_footer'] }} &bull; Generated {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
