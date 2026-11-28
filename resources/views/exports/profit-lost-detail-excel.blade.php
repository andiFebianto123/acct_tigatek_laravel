<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-header h1, .report-header h2 {
            margin: 0;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
            padding: 6px 10px;
        }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
    <table>
    <tr>
        <td colspan="2" style="text-align:center; font-weight:bold;">LAPORAN LABA RUGI</td>
    </tr>
    <tr>
        <td colspan="2" style="text-align:center; font-weight:bold;">({{ $profit_lost->clientPo->job_name ?? '-' }})</td>
    </tr>
    @php
        $cat = $profit_lost->clientPo->category ?? '';
        $catClean = strtoupper(str_replace(['_', ' '], '', $cat));
        $isNonReimburse = in_array($catClean, ['NONREIMBURSE', 'NONREIMBURSMENT']);
    @endphp
    @if(!empty($cat) && !$isNonReimburse)
    <tr>
        <td colspan="2" style="text-align:center;">({{ $cat }})</td>
    </tr>
    @endif
</table>

<br>

<table border="1">
    <tr>
        <td width="60%"><b>A. Pendapatan Invoice (exc PPn)</b></td>
        <td align="right"><b>{{ $report['price_po_excl_ppn'] }}</b></td>
    </tr>

    <tr>
        <td colspan="2"><b>B. Biaya Pekerjaan Proyek</b></td>
    </tr>
    <tr>
        <td>Jenis Biaya</td>
        <td align="right">Nilai (Rp)</td>
    </tr>
    @if(($report['price_konsultasi_raw'] ?? $report['price_konsultasi'] ?? 0) > 0)
    <tr>
        <td>Biaya Konsultasi</td>
        <td align="right">{{ $report['price_konsultasi'] }}</td>
    </tr>
    @endif
    @if(($report['price_manajemen_raw'] ?? $report['price_manajemen'] ?? 0) > 0)
    <tr>
        <td>Biaya Manajemen Fee</td>
        <td align="right">{{ $report['price_manajemen'] }}</td>
    </tr>
    @endif
    @if(($report['price_other_raw'] ?? $report['price_other'] ?? 0) > 0)
    <tr>
        <td>Biaya Lainnya</td>
        <td align="right">{{ $report['price_other'] }}</td>
    </tr>
    @endif
    @if(($report['price_after_year_raw'] ?? $report['price_after_year'] ?? 0) > 0)
    <tr>
        <td>{{ __('backpack::crud.profit_lost.fields.price_after_year.label') }}</td>
        <td align="right">{{ $report['price_after_year'] }}</td>
    </tr>
    @endif
    @if(($report['price_small_cash_raw'] ?? $report['price_small_cash'] ?? 0) > 0)
    <tr>
        <td>{{ __('backpack::crud.profit_lost.fields.price_small_cash.label') }}</td>
        <td align="right">{{ $report['price_small_cash'] }}</td>
    </tr>
    @endif
    <tr>
        <td><b>Total Biaya</b></td>
        <td align="right"><b>{{ $report['price_total'] }}</b></td>
    </tr>

    <tr>
        <td><b>C. Laba Rugi invoice</b></td>
        <td align="right"><b>{{ $report['price_profit_lost_po'] }}</b></td>
    </tr>
    @if(($report['price_general_raw'] ?? $report['price_general'] ?? 0) > 0)
    <tr>
        <td><b>{{ __('backpack::crud.profit_lost.fields.price_general.label') }}</b></td>
        <td align="right"><b>{{ $report['price_general'] }}</b></td>
    </tr>
    @endif
    <tr>
        <td><b>D. Laba Akhir</b></td>
        <td align="right"><b>{{ $report['price_profit_final'] }}</b></td>
    </tr>
    <tr>
        <td><b>E. prosentase margin (%)</b></td>
        <td align="right"><b>{{ $report['margin_percent'] ?? '0 %' }}</b></td>
    </tr>
</table>

@if (!empty($report['vouchers']) && count($report['vouchers']) > 0)
<br>
<table>
    <tr>
        <td colspan="6" style="font-weight:bold;">Rincian Voucher Pengeluaran Proyek</td>
    </tr>
</table>
<table border="1">
    <thead>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <th width="5%" align="center">No</th>
            <th width="20%">No. Voucher</th>
            <th width="15%" align="center">Tgl Voucher</th>
            <th width="25%">Akun Beban</th>
            <th width="20%">Deskripsi / No. Bill</th>
            <th width="15%" align="right">Nilai Biaya (Base)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($report['vouchers'] as $vIdx => $vItem)
            <tr>
                <td align="center">{{ $vIdx + 1 }}</td>
                <td>{{ $vItem['no_voucher'] }}</td>
                <td align="center">{{ $vItem['date_voucher'] }}</td>
                <td>{{ $vItem['account_name'] }}</td>
                <td>{{ $vItem['description'] }} @if(!empty($vItem['bill_number']) && $vItem['bill_number'] !== '-') (Bill: {{ $vItem['bill_number'] }}) @endif</td>
                <td align="right">{{ $vItem['bill_value_base'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

</body>
</html>
