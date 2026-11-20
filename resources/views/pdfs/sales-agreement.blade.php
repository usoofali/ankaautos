<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Sales Agreement - {{ $sale->reference_number }}</title></head>
<body>
    @include('pdfs.partials.header', [
        'title' => 'Vehicle Sales Agreement & Invoice',
        'subtitle' => "Sale Ref: {$sale->reference_number} | Status: {$sale->status->label()} | Date: {$sale->confirmed_at->format('d/m/Y')}",
    ])

    <div class="section">
        <div class="section-title">1. Buyer &amp; Vehicle Information</div>
        <table class="grid">
            <tr>
                <td class="label">Buyer Name</td>
                <td>{{ $sale->customer?->name }}</td>
                <td class="label">Phone / Ref</td>
                <td>{{ $sale->customer?->phone }} ({{ $sale->customer?->reference_number }})</td>
            </tr>
            <tr>
                <td class="label">Vehicle</td>
                <td>{{ $sale->vehicle?->displayName() }}</td>
                <td class="label">Inventory Ref</td>
                <td>{{ $sale->vehicle?->reference_number }}</td>
            </tr>
            <tr>
                <td class="label">VIN / Chassis</td>
                <td>{{ $sale->vehicle?->vin ?: $sale->vehicle?->chassis_number ?: '—' }}</td>
                <td class="label">Colour / Mileage</td>
                <td>{{ $sale->vehicle?->exterior_colour }} / {{ $sale->vehicle?->mileage ? number_format($sale->vehicle->mileage).' km' : '—' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Financial Summary</div>
        <table class="grid">
            <tr><td class="label">List Price</td><td class="num">&#8358;{{ number_format((float) $sale->list_price, 2) }}</td></tr>
            <tr><td class="label">Approved Discount</td><td class="num">- &#8358;{{ number_format((float) $sale->discount_amount, 2) }}</td></tr>
            <tr class="total-row"><td>Final Agreed Sale Price</td><td class="num">&#8358;{{ number_format((float) $sale->final_price, 2) }}</td></tr>
            <tr><td class="label">Total Paid to Date</td><td class="num">&#8358;{{ number_format((float) $sale->amount_paid, 2) }}</td></tr>
            <tr><td class="label">Outstanding Balance</td><td class="num"><strong>&#8358;{{ number_format((float) $sale->balance_due, 2) }}</strong></td></tr>
        </table>
        @if($sale->is_installment && $sale->installment_terms)
            <p><strong>Installment Terms:</strong> {{ $sale->installment_terms }}</p>
        @endif
    </div>

    <table class="signatures">
        <tr>
            <td><div class="sig-line"><strong>Buyer Signature &amp; Date</strong><br>{{ $sale->customer?->name }}</div></td>
            <td><div class="sig-line"><strong>For {{ $business['company_name'] }}</strong><br>Sales Officer: {{ $sale->salesOfficer?->name ?? 'Authorized Staff' }}</div></td>
        </tr>
    </table>

    <div class="footer">{{ $business['receipt_footer'] }} &bull; Generated {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
