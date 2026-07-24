<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher - {{$voucher->no_voucher}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            padding: 0px;
        }

        .header-table, .detail-table, .section-title, .section-title-nominal {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: middle;
        }

        .voucher-title {
            background: #dce9f7;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            padding: 6px;
            border: 1px solid #000;
        }

        .info-table td {
            padding: 6px;
            /* border: 1px solid #000; */
        }

        .info-table{
            border-top: 1px solid #000;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .section-title {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .section-detail {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            /* border-bottom: 1px solid #000; */
            padding: 7px;
        }

        .section-title td {
            padding: 8px 6px 8px 6px;
            /* border: 1px solid #000; */
            background: #dce9f7;
            font-weight: bold;
        }

        .section-title-nominal td {
            border: 1px solid #000;
            background: #dce9f7;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .float-left { float: left; }
        .float-right { float: right; }
        .clear { clear: both; }

        .row { width: 100%; overflow: hidden; margin-bottom: 5px; }
        .left { float: left; width: 52%; }
        .right { float: right; width: 45%; }
        .label { float: left; width: 140px; }
        .colon { float: left; width: 15px; }
        .value { float: left; }

        .title-section {
            font-weight: bold;
            margin: 10px 0 5px 0;
            text-decoration: underline;
        }


    </style>
</head>
<body>
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 30%; text-align: center;">
                <img src="{{ public_path('kalinyamat-logo-export.jpeg') }}" alt="" style="height: 40px;">
            </td>
            <td style="width: 40%; text-align: center; font-size: 18px;">
                <b>{{$voucher->account_source->name}}</b>
            </td>
            <td style="width: 30%; text-align: center;">
                <small>{{$voucher->account->code}} - {{$voucher->account->name}}</small>
            </td>
        </tr>
    </table>

    <!-- TITLE -->
    <div class="voucher-title">Voucher Pembayaran</div>

    <!-- INFO SECTION -->
    <table width="100%" class="info-table" border="0" cellspacing="0" cellpadding="0" style="font-size:12px;">
        <tr>
            <td style="width: 20%;">Dibayarkan Kepada</td>
            <td style="width: 30%;">: {{$voucher?->subkon?->name}}</td>
            <td style="width: 20%;">No. Rekening</td>
            <td style="width: 30%;">: {{$voucher?->subkon?->bank_account}}</td>
        </tr>

        <tr>
            <td>NPWP</td>
            <td>: {{$voucher?->subkon?->npwp}}</td>
            <td>A/N</td>
            <td>: {{$voucher?->subkon?->account_holder_name}}</td>
        </tr>

        <tr>
            <td>Alamat</td>
            <td>: {{$voucher?->subkon?->address}}</td>
            <td>Bank</td>
            <td>: {{$voucher?->subkon?->bank_name}}</td>
        </tr>
    </table>

    <table class="section-title">
        <tr>
            <td style="width: 20%;">{{$voucher?->client_po?->work_code}}</td>
            <td style="width: 30%;" colspan="3">: {{$voucher?->job_name}}</td>
            {{-- <td style="width: 20%;"></td>
            <td style="width: 30%;"></td> --}}
        </tr>
    </table>

    <div class="section-detail">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-size:12px;">
            <tr>
                <td style="width:430px;" valign="top">
                    <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="20%">No. Pengajuan Pembayaran</td>
                            <td style="width: 10px;">:</td>
                            <td>{{$voucher?->no_payment}}</td>
                        </tr>
                        <tr>
                            <td>Deskripsi Pembayaran</td>
                            <td>:</td>
                            <td>{{$voucher?->payment_description}}</td>
                        </tr>
                        <tr>
                            <td>PO / SPK</td>
                            <td>:</td>
                            <td>{{$voucher?->reference?->po_number ?? $voucher?->reference?->no_spk}}</td>
                        </tr>
                        <tr>
                            <td>Tgl PO / SPK</td>
                            <td>:</td>
                            <td>{{ $voucher?->reference_date_str }}</td>
                        </tr>
                    </table>
                </td>
                <td style="" valign="top">
                    <table border="0" cellspacing="0" cellpadding="0" style="width: 330px;">
                        <tr>
                            <td width="40%">Tgl Terima Tagihan</td>
                            <td width="5%">:</td>
                            <td wdidth="30%">{{$voucher?->date_receipt_bill_str}}</td>
                        </tr>
                        <tr>
                            <td>No. Voucher</td>
                            <td>:</td>
                            <td>{{$voucher?->no_voucher}}</td>
                        </tr>
                        <tr>
                            <td>Tgl Voucher</td>
                            <td>:</td>
                            <td>{{$voucher?->date_voucher_str}}</td>
                        </tr>
                        <tr>
                            <td>Jatuh Tempo Tagihan</td>
                            <td>:</td>
                            <td>{{$voucher?->due_date_str}}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table style="padding-top:12px;" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse: collapse; font-size: 12px;">
            <tr>
                <td style="width: 340px;">
                    <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="width: 90px;"><span style="border-bottom: 1px solid black;"><strong>Tagihan</strong></span></td>
                            <td style="width: 15px;"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Nomor</td>
                            <td>:</td>
                            <td>{{$voucher?->bill_number}}</td>
                        </tr>
                        <tr>
                            <td>Tanggal</td>
                            <td>:</td>
                            <td>{{$voucher?->bill_date_str}}</td>
                        </tr>
                        <tr>
                            <td>Nilai Tagihan</td>
                            <td>:</td>
                            <td>{{$voucher->bill_value_str}}</td>
                        </tr>
                        <tr>
                            <td>PPn</td>
                            <td>:</td>
                            <td>{{$voucher->price_ppn_str}}</td>
                        </tr>
                        <tr>
                            <td style="padding-top:20px;"></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Total Tagihan</td>
                            <td>:</td>
                            <td>{{$voucher?->total_str}}</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="width: 120px;"><span style="border-bottom: 1px solid black;"><strong>Potongan</strong></span></td>
                            <td style="width: 70px;"><span style="border-bottom: 1px solid black; text-align: center;"><strong>Tarif PPh</strong></span></td>
                            <td style="width: 15px;"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>PPh Pasal 23</td>
                            <td><center>{{($voucher->pph_23 > 0) ? $voucher->pph_23.'%' : ''}}</center></td>
                            <td>:</td>
                            <td>{{$voucher->discount_pph_23_str}}</td>
                        </tr>
                        <tr>
                            <td>PPh Pasal 4 (2)</td>
                            <td><center>{{($voucher->pph_4 > 0) ? $voucher->pph_4.'%' : ''}}</center></td>
                            <td>:</td>
                            <td>{{$voucher->discount_pph_4_str}}</td>
                        </tr>
                        <tr>
                            <td>PPh Pasal 21</td>
                            <td><center>{{($voucher->pph_21 > 0) ? $voucher->pph_21.'%' : ''}}</center></td>
                            <td>:</td>
                            <td>{{$voucher->discount_pph_21_str}}</td>
                        </tr>
                        <tr>
                            <td>Status Faktur</td>
                            <td></td>
                            <td>:</td>
                            <td><center>{{$voucher?->factur_status}}</center></td>
                        </tr>
                        <tr>
                            <td>No. Faktur</td>
                            <td></td>
                            <td>:</td>
                            <td>{{$voucher?->no_factur}}</td>
                        </tr>
                        <tr>
                            <td>Tgl. Faktur</td>
                            <td></td>
                            <td>:</td>
                            <td>{{$voucher?->date_factur_str}}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <table class="section-title-nominal">
        <tr>
            <td style="font-weight: bold; width: 75%;">
                <center>Total Pembayaran</center>
            </td>
            <td rowspan="2"><center><span></span> <strong>{{$voucher?->payment_transfer_str}}</strong></center></td>
        </tr>
        <tr>
            <td><center>({{$voucher?->payment_transfer_word}} {{ ($voucher->currency_code === 'USD') ? 'Dollars' : 'Rupiah' }})</center></td>
        </tr>
    </table>

    <div class="section-footer">
        <table width="100%" border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse; font-family: Arial; font-size: 13px;">
            <tr>
                <!-- Kolom Keterangan -->
                <td width="50%" valign="top" height="130">
                    <b>Keterangan :</b><br>
                    - {{$voucher?->information}}
                </td>

                <!-- Kolom Tanda Tangan -->
                <td width="50%" valign="top" align="center">

                    <table width="80%" border="0" align="center" style="font-size: 12px;">
                        <tr>
                            <td align="center">
                                <div style="height: 100px; padding-top:20px;">
                                    <div style="font-size: 12px; visibility: hidden;">
                                        Telah dibayarkan Pada <br> Tanggal
                                    </div>
                                    <div style="
                                        width: 180px;
                                        height: 25px;
                                        box-sizing: border-box;
                                        border: 1px solid #ccc;
                                        background: #e8eef9;
                                        margin: 10px auto;
                                        text-align: center;
                                        padding-top: 8px;
                                        visibility: hidden;
                                    ">
                                        12 April 2026
                                    </div>
                                </div>
                                <div>
                                    <span style="border-bottom: 1px solid black;">Ida Noorma Z</span> <br>
                                    <span style="font-size: 11px;">Verifikator</span>
                                </div>
                            </td>
                            <td align="center">
                                <div style="height: 100px; padding-top:20px;">
                                    <div style="font-size: 12px;">
                                        Telah dibayarkan Pada <br> Tanggal
                                    </div>
                                    <div style="
                                        width: 180px;
                                        height: 25px;
                                        box-sizing: border-box;
                                        border: 1px solid #ccc;
                                        background: #e8eef9;
                                        margin: 10px auto;
                                        text-align: center;
                                        padding-top: 20px;
                                        @if($voucher?->payment_date_str == null)
                                            visibility: hidden;
                                        @endif
                                    ">
                                        @if($voucher?->payment_date_str == null)
                                            12 April 2026
                                        @else
                                            {{$voucher?->payment_date_str}}
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <span style="border-bottom: 1px solid black;">Ibnu Hajar</span> <br>
                                    <span style="font-size: 11px;">Mgr. Keuangan</span>
                                </div>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>
        <table width="100%" style="font-family: Arial; font-size: 13px;">
            <tr>
                <td width="20%">
                    <span><i>Cetak Tanggal</i></span>
                </td>
                <td width="30%">
                    <i>{{$voucher?->date_now_str}}</i>
                </td>
                <td width="50%">
                    <span style="padding-left: 12px;">Status Bayar &nbsp;</span> : &nbsp; <span>{{$voucher?->payment_status}}</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>