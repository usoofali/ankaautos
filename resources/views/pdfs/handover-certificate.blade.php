<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Handover Certificate - {{ $handover->reference_number }}</title></head>
<body>
    @include('pdfs.partials.header', [
        'title' => $transactionLabel.' Certificate',
        'subtitle' => "Handover Ref: {$handover->reference_number} | Source Ref: {$parentReference} | Date: {$handover->handed_over_at->format('d/m/Y H:i')}",
    ])

    <div class="section">
        <div class="section-title">1. Vehicle &amp; Recipient Details</div>
        <table class="grid">
            <tr>
                <td class="label">Vehicle</td>
                <td>{{ $handover->vehicle?->displayName() }} ({{ $handover->vehicle?->reference_number }})</td>
                <td class="label">VIN / Chassis</td>
                <td>{{ $handover->vehicle?->vin ?: $handover->vehicle?->chassis_number ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Recipient Name</td>
                <td>{{ $handover->recipient_name }}</td>
                <td class="label">Phone / ID</td>
                <td>{{ $handover->recipient_phone }} {{ $handover->recipient_id_number ? "({$handover->recipient_id_number})" : '' }}</td>
            </tr>
            <tr>
                <td class="label">Odometer Reading</td>
                <td>{{ $handover->odometer_reading ? number_format($handover->odometer_reading).' km' : '—' }}</td>
                <td class="label">Fuel Level</td>
                <td>{{ $handover->fuel_level ?: '—' }}</td>
            </tr>
        </table>
    </div>

    @if($handover->items->isNotEmpty())
        <div class="section">
            <div class="section-title">2. Accessories &amp; Documents Checklist</div>
            <table class="grid">
                <thead>
                    <tr><th>Item</th><th>Included</th><th>Remarks</th></tr>
                </thead>
                <tbody>
                    @foreach($handover->items as $item)
                        <tr>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->is_included ? 'Yes' : 'No' }}</td>
                            <td>{{ $item->remarks ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <table class="signatures">
        <tr>
            <td><div class="sig-line"><strong>Recipient Acknowledgement &amp; Signature</strong><br>{{ $handover->recipient_name }}</div></td>
            <td><div class="sig-line"><strong>Handed Over By ({{ $business['company_name'] }})</strong><br>{{ $handover->officer?->name ?? 'Authorized Staff' }}</div></td>
        </tr>
    </table>

    <div class="footer">{{ $business['receipt_footer'] }} &bull; Generated {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
