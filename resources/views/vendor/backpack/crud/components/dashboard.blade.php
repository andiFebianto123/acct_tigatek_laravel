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
            <div class="card2 h-100">
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
                            <div class="mb-3">
                                <div class="label fw-bold mb-1">Rutin</div>
                                <div class="item">
                                    <div class="icon blue"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Omzet<br><strong id="rp_rutin_omzet_total">Rp0</strong></div>
                                </div>
                                <div class="item">
                                    <div class="icon cyan"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Biaya<br><strong id="rp_rutin_biaya_total">Rp0</strong></div>
                                </div>
                            </div>
                            <div>
                                <div class="label fw-bold mb-1">Non Rutin</div>
                                <div class="item">
                                    <div class="icon orange"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Omzet<br><strong id="rp_non_rutin_omzet_total">Rp0</strong></div>
                                </div>
                                <div class="item">
                                    <div class="icon pink"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Biaya<br><strong id="rp_non_rutin_biaya_total">Rp0</strong></div>
                                </div>
                            </div>
                        </div>

                        <!-- Stack 2: Realisasi Penjualan -->
                        <div class="col-md-6 ps-md-4">
                            <div class="fw-bold fs-6 text-success mb-3 pb-1 border-bottom d-flex align-items-center">
                                <i class="la la-shopping-cart me-2"></i> Realisasi Penjualan
                            </div>
                            <div class="mb-3">
                                <div class="label fw-bold mb-1">Rutin</div>
                                <div class="item">
                                    <div class="icon blue"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Omzet<br><strong id="rp_rutin_omzet_penjualan">Rp0</strong></div>
                                </div>
                                <div class="item">
                                    <div class="icon cyan"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Biaya<br><strong id="rp_rutin_biaya_penjualan">Rp0</strong></div>
                                </div>
                            </div>
                            <div>
                                <div class="label fw-bold mb-1">Non Rutin</div>
                                <div class="item">
                                    <div class="icon orange"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Omzet<br><strong id="rp_non_rutin_omzet_penjualan">Rp0</strong></div>
                                </div>
                                <div class="item">
                                    <div class="icon pink"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Biaya<br><strong id="rp_non_rutin_biaya_penjualan">Rp0</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card2 h-100">
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
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="label fw-bold">Rutin</span>
                                    <button class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 11px;" id="btn-rutin" title="Lihat Detail">
                                        <i class="la la-search me-1"></i>Detail
                                    </button>
                                </div>
                                <div class="item">
                                    <div class="icon blue"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Laba<br><strong id="laba_rutin_total" class="text-primary">Rp0</strong></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="label fw-bold">Non Rutin</span>
                                    <button class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 11px;" id="btn-non-rutin" title="Lihat Detail">
                                        <i class="la la-search me-1"></i>Detail
                                    </button>
                                </div>
                                <div class="item">
                                    <div class="icon orange"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Laba<br><strong id="laba_non_rutin_total" class="text-primary">Rp0</strong></div>
                                </div>
                            </div>
                        </div>

                        <!-- Stack 2: Laba Penjualan -->
                        <div class="col-md-6 ps-md-4">
                            <div class="fw-bold fs-6 text-success mb-3 pb-1 border-bottom d-flex align-items-center">
                                <i class="la la-chart-line me-2"></i> Laba Penjualan
                            </div>
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="label fw-bold">Rutin</span>
                                    <button class="btn btn-outline-success btn-sm py-0 px-2" style="font-size: 11px;" id="btn-rutin-penjualan" title="Lihat Detail">
                                        <i class="la la-search me-1"></i>Detail
                                    </button>
                                </div>
                                <div class="item">
                                    <div class="icon blue"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Laba<br><strong id="laba_rutin_penjualan_total" class="text-success">Rp0</strong></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="label fw-bold">Non Rutin</span>
                                    <button class="btn btn-outline-success btn-sm py-0 px-2" style="font-size: 11px;" id="btn-non-rutin-penjualan" title="Lihat Detail">
                                        <i class="la la-search me-1"></i>Detail
                                    </button>
                                </div>
                                <div class="item">
                                    <div class="icon orange"><i class="la la-file-invoice-dollar fs-4"></i></div>
                                    <div class="text-break">Laba<br><strong id="laba_non_rutin_penjualan_total" class="text-success">Rp0</strong></div>
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
                    <div class="card2-header fs-6 mb-0">Informasi Stok Lintas Kategori</div>
                    <button class="btn btn-outline-primary btn-sm fw-bold px-3" id="btn-device-stock">
                        <i class="la la-list me-1"></i> Detail All Item
                    </button>
                </div>
                <div class="card2-body">
                    <div class="row g-3" id="category_cards_container">
                        @if (!empty($data_device_stock['categories_summary']))
                            @foreach ($data_device_stock['categories_summary'] as $cat)
                                <div class="col-md-3 col-sm-6">
                                    <div class="category-stock-card p-3 rounded-3 h-100 d-flex flex-column justify-content-between">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="la {{ $cat['icon'] }} fs-4 me-2 text-primary"></i>
                                                <span class="fw-bold fs-6 text-dark tracking-wide">{{ $cat['name'] }}</span>
                                            </div>
                                            <button class="btn-category-expand btn btn-sm btn-light border-0 shadow-sm rounded-3 p-1 d-flex align-items-center justify-content-center" 
                                                    style="width: 32px; height: 32px;" 
                                                    data-category-id="{{ $cat['id'] }}" 
                                                    data-category-name="{{ $cat['name'] }}"
                                                    title="Lihat Detail {{ $cat['name'] }}">
                                                <i class="la la-expand-arrows-alt text-primary fs-5"></i>
                                            </button>
                                        </div>
                                        <div class="mt-1">
                                            <div class="text-secondary small fw-semibold">
                                                Total Stock: <span class="text-dark fw-bold">{{ $cat['total_stock'] }} Unit</span>
                                            </div>
                                            <div class="text-secondary small fw-semibold">
                                                Total Nilai: <span class="text-success fw-bold">Rp {{ $cat['total_nominal'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-12 text-center text-muted py-3">
                                Tidak ada data kategori stok barang.
                            </div>
                        @endif
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

    .category-stock-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease-in-out;
    }
    .category-stock-card:hover {
        background: #ffffff;
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        transform: translateY(-2px);
    }
    .btn-category-expand {
        background-color: #f1f5f9;
        transition: all 0.15s ease-in-out;
    }
    .btn-category-expand:hover {
        background-color: #005792 !important;
        color: #ffffff !important;
    }
    .btn-category-expand:hover i {
        color: #ffffff !important;
    }

    #modalCategoryStockDetail .table th,
    #modalCategoryStockDetail .table td,
    #modalDeviceStock .table th,
    #modalDeviceStock .table td {
        padding: 10px 14px;
        vertical-align: middle;
    }

    #modalCategoryStockDetail .modal-dialog,
    #modalDeviceStock .modal-dialog {
        height: calc(100vh - 60px);
        max-height: calc(100vh - 60px);
        margin: 30px auto;
    }

    #modalCategoryStockDetail .modal-content,
    #modalDeviceStock .modal-content {
        height: 100%;
        max-height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    #modalCategoryStockDetail .modal-body,
    #modalDeviceStock .modal-body {
        overflow: hidden;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }

    #modalCategoryStockDetail .table-responsive,
    #modalDeviceStock .table-responsive {
        flex: 1 1 auto;
        overflow-y: auto !important;
        overflow-x: auto !important;
        min-height: 0;
        max-height: 100%;
    }

    #modalCategoryStockDetail tfoot,
    #modalDeviceStock tfoot {
        position: sticky;
        bottom: 0;
        z-index: 5;
    }

    #modalCategoryStockDetail tfoot td,
    #modalDeviceStock tfoot td {
        background-color: #f8fafc !important;
        border-top: 2px solid #cbd5e1 !important;
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
    }

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
                                window.latestDeviceStockData = result.data_device_stock;
                                if(result.data_device_stock.categories_summary && result.data_device_stock.categories_summary.length > 0) {
                                    let html = '';
                                    result.data_device_stock.categories_summary.forEach(function(cat) {
                                        html += `
                                        <div class="col-md-3 col-sm-6">
                                            <div class="category-stock-card p-3 rounded-3 h-100 d-flex flex-column justify-content-between">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div class="d-flex align-items-center">
                                                        <i class="la ${cat.icon} fs-4 me-2 text-primary"></i>
                                                        <span class="fw-bold fs-6 text-dark tracking-wide">${cat.name}</span>
                                                    </div>
                                                    <button class="btn-category-expand btn btn-sm btn-light border-0 shadow-sm rounded-3 p-1 d-flex align-items-center justify-content-center" 
                                                            style="width: 32px; height: 32px;" 
                                                            data-category-id="${cat.id}" 
                                                            data-category-name="${cat.name}"
                                                            title="Lihat Detail ${cat.name}">
                                                        <i class="la la-expand-arrows-alt text-primary fs-5"></i>
                                                    </button>
                                                </div>
                                                <div class="mt-1">
                                                    <div class="text-secondary small fw-semibold">
                                                        Total Stock: <span class="text-dark fw-bold">${cat.total_stock} Unit</span>
                                                    </div>
                                                    <div class="text-secondary small fw-semibold">
                                                        Total Nilai: <span class="text-success fw-bold">Rp ${cat.total_nominal}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>`;
                                    });
                                    $('#category_cards_container').html(html);
                                }
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

        // Store initial stock data from server
        window.latestDeviceStockData = @json($data_device_stock);

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

        // Open Category Stock Detail Modal (Foto 2)
        $(document).on('click', '.btn-category-expand', function(){
            const catId = $(this).data('category-id');
            const catName = $(this).data('category-name');
            $('#categoryModalTitle').text(catName);
            $('#btnExportCategoryPdf').attr('data-category-id', catId);

            let allStocks = (window.latestDeviceStockData && window.latestDeviceStockData.list_stocks) ? window.latestDeviceStockData.list_stocks : [];
            let filtered = [];
            if (catId === 'uncategorized') {
                filtered = allStocks.filter(item => !item.category_id);
            } else {
                filtered = allStocks.filter(item => String(item.category_id) === String(catId));
            }

            let tbodyHtml = '';
            let totalQty = 0;
            let totalHpp = 0;

            if (filtered.length > 0) {
                filtered.forEach(function(item, idx) {
                    const qty = parseFloat(item.qty) || 0;
                    totalQty += qty;
                    const hpp = parseFloat(item.total_hpp_base) || (qty * (parseFloat(item.buy_price_base) || parseFloat(item.buy_price) || 0));
                    totalHpp += hpp;

                    const buyPriceFormatted = window.formatMoney ? window.formatMoney(item.buy_price, item.currency_code) : (item.currency_code === 'USD' ? '$ ' + Number(item.buy_price).toLocaleString('en-US', {minimumFractionDigits: 2}) : 'Rp ' + Number(item.buy_price).toLocaleString('id-ID'));
                    const sellPriceFormatted = window.formatMoney ? window.formatMoney(item.latest_sell_price, item.latest_currency_code) : (item.latest_currency_code === 'USD' ? '$ ' + Number(item.latest_sell_price).toLocaleString('en-US', {minimumFractionDigits: 2}) : 'Rp ' + Number(item.latest_sell_price).toLocaleString('id-ID'));
                    const hppFormatted = 'Rp ' + Number(hpp).toLocaleString('id-ID');

                    tbodyHtml += `
                        <tr>
                            <td class="text-center align-middle">${idx + 1}</td>
                            <td class="align-middle text-break"><span class="badge bg-light text-dark border px-2 py-1 font-monospace text-wrap" style="font-size: 11px; word-break: break-word; white-space: normal; display: inline-block; max-width: 100%;">${item.code || '-'}</span></td>
                            <td class="align-middle fw-semibold text-break" style="word-break: break-word;">${item.name || '-'}</td>
                            <td class="text-center align-middle fw-bold">${Number(qty).toLocaleString('id-ID')}</td>
                            <td class="text-center align-middle">${buyPriceFormatted}</td>
                            <td class="text-center align-middle">${sellPriceFormatted}</td>
                            <td class="text-end align-middle fw-bold ${hpp > 0 ? 'text-success' : 'text-muted'}">${hppFormatted}</td>
                        </tr>
                    `;
                });
            } else {
                tbodyHtml = `<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data barang dalam kategori ini.</td></tr>`;
            }

            $('#categoryStockTableBody').html(tbodyHtml);
            $('#categoryTotalQty').text(Number(totalQty).toLocaleString('id-ID'));
            $('#categoryTotalHpp').text('Rp ' + Number(totalHpp).toLocaleString('id-ID'));

            $('#modalCategoryStockDetail').modal('show');
        });

        // Export PDF per Category
        $('#btnExportCategoryPdf').click(function(){
            const catId = $(this).attr('data-category-id') || 'all';
            const url = "{{ url(config('backpack.base.route_prefix') . '/dashboard/export-device-stock-pdf') }}?category_id=" + catId;
            window.open(url, '_blank');
        });

        // Export PDF All Device Stocks
        $('#btnExportAllDevicePdf').click(function(){
            const url = "{{ url(config('backpack.base.route_prefix') . '/dashboard/export-device-stock-pdf') }}?category_id=all";
            window.open(url, '_blank');
        });

        // Close modals handler (supports data-bs-dismiss, .btn-close, and .btn-modal-close)
        $(document).on('click', '.modal .btn-close, .modal [data-bs-dismiss="modal"], .btn-modal-close', function(e) {
            e.preventDefault();
            var $modal = $(this).closest('.modal');
            if ($modal.length) {
                $modal.modal('hide');
                try {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var inst = bootstrap.Modal.getInstance($modal[0]);
                        if (inst) {
                            inst.hide();
                        }
                    }
                } catch (err) {
                    console.error('Error hiding modal:', err);
                }
            } else {
                $('#modalInfoLabaRutin, #modalInfoLabaNonRutin, #modalInfoLabaRutinPenjualan, #modalInfoLabaNonRutinPenjualan, #modalDeviceStock, #modalCategoryStockDetail').modal('hide');
            }
        });

        $('#filter_year').change(function(){
            var year = $(this).val();
            window.location.href = "{{ url($crud->route) }}?year=" + year;
        });
    });
