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
            width: 52%;
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
            width: 44%;
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
            width: 25%;
            font-weight: bold;
            white-space: nowrap;
        }
        .po-meta .colon {
            width: 5%;
            text-align: center;
            font-weight: bold;
        }
        .po-meta .val {
            width: 70%;
            word-wrap: break-word;
            word-break: break-all;
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
            padding: 8px 4px;
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px 4px;
            vertical-align: middle;
            font-size: 10pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .col-price {
            white-space: nowrap;
        }
        
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
        .terms-content ul, .terms-content ol {
            margin: 0;
            padding-left: 15px;
            margin-bottom: 10px;
        }
        .terms-content p {
            margin: 0 0 5px 0;
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
        $currencyCode = $entry->currency_code ?? 'IDR';
        $isUsd = strtoupper($currencyCode) === 'USD';
        $symbol = $isUsd ? '$' : 'Rp';
        $decimals = $isUsd ? 2 : 0;
        $decPoint = $isUsd ? '.' : ',';
        $thousandsSep = $isUsd ? ',' : '.';

        $details = $entry->details;
        $hasDetails = $details && $details->count() > 0;

        if ($hasDetails) {
            $subtotal = (float) $details->sum('total_price');
        } else {
            $subtotal = (float) ($entry->job_value ?? 0);
        }

        $ppn_percent = (float) ($entry->tax_ppn ?? 11);
        $ppn_nominal = (float) ($subtotal * $ppn_percent / 100);
        $grand_total = (float) ($entry->job_value_include_ppn ?? ($subtotal + $ppn_nominal));
        if ($hasDetails && (!$entry->job_value_include_ppn || $entry->job_value_include_ppn == 0)) {
            $grand_total = $subtotal + $ppn_nominal;
        }
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-td">
                    @php
                        $logoData = "";
                        $mimeType = 'image/png';
                        $company = $entry->company ?? null;
                        if ($company && !empty($company->logo)) {
                            $storagePath = storage_path('app/public/' . $company->logo);
                            $publicStoragePath = public_path('storage/' . $company->logo);
                            $targetPath = file_exists($storagePath) ? $storagePath : (file_exists($publicStoragePath) ? $publicStoragePath : null);
                            if ($targetPath) {
                                $logoData = base64_encode(file_get_contents($targetPath));
                                $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                                $mimeType = match($ext) {
                                    'jpg', 'jpeg' => 'image/jpeg',
                                    'svg' => 'image/svg+xml',
                                    'webp' => 'image/webp',
                                    default => 'image/png',
                                };
                            }
                        }
                        if (!$logoData) {
                            $fallbackPath = public_path('logo-tigatek-mini.png');
                            if (file_exists($fallbackPath)) {
                                $logoData = base64_encode(file_get_contents($fallbackPath));
                                $mimeType = 'image/png';
                            }
                        }
                    @endphp
                    @if($logoData)
                        <img src="data:{{ $mimeType }};base64,{{ $logoData }}" class="logo-img" alt="Logo">
                    @else
                        <div style="color: #c9a227; font-size: 30pt; font-weight: bold;">{{ substr($company->name ?? 'T', 0, 1) }}</div>
                    @endif
                </td>
                <td class="info-td">
                    <div class="company-name">{{ $company->name ?? 'PT. TIGA TEKNOLOGI PERSADA' }}</div>
                    <div class="company-info">
                        @if($company)
                            {!! nl2br(e($company->address ?? '')) !!}
                            @if($company->city || $company->province)
                                <br>{{ implode(', ', array_filter([$company->city, $company->province, $company->postal_code])) }}
                            @endif
                            @if($company->phone)
                                <br>Telp: {{ $company->phone }}
                            @endif
                            @if($company->email || $company->website)
                                <br>{{ implode(' | ', array_filter([$company->email ? 'Email: ' . $company->email : null, $company->website ? $company->website : null])) }}
                            @endif
                        @else
                            Jl. H. Syahrin Blok 3C/5<br>
                            Keb, Baru. Jakarta Selatan
                        @endif
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
            {{-- @if(!empty($entry->pic))
                <div class="client-pic">Attn / PIC: {{ $entry->pic }}</div>
            @endif --}}
            <div style="width: 80%;">
                {!! nl2br(e($entry->client->address ?? 'Jakarta, Indonesia')) !!}
            </div>
        </div>
        @php
            $validityDays = null;
            if (!empty($entry->start_date) && !empty($entry->end_date)) {
                $start = \Carbon\Carbon::parse($entry->start_date);
                $end = \Carbon\Carbon::parse($entry->end_date);
                $validityDays = $start->diffInDays($end);
            }
        @endphp

        <div class="po-meta">
            <table>
                <tr>
                    <td class="label">Nomor</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $entry->po_number ?? $entry->work_code ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $entry->date_po ? \Carbon\Carbon::parse($entry->date_po)->format('d / m / Y') : \Carbon\Carbon::parse($entry->created_at)->format('d / m / Y') }}</td>
                </tr>
                @if($validityDays !== null)
                <tr>
                    <td class="label">Valid</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $validityDays }} hari</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Currency</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $currencyCode }}</td>
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
                    <th width="5%">No.</th>
                    <th width="43%" style="text-align: left;">Desc</th>
                    <th width="10%">QTY</th>
                    <th width="21%">Unit price</th>
                    <th width="21%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @if($hasDetails)
                    @foreach($details as $index => $detail)
                        @php
                            $qty = (float) $detail->qty;
                            $unitPrice = (float) ($detail->unit_price ?? $detail->price ?? 0);
                            $totalPrice = (float) ($detail->total_price ?? ($qty * $unitPrice));
                            $itemName = $detail->item_name ?? optional($detail->deviceStock)->name ?? '-';
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td style="text-align: left;">
                                {!! nl2br(e($itemName)) !!}
                            </td>
                            <td class="text-center">{{ $qty }} {{ $detail->unit ?? '' }}</td>
                            <td class="text-right col-price">
                                <span style="float: left;">{{ $symbol }}</span> {{ number_format($unitPrice, $decimals, $decPoint, $thousandsSep) }}
                            </td>
                            <td class="text-right col-price">
                                <span style="float: left;">{{ $symbol }}</span> {{ number_format($totalPrice, $decimals, $decPoint, $thousandsSep) }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-center">1</td>
                        <td style="text-align: left;">
                            {{ $entry->job_name ?? '-' }}
                        </td>
                        <td class="text-center">1</td>
                        <td class="text-right col-price">
                            <span style="float: left;">{{ $symbol }}</span> {{ number_format($subtotal, $decimals, $decPoint, $thousandsSep) }}
                        </td>
                        <td class="text-right col-price">
                            <span style="float: left;">{{ $symbol }}</span> {{ number_format($subtotal, $decimals, $decPoint, $thousandsSep) }}
                        </td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid #000;">
                    <td colspan="4" class="text-right" style="padding: 6px 4px; font-weight: normal;">TOTAL</td>
                    <td class="text-right col-price" style="padding: 6px 4px;">
                        <span style="float: left;">{{ $symbol }}</span> {{ number_format($subtotal, $decimals, $decPoint, $thousandsSep) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right" style="padding: 4px 4px; font-weight: normal;">PPN {{ $ppn_percent }}%</td>
                    <td class="text-right col-price" style="padding: 4px 4px;">
                        <span style="float: left;">{{ $symbol }}</span> {{ number_format($ppn_nominal, $decimals, $decPoint, $thousandsSep) }}
                    </td>
                </tr>
                <tr style="border-top: 0px solid #000; border-bottom: 0px solid #000;">
                    <td colspan="4" class="text-right" style="padding: 6px 4px; font-weight: bold; font-size: 11pt;">GRAND TOTAL</td>
                    <td class="text-right col-price" style="padding: 6px 4px; font-weight: bold; font-size: 11pt;">
                        <span style="float: left;">{{ $symbol }}</span> {{ number_format($grand_total, $decimals, $decPoint, $thousandsSep) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="bottom-section">
        <div class="terms-section">
            <div class="terms-title">Terms :</div>
            @if(!empty($entry->term))
                <div class="terms-content">
                    {!! $entry->term !!}
                </div>
            @else
                <ol class="terms-list">
                    <li>FOB Jabodetabek</li>
                    <li>Include PPN {{ $ppn_percent }}%</li>
                    <li>Terms Of Payment :
                        <br>&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;100% After Invoice Submitted
                    </li>
                </ol>
            @endif
            
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
                    <td>: &nbsp; {{ $company->name ?? 'PT. Tiga Teknologi Persada' }}</td>
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
            <div class="signer-name">{{ !empty($entry->pic) ? $entry->pic : (backpack_user()->name ?? 'Defina Maharani') }}</div>
        </div>
        <div class="clearfix"></div>
    </div>

</body>
</html>
