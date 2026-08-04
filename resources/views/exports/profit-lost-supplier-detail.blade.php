<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laba Rugi Supplier</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #333;
            margin: 0;
            padding: 10px;
        }

        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .report-header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }

        .report-header h2 {
            margin: 4px 0;
            font-size: 14px;
            font-weight: normal;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .report-table td {
            padding: 6px 10px;
            vertical-align: top;
        }

        .nested-table {
            width: 100%;
            border-collapse: collapse;
        }

        .nested-table td {
            border: none;
            padding: 3px 0;
        }

        .indent {
            padding-left: 20px !important;
        }

        .total-row td {
            padding-top: 6px;
            font-weight: bold;
            border-top: 1px solid #ddd;
        }

        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 15px;
        }

        .table-data th, .table-data td {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }

        .table-data th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .section-header {
            font-size: 13px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>LAPORAN LABA RUGI SUPPLIER</h1>
        <h2>No. Invoice: {{ $report['invoice_number'] ?? '-' }}</h2>
        <h2>Supplier / Klien: {{ $report['supplier_name'] ?? '-' }}</h2>
        <h2>Tanggal Invoice: {{ $report['invoice_date'] ?? '-' }} | Currency: {{ $report['currency_code'] ?? 'IDR' }}</h2>
    </div>

    <!-- Ringkasan Finansial -->
    <table class="report-table">
        <tbody>
            <tr>
                <td width="65%"><span class="bold">A. Pendapatan Penjualan (exc PPn)</span></td>
                <td class="text-right bold">{{ $report['sell_value'] ?? 'Rp 0' }}</td>
            </tr>

            <tr>
                <td colspan="2">
                    <table class="nested-table">
                        <tr>
                            <td colspan="2" class="bold">B. Modal & Biaya (HPP)</td>
                        </tr>
                        <tr>
                            <td><center>Jenis Biaya</center></td>
                            <td><center>Nilai (Rp)</center></td>
                        </tr>
                        <tr>
                            <td class="indent">Total Modal HPP FIFO Awal (Gross)</td>
                            <td class="text-right">{{ $report['cogs_gross'] ?? 'Rp 0' }}</td>
                        </tr>
                        <tr>
                            <td class="indent">Biaya Lain - Lain (Voucher Supplier)</td>
                            <td class="text-right">{{ $report['voucher_supplier_value'] ?? 'Rp 0' }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="bold">Total HPP Modal Bersih (Net)</td>
                            <td class="text-right">{{ $report['purchase_value'] ?? 'Rp 0' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td><span class="bold">C. Laba Rugi Supplier</span></td>
                <td class="text-right bold">
                    @if (($report['profit_supplier_raw'] ?? 0) >= 0)
                        <span>{{ $report['profit_supplier'] }}</span>
                    @else
                        <span>({{ $report['profit_supplier'] }})</span>
                    @endif
                </td>
            </tr>

            <tr>
                <td><span class="bold">D. Persentase Margin (%)</span></td>
                <td class="text-right bold">{{ $report['margin_percent'] ?? '0%' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Rincian Barang Terjual -->
    @if (!empty($report['items']) && count($report['items']) > 0)
        <div class="section-header">Rincian Barang Terjual (FIFO)</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="45%">Nama Barang / Spesifikasi</th>
                    <th width="10%" class="text-center">Qty</th>
                    <th width="20%" class="text-right">Harga Jual</th>
                    <th width="20%" class="text-right">HPP Modal (FIFO)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['items'] as $idx => $item)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td class="text-center">{{ $item['qty'] }}</td>
                        <td class="text-right">{{ $item['revenue'] }}</td>
                        <td class="text-right">{{ $item['cogs'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Rincian Voucher Supplier (Biaya Lain-Lain) -->
    @if (!empty($report['vouchers']) && count($report['vouchers']) > 0)
        <div class="section-header">Rincian Voucher Supplier (Biaya Lain - Lain)</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="20%">No. Voucher</th>
                    <th width="15%">Tgl Voucher</th>
                    <th width="40%">Deskripsi / No. Bill</th>
                    <th width="20%" class="text-right">Nilai Biaya (Base)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['vouchers'] as $vIdx => $vItem)
                    <tr>
                        <td class="text-center">{{ $vIdx + 1 }}</td>
                        <td>{{ $vItem['no_voucher'] }}</td>
                        <td>{{ $vItem['date_voucher'] }}</td>
                        <td>{{ $vItem['description'] }} (Bill: {{ $vItem['bill_number'] }})</td>
                        <td class="text-right bold">{{ $vItem['bill_value_base'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
