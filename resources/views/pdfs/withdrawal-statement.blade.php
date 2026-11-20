<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Consignment Withdrawal Statement - {{ $withdrawal->reference_number }}</title></head>
<body>
    @include('pdfs.partials.header', [
        'title' => 'Consignment Withdrawal Statement',
        'subtitle' => "Withdrawal Ref: {$withdrawal->reference_number} | Consignment: {$withdrawal->consignment?->reference_number} | Status: {$withdrawal->status->label()}",
    ])

    <div class="section">
        <div class="section-title">1. Owner &amp; Vehicle Details</div>
        <table class="grid">
            <tr>
                <td class="label">Vehicle Owner</td>
                <td>{{ $withdrawal->owner?->name }} ({{ $withdrawal->owner?->phone }})</td>
                <td class="label">Vehicle</td>
                <td>{{ $withdrawal->vehicle?->displayName() }} ({{ $withdrawal->vehicle?->reference_number }})</td>
            </tr>
            <tr>
                <td class="label">Withdrawal Reason</td>
                <td colspan="3">{{ $withdrawal->reason }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Charges &amp; Clearance Summary</div>
        <table class="grid">
            <tr><td class="label">Owner-Deductible Expenses</td><td class="num">&#8358;{{ number_format((float) $withdrawal->total_owner_expenses, 2) }}</td></tr>
            <tr><td class="label">Withdrawal Service Charges</td><td class="num">&#8358;{{ number_format((float) $withdrawal->total_withdrawal_charges, 2) }}</td></tr>
            <tr class="total-row"><td>Total Payable Before Release</td><td class="num">&#8358;{{ number_format((float) $withdrawal->total_due, 2) }}</td></tr>
            <tr><td class="label">Amount Paid</td><td class="num">&#8358;{{ number_format((float) $withdrawal->amount_paid, 2) }}</td></tr>
            <tr><td class="label">Outstanding Balance</td><td class="num"><strong>&#8358;{{ number_format((float) $withdrawal->balance_due, 2) }}</strong></td></tr>
        </table>
    </div>

    <div class="footer">{{ $business['receipt_footer'] }} &bull; Generated {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
