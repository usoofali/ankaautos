<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Owner Settlement Statement - {{ $settlement->reference_number }}</title></head>
<body>
    @include('pdfs.partials.header', [
        'title' => 'Consignment Owner Settlement Statement',
        'subtitle' => "Settlement Ref: {$settlement->reference_number} | Consignment: {$settlement->consignment?->reference_number} | Status: {$settlement->status->label()}",
    ])

    <div class="section">
        <div class="section-title">1. Owner &amp; Vehicle Details</div>
        <table class="grid">
            <tr>
                <td class="label">Vehicle Owner</td>
                <td>{{ $settlement->owner?->name }} ({{ $settlement->owner?->phone }})</td>
                <td class="label">Bank Details</td>
                <td>{{ $settlement->owner?->bank_name ?: '—' }} / {{ $settlement->owner?->bank_account_number ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Vehicle Sold</td>
                <td>{{ $settlement->vehicle?->displayName() }} ({{ $settlement->vehicle?->reference_number }})</td>
                <td class="label">Sale Reference</td>
                <td>{{ $settlement->sale?->reference_number }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Itemized Settlement Computation</div>
        <table class="grid">
            <thead><tr><th>Category</th><th>Description</th><th class="num">Amount (&#8358;)</th></tr></thead>
            <tbody>
                @foreach($settlement->items as $item)
                    <tr>
                        <td>{{ $item->item_type->label() }}</td>
                        <td>{{ $item->label }}</td>
                        <td class="num">
                            {{ $item->item_type->value === 'sale_proceeds' ? '' : '- ' }}&#8358;{{ number_format((float) $item->amount, 2) }}
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">Net Payable to Vehicle Owner</td>
                    <td class="num">&#8358;{{ number_format((float) $settlement->net_payable_to_owner, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2">Total Disbursed to Date</td>
                    <td class="num">&#8358;{{ number_format((float) $settlement->amount_paid, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Outstanding Owner Balance</strong></td>
                    <td class="num"><strong>&#8358;{{ number_format((float) $settlement->balance_due, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">{{ $business['receipt_footer'] }} &bull; Generated {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
