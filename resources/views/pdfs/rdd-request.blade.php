<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $rddRequest['reference_no'] }}</title>
    <style>
        @page { margin: 26pt 42pt 32pt; }
        body { color: #000; font-family: helvetica, sans-serif; font-size: 10pt; line-height: 1.2; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }
        .form-code { font-size: 9pt; line-height: 1.3; margin-bottom: 4pt; text-align: right; }
        .letterhead { border: 0.75pt solid #000; }
        .letterhead td { padding: 6pt 10pt; vertical-align: middle; }
        .letterhead .logo { text-align: center; width: 92pt; }
        .letterhead .logo img { height: 62pt; width: 62pt; }
        .letterhead .organization { line-height: 1.35; text-align: center; }
        .organization .department { font-size: 9pt; }
        .organization .institute { font-size: 11.5pt; font-weight: bold; }
        .organization .division { font-size: 9.5pt; font-weight: bold; }
        .organization .details { font-size: 8.5pt; }
        .form-title { font-size: 14pt; font-style: italic; font-weight: bold; margin: 12pt 0 6pt; text-align: center; }
        .section-title { font-size: 10.5pt; margin: 0 0 12pt; text-align: center; }
        .section-title strong { text-decoration: underline; }
        .section-title em { margin-left: 8pt; }
        .field-row { margin-bottom: 8pt; }
        .field-row .label { white-space: nowrap; }
        .value { font-weight: bold; text-decoration: underline; }
        .two-columns td:nth-child(1) { width: 58%; }
        .two-columns td:nth-child(2) { width: 42%; }
        .two-columns.even td { width: 50%; }
        .three-columns td:nth-child(1) { width: 42%; }
        .three-columns td:nth-child(2) { width: 25%; }
        .three-columns td:nth-child(3) { width: 33%; }
        .section-two { margin-top: 18pt; }
        .items { border: 0.75pt solid #000; font-size: 9.5pt; margin-top: 10pt; }
        .items th, .items td { border: 0.75pt solid #000; padding: 5pt 4pt; text-align: center; }
        .items th { font-weight: bold; }
        .items .request { width: 37%; }
        .items .specifications { width: 19%; }
        .items .quantity { width: 7%; }
        .items .money { width: 18.5%; }
        .after-items { margin-top: 8pt; }
        .payment-summary .payment-column { padding-right: 12pt; width: 68%; }
        .payment-summary .totals-column { width: 32%; }
        .payment-lines td { padding-bottom: 6pt; }
        .payment-lines .reference-label { white-space: nowrap; width: 108pt; }
        .payment-lines .date-label { padding-left: 8pt; white-space: nowrap; width: 30pt; }
        .payment-lines .date-value { padding-left: 3pt; }
        .blank { border-bottom: 0.75pt solid #000; }
        .payment-lines .mode-value { white-space: nowrap; }
        .totals { font-size: 9.5pt; }
        .totals td { padding-bottom: 5pt; }
        .totals td:last-child { font-weight: bold; text-align: right; text-decoration: underline; }
        .signature-table { border: 0.75pt solid #000; font-size: 9.5pt; margin-top: 22pt; }
        .signature-table td, .signature-table th { border: 0.75pt solid #000; height: 24pt; padding: 5pt 4pt; }
        .signature-table th { font-weight: bold; text-align: center; }
        .signature-table .role { width: 42%; }
        .signature-table .signature { width: 36%; }
        .signature-table .date { width: 22%; }
        .footer { bottom: 8pt; left: 42pt; position: fixed; right: 42pt; }
        .footer-rule { border-top: 0.75pt dashed #000; height: 16pt; }
        .footer-note { font-size: 9pt; font-weight: bold; margin: 0 0 8pt; text-align: center; }
        .release-table td { font-size: 9pt; padding-bottom: 2pt; white-space: nowrap; }
        .release-table .label { width: 70pt; }
        .release-table .blank { width: 90pt; }
    </style>
</head>
<body>
    <div class="form-code">RDD Form No. 001<br>Rev. 2/15-05-17</div>

    <table class="letterhead">
        <tr>
            <td class="logo"><img src="{{ $logoPath }}" alt="PTRI"></td>
            <td class="organization">
                <div class="department">Department of Science and Technology</div>
                <div class="institute">PHILIPPINE TEXTILE RESEARCH INSTITUTE</div>
                <div class="division">Research and Development Division</div>
                <div class="details">Gen. Santos Ave., Bicutan Taguig City, 1631 Philippines</div>
                <div class="details">Tel Nos. (632) 827-2171 to 82 loc. 2367 Telefax No. 88371349</div>
                <div class="details">http://www.ptri.dost.gov.ph</div>
            </td>
        </tr>
    </table>

    <div class="form-title">SERVICE REQUEST FORM</div>
    <p class="section-title"><strong>Section 1</strong><em>(To be filled out by customer)</em></p>

    <table class="two-columns even field-row">
        <tr>
            <td><strong>Customer Information</strong></td>
            <td style="text-align: right;">Date/Time: <span class="value">{{ $serviceRequest['created_at'] }}</span></td>
        </tr>
    </table>
    <table class="two-columns field-row">
        <tr>
            <td>Requesting Official/Name: <span class="value">{{ $serviceRequest['client']['fullname'] }}</span></td>
            <td>Designation: <span class="value">{{ $serviceRequest['client']['type_client'] ?? '' }}</span></td>
        </tr>
    </table>
    <div class="field-row">Company/Affiliation: <span class="value">{{ $serviceRequest['client']['company_or_school'] ?? '' }}</span></div>
    <div class="field-row">Address: <span class="value">{{ $serviceRequest['client']['address'] }}</span></div>
    <table class="three-columns field-row">
        <tr>
            <td>Email: <span class="value">{{ $serviceRequest['client']['email'] }}</span></td>
            <td>Fax No.: <span class="value">{{ $serviceRequest['client']['fax_no'] ?: 'N/A' }}</span></td>
            <td>Contact No.: <span class="value">{{ $serviceRequest['client']['mobile_no'] }}</span></td>
        </tr>
    </table>
    <div class="field-row"><strong>Sample Description:</strong> <span class="value">{{ $serviceRequest['description'] }}</span></div>

    <div class="section-two">
        <p class="section-title"><strong>Section 2</strong><em>(To be filled out by Receiving Officer)</em></p>
        <table class="two-columns even field-row">
            <tr>
                <td>Customer Reference No.: <span class="value">{{ $rddRequest['reference_no'] }}</span></td>
                <td>Due Date: <span class="value">{{ $rddRequest['due_date'] }}</span></td>
            </tr>
        </table>

        <table class="items">
            <thead><tr><th class="request">SERVICE REQUEST</th><th class="specifications">SPECIFICATIONS</th><th class="quantity">QTY</th><th class="money">UNIT FEE</th><th class="money">TOTAL FEE</th></tr></thead>
            <tbody>
                @foreach ($rddRequest['items'] as $item)
                    <tr><td>{{ $item['item'] }}</td><td>{{ $item['specification'] }}</td><td>{{ $item['quantity'] }}</td><td>{{ $peso($item['unit_fee']) }}</td><td>{{ $peso($item['total_fee']) }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <div class="after-items">
            <table class="payment-summary"><tr>
                <td class="payment-column">
                    <table class="payment-lines">
                        <tr>
                            <td class="reference-label">OP No.:</td>
                            <td @if(! $rddRequest['op_no']) class="blank" @endif>@if($rddRequest['op_no'])<span class="value">{{ $rddRequest['op_no'] }}</span>@endif</td>
                            <td class="date-label">Date:</td>
                            <td class="date-value @if(! $rddRequest['op_no']) blank @endif">@if($rddRequest['op_no'])<span class="value">{{ $rddRequest['payment_verified_at'] }}</span>@endif</td>
                        </tr>
                        <tr>
                            <td class="reference-label">Official Receipt No.:</td>
                            <td @if(! $rddRequest['or_no']) class="blank" @endif>@if($rddRequest['or_no'])<span class="value">{{ $rddRequest['or_no'] }}</span>@endif</td>
                            <td class="date-label">Date:</td>
                            <td class="date-value @if(! $rddRequest['or_no']) blank @endif">@if($rddRequest['or_no'])<span class="value">{{ $rddRequest['payment_verified_at'] }}</span>@endif</td>
                        </tr>
                        <tr>
                            <td class="reference-label">Mode of Charging:</td>
                            <td class="mode-value" colspan="3">( &nbsp; ) Cash &nbsp; &nbsp; &nbsp; ( &nbsp; ) Managers Check</td>
                        </tr>
                    </table>
                </td>
                <td class="totals-column"><table class="totals"><tr><td>SUB-TOTAL:</td><td>{{ $peso($rddRequest['sub_total']) }}</td></tr><tr><td>DISCOUNT:</td><td>{{ $peso($rddRequest['discount']) }}</td></tr><tr><td>TOTAL FEE:</td><td>{{ $peso($rddRequest['total_fee']) }}</td></tr></table></td>
            </tr></table>
        </div>

        <table class="signature-table"><thead><tr><th class="role"></th><th class="signature">SIGNATURE</th><th class="date">DATE</th></tr></thead><tbody><tr><td>Customer/Authorized Representative</td><td></td><td></td></tr><tr><td>Received by:</td><td></td><td></td></tr><tr><td>Reviewed by:</td><td></td><td></td></tr></tbody></table>
    </div>

    <div class="footer">
        <div class="footer-rule"></div>
        <p class="footer-note">Materials accepted in good quality condition.</p>
        <table class="release-table"><tr>
            <td class="label">Released by:</td><td class="blank"></td>
            <td class="label" style="padding-left: 10pt;">Received by:</td><td class="blank"></td>
            <td class="label" style="padding-left: 10pt;">Date:</td><td class="blank" style="width: 55pt;"></td>
        </tr></table>
    </div>
</body>
</html>
