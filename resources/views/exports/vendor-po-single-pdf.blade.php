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
            width: 52%;
        }
        .client-info b {
            display: block;
            margin-bottom: 5px;
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
            padding: 2px 0;
            vertical-align: top;
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
        .terms-content ul, .terms-content ol {
            margin: 0;
            padding-left: 15px;
            margin-bottom: 10px;
        }
        .terms-content p {
            margin: 0 0 5px 0;
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
                            Jl. H. Syahrin Blok 3C/5, Kebayoran Baru, Jakarta Selatan<br>
                            Email: sales@tigatek.id | www.tigatek.id
                        @endif
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
                    <td class="colon">:</td>
                    <td class="val">{{ $entry->po_number }}</td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td class="colon">:</td>
                    <td class="val">{{ \Carbon\Carbon::parse($entry->date_po)->format('d / m / Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Currency</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $entry->currency_code ?? 'IDR' }}</td>
                </tr>
            </table>
        </div>
        <div class="clearfix"></div>
    </div>

    @php
        $currencyCode = $entry->currency_code ?? 'IDR';
        $isUsd = strtoupper($currencyCode) === 'USD';
        $symbol = $isUsd ? '$' : 'Rp';
        $decimals = $isUsd ? 2 : 0;
        $decPoint = $isUsd ? '.' : ',';
        $thousandsSep = $isUsd ? ',' : '.';

        $hasDetails = isset($entry->purchase_order_details) && count($entry->purchase_order_details) > 0;
        if ($hasDetails) {
            $subtotal = 0;
            foreach ($entry->purchase_order_details as $detail) {
                $qty = (int)($detail->qty ?? 1);
                $price = (float)($detail->price ?? 0);
                $subtotal += ($qty * $price);
            }
        } else {
            $subtotal = (float)($entry->job_value ?? 0);
        }

        $ppn_percent = isset($entry->tax_ppn) && $entry->tax_ppn !== null ? (float)$entry->tax_ppn : 0;
        $ppn_nominal = (float)($subtotal * $ppn_percent / 100);
        $grand_total = (float)($entry->total_value_with_tax ?? ($subtotal + $ppn_nominal));
        if ($grand_total == 0 && $subtotal > 0) {
            $grand_total = $subtotal + $ppn_nominal;
        }
        $ppn_percent_display = (float)$ppn_percent == (int)$ppn_percent ? (int)$ppn_percent : (float)$ppn_percent;
    @endphp

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
                @if($hasDetails)
                    @foreach($entry->purchase_order_details as $index => $detail)
                        @php
                            $itemName = !empty($detail->name) ? $detail->name : ($detail->device_stock?->name ?? '-');
                            $qty = (int)($detail->qty ?? 1);
                            $price = (float)($detail->price ?? 0);
                            $amount = $qty * $price;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}.</td>
                            <td style="text-align: left;">{{ $itemName }}</td>
                            <td class="text-center">{{ $qty }}</td>
                            <td class="text-right col-price">
                                {{ $symbol }} {{ number_format($price, $decimals, $decPoint, $thousandsSep) }}
                            </td>
                            <td class="text-right col-price">
                                {{ $symbol }} {{ number_format($amount, $decimals, $decPoint, $thousandsSep) }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-center">1.</td>
                        <td style="text-align: left;">
                            {{ $entry->job_name }}<br>
                            <span style="font-size: 9pt; color: #555;">{{ $entry->job_description }}</span>
                        </td>
                        <td class="text-center">1</td>
                        <td class="text-right col-price">
                            {{ $symbol }} {{ number_format($subtotal, $decimals, $decPoint, $thousandsSep) }}
                        </td>
                        <td class="text-right col-price">
                            {{ $symbol }} {{ number_format($subtotal, $decimals, $decPoint, $thousandsSep) }}
                        </td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right" style="border-top: 2px solid #000; padding: 6px 4px; font-weight: normal;">TOTAL</td>
                    <td class="text-right col-price" style="border-top: 2px solid #000; padding: 6px 4px;">
                        {{ $symbol }} {{ number_format($subtotal, $decimals, $decPoint, $thousandsSep) }}
                    </td>
                </tr>
                @if($ppn_percent > 0 || $ppn_nominal > 0)
                <tr>
                    <td colspan="4" class="text-right" style="padding: 4px 4px; font-weight: normal;">PPN {{ $ppn_percent_display }}%</td>
                    <td class="text-right col-price" style="padding: 4px 4px;">
                        {{ $symbol }} {{ number_format($ppn_nominal, $decimals, $decPoint, $thousandsSep) }}
                    </td>
                </tr>
                @endif
                <tr style="border-top: 0px solid #000; border-bottom: 0px solid #000;">
                    <td colspan="4" class="text-right" style="padding: 6px 4px; font-weight: bold; font-size: 11pt;">GRAND TOTAL</td>
                    <td class="text-right col-price" style="padding: 6px 4px; font-weight: bold; font-size: 11pt;">
                        {{ $symbol }} {{ number_format($grand_total, $decimals, $decPoint, $thousandsSep) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="terms-section">
        <div class="terms-title">Terms :</div>
        @if(!empty($entry->term))
            <div class="terms-content">
                {!! $entry->term !!}
            </div>
        @else
            <ol class="terms-list">
                <li>Exclude All taxes extra</li>
                <li>Price for JABODETABEK</li>
                <li>Terms of payment : 
                    <br>&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;30 days after final invoice received
                </li>
            </ol>
        @endif
    </div>

    <div class="signature-section">
        <div class="signature-box">
            {{-- Placeholder for signature --}}
            <br><br><br>
        </div>
        <div class="signer-name">
            <b>{{ !empty($entry->pic) ? $entry->pic : (backpack_user()->name ?? 'Defina Maharani') }}</b>
        </div>
    </div>
    <div class="clearfix"></div>

</body>
</html>
