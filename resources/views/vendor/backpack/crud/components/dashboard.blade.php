<div>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card2 p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="la la-filter fs-4 me-2 text-primary"></i>
                    <span class="fw-bold me-3 fs-5">Filter Dashboard</span>
                </div>
                <div class="d-flex align-items-center">
                    <label for="filter_year" class="me-2 fw-bold text-nowrap">Pilih Tahun:</label>
                    <select id="filter_year" class="form-select form-select-sm w-auto" style="min-width: 150px; font-weight:bold;">
                        <option value="all" {{ request('year') == 'all' ? 'selected' : '' }}>Semua Tahun</option>
                        @for ($i = date('Y'); $i >= 2020; $i--)
                            <option value="{{ $i }}" {{ $i == (request('year') ?: date('Y')) ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card2 mb-4">
                <div class="card2-parent-header">
                    <div class="card2-header fs-6">Total Omzet (Exclude PPn)</div>
                </div>
                <div class="card2-body">
                    <div class="sub-header">Per - <span class="date-invoice"></span></div>
                    <div class="amount" id="omzet_all_total">Rp0</div>
                </div>

            </div>
        </div>
        <div class="col-md-6">
            <div class="card2 mb-4">
                <div class="card2-parent-header">
                    <div class="card2-header fs-6">Laba</div>
                </div>
                <div class="card2-body">
                    <div class="sub-header">Per - <span class="date-invoice"></span></div>
                    <div class="amount" id="laba_all_total">Rp0</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card2">
                <div class="card2-parent-header">
                    <div class="card2-header fs-6">
                        Realisasi Pekerjaan | Realisasi Penjualan
                    </div>
                </div>
                <div class="card2-body">
                    <div class="row">
                        <!-- Stack 1: Realisasi Pekerjaan -->
                        <div class="col-md-6 border-end pe-md-4 mb-3 mb-md-0">
                            <div class="fw-bold fs-6 text-primary mb-3 pb-1 border-bottom d-flex align-items-center">
                                <i class="la la-tasks me-2"></i> Realisasi Pekerjaan
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="label fw-bold mb-1">Rutin</div>
                                    <div class="item">
                                        <div class="icon blue"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div>Omzet<br><strong id="rp_rutin_omzet_total">Rp0</strong></div>
                                    </div>
                                    <div class="item">
                                        <div class="icon cyan"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div>Biaya<br><strong id="rp_rutin_biaya_total">Rp0</strong></div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="label fw-bold mb-1">Non Rutin</div>
                                    <div class="item">
                                        <div class="icon orange"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div>Omzet<br><strong id="rp_non_rutin_omzet_total">Rp0</strong></div>
                                    </div>
                                    <div class="item">
                                        <div class="icon pink"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div>Biaya<br><strong id="rp_non_rutin_biaya_total">Rp0</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stack 2: Realisasi Penjualan -->
                        <div class="col-md-6 ps-md-4">
                            <div class="fw-bold fs-6 text-success mb-3 pb-1 border-bottom d-flex align-items-center">
                                <i class="la la-shopping-cart me-2"></i> Realisasi Penjualan
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="label fw-bold mb-1">Rutin</div>
                                    <div class="item">
                                        <div class="icon blue"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div>Omzet<br><strong id="rp_rutin_omzet_penjualan">Rp0</strong></div>
                                    </div>
                                    <div class="item">
                                        <div class="icon cyan"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div>Biaya<br><strong id="rp_rutin_biaya_penjualan">Rp0</strong></div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="label fw-bold mb-1">Non Rutin</div>
                                    <div class="item">
                                        <div class="icon orange"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div>Omzet<br><strong id="rp_non_rutin_omzet_penjualan">Rp0</strong></div>
                                    </div>
                                    <div class="item">
                                        <div class="icon pink"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div>Biaya<br><strong id="rp_non_rutin_biaya_penjualan">Rp0</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card2">
                <div class="card2-parent-header">
                    <div class="card2-header fs-6">Laba Proyek | Laba Penjualan</div>
                </div>
                <div class="card2-body">
                    <div class="row">
                        <!-- Stack 1: Laba Proyek -->
                        <div class="col-md-6 border-end pe-md-4 mb-3 mb-md-0">
                            <div class="fw-bold fs-6 text-primary mb-3 pb-1 border-bottom d-flex align-items-center">
                                <i class="la la-calculator me-2"></i> Laba Proyek
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="btn mb-3">
                                        <button class="btn btn-primary btn-sm" id="btn-rutin">Rutin</button>
                                    </div>
                                    <div class="item">
                                        <div class="icon blue"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div><strong id="laba_rutin_total">Rp0</strong></div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="btn mb-3">
                                        <button class="btn btn-primary btn-sm" id="btn-non-rutin">Non Rutin</button>
                                    </div>
                                    <div class="item">
                                        <div class="icon orange"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div><strong id="laba_non_rutin_total">Rp0</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stack 2: Laba Penjualan -->
                        <div class="col-md-6 ps-md-4">
                            <div class="fw-bold fs-6 text-success mb-3 pb-1 border-bottom d-flex align-items-center">
                                <i class="la la-chart-line me-2"></i> Laba Penjualan
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="btn mb-3">
                                        <button class="btn btn-success btn-sm" id="btn-rutin-penjualan">Rutin</button>
                                    </div>
                                    <div class="item">
                                        <div class="icon blue"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div><strong id="laba_rutin_penjualan_total">Rp0</strong></div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="btn mb-3">
                                        <button class="btn btn-success btn-sm" id="btn-non-rutin-penjualan">Non Rutin</button>
                                    </div>
                                    <div class="item">
                                        <div class="icon orange"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                        <div><strong id="laba_non_rutin_penjualan_total">Rp0</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 mb-4">
            <div class="card2">
                <div class="card2-parent-header d-flex justify-content-between align-items-center pe-3 pb-2">
                    <div class="card2-header fs-6 mb-0">{{ __('backpack::crud.device_stock.info_header') }}</div>
                    <button class="btn btn-primary btn-sm" id="btn-device-stock">
                        <i class="la la-list me-1"></i> Detail
                    </button>
                </div>
                <div class="card2-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="p-3 bg-light rounded">
                                <div class="text-muted small fw-bold mb-1">{{ __('backpack::crud.device_stock.dashboard.total_stok') }}</div>
                                <div class="fs-4 fw-bold text-primary" id="ds_total_stok">{{ $data_device_stock['total_stok'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="p-3 bg-light rounded">
                                <div class="text-muted small fw-bold mb-1">{{ __('backpack::crud.device_stock.dashboard.total_barang') }}</div>
                                <div class="fs-4 fw-bold text-info" id="ds_total_barang">{{ $data_device_stock['total_barang'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <div class="text-muted small fw-bold mb-1">{{ __('backpack::crud.device_stock.dashboard.total_nominal') }}</div>
                                <div class="fs-4 fw-bold text-success" id="ds_total_nominal">Rp{{ $data_device_stock['total_nominal'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 mb-4">
            <div class="card2">
                <div class="card2-parent-header">
                    <div class="card2-header fs-6">Monitoring Biaya Pekerjaan Berjalan (Non Rutin)</div>
                </div>
                <div class="card2-body">
                    <div class="fs-5 fw-bold text-center mb-3">
                       Per - <span class="date-invoice"></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead class="bg-light-actual">
                                <tr>
                                    <th>Total Nilai Pekerjaan</th>
                                    <th>Total Biaya Pekerjaan</th>
                                    <th>Laba Berjalan</th>
                                    <th>Jumlah Pekerjaan</th>
                                </tr>
                            </thead>
                            <tbody>
                                    <th id="mon_total_job_value">Rp{{ $data_monitoring['total_job_value'] }}</th>
                                    <th id="mon_price_total">Rp{{ $data_monitoring['price_total_str'] }}</th>
                                    <th id="mon_laba_berjalan">Rp{{ $data_monitoring['price_profit_lost_str'] }}</th>
                                    <th id="mon_jumlah_pekerjaan">{{ $data_monitoring['total_job'] }}</th>
                                {{-- @endforeach --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('inline_scripts')
<style>
    .card2 {
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      overflow: hidden;
      /* padding: 20px; */
    }

    .card2-parent-header {
        padding-top:15px;
        border-bottom: 1px solid rgba(197, 197, 197, 0.705);
    }

    .card2-header {
      border-left: 3px solid #005792;
      font-weight: bold;
      padding-top:7px;
      padding-bottom: 7px;
      margin-bottom: 15px;
      padding-left: 16px;
    }

    .card2-body {
        padding: 15px;
    }

    .sub-header {
      background: #f1f3f5;
      padding: 5px 10px;
      font-size: 0.9rem;
      color: #555;
      margin-bottom: 10px;
    }

    .amount {
      font-size: 1.8rem;
      font-weight: bold;
      color: #000;
      padding-top: 12px;
      padding-bottom: 16px;
      text-align: center;
    }

    .icon {
      width: 40px !important;
      height: 40px !important;
      border-radius: 8px;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .icon.blue { background: #1e6091; }
    .icon.cyan { background: #48cae4; }
    .icon.orange { background: #f9a825; }
    .icon.pink { background: #f06292; }

    .item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .bg-light-actual {
            background-color: #ededed !important;
            --bs-table-color: black;
            --bs-table-bg: #ededed;
            --bs-table-border-color: #ededed;
            --bs-table-striped-bg: #ededed;
            --bs-table-striped-color: #ededed;
            --bs-table-active-bg: #030303;
            --bs-table-active-color: #ededed;
            --bs-table-hover-bg: #ededed;
            --bs-table-hover-color: #e9ecef;

            color: black;
            border-color: #ededed !important;
        }

    .status-box {
      border-radius: 10px;
      padding: 20px;
      color: white;
      font-weight: bold;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      min-width: 180px;
      margin-bottom: 12px;
    }

    .status-value {
      font-weight: normal;
      font-size: 16px;
      margin-top: 20px;
    }
    .red { background-color: #d9534f; }
    .yellow { background-color: #f0ad4e; color: black; }
    .blue { background-color: #195381; }
    .green { background-color: #28a745; }

</style>
@endpush

@push('after_scripts')
<script>
    $(function(){
        SIAOPS.setAttribute('dashboard', function(){
            return {
                name: 'dashboard',
                accounts_compact:[],
                eventLoader: async function(){
                    var instance = this;
                    // eventEmitter.on("crudTable-voucher_plugin_load", function(data){
                    //     instance.load();
                    // });
                },
                load: function(){
                    var instance = this;
                    instance.eventLoader();
                    $.ajax({
                        url: "{{ url($crud->route.'/get-chart') }}",
                        type: 'GET',
                        typeData: 'json',
                        data: {
                            year: $('#filter_year').val()
                        },
                        success: function (result) {
                            // console.log(result);
                            var date_invoice = $('.date-invoice');
                            date_invoice.each(function(){
                                $(this).html(result.first_invoice.invoice_first_date);
                            });
                            $('#rp_rutin_omzet_total').html('Rp'+result.total_job_realisasion.total_omzet_rutin);
                            $('#rp_non_rutin_omzet_total').html('Rp'+result.total_job_realisasion.total_omzet_non_rutin);
                            $('#rp_rutin_biaya_total').html('Rp'+result.total_job_realisasion.total_biaya_rutin);
                            $('#rp_non_rutin_biaya_total').html('Rp'+result.total_job_realisasion.total_biaya_non_rutin);

                            $('#rp_rutin_omzet_penjualan').html('Rp'+result.total_job_realisasion.total_omzet_rutin_penjualan);
                            $('#rp_rutin_biaya_penjualan').html('Rp'+result.total_job_realisasion.total_biaya_rutin_penjualan);
                            $('#rp_non_rutin_omzet_penjualan').html('Rp'+result.total_job_realisasion.total_omzet_non_rutin_penjualan);
                            $('#rp_non_rutin_biaya_penjualan').html('Rp'+result.total_job_realisasion.total_biaya_non_rutin_penjualan);
                            // $('#total_job_value_non_rutin').html('Rp'+result.total_job_realisasion.total_job_value_non_rutin);

                            $('#laba_all_total').html('Rp'+result.total_job_realisasion.total_all_laba);

                            // $('#laba_rutin_total').html('Rp'+result.total_laba_category.total_laba_rutin);
                            // $('#laba_non_rutin_total').html('Rp'+result.total_laba_category.total_laba_non_rutin);

                            $('#laba_rutin_total').html('Rp'+result.total_job_realisasion.total_laba_rutin);
                            $('#laba_non_rutin_total').html('Rp'+result.total_job_realisasion.total_laba_non_rutin);

                            $('#laba_rutin_penjualan_total').html('Rp'+result.total_job_realisasion.total_laba_rutin_penjualan);
                            $('#laba_non_rutin_penjualan_total').html('Rp'+result.total_job_realisasion.total_laba_non_rutin_penjualan);

                            // $('#omzet_all_total').html('Rp'+result.total_omzet_all.total_omzet);
                            $('#omzet_all_total').html('Rp'+result.total_job_realisasion.total_all_omzet);

                            // Update Monitoring Table
                            if(result.data_monitoring) {
                                $('#mon_total_job_value').html('Rp' + result.data_monitoring.total_job_value);
                                $('#mon_price_total').html('Rp' + result.data_monitoring.price_total_str);
                                $('#mon_laba_berjalan').html('Rp' + result.data_monitoring.price_profit_lost_str);
                                $('#mon_jumlah_pekerjaan').html(result.data_monitoring.total_job);
                            }

                            // Update Device Stock Summary
                            if(result.data_device_stock) {
                                $('#ds_total_stok').html(result.data_device_stock.total_stok);
                                $('#ds_total_barang').html(result.data_device_stock.total_barang);
                                $('#ds_total_nominal').html('Rp' + result.data_device_stock.total_nominal);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error(xhr);
                            alert('An error occurred while loading the create form.');
                        }
                    });
                }
            }
        });

        SIAOPS.getAttribute('dashboard').load();

        $('#btn-rutin').click(function(){
            $('#modalInfoLabaRutin').modal('show');
        });
        $('#btn-non-rutin').click(function(){
            $('#modalInfoLabaNonRutin').modal('show');
        });
        $('#btn-rutin-penjualan').click(function(){
            $('#modalInfoLabaRutinPenjualan').modal('show');
        });
        $('#btn-non-rutin-penjualan').click(function(){
            $('#modalInfoLabaNonRutinPenjualan').modal('show');
        });
        $('#btn-device-stock').click(function(){
            $('#modalDeviceStock').modal('show');
        });
        $('.modal .btn-close').click(function(){
            $('#modalInfoLabaRutin').modal('hide');
            $('#modalInfoLabaNonRutin').modal('hide');
            $('#modalInfoLabaRutinPenjualan').modal('hide');
            $('#modalInfoLabaNonRutinPenjualan').modal('hide');
            $('#modalDeviceStock').modal('hide');
        });


        $('#filter_year').change(function(){
            var year = $(this).val();
            window.location.href = "{{ url($crud->route) }}?year=" + year;
        });
    });
</script>
@endpush

@push('after_scripts')
<div class="modal fade" id="modalInfoLabaRutin" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5 class="modal-title text-center w-100" id="modalTitleCentered">Tabel Pekerjaan Rutin</h5>
                <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-borderless" style="width: 800px;">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>KDP</th>
                                <th>Nama Pekerjaan</th>
                                <th>Nilai Invoice</th>
                                <th>Biaya</th>
                                <th>Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data_laba['data_laba_rutin'] as $key => $laba)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $laba->work_code }}</td>
                                    <td>{{ $laba->job_name }}</td>
                                    <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->price_job_exlude_ppn_logic) }}</td>
                                    <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->price_total_str) }}</td>
                                    <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->price_profit_lost_str) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalInfoLabaNonRutin" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5 class="modal-title text-center w-100" id="modalTitleCentered">Tabel Pekerjaan Non Rutin</h5>
                <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-borderless" style="width: 800px;">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>KDP</th>
                                <th>Nama Pekerjaan</th>
                                <th>Nilai Invoice</th>
                                <th>Biaya</th>
                                <th>Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data_laba['data_laba_non_rutin'] as $key => $laba)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $laba->kdp }}</td>
                                    <td>{{ $laba->job_name }}</td>
                                    <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->price_job_exlude_ppn_logic) }}</td>
                                    <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->price_total_str) }}</td>
                                    <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->price_profit_lost_str) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInfoLabaRutinPenjualan" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalInfoLabaRutinPenjualanLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5 class="modal-title text-center w-100" id="modalInfoLabaRutinPenjualanLabel">Tabel Penjualan Rutin</h5>
                <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-borderless" style="width: 800px;">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>No. Invoice</th>
                                <th>Supplier</th>
                                <th>Nilai Invoice</th>
                                <th>Biaya</th>
                                <th>Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (!empty($data_laba['data_laba_rutin_penjualan']))
                                @foreach ($data_laba['data_laba_rutin_penjualan'] as $key => $laba)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $laba->supplier_invoice_number }}</td>
                                        <td>{{ $laba->supplier_name }}</td>
                                        <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->total_harga_jual_base) }}</td>
                                        <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->total_harga_beli_base) }}</td>
                                        <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->laba_kotor_base) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Tidak ada data</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInfoLabaNonRutinPenjualan" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalInfoLabaNonRutinPenjualanLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5 class="modal-title text-center w-100" id="modalInfoLabaNonRutinPenjualanLabel">Tabel Penjualan Non Rutin</h5>
                <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-borderless" style="width: 800px;">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>No. Invoice</th>
                                <th>Supplier</th>
                                <th>Nilai Invoice</th>
                                <th>Biaya</th>
                                <th>Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (!empty($data_laba['data_laba_non_rutin_penjualan']))
                                @foreach ($data_laba['data_laba_non_rutin_penjualan'] as $key => $laba)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $laba->supplier_invoice_number }}</td>
                                        <td>{{ $laba->supplier_name }}</td>
                                        <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->total_harga_jual_base) }}</td>
                                        <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->total_harga_beli_base) }}</td>
                                        <td>Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($laba->laba_kotor_base) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Tidak ada data</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalDeviceStock" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalDeviceStockLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5 class="modal-title text-center w-100" id="modalDeviceStockLabel">{{ __('backpack::crud.device_stock.modal_title') }}</h5>
                <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light-actual">
                            <tr>
                                <th>No.</th>
                                <th>{{ __('backpack::crud.device_stock.column.code') }}</th>
                                <th>{{ __('backpack::crud.device_stock.column.name') }}</th>
                                <th>{{ __('backpack::crud.device_stock.column.category') }}</th>
                                <th>{{ __('backpack::crud.device_stock.column.qty') }}</th>
                                <th>{{ __('backpack::crud.device_stock.column.buy_price') }}</th>
                                <th>{{ __('backpack::crud.device_stock.column.latest_sell_price') }}</th>
                                <th>{{ __('backpack::crud.device_stock.column.total_sell_nominal') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data_device_stock['list_stocks'] as $key => $stock)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td><span class="badge bg-secondary">{{ $stock->code }}</span></td>
                                    <td class="fw-bold">{{ $stock->name }}</td>
                                    <td>{{ $stock->category_name ?? '-' }}</td>
                                    <td class="text-center">{{ number_format($stock->qty, 0, ',', '.') }}</td>
                                    <td>{{ \App\Http\Helpers\CustomHelper::formatCurrency($stock->buy_price, $stock->currency_code ?? 'IDR') }}</td>
                                    <td>
                                        {{ \App\Http\Helpers\CustomHelper::formatCurrency($stock->latest_sell_price, $stock->latest_currency_code) }}
                                        @if ($stock->is_from_invoice)
                                            <span class="badge bg-info ms-1" style="font-size: 10px;" title="{{ __('backpack::crud.device_stock.source_invoice_title') }}">{{ __('backpack::crud.device_stock.source_invoice') }}</span>
                                        @else
                                            <span class="badge bg-light text-dark ms-1" style="font-size: 10px;" title="{{ __('backpack::crud.device_stock.source_master_title') }}">{{ __('backpack::crud.device_stock.source_master') }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-success">Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($stock->total_jual_base) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">{{ __('backpack::crud.device_stock.no_data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush
