<div>
    <div class="row">
        <div class="col-md-3">
            <div id="list-account-setting" class="list-group">
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="#list-item-1">
                    Pengaturan Merk
                    <i class="la la-angle-right"></i>
                </a>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="#list-item-2">
                    Pengaturan Sistem
                    <i class="la la-angle-right"></i>
                </a>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="#list-item-3">
                    Pengaturan Perusahaan
                    <i class="la la-angle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-6" data-bs-spy="scroll" data-bs-target="#list-account-setting" data-bs-offset="0" class="scrollspy-example" tabindex="0">
            <div class="card2 mb-4" id="list-item-1">
                <div class="card2-parent-header">
                    <div class="card2-header fs-6">Pengaturan Merk</div>
                </div>
                <div class="card2-body">
                    <form action="{{ url($crud->route.'/updated-logo') }}" id="form-update-logo" method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card2">
                                <div class="card2-parent-header">
                                    <div class="card2-header fs-6">Logo Dark</div>
                                </div>
                                <div class="card2-body">
                                    @if ($setting?->logo_dark != null)
                                    <img src="{{ asset("storage/logos/".$setting->logo_dark) }}" width="200" class="sidebar-brand-full mb-4">
                                    @else
                                    <img src="{{ asset("kp-logo.png") }}" width="200" class="sidebar-brand-full mb-4">
                                    @endif
                                    <div class="">
                                        <div class="d-flex align-items-center gap-2 form-group">
                                            <button type="button" class="btn btn-primary" onclick="document.getElementById('upload-input-logo-dark').click()"><i class="la la-upload"></i> Upload File Here</button>
                                            <span id="file-name-dark" class=""></span>
                                        </div>
                                        <input type="file" id="upload-input-logo-dark" name="logo_dark" class="d-none" onchange="updateFileName(this, 'file-name-dark')">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card2">
                                <div class="card2-parent-header">
                                    <div class="card2-header fs-6">Logo Light</div>
                                </div>
                                <div class="card2-body">
                                    @if ($setting?->logo_light != null)
                                    <img src="{{ asset("storage/logos/".$setting->logo_light) }}" width="200" class="sidebar-brand-full mb-4">
                                    @else
                                    <img src="{{ asset("kp-logo.png") }}" width="200" class="sidebar-brand-full mb-4">
                                    @endif
                                    <div class="">
                                        <div class="d-flex align-items-center gap-2 form-group">
                                            <button type="button" class="btn btn-primary" onclick="document.getElementById('upload-input-logo-light').click()"><i class="la la-upload"></i> Upload File Here</button>
                                            <span id="file-name-light" class=""></span>
                                        </div>
                                        <input type="file" id="upload-input-logo-light" name="logo_light" class="d-none" onchange="updateFileName(this, 'file-name-light')">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="card2">
                                <div class="card2-parent-header">
                                    <div class="card2-header fs-6">Favicon</div>
                                </div>
                                <div class="card2-body">
                                    @if ($setting?->favicon != null)
                                    <img src="{{ asset("storage/logos/".$setting->favicon) }}" width="200" class="sidebar-brand-full mb-4">
                                    @else
                                    <img src="{{ asset("kp-logo.png") }}" width="200" class="sidebar-brand-full mb-4">
                                    @endif
                                    <div class="">
                                        <div class="d-flex align-items-center gap-2 form-group">
                                            <button type="button" class="btn btn-primary" onclick="document.getElementById('upload-input-favicon').click()"><i class="la la-upload"></i> Upload File Here</button>
                                            <span id="file-name-favicon" class=""></span>
                                        </div>
                                        <input type="file" id="upload-input-favicon" name="favicon" class="d-none" onchange="updateFileName(this, 'file-name-favicon')">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="mb-3 form-group">
                        <label class="form-label d-block">Mode Gelap</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="dark_mode" role="switch" id="nomorPajakSwitch">
                        </div>
                    </div> --}}
                    <div class="text-end">
                        <button type="submit" id="btn-update-logo" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="card2 mb-4" id="list-item-2">
                <div class="card2-parent-header">
                    <div class="card2-header fs-6">Pengaturan Sistem</div>
                </div>
                <div class="card2-body">
                    <form action="{{ url($crud->route.'/updated-system') }}" method="POST" id="form-update-system">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="mb-3 form-group">
                                    <label for="mataUang" class="form-label">Mata Uang</label>
                                    <input type="text" class="form-control" id="mataUang" name="currency" value="{{$setting?->currency}}">
                                </div>
                                <div class="form-group" style="margin-bottom:1.75rem;">
                                    <label class="form-label">Posisi Simbol Mata Uang</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="position_currency_symbol" id="posisiPre" value="pre" {{ ($setting?->position_currency_symbol == 'pre') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="posisiPre">Pre</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="position_currency_symbol" id="posisiPost" value="post" {{ ($setting?->position_currency_symbol == 'post') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="posisiPost">Post</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="poPrefix" class="form-label">PO Prefix</label>
                                    <input type="text" class="form-control" id="poPrefix" name="po_prefix" value="{{$setting?->po_prefix}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="kodeKerjaPrefix" class="form-label">Kode Kerja Prefix</label>
                                    <input type="text" class="form-control" id="kodeKerjaPrefix" name="work_code_prefix" value="{{$setting?->work_code_prefix}}">
                                </div>
                                 <div class="mb-3 form-group">
                                    <label for="fakturPrefix" class="form-label">Faktur Prefix</label>
                                    <input type="text" class="form-control" id="fakturPrefix" name="faktur_prefix" value="{{$setting?->faktur_prefix}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="piPrefix" class="form-label">{{ trans('backpack::crud.setting.pi_prefix') }}</label>
                                    <input type="text" class="form-control" id="piPrefix" name="pi_prefix" value="{{$setting?->pi_prefix}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="quotationPrefix" class="form-label">{{ trans('backpack::crud.setting.quotation_prefix') }}</label>
                                    <input type="text" class="form-control" id="quotationPrefix" name="quotation_prefix" value="{{$setting?->quotation_prefix}}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-group">
                                    <label for="simbolMataUang" class="form-label">Simbol Mata Uang</label>
                                    <input type="text" class="form-control" id="simbolMataUang" name="currency_symbol" value="{{$setting?->currency_symbol}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="formatAngkaDesimal" class="form-label">Format Angka Desimal</label>
                                    <input type="number" class="form-control" id="formatAngkaDesimal" name="format_decimal_number" value="{{$setting?->format_decimal_number}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="spkPrefix" class="form-label">SPK Prefix</label>
                                    <input type="text" class="form-control" id="spkPrefix" name="spk_prefix" value="{{$setting?->spk_prefix}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="voucherPrefix" class="form-label">Voucher Prefix</label>
                                    <input type="text" class="form-control" id="voucherPrefix" name="vouhcer_prefix" value="{{$setting?->vouhcer_prefix}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="invoicePrefix" class="form-label">Invoice Prefix</label>
                                    <input type="text" class="form-control" id="invoicePrefix" name="invoice_prefix" value="{{$setting?->invoice_prefix}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="suratJalanPrefix" class="form-label">{{ trans('backpack::crud.setting.surat_jalan_prefix') }}</label>
                                    <input type="text" class="form-control" id="suratJalanPrefix" name="surat_jalan_prefix" value="{{$setting?->surat_jalan_prefix}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="bastPrefix" class="form-label">{{ trans('backpack::crud.setting.bast_prefix') }}</label>
                                    <input type="text" class="form-control" id="bastPrefix" name="bast_prefix" value="{{$setting?->bast_prefix}}">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" id="btn-update-system" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card2 mb-4" id="list-item-3">
                <div class="card2-parent-header">
                    <div class="card2-header fs-6">Pengaturan Perusahaan</div>
                </div>
                <div class="card2-body">
                    <form action="{{ url($crud->route.'/updated-company') }}" method="POST" id="form-update-company">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="mb-3 form-group">
                                    <label for="namaPerusahaan" class="form-label">Nama Perusahaan</label>
                                    <input type="text" class="form-control" id="namaPerusahaan" name="name_company" value="{{$setting?->name_company}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="kotaKabupaten" class="form-label">Kota/Kabupaten</label>
                                    <input type="text" class="form-control" id="kotaKabupaten" name="city" value="{{$setting?->city}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="zipKodePos" class="form-label">ZIP/Kode Pos</label>
                                    <input type="text" class="form-control" id="zipKodePos" name="zip_code" value="{{$setting?->zip_code}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="telepon" class="form-label">Telepon</label>
                                    <input type="tel" class="form-control" id="telepon" name="telp" value="{{$setting?->telp}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="waktuMulai" class="form-label">Waktu Mulai Perusahaan</label>
                                    <input type="time" class="form-control" id="waktuMulai" name="start_time" value="{{$setting?->start_time}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label class="form-label d-block">Nomor Pajak</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="nomorPajakSwitch" name="no_fax" value="1" {{ ($setting?->no_fax == 1) ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-group">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <input type="text" class="form-control" id="alamat" name="address" value="{{$setting?->address}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="provinsi" class="form-label">Provinsi</label>
                                    <input type="text" class="form-control" id="provinsi" name="province" value="{{$setting?->province}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="negara" class="form-label">Negara</label>
                                    <input type="text" class="form-control" id="negara" name="country" value="{{$setting?->country}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="nomorRegistrasi" class="form-label">Nomor Registrasi Perusahaan</label>
                                    <input type="text" class="form-control" id="nomorRegistrasi" name="no_register_company" value="{{$setting?->no_register_company}}">
                                </div>
                                <div class="mb-3 form-group">
                                    <label for="waktuBerakhir" class="form-label">Waktu Berakhir Perusahaan</label>
                                    <input type="time" class="form-control" id="waktuBerakhir" name="end_time" value="{{$setting?->end_time}}">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" id="btn-update-company" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
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

    .list-group-item.active {
        z-index: 2;
        background-color: #145388 !important;
        border-color: #145388 !important;
    }

    </style>
@endpush

@push('after_scripts')
<script>
    function updateFileName(input, file_name) {
        const fileName = input.files[0] ? input.files[0].name : 'Belum ada file dipilih';
        document.getElementById(file_name).textContent = fileName;
    }
</script>
<script>

    $('#btn-update-logo').on('click', async function (e) {
        e.preventDefault();
        var url = $('#form-update-logo').attr('action');

        var lazySubmit = await new Promise((resolve, reject) => {
            setTimeout(() => {
                resolve(1)
            }, 100);
        });

        var formData = new FormData($('#form-update-logo')[0]);
        normalizeShowMessage('form-update-logo');
        setLoadingButton('#btn-update-logo', true);
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            typeData: 'json',
            success: function (data) {
                setLoadingButton('#btn-update-logo', false);
                if(data.success){
                    swal({
                        title: "Success",
                        text: "{!! trans('backpack::crud.update_success') !!}",
                        icon: "success",
                        timer: 4000,
                        buttons: false,
                    });
                    window.location.reload();
                    if(window.crud.table){
                        window.crud.table.ajax.reload();
                    }
                    if(data.events){
                        forEachFlexible(data.events, function(eventname, data){
                            eventEmitter.emit(eventname, data);
                        });
                    }
                }else{
                    swal({
                        title: "{!! trans('backpack::crud.ajax_error_title') !!}",
                        text: data.error,
                        icon: "error",
                        timer: 4000,
                        buttons: false,
                    });
                }
            },
            error: function (xhr, status, error) {
                // console.error(xhr);
                setLoadingButton('#btn-update-logo', true);
                errorShowMessage('form-update-logo', xhr.responseJSON.errors);
            }
        });
    })

    $('#btn-update-system').on('click', async function (e) {
        e.preventDefault();
        var url = $('#form-update-system').attr('action');

        var lazySubmit = await new Promise((resolve, reject) => {
            setTimeout(() => {
                resolve(1)
            }, 100);
        });

        var formData = new FormData($('#form-update-system')[0]);
        normalizeShowMessage('form-update-system');
        setLoadingButton('#btn-update-system', true);
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            typeData: 'json',
            success: function (data) {
                setLoadingButton('#btn-update-system', false);
                if(data.success){
                    swal({
                        title: "Success",
                        text: "{!! trans('backpack::crud.update_success') !!}",
                        icon: "success",
                        timer: 4000,
                        buttons: false,
                    });
                    if(window.crud.table){
                        window.crud.table.ajax.reload();
                    }
                    if(data.events){
                        forEachFlexible(data.events, function(eventname, data){
                            eventEmitter.emit(eventname, data);
                        });
                    }
                }else{
                    swal({
                        title: "{!! trans('backpack::crud.ajax_error_title') !!}",
                        text: data.error,
                        icon: "error",
                        timer: 4000,
                        buttons: false,
                    });
                }
            },
            error: function (xhr, status, error) {
                // console.error(xhr);
                setLoadingButton('#btn-update-system', true);
                errorShowMessage('form-update-system', xhr.responseJSON.errors);
            }
        });
    });

    $('#btn-update-company').on('click', async function (e) {
        e.preventDefault();
        var url = $('#form-update-company').attr('action');

        var lazySubmit = await new Promise((resolve, reject) => {
            setTimeout(() => {
                resolve(1)
            }, 100);
        });

        var formData = new FormData($('#form-update-company')[0]);
        normalizeShowMessage('form-update-company');
        setLoadingButton('#btn-update-company', true);
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            typeData: 'json',
            success: function (data) {
                setLoadingButton('#btn-update-company', false);
                if(data.success){
                    swal({
                        title: "Success",
                        text: "{!! trans('backpack::crud.update_success') !!}",
                        icon: "success",
                        timer: 4000,
                        buttons: false,
                    });
                    if(window.crud.table){
                        window.crud.table.ajax.reload();
                    }
                    if(data.events){
                        forEachFlexible(data.events, function(eventname, data){
                            eventEmitter.emit(eventname, data);
                        });
                    }
                }else{
                    swal({
                        title: "{!! trans('backpack::crud.ajax_error_title') !!}",
                        text: data.error,
                        icon: "error",
                        timer: 4000,
                        buttons: false,
                    });
                }
            },
            error: function (xhr, status, error) {
                // console.error(xhr);
                setLoadingButton('#btn-update-company', false);
                errorShowMessage('form-update-company', xhr.responseJSON.errors);
            }
        });
    });
</script>
@endpush