</script>
@endpush

@push('after_scripts')
<!-- Modal Detail Barang per Kategori (Sesuai Foto 2) -->
<div class="modal fade" id="modalCategoryStockDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalCategoryStockDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow border-0">
            <div class="modal-header d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="la la-list-ul text-primary fs-4 me-2"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0">
                        Detail Barang - Kategori: <span id="categoryModalTitle" class="text-primary text-uppercase">DEVICE</span>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive rounded border" style="overflow-y: auto;">
                    <table class="table table-bordered table-hover mb-0 w-100" style="font-size: 13px; table-layout: fixed;">
                        <thead class="bg-light sticky-top" style="z-index: 1;">
                            <tr class="align-middle text-secondary fw-bold">
                                <th style="width: 50px;" class="text-center">No.</th>
                                <th style="width: 140px;">Kode Barang</th>
                                <th style="width: 250px;">Nama Barang</th>
                                <th style="width: 70px;" class="text-center">Qty</th>
                                <th style="width: 120px;" class="text-center">Harga Beli</th>
                                <th style="width: 120px;" class="text-center">Harga Jual</th>
                                <th style="width: 140px;" class="text-end">Total HPP</th>
                            </tr>
                        </thead>
                        <tbody id="categoryStockTableBody">
                            <!-- Populated via Javascript -->
                        </tbody>
                        <tfoot class="bg-light sticky-bottom" style="z-index: 1;">
                            <tr class="align-middle fw-bold border-top">
                                <td colspan="3" class="text-end text-dark pe-3">TOTAL:</td>
                                <td class="text-center text-dark" id="categoryTotalQty">0</td>
                                <td colspan="2" class="text-end text-dark pe-3">TOTAL:</td>
                                <td class="text-end text-success fs-6" id="categoryTotalHpp">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer px-4 py-2 border-top bg-light d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm px-3 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" id="btnExportCategoryPdf">
                    <i class="la la-file-pdf me-1"></i> Ekspor PDF
                </button>
            </div>
        </div>
    </div>
