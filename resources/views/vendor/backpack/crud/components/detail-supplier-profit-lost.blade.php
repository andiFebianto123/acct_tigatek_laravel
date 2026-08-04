<div id="detail-supplier">
    <div class="text-center mb-2">
        <h5>LAPORAN LABA RUGI SUPPLIER</h5>
        <h5>({{ $report['invoice_number'] ?? '-' }})</h5>
        <h5>({{ $report['supplier_name'] ?? '-' }})</h5>
    </div>

    <!-- Ringkasan Keuangan Laba Rugi -->
    <table class="report-table">
        <tbody>
            <tr>
                <td width="60%"><span class="bold">A. Pendapatan Penjualan (exc PPn)</span></td>
                <td class="text-right">{{ $report['sell_value'] ?? 'Rp 0' }}</td>
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

    <!-- Rincian Detail Barang Terjual -->
    @if (!empty($report['items']) && count($report['items']) > 0)
        <div class="mt-4">
            <h6 class="bold mb-2"><i class="la la-cube"></i> Rincian Barang Terjual (FIFO)</h6>
            <table class="table table-bordered table-striped table-sm" style="font-size: 14px;">
                <thead class="thead-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="40%">Nama Barang / Item</th>
                        <th width="10%" class="text-center">Qty</th>
                        <th width="20%" class="text-right">Harga Jual</th>
                        <th width="25%" class="text-right">HPP Modal (FIFO)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['items'] as $idx => $item)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td><span class="bold">{{ $item['name'] }}</span></td>
                            <td class="text-center">{{ $item['qty'] }}</td>
                            <td class="text-right">{{ $item['revenue'] }}</td>
                            <td class="text-right">{{ $item['cogs'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Rincian Detail Voucher Supplier (Biaya Lain-Lain) -->
    @if (!empty($report['vouchers']) && count($report['vouchers']) > 0)
        <div class="mt-4">
            <h6 class="bold mb-2"><i class="la la-file-text"></i> Rincian Voucher Supplier (Biaya Lain - Lain)</h6>
            <table class="table table-bordered table-striped table-sm" style="font-size: 14px;">
                <thead class="thead-light">
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
                            <td><span class="bold">{{ $vItem['no_voucher'] }}</span></td>
                            <td>{{ $vItem['date_voucher'] }}</td>
                            <td>{{ $vItem['description'] }} (Bill: {{ $vItem['bill_number'] }})</td>
                            <td class="text-right font-weight-bold">{{ $vItem['bill_value_base'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('inline_scripts')
    <style>
        #detail-supplier p {
            font-size: 20px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 20px;
        }
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
            font-size: 20px;
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th,
        .report-table td {
            padding: 8px 12px;
            vertical-align: top;
        }

        /* Tabel bersarang untuk rincian biaya */
        .nested-table {
            width: 100%;
            border-collapse: collapse;
        }

        .nested-table td {
            border: none;
            padding: 4px 0;
        }

        .indent {
            padding-left: 20px !important;
        }

        /* Baris Total Biaya dengan garis atas */
        .total-row td {
            padding-top: 8px;
            font-weight: bold;
        }

        /* Kelas utilitas untuk styling */
        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .no-padding {
            padding: 0;
        }
    </style>
@endpush
