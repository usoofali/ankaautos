<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - {{ $payment->reference_number }}</title>
</head>
<body>
    @php
        $isZeroBalanceAfter = (float) $balanceAfterReceipt <= 0.0;
        $receiptTitle = $sale->is_installment && ! $isZeroBalanceAfter
            ? 'Payment Receipt'
            : 'Payment Receipt';
        $watermarkLabel = $isZeroBalanceAfter ? 'PAID IN FULL' : 'PARTIAL PAYMENT';
        $watermarkColor = $isZeroBalanceAfter ? '#059669' : '#d97706';
    @endphp

    @include('pdfs.partials.header', [
        'business' => $business,
        'title' => $receiptTitle,
        'subtitle' => "Receipt No: {$payment->reference_number} | Sale Ref: {$sale->reference_number} | Payment Date: {$payment->paid_at->format('d M Y')}",
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
        .amount-highlight { padding: 8px 12px; margin-bottom: 10px; }
        .amount-highlight-label { font-size: 9px; }
        .amount-highlight-value { font-size: 18px; margin-top: 1px; }
        .amount-highlight-meta { font-size: 9.2px; }
        .section { margin-bottom: 9px; }
        .section-title { font-size: 9.8px; padding: 4px 8px; margin-bottom: 5px; }
        table.grid th, table.grid td { padding: 4px 6px; }
        table.grid td.label { width: 22%; }
        .grand-total-row td { font-size: 10.5px; }
        .signatures { margin-top: 16px; }
        .signatures td { padding: 4px 16px 0 0; }
        .sig-line { margin-top: 24px; padding-top: 3px; font-size: 9.2px; }
        .footer { margin-top: 12px; padding-top: 6px; font-size: 8.5px; }
        .receipt-watermark {
            position: fixed;
            top: 40%;
            left: -5%;
            width: 110%;
            text-align: center;
            z-index: -1000;
            opacity: 0.11;
            transform: rotate(-30deg);
        }
        .receipt-watermark-box {
            display: inline-block;
            border: 5px solid {{ $watermarkColor }};
            color: {{ $watermarkColor }};
            font-size: 52px;
            font-weight: bold;
            letter-spacing: 5px;
            text-transform: uppercase;
            padding: 6px 24px;
            border-radius: 8px;
        }
    </style>

    <div class="receipt-watermark">
        <span class="receipt-watermark-box">{{ $watermarkLabel }}</span>
    </div>

    <div class="amount-highlight">
        <table class="amount-highlight-table">
            <tr>
                <td>
                    <div class="amount-highlight-label">Amount Received (This Transaction)</div>
                    <div class="amount-highlight-value">&#8358;{{ number_format((float) $payment->amount, 2) }}</div>
                </td>
                <td class="amount-highlight-meta">
                    <div><strong>{{ $paymentSequenceLabel }}</strong></div>
                    <div>Type: {{ $payment->payment_type->label() }}</div>
                    <div>Method: {{ $payment->paymentMethod?->name ?? '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">1. Payer &amp; Vehicle Details</div>
        <table class="grid">
            <tr>
                <td class="label">Received From</td>
                <td><strong>{{ $sale->customer?->name }}</strong> ({{ $sale->customer?->reference_number }})</td>
                <td class="label">Invoice Ref</td>
                <td><strong>INV-{{ $sale->reference_number }}</strong></td>
            </tr>
            <tr>
                <td class="label">Phone / Email</td>
                <td>{{ $sale->customer?->phone }}{{ $sale->customer?->email ? ' · '.$sale->customer->email : '' }}</td>
                <td class="label">Inventory Ref</td>
                <td>{{ $sale->vehicle?->reference_number }}</td>
            </tr>
            <tr>
                <td class="label">Vehicle</td>
                <td>{{ $sale->vehicle?->displayName() }} ({{ $sale->vehicle?->exterior_colour }})</td>
                <td class="label">VIN / Chassis</td>
                <td>{{ $sale->vehicle?->vin ?: $sale->vehicle?->chassis_number ?: '—' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. This Transaction Details</div>
        <table class="grid">
            <tr>
                <td class="label">Receipt Ref</td>
                <td><strong>{{ $payment->reference_number }}</strong></td>
                <td class="label">Date Received</td>
                <td>{{ $payment->paid_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="label">Payment Type</td>
                <td>{{ $payment->payment_type->label() }} ({{ $paymentSequenceLabel }})</td>
                <td class="label">Payment Method</td>
                <td>{{ $payment->paymentMethod?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Account</td>
                <td>{{ $payment->financialAccount?->name ?? '—' }}</td>
                <td class="label">Bank / POS Ref</td>
                <td>{{ $payment->external_reference ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Recorded By</td>
                <td>{{ $payment->recorder?->name ?? 'Authorized Staff' }}</td>
                <td class="label">Amount Paid</td>
                <td class="num"><strong>&#8358;{{ number_format((float) $payment->amount, 2) }}</strong></td>
            </tr>
            @if(!empty($payment->notes))
                <tr>
                    <td class="label">Remarks</td>
                    <td colspan="3">{{ $payment->notes }}</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="section" style="margin-bottom: 6px;">
        <div class="section-title">3. Running Sale Account Balance (As of This Receipt)</div>
        <table class="grid">
            <tr>
                <td class="label" style="width: 55%;">Total Agreed Sale Price</td>
                <td class="num">&#8358;{{ number_format((float) $sale->final_price, 2) }}</td>
            </tr>
            <tr>
                <td class="label" style="width: 55%;">Previously Paid (Before This Receipt)</td>
                <td class="num">&#8358;{{ number_format((float) $previouslyPaid, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Paid on This Receipt ({{ $payment->reference_number }})</td>
                <td class="num">&#8358;{{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label" style="width: 55%;">Cumulative Total Paid (Including This Receipt)</td>
                <td class="num">&#8358;{{ number_format((float) $cumulativePaidAtReceipt, 2) }}</td>
            </tr>
            <tr class="grand-total-row">
                <td>Balance Remaining After This Payment</td>
                <td class="num">&#8358;{{ number_format((float) $balanceAfterReceipt, 2) }}</td>
            </tr>
        </table>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">
                    <strong>Customer / Payer Signature</strong> &mdash; {{ $sale->customer?->name }}
                </div>
            </td>
            <td>
                <div class="sig-line">
                    <strong>For {{ $business['company_name'] }}</strong> &mdash; {{ $payment->recorder?->name ?? 'Authorized Staff' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Receipt for transaction {{ $payment->reference_number }} only. For a consolidated statement of all payments, see Invoice INV-{{ $sale->reference_number }}.<br>
        {{ $business['receipt_footer'] }} &bull; Generated {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
