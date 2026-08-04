<table>
    <thead>
        <tr>
            <th colspan="2" style="font-weight: bold; text-align: center; font-size: 14pt;">LAPORAN LABA RUGI SUPPLIER</th>
        </tr>
        <tr>
            <th colspan="2" style="text-align: center; font-size: 12pt;">No. Invoice: {{ $report['invoice_number'] ?? '-' }}</th>
        </tr>
        <tr>
            <th colspan="2" style="text-align: center; font-size: 11pt;">Supplier / Klien: {{ $report['supplier_name'] ?? '-' }}</th>
        </tr>
        <tr>
            <th colspan="2" style="text-align: center; font-size: 10pt;">Tanggal: {{ $report['invoice_date'] ?? '-' }} | Currency: {{ $report['currency_code'] ?? 'IDR' }}</th>
        </tr>
        <tr></tr>
        <tr>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2;">Deskripsi Komponen</th>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2;">Nilai (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="font-weight: bold;">A. Pendapatan Penjualan (exc PPn)</td>
            <td style="font-weight: bold; text-align: right;">{{ $report['sell_value'] ?? 0 }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">B. Modal & Biaya (HPP)</td>
            <td></td>
        </tr>
        <tr>
            <td style="padding-left: 20px;">   1. Total Modal HPP FIFO Awal (Gross)</td>
            <td style="text-align: right;">{{ $report['cogs_gross'] ?? 0 }}</td>
        </tr>
        <tr>
            <td style="padding-left: 20px;">   2. Biaya Lain - Lain (Voucher Supplier)</td>
            <td style="text-align: right;">{{ $report['voucher_supplier_value'] ?? 0 }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding-left: 20px;">   Total HPP Modal Bersih (Net)</td>
            <td style="font-weight: bold; text-align: right;">{{ $report['purchase_value'] ?? 0 }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">C. Laba Rugi Supplier</td>
            <td style="font-weight: bold; text-align: right;">{{ $report['profit_supplier'] ?? 0 }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">D. Persentase Margin (%)</td>
            <td style="font-weight: bold; text-align: right;">{{ $report['margin_percent'] ?? 0 }}</td>
        </tr>
    </tbody>
</table>

@if (!empty($report['items']) && count($report['items']) > 0)
    <table>
        <tr></tr>
        <thead>
            <tr>
                <th colspan="5" style="font-weight: bold;">Rincian Barang Terjual (FIFO)</th>
            </tr>
            <tr>
                <th style="font-weight: bold; text-align: center; background-color: #e6e6e6;">No</th>
                <th style="font-weight: bold; text-align: center; background-color: #e6e6e6;">Nama Barang / Item</th>
                <th style="font-weight: bold; text-align: center; background-color: #e6e6e6;">Qty</th>
                <th style="font-weight: bold; text-align: center; background-color: #e6e6e6;">Harga Jual</th>
                <th style="font-weight: bold; text-align: center; background-color: #e6e6e6;">HPP Modal (FIFO)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['items'] as $idx => $item)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td style="text-align: center;">{{ $item['qty'] }}</td>
                    <td style="text-align: right;">{{ $item['revenue_raw'] ?? 0 }}</td>
                    <td style="text-align: right;">{{ $item['cogs_raw'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if (!empty($report['vouchers']) && count($report['vouchers']) > 0)
    <table>
        <tr></tr>
        <thead>
            <tr>
                <th colspan="5" style="font-weight: bold;">Rincian Voucher Supplier (Biaya Lain - Lain)</th>
            </tr>
            <tr>
                <th style="font-weight: bold; text-align: center; background-color: #e6e6e6;">No</th>
                <th style="font-weight: bold; text-align: center; background-color: #e6e6e6;">No. Voucher</th>
                <th style="font-weight: bold; text-align: center; background-color: #e6e6e6;">Tgl Voucher</th>
                <th style="font-weight: bold; text-align: center; background-color: #e6e6e6;">Deskripsi / No. Bill</th>
                <th style="font-weight: bold; text-align: center; background-color: #e6e6e6;">Nilai Biaya (Base)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['vouchers'] as $vIdx => $vItem)
                <tr>
                    <td style="text-align: center;">{{ $vIdx + 1 }}</td>
                    <td>{{ $vItem['no_voucher'] }}</td>
                    <td>{{ $vItem['date_voucher'] }}</td>
                    <td>{{ $vItem['description'] }} (Bill: {{ $vItem['bill_number'] }})</td>
                    <td style="text-align: right; font-weight: bold;">{{ $vItem['bill_value_base_raw'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
