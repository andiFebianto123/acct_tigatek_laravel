<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin-top: 5cm;
            margin-bottom: 30px;
            margin-left: 45px;
            margin-right: 45px;
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
            font-size: 24pt;
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
        .client-phone {
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
        .items-section {
            margin-top: 30px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px 5px;
            text-align: center;
            font-size: 10.5pt;
            font-weight: bold;
        }
        .items-table td {
            padding: 8px 5px;
            vertical-align: top;
            font-size: 10.5pt;
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
            font-size: 10pt;
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
            background-color: #f59e0b;
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
        $currencyCode = $header->currency_code ?? 'IDR';
        $isUsd = strtoupper($currencyCode) === 'USD';
        $symbol = $isUsd ? '$' : 'Rp';
        $decimals = $isUsd ? 2 : 0;
        $decPoint = $isUsd ? '.' : ',';
        $thousandsSep = $isUsd ? ',' : '.';

        $subtotal = (float) ($header->price_total_exclude_ppn ?? 0);
        $grand_total = (float) ($header->price_total_include_ppn ?? 0);
        
        if (isset($header->tax_ppn) && $header->tax_ppn !== null && $header->tax_ppn !== '') {
            $ppn_percent = (float) $header->tax_ppn;
        } elseif ($subtotal > 0 && $grand_total > $subtotal) {
            $ppn_percent = round((($grand_total - $subtotal) / $subtotal) * 100, 2);
        } else {
            $ppn_percent = 11;
        }

        $ppn_nominal = $grand_total > $subtotal ? ($grand_total - $subtotal) : (($subtotal * $ppn_percent) / 100);
        if ($grand_total == 0 && $subtotal > 0) {
            $grand_total = $subtotal + $ppn_nominal;
        }
        
        $ppn_percent_display = (float)$ppn_percent == (int)$ppn_percent ? (int)$ppn_percent : (float)$ppn_percent;
        
        $items = [];
        if (isset($details) && count($details) > 0) {
            foreach ($details as $detail) {
                $itemName = !empty($detail->name) ? $detail->name : ($detail->device_stock?->name ?? '-');
                $items[] = (object)[
                    'name' => $itemName,
                    'price' => $detail->price,
                    'qty' => $detail->qty ?? 1
                ];
            }
        } else {
            $items[] = (object)[
                'name' => $header->client_po->job_name ?? $header->name,
                'price' => $subtotal,
                'qty' => 1
            ];
        }
    @endphp

    <div class="header">
        <table class="header-table">
                <td class="logo-td">
                    @php
                        $logoData = "";
                        $mimeType = 'image/png';
                        $company = $header->company ?? $entry->company ?? null;
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
                    <div class="po-title">PROFORMA INVOICE</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <div class="client-info">
            <b>Invoice to :</b>
            <div class="client-name">{{ $header->subkon->name ?? '-' }}</div>
            <div class="client-phone">{{ $header->subkon->phone ?? '-' }}</div>
            <div style="width: 80%;">
                {!! nl2br(e($header->subkon->address ?? '-')) !!}
            </div>
        </div>
        <div class="po-meta">
            <table>
                <tr>
                    <td class="label">No</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $header->invoice_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $header->invoice_date ? \Carbon\Carbon::parse($header->invoice_date)->format('d / m / Y') : \Carbon\Carbon::parse($header->created_at)->format('d / m / Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Currency</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $currencyCode }}</td>
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
                    <th width="43%" style="text-align: left;">DESCRIPTION</th>
                    <th width="10%">QTY</th>
                    <th width="21%">PRICE PER UNIT</th>
                    <th width="21%">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $key => $item)
                    @php
                        $qty = (int) ($item->qty ?? 1);
                        if ($qty <= 1) {
                            $qty = 1;
                            $unit_price = $item->price;

                            if (str_contains(strtolower($item->name), 'gps tracker eg02') && (int)$item->price == 22500000) {
                                $qty = 50;
                                $unit_price = 450000;
                            } else {
                                if (preg_match('/[\(\[\s](\d+)\s*(pcs|unit|box|pack|x)?[\)\]\s]/i', $item->name, $matches)) {
                                    $parsed_qty = (int)$matches[1];
                                    if ($parsed_qty > 0 && (int)$item->price % $parsed_qty == 0) {
                                        $qty = $parsed_qty;
                                        $unit_price = (int)$item->price / $parsed_qty;
                                    }
                                }
                            }
                        } else {
                            $unit_price = (float)$item->price;
                        }
                    @endphp

                    <tr>
                        <td class="text-center">{{ $key + 1 }}.</td>
                        <td style="text-align: left;">{{ $item->name }}</td>
                        <td class="text-center">{{ $qty }}</td>
                        <td class="text-right col-price">
                            {{ $symbol }} {{ number_format($unit_price, $decimals, $decPoint, $thousandsSep) }}
                        </td>
                        <td class="text-right col-price">
                            {{ $symbol }} {{ number_format($qty * $unit_price, $decimals, $decPoint, $thousandsSep) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right" style="border-top: 2px solid #000; padding: 6px 4px; font-weight: normal;">TOTAL</td>
                    <td class="text-right col-price" style="border-top: 2px solid #000; padding: 6px 4px;">
                        {{ $symbol }} {{ number_format($subtotal, $decimals, $decPoint, $thousandsSep) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right" style="padding: 4px 4px; font-weight: normal;">PPN {{ $ppn_percent_display }}%</td>
                    <td class="text-right col-price" style="padding: 4px 4px;">
                        {{ $symbol }} {{ number_format($ppn_nominal, $decimals, $decPoint, $thousandsSep) }}
                    </td>
                </tr>
                <tr style="border-top: 0px solid #000; border-bottom: 0px solid #000;">
                    <td colspan="4" class="text-right" style="padding: 6px 4px; font-weight: bold; font-size: 11pt;">GRAND TOTAL</td>
                    <td class="text-right col-price" style="padding: 6px 4px; font-weight: bold; font-size: 11pt;">
                        {{ $symbol }} {{ number_format($grand_total, $decimals, $decPoint, $thousandsSep) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="bottom-section">
        <div class="terms-section">
            <div class="terms-title">Terms :</div>
            @if(!empty($header->term))
                <div class="terms-content">
                    {!! $header->term !!}
                </div>
            @else
                <ol class="terms-list">
                    <li>Include PPN {{ $ppn_percent_display }}%</li>
                    <li>Include shipping costs</li>
                    <li>Terms Of Payment :
                        <br>&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;100% before device delivered
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
                    <td class="label">Address</td>
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
                <br><br><br>
            </div>
            <div class="orange-line"></div>
            <div class="signer-name">{{ !empty($header->pic) ? $header->pic : (backpack_user()->name ?? 'Defina Maharani') }}</div>
        </div>
        <div class="clearfix"></div>
    </div>

</body>
</html>
