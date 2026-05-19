<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 30px 45px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.4;
        }
        .header {
            width: 100%;
            margin-bottom: 30px;
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
            font-size: 13pt;
            color: #000;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .po-title-td {
            text-align: right;
            vertical-align: middle;
        }
        .po-title {
            font-size: 32pt;
            font-weight: bold;
            letter-spacing: 1px;
            color: #000;
        }
        .clearfix {
            clear: both;
        }
        .info-section {
            width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .client-info {
            float: left;
            width: 55%;
            font-size: 11pt;
        }
        .client-info b {
            display: block;
            margin-bottom: 5px;
        }
        .client-name {
            font-weight: bold;
            font-size: 13pt;
            margin-bottom: 2px;
        }
        .client-pic {
            font-weight: bold;
            margin-bottom: 2px;
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
            padding: 3px 0;
            vertical-align: top;
            font-size: 11pt;
        }
        .po-meta .label {
            width: 40%;
            font-weight: bold;
        }
        .greeting {
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 10.5pt;
        }
        .items-section {
            margin-top: 10px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table thead tr {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        .items-table th {
            padding: 8px 5px;
            text-align: center;
            font-size: 10.5pt;
            font-weight: bold;
        }
        .items-table td {
            padding: 12px 5px;
            vertical-align: middle;
            font-size: 10.5pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        
        .totals-table-container {
            width: 100%;
            margin-top: 5px;
            border-top: 2px solid #000;
        }
        .totals-table {
            width: 45%;
            float: right;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .totals-table td {
            padding: 4px 5px;
            font-size: 11pt;
            vertical-align: middle;
        }
        .totals-table .label {
            text-align: right;
            font-weight: normal;
        }
        .totals-table .label-bold {
            text-align: right;
            font-weight: bold;
            font-size: 12pt;
        }
        .totals-table .val-bold {
            font-weight: bold;
            font-size: 12pt;
            text-align: right;
        }
        .bottom-section {
            margin-top: 40px;
            width: 100%;
        }
        .terms-section {
            float: left;
            width: 60%;
            font-size: 9pt;
        }
        .terms-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 9.5pt;
        }
        .terms-list {
            margin: 0;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .payment-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 9.5pt;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-table td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 9pt;
        }
        .payment-table .label {
            width: 30%;
            font-weight: bold;
        }
        .signature-section {
            float: right;
            width: 30%;
            text-align: center;
            margin-top: 10px;
        }
        .signature-box {
            height: 90px;
            position: relative;
        }
        .signature-img {
            max-width: 120px;
            max-height: 80px;
        }
        .orange-line {
            height: 4px;
            background-color: #f59e0b; /* Orange color */
            border: none;
            width: 80%;
            margin: 0 auto 5px auto;
        }
        .signer-name {
            font-weight: bold;
            font-size: 10.5pt;
            margin-top: 5px;
            color: #000;
        }
    </style>
</head>
<body>
    @php
        $subtotal = $entry->job_value ?? 0;
        $ppn_percent = $entry->tax_ppn ?? 11;
        $ppn_nominal = ($subtotal * $ppn_percent) / 100;
        $grand_total = $entry->job_value_include_ppn ?? ($subtotal + $ppn_nominal);
    @endphp

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
                        Jl. H. Syahrin Blok 3C/5<br>
                        Keb, Baru. Jakarta Selatan
                    </div>
                </td>
                <td class="po-title-td">
                    <div class="po-title">QUOTATION</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <div class="client-info">
            <b>Quotation to :</b>
            <div class="client-name">{{ $entry->client->name ?? '-' }}</div>
            <div class="client-pic">Pic {{ $entry->pic ?? 'Bapak Wukir' }}</div>
            <div style="width: 80%;">
                {!! nl2br(e($entry->client->address ?? 'Jakarta, Indonesia')) !!}
            </div>
        </div>
        <div class="po-meta">
            <table>
                <tr>
                    <td class="label">Quotation</td>
                    <td>: &nbsp; {{ $entry->work_code ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td>: &nbsp; {{ $entry->date_po ? \Carbon\Carbon::parse($entry->date_po)->format('d / m / Y') : \Carbon\Carbon::parse($entry->created_at)->format('d / m / Y') }}</td>
                </tr>
            </table>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="greeting">
        Berikut daftar harga yang kami tawarkan :
    </div>

    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th width="8%">No.</th>
                    <th width="52%" style="text-align: left;">Desc</th>
                    <th width="8%">QTY</th>
                    <th width="16%">Unit price</th>
                    <th width="16%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td style="text-align: left;">
                        {{ $entry->job_name ?? '-' }}
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-right">
                        <span style="float: left;">Rp</span> {{ number_format($subtotal, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        <span style="float: left;">Rp</span> {{ number_format($subtotal, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="totals-table-container">
            <table class="totals-table">
                <tr>
                    <td class="label">TOTAL</td>
                    <td style="width: 15%;">Rp</td>
                    <td class="text-right">{{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">PPN {{ $ppn_percent }}%</td>
                    <td>Rp</td>
                    <td class="text-right">{{ number_format($ppn_nominal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label-bold">GRAND TOTAL</td>
                    <td class="val-bold">Rp</td>
                    <td class="val-bold">{{ number_format($grand_total, 0, ',', '.') }}</td>
                </tr>
            </table>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="bottom-section">
        <div class="terms-section">
            <div class="terms-title">Terms :</div>
            <ol class="terms-list">
                <li>FOB Jabodetabek</li>
                <li>Include PPN {{ $ppn_percent }}%</li>
                <li>Terms Of Payment :
                    <br>&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;100% After Invoice Submitted
                </li>
            </ol>
            
            <div class="payment-title">Payment to be made to</div>
            <table class="payment-table">
                <tr>
                    <td class="label">Account Number</td>
                    <td>: &nbsp; 2192222002</td>
                </tr>
                <tr>
                    <td class="label">Bank</td>
                    <td>: &nbsp; BCA</td>
                </tr>
                <tr>
                    <td class="label">Branch</td>
                    <td>: &nbsp; Radio Dalam</td>
                </tr>
                <tr>
                    <td class="label">Branch Address</td>
                    <td>: &nbsp; Jl. Radio Dalam Raya No.5, RT.3/RW.8<br>&nbsp;&nbsp; Daerah Khusus Ibukota Jakarta, 12140</td>
                </tr>
                <tr>
                    <td class="label">Name</td>
                    <td>: &nbsp; PT. Tiga Teknologi Persada</td>
                </tr>
                <tr>
                    <td class="label">Swift Code</td>
                    <td>: &nbsp; CENAIDJA</td>
                </tr>
            </table>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                {{-- Signature Image if available --}}
                <br><br><br>
            </div>
            <div class="orange-line"></div>
            <div class="signer-name">{{ backpack_user()->name ?? 'Defina Maharani' }}</div>
        </div>
        <div class="clearfix"></div>
    </div>

</body>
</html>
