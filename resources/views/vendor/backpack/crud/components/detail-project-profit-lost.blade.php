<div id="detail-project">
    <div class="text-center mb-2">
        <h5>LAPORAN LABA RUGI</h5>
        <h5>({{$data->clientPo->job_name}})</h5>
        <h5>({{$data->clientPo->category}})</h5>
    </div>
    <table class="report-table">
        <tbody>
            <tr>
                <td width="60%"><span class="bold">A. Pendapatan Invoice (exc PPn)</span></td>
                <td class="text-right">{{$report['price_po_excl_ppn']}}</td>
            </tr>

            <tr>
                <td colspan="2">
                    <table class="nested-table">
                        <tr>
                            <td colspan="2" class="bold">B. Biaya Pekerjaan</td>
                        </tr>
                        <tr>
                            <td><center>Jenis Biaya</center></td>
                            <td><center>Nilai (Rp)</center></td>
                        </tr>
                        <tr>
                            <td class="indent">Material</td>
                            <td class="text-right">{{$report['price_material']}}</td>
                        </tr>
                        <tr>
                            <td class="indent">Biaya Subkont</td>
                            <td class="text-right">{{$report['price_subkon']}}</td>
                        </tr>
                        <tr>
                            <td class="indent">Biaya Tenaga Kerja Langsung (BTKL)</td>
                            <td class="text-right">{{$report['price_btkl']}}</td>
                        </tr>
                            <tr>
                            <td class="indent">Biaya lainnya</td>
                            <td class="text-right">{{$report['price_other']}}</td>
                        </tr>
                            <tr>
                            <td class="indent">Biaya lewat Tahun</td>
                            <td class="text-right">{{$report['price_profit_lost_project']}}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="bold">Total Biaya</td>
                            <td class="text-right">{{$report['price_total']}}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td><span class="bold">C. Laba Rugi Invoice</span></td>
                <td class="text-right">{{$report['price_profit_lost_po']}}</td>
            </tr>

            <tr>
                <td>D. Beban Umum</td>
                <td class="text-right">{{$report['price_general']}}</td>
            </tr>

            <tr>
                <td><span class="bold">E. Laba Akhir</span></td>
                <td class="text-right bold">{{$report['price_profit_final']}}</td>
            </tr>
        </tbody>
    </table>
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
