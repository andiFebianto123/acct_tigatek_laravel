<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 20px 40px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.4;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }
        .logo-td {
            width: 70px;
            vertical-align: middle;
            padding-right: 15px;
        }
        .logo-img {
            width: 60px;
            height: auto;
            display: block;
        }
        .info-td {
            vertical-align: middle;
            text-align: left;
        }
        .company-info {
            font-size: 9pt;
            color: #000;
        }
        .company-name {
            font-weight: bold;
            font-size: 14pt;
            color: #000;
            margin-bottom: 2px;
        }
        .po-title-box {
            float: right;
            width: 60%;
            text-align: right;
        }
        .po-title {
            font-size: 24pt;
            font-weight: bold;
            margin-top: 20px;
            letter-spacing: 1pt;
        }
        .clearfix {
            clear: both;
        }
        .info-section {
            width: 100%;
            margin-top: 40px;
        }
        .client-info {
            float: left;
            width: 55%;
        }
        .client-info b {
            display: block;
            margin-bottom: 5px;
        }
        .po-meta {
            float: right;
            width: 40%;
        }
        .po-meta table {
            width: 100%;
            border-collapse: collapse;
        }
        .po-meta td {
            padding: 2px 0;
            vertical-align: top;
        }
        .po-meta .label {
            width: 30%;
            font-weight: bold;
        }
        .items-section {
            margin-top: 40px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        .items-table th {
            border-bottom: 1px solid #000;
            padding: 8px 5px;
            text-align: center;
            text-transform: uppercase;
            font-size: 10pt;
        }
        .items-table td {
            padding: 15px 5px;
            vertical-align: top;
            font-size: 10pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .total-row {
            border-top: 2px solid #000;
            font-weight: bold;
        }
        .grand-total-label {
            padding: 10px 5px;
            text-align: right;
            text-transform: uppercase;
            font-size: 11pt;
        }
        .grand-total-value {
            padding: 10px 5px;
            text-align: right;
            font-size: 11pt;
        }
        .terms-section {
            margin-top: 40px;
            font-size: 9pt;
            width: 60%;
            float: left;
        }
        .terms-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .terms-list {
            margin: 0;
            padding-left: 15px;
        }
        .signature-section {
            margin-top: 40px;
            float: right;
            width: 30%;
            text-align: center;
        }
        .signature-box {
            height: 100px;
            position: relative;
        }
        .signature-img {
            max-width: 120px;
            max-height: 80px;
        }
        .signer-name {
            border-top: 1px solid #000;
            padding-top: 5px;
            display: inline-block;
            width: 100%;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-td">
                    @php
                        $logoPath = public_path('logo-tigatek-mini.png');
                        $logoData = "";
                        if(file_exists($logoPath)){
                            $logoData = base64_encode(file_get_contents($logoPath));
                        }
                    @endphp
                    @if($logoData)
                        <img src="data:image/png;base64,{{ $logoData }}" class="logo-img" alt="Logo">
                    @else
                        <div style="color: #c9a227; font-size: 30pt; font-weight: bold;">T</div>
                    @endif
                </td>
                <td class="info-td">
                    <div class="company-name">PT. TIGA TEKNOLOGI PERSADA</div>
                    <div class="company-info">
                        Jl. H. Syahrin Blok 3C/5, Kebayoran Baru, Jakarta Selatan<br>
                        Email: sales@tigatek.id | www.tigatek.id
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="po-title-box">
        <div class="po-title">PURCHASE ORDER</div>
    </div>
    <div class="clearfix"></div>

    <div class="info-section">
        <div class="client-info">
            <b>Purchase Order to :</b>
            <div style="font-weight: bold; font-size: 12pt;">{{ $entry->subkon->name ?? '-' }}</div>
            <div style="width: 80%;">
                {!! nl2br(e($entry->subkon->address ?? '-')) !!}
            </div>
            <div>
                {{ $entry->subkon->phone ?? '-' }}
            </div>
        </div>
        <div class="po-meta">
            <table>
                <tr>
                    <td class="label">PO</td>
                    <td>: &nbsp; {{ $entry->po_number }}</td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td>: &nbsp; {{ \Carbon\Carbon::parse($entry->date_po)->format('d / m / Y') }}</td>
                </tr>
            </table>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%">No.</th>
                    <th width="50%" style="text-align: left;">Item</th>
                    <th width="10%">Qty</th>
                    <th width="15%">Unit Price</th>
                    <th width="20%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        {{ $entry->job_name }}<br>
                        <span style="font-size: 9pt; color: #555;">{{ $entry->job_description }}</span>
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-right">
                        <span style="float: left;">Rp</span> {{ number_format($entry->job_value, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        <span style="float: left;">Rp</span> {{ number_format($entry->job_value, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" style="border: none;"></td>
                    <td class="grand-total-label">GRAND TOTAL</td>
                    <td class="grand-total-value">
                        <span style="float: left;">Rp</span> {{ number_format($entry->job_value, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="terms-section">
        <div class="terms-title">Terms :</div>
        <ol class="terms-list">
            <li>Exclude All taxes extra</li>
            <li>Price for JABODETABEK</li>
            <li>Terms of payment : 
                <br>&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;30 days after final invoice received
            </li>
        </ol>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            {{-- Placeholder for signature --}}
            <br><br><br>
        </div>
        <div class="signer-name">
            <b>{{ backpack_user()->name ?? 'Defina Maharani' }}</b>
        </div>
    </div>
    <div class="clearfix"></div>

</body>
</html>
