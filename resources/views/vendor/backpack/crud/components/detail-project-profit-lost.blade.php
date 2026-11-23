<div id="detail-project">
    <div class="text-center mb-2">
        <h5>LAPORAN LABA RUGI</h5>
        <h5>({{ $data->clientPo->job_name ?? '-' }})</h5>
        @php
            $cat = $data->clientPo->category ?? '';
            $catClean = strtoupper(str_replace(['_', ' '], '', $cat));
            $isNonReimburse = in_array($catClean, ['NONREIMBURSE', 'NONREIMBURSMENT']);
        @endphp
        @if(!empty($cat) && !$isNonReimburse)
            <h5>({{ $cat }})</h5>
        @endif
    </div>

    <!-- Ringkasan Finansial Laba Rugi -->
    <table class="report-table">
        <tbody>
            <tr>
                <td width="60%"><span class="bold">A. Pendapatan Invoice (exc PPn)</span></td>
                <td class="text-right">{{ $report['price_po_excl_ppn'] }}</td>
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
                        <tr class="total-row">
                            <td class="bold">Total Biaya</td>
                            <td class="text-right bold">{{ $report['price_total'] }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td><span class="bold">C. Laba Rugi invoice</span></td>
                <td class="text-right bold">{{ $report['price_profit_lost_po'] }}</td>
            </tr>

            <tr>
                <td><span class="bold">D. Laba Akhir</span></td>
                <td class="text-right bold">{{ $report['price_profit_final'] }}</td>
            </tr>

            <tr>
                <td><span class="bold">E. prosentase margin (%)</span></td>
                <td class="text-right bold">{{ $report['margin_percent'] ?? '0 %' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Rincian Detail Voucher Pengeluaran Proyek (Sama seperti Laporan Penjualan) -->
    @if (!empty($report['vouchers']) && count($report['vouchers']) > 0)
        <div class="mt-4">
            <h6 class="bold mb-2"><i class="la la-file-text"></i> Rincian Voucher Pengeluaran Proyek</h6>
            <table class="table table-bordered table-striped table-sm" style="font-size: 14px;">
                <thead class="thead-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="20%">No. Voucher</th>
                        <th width="12%">Tgl Voucher</th>
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
                            <td>{{ $vItem['date_voucher'] }}</td>
                            <td><span class="badge bg-secondary text-white">{{ $vItem['account_name'] }}</span></td>
                            <td>{{ $vItem['description'] }} @if(!empty($vItem['bill_number']) && $vItem['bill_number'] !== '-') (Bill: {{ $vItem['bill_number'] }}) @endif</td>
                            <td class="text-right bold">{{ $vItem['bill_value_base'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="row">
    <div class="col-md-6">

    </div>
</div>


@push('inline_scripts')
    <style>
        #detail-project p {
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
            border-collapse: collapse; /* Menyatukan border sel */
            /* border: 1px solid #000; */
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

        /* Menghilangkan padding dari sel yang berisi tabel lain */
        .no-padding {
            padding: 0;
        }
    </style>
@endpush
