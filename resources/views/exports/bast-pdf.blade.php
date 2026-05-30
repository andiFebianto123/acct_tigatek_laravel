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
        
        .bast-title-container {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .bast-title {
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .bast-subtitle {
            font-size: 11pt;
            font-weight: normal;
        }

        .intro {
            margin-bottom: 15px;
        }

        .party-section {
            width: 100%;
            margin-bottom: 15px;
        }
        .party-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .party-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .party-table .label {
            width: 12%;
        }
        .party-table .colon {
            width: 3%;
            text-align: center;
        }
        .party-table .value {
            width: 85%;
        }
        .party-role {
            font-weight: normal;
            margin-top: 2px;
            margin-bottom: 15px;
        }

        .statement {
            margin-top: 15px;
            margin-bottom: 15px;
            text-align: justify;
        }

        .items-section {
            margin-top: 15px;
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
            background-color: #ffffff;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 8px 5px;
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .closing {
            margin-bottom: 30px;
            text-align: justify;
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
            width: 50%;
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
                    <div class="po-title">BAST</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Title BAST -->
    <div class="bast-title-container">
        <div class="bast-title">BERITA ACARA SERAH TERIMA</div>
        <div class="bast-subtitle">No. {{ $entry->number ?? '-' }}</div>
    </div>

    <div class="intro">
        Yang bertanda tangan di bawah ini :
    </div>

    <!-- Pihak Pertama -->
    <div class="party-section">
        <table class="party-table">
            <tr>
                <td class="label">Nama</td>
                <td class="colon">:</td>
                <td class="value">{{ $entry->first_party ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td class="colon">:</td>
                <td class="value">{{ $entry->first_party_address ?? '-' }}</td>
            </tr>
        </table>
        <div class="party-role">Selanjutnya disebut <strong>Pihak Pertama</strong></div>
    </div>

    <!-- Pihak Kedua -->
    <div class="party-section">
        <table class="party-table">
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
                <td class="value">{{ $entry->address ?? '-' }}</td>
            </tr>
        </table>
        <div class="party-role">Selanjutnya disebut <strong>pihak Kedua</strong></div>
    </div>

    <!-- Statement penyerahan -->
    <div class="statement">
        <strong>Pihak Pertama</strong> telah menyerahkan barang kepada <strong>Pihak Kedua</strong> dan <strong>Pihak Kedua</strong> telah menerima barang dari <strong>Pihak Pertama</strong> dalam jumlah yang lengkap dan kondisi yang baik sesuai dengan rincian sebagai berikut :
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

    <!-- Penutup -->
    <div class="closing">
        Demikian Berita Acara Serah Terima ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.
    </div>

    <!-- Tanda Tangan -->
    @php
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $dateObj = $entry->date ? \Carbon\Carbon::parse($entry->date) : \Carbon\Carbon::now();
        $formattedDate = $dateObj->format('d') . ' ' . ($months[$dateObj->month] ?? $dateObj->format('F')) . ' ' . $dateObj->format('Y');
    @endphp
    <div class="signature-container">
        <table class="signature-table">
            <tr>
                <td>
                    Yang Menyerahkan
                </td>
                <td style="text-align: right; padding-right: 45px;">
                    Jakarta, {{ $formattedDate }}<br>
                    Yang Menerima
                </td>
            </tr>
            <tr>
                <td class="signature-box"></td>
                <td class="signature-box"></td>
            </tr>
            <tr>
                <td>
                    ( &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; )
                </td>
                <td style="text-align: right; padding-right: 45px;">
                    ( &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; )
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