</div>

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
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow border-0">
            <div class="modal-header d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="la la-cubes text-primary fs-4 me-2"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalDeviceStockLabel">{{ __('backpack::crud.device_stock.modal_title') }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive rounded border" style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-bordered table-hover mb-0 w-100" style="font-size: 13px; table-layout: fixed;">
                        <thead class="bg-light sticky-top" style="z-index: 1;">
                            <tr class="align-middle text-secondary fw-bold">
                                <th style="width: 45px;" class="text-center">No.</th>
                                <th style="width: 130px;">{{ __('backpack::crud.device_stock.column.code') }}</th>
                                <th style="width: 220px;">{{ __('backpack::crud.device_stock.column.name') }}</th>
                                <th style="width: 140px;">{{ __('backpack::crud.device_stock.column.category') }}</th>
                                <th style="width: 65px;" class="text-center">{{ __('backpack::crud.device_stock.column.qty') }}</th>
                                <th style="width: 110px;" class="text-center">{{ __('backpack::crud.device_stock.column.buy_price') }}</th>
                                <th style="width: 125px;" class="text-center">{{ __('backpack::crud.device_stock.column.latest_sell_price') }}</th>
                                <th style="width: 130px;" class="text-end">{{ __('backpack::crud.device_stock.column.total_sell_nominal') }}</th>
                            </tr>
                        </thead>
                        <tbody id="allDeviceStockTableBody">
                            @forelse ($data_device_stock['list_stocks'] as $key => $stock)
                                <tr>
                                    <td class="text-center align-middle">{{ $key + 1 }}</td>
                                    <td class="align-middle text-break"><span class="badge bg-light text-dark border px-2 py-1 font-monospace text-wrap" style="font-size: 11px; word-break: break-word; white-space: normal; display: inline-block; max-width: 100%;">{{ $stock->code }}</span></td>
                                    <td class="align-middle fw-semibold text-break" style="word-break: break-word;">{{ $stock->name }}</td>
                                    <td class="align-middle text-break" style="word-break: break-word;">{{ $stock->category_name ?? '-' }}</td>
                                    <td class="text-center align-middle fw-bold">{{ number_format($stock->qty, 0, ',', '.') }}</td>
                                    <td class="text-center align-middle">{{ \App\Http\Helpers\CustomHelper::formatCurrency($stock->buy_price, $stock->currency_code ?? 'IDR') }}</td>
                                    <td class="text-center align-middle">
                                        {{ \App\Http\Helpers\CustomHelper::formatCurrency($stock->latest_sell_price, $stock->latest_currency_code) }}
                                        @if ($stock->is_from_invoice)
                                            <span class="badge bg-info ms-1" style="font-size: 10px;" title="{{ __('backpack::crud.device_stock.source_invoice_title') }}">{{ __('backpack::crud.device_stock.source_invoice') }}</span>
                                        @else
                                            <span class="badge bg-light text-dark border ms-1" style="font-size: 10px;" title="{{ __('backpack::crud.device_stock.source_master_title') }}">{{ __('backpack::crud.device_stock.source_master') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end align-middle fw-bold {{ ($stock->total_jual_base > 0) ? 'text-success' : 'text-muted' }}">Rp{{ \App\Http\Helpers\CustomHelper::formatRupiah($stock->total_jual_base) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">{{ __('backpack::crud.device_stock.no_data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light sticky-bottom" style="z-index: 1;">
                            <tr class="align-middle fw-bold border-top">
                                <td colspan="4" class="text-end text-dark pe-3">TOTAL:</td>
                                <td class="text-center text-dark" id="allDeviceTotalQty">
                                    {{ number_format($data_device_stock['list_stocks']->sum('qty'), 0, ',', '.') }}
                                </td>
                                <td colspan="2" class="text-end text-dark pe-3">TOTAL HPP:</td>
                                <td class="text-end text-success fs-6" id="allDeviceTotalHpp">
                                    Rp {{ \App\Http\Helpers\CustomHelper::formatRupiah($data_device_stock['list_stocks']->sum('total_hpp_base')) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer px-4 py-2 border-top bg-light d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm px-3 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" id="btnExportAllDevicePdf">
                    <i class="la la-file-pdf me-1"></i> Ekspor PDF
                </button>
            </div>
        </div>
    </div>
</div>
@endpush
