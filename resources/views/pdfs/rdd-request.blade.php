<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $rddRequest['reference_no'] }}</title>
    <style>
        @page { margin: 26pt 42pt 32pt; }
        * { box-sizing: border-box; }
        body { color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 10pt; line-height: 1.15; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        .form-code { font-size: 9pt; line-height: 1.35; margin-bottom: 2pt; text-align: right; }
        .letterhead { border: 0.75pt solid #000; height: 86pt; }
        .letterhead .logo { padding-left: 12pt; vertical-align: middle; width: 98pt; }
        .letterhead .logo img { height: 68pt; width: 68pt; }
        .letterhead .organization { line-height: 1.28; padding-right: 86pt; text-align: center; vertical-align: middle; }
        .organization .department { font-size: 9.5pt; }
        .organization .institute { font-size: 12pt; font-weight: bold; }
        .organization .division { font-size: 10pt; font-weight: bold; }
        .organization .details { font-size: 8.5pt; }
        .form-title { font-size: 14pt; font-style: italic; font-weight: bold; margin: 15pt 0 11pt; text-align: center; }
        .section-title { font-size: 10.5pt; margin: 0 0 16pt; text-align: center; }
        .section-title strong { text-decoration: underline; }
        .section-title em { margin-left: 8pt; }
        .customer-heading { font-size: 10pt; font-weight: bold; margin-bottom: 20pt; }
        .customer-heading .date-time { float: right; font-weight: normal; }
        .field-row { margin-bottom: 10pt; }
        .two-columns td { padding: 0 0 10pt; vertical-align: top; width: 50%; }
        .three-columns td { padding: 0 0 15pt; vertical-align: top; }
        .three-columns td:nth-child(1) { width: 50%; }
        .three-columns td:nth-child(2) { width: 25%; }
        .three-columns td:nth-child(3) { width: 25%; }
        .value { font-weight: bold; text-decoration: underline; }
        .section-two { margin-top: 22pt; }
        .reference-row { margin-bottom: 12pt; }
        .reference-row td { width: 50%; }
        .items { border: 0.75pt solid #000; font-size: 9.5pt; }
        .items th, .items td { border: 0.75pt solid #000; padding: 5pt 4pt; text-align: center; vertical-align: middle; }
        .items th { font-weight: bold; }
        .items .request { width: 37%; }
        .items .specifications { width: 19%; }
        .items .quantity { width: 7%; }
        .items .money { width: 18.5%; }
        .after-items { margin-top: 9pt; }
        .payment-summary { font-size: 9.5pt; table-layout: fixed; }
        .payment-summary .payment-column { padding-right: 12pt; vertical-align: top; width: 69%; }
        .payment-summary .totals-column { vertical-align: top; width: 31%; }
        .payment-lines { table-layout: fixed; }
        .payment-lines td { padding: 0 0 6pt; vertical-align: bottom; }
        .payment-lines .reference-label { width: 17%; }
        .payment-lines .reference-value { width: 47%; }
        .payment-lines .date-label { text-align: right; width: 13%; }
        .payment-lines .date-field { padding-left: 5pt; width: 23%; }
        .line-value { border-bottom: 0.75pt solid #000; display: inline-block; height: 11pt; vertical-align: bottom; }
        .payment-lines .line-value { display: block; overflow: hidden; white-space: nowrap; width: 100%; }
        .mode { padding-top: 2pt !important; }
        .choice { display: inline-block; margin-left: 25pt; }
        .totals { font-size: 9.5pt; }
        .totals td { padding: 0 0 5pt; }
        .totals td:last-child { font-weight: bold; text-align: right; text-decoration: underline; }
        .signature-table { border: 0.75pt solid #000; font-size: 9.5pt; margin-top: 28pt; }
        .signature-table td, .signature-table th { border: 0.75pt solid #000; height: 25pt; padding: 5pt 4pt; }
        .signature-table th { font-weight: bold; text-align: center; }
        .signature-table .role { width: 42%; }
        .signature-table .signature { width: 36%; }
        .signature-table .date { width: 22%; }
        .footer { bottom: 8pt; left: 42pt; position: fixed; right: 42pt; }
        .footer-rule { border-top: 0.75pt dashed #000; height: 16pt; }
        .footer-note { font-size: 9pt; font-weight: bold; margin: 0; text-align: center; }
        .release-table { font-size: 9pt; margin-top: 13pt; }
        .release-table td { white-space: nowrap; }
        .release-table td:nth-child(1) { width: 42%; }
        .release-table td:nth-child(2) { width: 42%; }
        .release-table td:nth-child(3) { width: 16%; }
        .release-table .line-value { height: 11pt; }
        .released { width: 140pt; }
        .received { width: 140pt; }
        .release-date { width: 49pt; }
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
    <div class="customer-heading">Customer Information <span class="date-time">Date/Time: <span class="value">{{ $serviceRequest['created_at'] }}</span></span></div>

    <table class="two-columns"><tr><td>Requesting Official/Name: <span class="value">{{ $serviceRequest['client']['fullname'] }}</span></td><td>Designation: <span class="value">{{ $serviceRequest['client']['type_client'] ?? '' }}</span></td></tr></table>
    <div class="field-row">Company/Affiliation: <span class="value">{{ $serviceRequest['client']['company_or_school'] ?? '' }}</span></div>
    <div class="field-row">Address: <span class="value">{{ $serviceRequest['client']['address'] }}</span></div>
    <table class="three-columns"><tr><td>Email: <span class="value">{{ $serviceRequest['client']['email'] }}</span></td><td>Fax No.: <span class="value">{{ $serviceRequest['client']['fax_no'] ?: 'N/A' }}</span></td><td>Contact No.: <span class="value">{{ $serviceRequest['client']['mobile_no'] }}</span></td></tr></table>
    <div class="field-row"><strong>Sample Description:</strong> <span class="value">{{ $serviceRequest['description'] }}</span></div>

    <div class="section-two">
        <p class="section-title"><strong>Section 2</strong><em>(To be filled out by Receiving Officer)</em></p>
        <table class="reference-row"><tr><td>Customer Reference No.: <span class="value">{{ $rddRequest['reference_no'] }}</span></td><td>Due Date: <span class="value">{{ $rddRequest['due_date'] }}</span></td></tr></table>
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
                        <tr><td class="reference-label">OP No.:</td><td class="reference-value"><span class="line-value">{{ $rddRequest['op_no'] }}</span></td><td class="date-label">Date:</td><td class="date-field"><span class="line-value">{{ $rddRequest['op_no'] ? $rddRequest['payment_verified_at'] : '' }}</span></td></tr>
                        <tr><td class="reference-label">Official Receipt No.:</td><td class="reference-value"><span class="line-value">{{ $rddRequest['or_no'] }}</span></td><td class="date-label">Date:</td><td class="date-field"><span class="line-value">{{ $rddRequest['or_no'] ? $rddRequest['payment_verified_at'] : '' }}</span></td></tr>
                        <tr><td colspan="4" class="mode">Mode of Charging:<span class="choice">( &nbsp; ) Cash</span><span class="choice">( &nbsp; ) Managers Check</span></td></tr>
                    </table>
                </td>
                <td class="totals-column"><table class="totals"><tr><td>SUB-TOTAL:</td><td>{{ $peso($rddRequest['sub_total']) }}</td></tr><tr><td>DISCOUNT:</td><td>{{ $peso($rddRequest['discount']) }}</td></tr><tr><td>TOTAL FEE:</td><td>{{ $peso($rddRequest['total_fee']) }}</td></tr></table></td>
            </tr></table>
        </div>
        <table class="signature-table"><thead><tr><th class="role"></th><th class="signature">SIGNATURE</th><th class="date">DATE</th></tr></thead><tbody><tr><td>Customer/Authorized Representative</td><td></td><td></td></tr><tr><td>Received by:</td><td></td><td></td></tr><tr><td>Reviewed by:</td><td></td><td></td></tr></tbody></table>
    </div>

    <div class="footer"><div class="footer-rule"></div><p class="footer-note">Materials accepted in good quality condition.</p><table class="release-table"><tr><td>Released by: <span class="line-value released"></span></td><td>Received by: <span class="line-value received"></span></td><td>Date: <span class="line-value release-date"></span></td></tr></table></div>
</body>
</html>
