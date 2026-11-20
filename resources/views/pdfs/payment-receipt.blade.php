<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Payment Receipt - {{ $payment->reference_number }}</title></head>
<body>
    @include('pdfs.partials.header', [
        'title' => 'Official Payment Receipt',
        'subtitle' => "Receipt Ref: {$payment->reference_number} | Sale Ref: {$sale->reference_number} | Date: {$payment->paid_at->format('d/m/Y')}",
    ])

    <div class="section">
        <div class="section-title">Customer &amp; Vehicle Details</div>
        <table class="grid">
            <tr>
                <td class="label">Received From</td>
                <td>{{ $sale->customer?->name }} ({{ $sale->customer?->phone }})</td>
                <td class="label">Customer Ref</td>
                <td>{{ $sale->customer?->reference_number }}</td>
            </tr>
            <tr>
                <td class="label">Vehicle</td>
                <td>{{ $sale->vehicle?->displayName() }} ({{ $sale->vehicle?->exterior_colour }})</td>
                <td class="label">Inventory Ref</td>
                <td>{{ $sale->vehicle?->reference_number }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Payment Breakdown</div>
        <table class="grid">
            <tr>
                <td class="label">Payment Type</td>
                <td>{{ $payment->payment_type->label() }}</td>
                <td class="label">Payment Method</td>
                <td>{{ $payment->paymentMethod?->name }} ({{ $payment->financialAccount?->name }})</td>
            </tr>
            <tr>
                <td class="label">External Ref</td>
                <td>{{ $payment->external_reference ?: '—' }}</td>
                <td class="label">Recorded By</td>
                <td>{{ $payment->recorder?->name ?? 'Authorized Staff' }}</td>
            </tr>
            <tr class="total-row">
                <td>Amount Received (This Receipt)</td>
                <td colspan="3" class="num">&#8358;{{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Agreed Sale Price</td>
                <td class="num">&#8358;{{ number_format((float) $sale->final_price, 2) }}</td>
                <td class="label">Total Paid to Date / Balance Due</td>
                <td class="num">&#8358;{{ number_format((float) $sale->amount_paid, 2) }} / <strong>&#8358;{{ number_format((float) $sale->balance_due, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="footer">{{ $business['receipt_footer'] }} &bull; Generated {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
