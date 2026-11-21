<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consignment Agreement - {{ $consignment->reference_number }}</title>
</head>
<body>
    @include('pdfs.partials.header', [
        'business' => $business,
        'title' => 'Vehicle Consignment Agreement',
        'subtitle' => "Reference: {$consignment->reference_number} | Agreement Version: v".($consignment->agreement?->current_version ?? 1)." | Date: {$consignment->received_at->format('d/m/Y')}",
    ])

    <div class="section">
        <div class="section-title">1. Vehicle Owner (Consignor) Details</div>
        <table class="grid">
            <tr>
                <td class="label">Owner Name</td>
                <td>{{ $consignment->owner?->name }}</td>
                <td class="label">Owner Reference</td>
                <td>{{ $consignment->owner?->reference_number }}</td>
            </tr>
            <tr>
                <td class="label">Phone</td>
                <td>{{ $consignment->owner?->phone }}</td>
                <td class="label">Category</td>
                <td>{{ $consignment->owner?->owner_category->label() }}</td>
            </tr>
            <tr>
                <td class="label">Address</td>
                <td colspan="3">{{ $consignment->owner?->address ?: 'Not specified' }}</td>
            </tr>
            <tr>
                <td class="label">ID Type / Number</td>
                <td>{{ $consignment->owner?->id_type ?: '—' }} / {{ $consignment->owner?->id_number ?: '—' }}</td>
                <td class="label">Settlement Bank</td>
                <td>{{ $consignment->owner?->bank_name ?: '—' }} ({{ $consignment->owner?->bank_account_number ?: '—' }})</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Vehicle Details</div>
        <table class="grid">
            <tr>
                <td class="label">Vehicle</td>
                <td>{{ $consignment->vehicle?->displayName() }}</td>
                <td class="label">Inventory Ref</td>
                <td>{{ $consignment->vehicle?->reference_number }}</td>
            </tr>
            <tr>
                <td class="label">VIN / Chassis</td>
                <td>{{ $consignment->vehicle?->vin ?: $consignment->vehicle?->chassis_number ?: '—' }}</td>
                <td class="label">Exterior Colour</td>
                <td>{{ $consignment->vehicle?->exterior_colour }}</td>
            </tr>
            <tr>
                <td class="label">Transmission / Fuel</td>
                <td>{{ $consignment->vehicle?->transmission->label() }} / {{ $consignment->vehicle?->fuel_type->label() }}</td>
                <td class="label">Mileage</td>
                <td>{{ $consignment->vehicle?->mileage ? number_format($consignment->vehicle->mileage).' km' : '—' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. Agreed Pricing &amp; Commission Terms</div>
        <table class="grid">
            <tr>
                <td class="label">Agreed Selling Price</td>
                <td>&#8358;{{ number_format((float) $consignment->agreed_selling_price, 2) }}</td>
                <td class="label">Minimum Acceptable Price</td>
                <td>{{ $consignment->minimum_acceptable_price ? '₦'.number_format((float) $consignment->minimum_acceptable_price, 2) : 'Not specified' }}</td>
            </tr>
            <tr>
                <td class="label">Commission Model</td>
                <td>{{ $consignment->commission_type->label() }}</td>
                <td class="label">Commission Value</td>
                <td>
                    @if($consignment->commission_type->value === 'percentage')
                        {{ $consignment->commission_value }}% of Final Sale Price
                    @elseif($consignment->commission_type->value === 'excess_over_minimum')
                        Excess above &#8358;{{ number_format((float) ($consignment->minimum_acceptable_price ?? 0), 2) }}
                    @else
                        &#8358;{{ number_format((float) $consignment->commission_value, 2) }}
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Commencement Date</td>
                <td>{{ $consignment->received_at->format('d/m/Y') }}</td>
                <td class="label">Review Date</td>
                <td>{{ $consignment->expected_review_date?->format('d/m/Y') ?? 'Open-ended' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. Expense, Withdrawal &amp; Settlement Conditions</div>
        <div class="terms-box">
            <p><strong>Expense Responsibility:</strong> {{ $consignment->expense_responsibility_notes ?: 'All owner-approved preparation, washing, or maintenance expenses recorded as Owner-Deductible will be settled upon sale or withdrawal.' }}</p>
            <p><strong>Withdrawal Conditions:</strong> {{ $consignment->withdrawal_terms ?: 'If the vehicle is withdrawn prior to sale, all applicable withdrawal charges and owner-deductible expenses must be paid in full before vehicle handover.' }}</p>
            <p><strong>Settlement Conditions:</strong> {{ $consignment->settlement_terms ?: 'Net owner proceeds (Final Sale Price minus agreed commission, owner-deductible expenses, and applicable consignment sale charges) will be disbursed once 100% of customer payment is received.' }}</p>
        </div>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">
                    <strong>Vehicle Owner / Consignor Signature &amp; Date</strong><br>
                    {{ $consignment->owner?->name }}
                </div>
            </td>
            <td>
                <div class="sig-line">
                    <strong>For {{ $business['company_name'] }} (Authorized Officer)</strong><br>
                    Received By: {{ $consignment->receiver?->name ?? 'Authorized Staff' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $business['receipt_footer'] }} &bull; Generated {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
