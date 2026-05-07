<button id="btn-export-pdf-voucher-payment" class="btn btn-primary">
    <i class="la la-file-download"></i> PDF
</button>

@push('after_scripts')
    <script>
        if(SIAOPS.getAttribute('export') == null){
            SIAOPS.setAttribute('export', function(){
                return {
                    tab_active: "",
                    url_pdf: "",
                    title_pdf: "",
                    url_excel: "",
                    title_excel: "",
                }
            });
        }

        $('#btn-export-pdf-voucher-payment').click(async function (){
            setLoadingButton("#btn-export-pdf-voucher-payment", true);

            var get_url_export = SIAOPS.getAttribute('export').url_pdf;
            var get_title_export = SIAOPS.getAttribute('export').title_pdf;
            var tab_active = SIAOPS.getAttribute('export').tab_active;

            // Gabungkan semua parameter ke dalam satu objek payload
            var payload = {
                searchRutin: {
                    columns: SIAOPS.getAttribute('crudTable-voucher_payment_rutin').table.ajax.params().columns,
                },
                searchNonRutin: {
                    columns: SIAOPS.getAttribute('crudTable-voucher_payment_non_rutin').table.ajax.params().columns
                },
                ...SIAOPS.getAttribute('SETUP_ALL_FILTER_voucher_payment_non_rutin').filterValues,
                type: 'export',
            };

            if(get_url_export == ''){
                setLoadingButton("#btn-export-pdf-voucher-payment", false);
                swal({
                    title: "Error",
                    text: "Export PDF URL not defined",
                    icon: "error",
                    timer: 4000,
                    buttons: false,
                });
                return;
            }

            // Kirim payload sebagai argumen ketiga (Body Request) bukan di URL
            const {response, errors} = await API_REQUEST("DOWNLOAD", get_url_export, payload);
            
            if(errors){
                swal({
                    title: "Error",
                    text: "Terjadi kesalahan saat mengunduh file PDF",
                    icon: "error",
                    timer: 4000,
                    buttons: false,
                });
                setLoadingButton("#btn-export-pdf-voucher-payment", false);
            }else if(response){
                let result = await response;
                setLoadingButton("#btn-export-pdf-voucher-payment", false);

                const url = window.URL.createObjectURL(result);
                const a = document.createElement('a');
                a.href = url;
                a.download = get_title_export;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            }
        });
    </script>
@endpush
