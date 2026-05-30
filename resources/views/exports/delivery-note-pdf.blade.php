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
            line-height: 1.5;
        }
        .header {
            width: 100%;
            margin-bottom: 25px;
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
        
        .sj-title-container {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 25px;
        }
        .sj-title {
            font-size: 16pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .sj-subtitle {
            font-size: 11pt;
            font-weight: normal;
        }

        .meta-section {
            width: 100%;
            margin-bottom: 20px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .meta-table .label {
            width: 12%;
        }
        .meta-table .colon {
            width: 3%;
            text-align: center;
        }
        .meta-table .value {
            width: 85%;
        }

        .items-section {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 8px 5px;
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .notice-section {
            margin-top: 20px;
            margin-bottom: 25px;
            font-size: 10.5pt;
        }
        .notice-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        .notice-list {
            margin: 0;
            padding-left: 20px;
        }
        .notice-list li {
            margin-bottom: 3px;
        }

        .received-statement {
            font-style: italic;
            font-weight: bold;
            margin-bottom: 35px;
        }

        .signature-container {
            width: 100%;
            margin-top: 20px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            vertical-align: top;
            width: 33.33%;
            text-align: center;
        }
        .signature-box {
            height: 90px;
        }
    </style>
</head>
<body>

    <!-- Header disamakan persis dengan client quotation PDF -->
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
                    <div class="po-title"></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Title Surat Jalan -->
    <div class="sj-title-container">
        <div class="sj-title">SURAT JALAN</div>
        <div class="sj-subtitle">No : {{ $entry->number ?? '-' }}</div>
    </div>

    <!-- Informasi Pengiriman -->
    @php
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $dateObj = $entry->date ? \Carbon\Carbon::parse($entry->date) : \Carbon\Carbon::now();
        $formattedDate = $dateObj->format('d') . ' ' . ($months[$dateObj->month] ?? $dateObj->format('F')) . ' ' . $dateObj->format('Y');
    @endphp
    <div class="meta-section">
        <table class="meta-table">
            <tr>
                <td class="label">Tanggal</td>
                <td class="colon">:</td>
                <td class="value">{{ $formattedDate }}</td>
            </tr>
            <tr style="height: 10px;"><td colspan="3"></td></tr>
            <tr>
                <td class="label">Kepada</td>
                <td class="colon">:</td>
                <td class="value"><strong>{{ $entry->client->name ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td class="label">No. Telp</td>
                <td class="colon">:</td>
                <td class="value">{{ $entry->client->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td class="colon">:</td>
                <td class="value">{!! nl2br(e($entry->address ?? '-')) !!}</td>
            </tr>
        </table>
    </div>

    <!-- Tabel Rincian Barang -->
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th width="8%">No</th>
                    <th width="52%">Barang</th>
                    <th width="15%">Qty</th>
                    <th width="25%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>{{ $entry->description ?? '-' }}</td>
                    <td class="text-center">
                        @if(is_numeric($entry->qty))
                            {{ $entry->qty }} unit
                        @else
                            {{ $entry->qty }}
                        @endif
                    </td>
                    <td class="text-center">{{ $entry->information ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Perhatian -->
    <div class="notice-section">
        <div class="notice-title">Perhatian :</div>
        <ol class="notice-list">
            <li>Surat Jalan ini merupakan bukti resmi penerimaan barang</li>
            <li>Surat Jalan ini bukan bukti penjualan</li>
        </ol>
    </div>

    <!-- Pernyataan Penerimaan -->
    <div class="received-statement">
        BARANG SUDAH DITERIMA DALAM KEADAAN BAIK DAN CUKUP Oleh:
    </div>

    <!-- Tanda Tangan Tiga Kolom -->
    <div class="signature-container">
        <table class="signature-table">
            <tr>
                <td>Penerima</td>
                <td>Pengirim</td>
                <td>Mengetahui</td>
            </tr>
            <tr>
                <td class="signature-box"></td>
                <td class="signature-box"></td>
                <td class="signature-box"></td>
            </tr>
            <tr>
                <td>( &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; )</td>
                <td>( &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; )</td>
                <td>( &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; )</td>
            </tr>
        </table>
    </div>

</body>
</html>
