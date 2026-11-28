<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laba Rugi</title>
    <style>
        /* Gaya dasar untuk keseluruhan halaman */
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
        }

        /* Kontainer utama untuk mengatur lebar dan posisi */
        /* .container {
            width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #000;
        } */

        /* Gaya untuk judul laporan */
        .report-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .report-header h1 {
            margin: 0;
            font-size: 18px;
        }

        .report-header h2 {
            margin: 5px 0;
            font-size: 16px;
            font-weight: normal;
        }

        /* Gaya untuk tabel utama */
        .report-table {
            width: 100%;
            border-collapse: collapse; /* Menyatukan border sel */
            /* border: 1px solid #000; */
            font-size: 16px;
        }

        .report-table th,
        .report-table td {
            /* border: 1px solid #000; */
            padding: 8px 12px;
            vertical-align: top;
        }

        /* Tabel bersarang untuk rincian biaya */
        .nested-table {
            width: 100%;
            border-collapse: collapse;
        }

        .nested-table td {
            border: none; /* Hilangkan semua border di tabel dalam */
            padding: 4px 0;
        }

        /* Menambahkan garis bawah pada setiap item biaya */
        .cost-item td {
            /* border-bottom: 1px solid #eee; */
        }

        /* Baris Total Biaya dengan garis atas */
        .total-row td {
            /* border-top: 1px solid #000; */
            padding-top: 12px;
            font-weight: bold;
        }

        /* Kelas utilitas untuk styling */
        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        /* Menghilangkan padding dari sel yang berisi tabel lain */
        .no-padding {
            padding: 0;
        }

        /* Memberi indentasi pada rincian biaya */
        .indent {
            padding-left: 20px !important;
        }

        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 15px;
        }

        .table-data th, .table-data td {
            border: 1px solid #ccc;
            padding: 5px 8px;
        }

        .table-data th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .section-header {
            font-size: 13px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 5px;
        }

        .text-center {
            text-align: center;
        }

    </style>
</head>
<body>

    <div class="container">
        <div class="report-header">
            <h1><b>LAPORAN LABA RUGI</b></h1>
            <h2><b>({{ $profit_lost->clientPo->job_name ?? '-' }})</b></h2>
            @php
                $cat = $profit_lost->clientPo->category ?? '';
                $catClean = strtoupper(str_replace(['_', ' '], '', $cat));
                $isNonReimburse = in_array($catClean, ['NONREIMBURSE', 'NONREIMBURSMENT']);
            @endphp
            @if(!empty($cat) && !$isNonReimburse)
                <h2>({{ $cat }})</h2>
            @endif
        </div>

        <table class="report-table">
            <tbody>
                <tr>
                    <td width="60%"><span class="bold">A. Pendapatan Invoice (exc PPn)</span></td>
                    <td class="text-right bold">{{ $report['price_po_excl_ppn'] }}</td>
                </tr>

                <tr>
                    <td colspan="2">
                        <table class="nested-table">
                            <tr>
                                <td colspan="2" class="bold">B. Biaya Pekerjaan Proyek</td>
                            </tr>
                            <tr>
                                <td><center>Jenis Biaya</center></td>
                                <td><center>Nilai (Rp)</center></td>
                            </tr>
                            @if(($report['price_konsultasi_raw'] ?? 0) > 0)
                                <tr>
                                    <td class="indent">Biaya Konsultasi</td>
                                    <td class="text-right">{{ $report['price_konsultasi'] }}</td>
                                </tr>
                            @endif
                            @if(($report['price_manajemen_raw'] ?? 0) > 0)
                                <tr>
                                    <td class="indent">Biaya Manajemen Fee</td>
                                    <td class="text-right">{{ $report['price_manajemen'] }}</td>
                                </tr>
                            @endif
                            @if(($report['price_other_raw'] ?? 0) > 0)
                                <tr>
                                    <td class="indent">Biaya Lainnya</td>
                                    <td class="text-right">{{ $report['price_other'] }}</td>
                                </tr>
                            @endif
                            @if(($report['price_after_year_raw'] ?? 0) > 0)
                                <tr>
                                    <td class="indent">{{ __('backpack::crud.profit_lost.fields.price_after_year.label') }}</td>
                                    <td class="text-right">{{ $report['price_after_year'] }}</td>
                                </tr>
                            @endif
                            @if(($report['price_small_cash_raw'] ?? 0) > 0)
                                <tr>
                                    <td class="indent">{{ __('backpack::crud.profit_lost.fields.price_small_cash.label') }}</td>
                                    <td class="text-right">{{ $report['price_small_cash'] }}</td>
                                </tr>
                            @endif
                            <tr class="total-row">
                                <td class="bold">Total Biaya</td>
                                <td class="text-right bold">{{ $report['price_total'] }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr class="total-row">
                    <td><span class="bold">C. Laba Rugi invoice</span></td>
                    <td class="text-right bold">{{ $report['price_profit_lost_po'] }}</td>
                </tr>

                @if(($report['price_general_raw'] ?? 0) > 0)
                    <tr class="total-row">
                        <td><span class="bold">{{ __('backpack::crud.profit_lost.fields.price_general.label') }}</span></td>
                        <td class="text-right bold">{{ $report['price_general'] }}</td>
                    </tr>
                @endif

                <tr class="total-row">
                    <td><span class="bold">D. Laba Akhir</span></td>
                    <td class="text-right bold">{{ $report['price_profit_final'] }}</td>
                </tr>

                <tr class="total-row">
                    <td><span class="bold">E. prosentase margin (%)</span></td>
                    <td class="text-right bold">{{ $report['margin_percent'] ?? '0 %' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Rincian Voucher Pengeluaran Proyek -->
        @if (!empty($report['vouchers']) && count($report['vouchers']) > 0)
            <div class="section-header">Rincian Voucher Pengeluaran Proyek</div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="20%">No. Voucher</th>
                        <th width="12%" class="text-center">Tgl Voucher</th>
                        <th width="25%">Akun Beban</th>
                        <th width="23%">Deskripsi / No. Bill</th>
                        <th width="15%" class="text-right">Nilai Biaya (Base)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['vouchers'] as $vIdx => $vItem)
                        <tr>
                            <td class="text-center">{{ $vIdx + 1 }}</td>
                            <td>{{ $vItem['no_voucher'] }}</td>
                            <td class="text-center">{{ $vItem['date_voucher'] }}</td>
                            <td>{{ $vItem['account_name'] }}</td>
                            <td>{{ $vItem['description'] }} @if(!empty($vItem['bill_number']) && $vItem['bill_number'] !== '-') (Bill: {{ $vItem['bill_number'] }}) @endif</td>
                            <td class="text-right bold">{{ $vItem['bill_value_base'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>

</body>
</html>
