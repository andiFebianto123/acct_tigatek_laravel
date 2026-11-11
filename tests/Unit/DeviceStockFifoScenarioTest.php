<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Client;
use App\Models\DeviceStock;
use App\Models\DeviceStockCategory;
use App\Models\DeviceStockHistory;
use App\Models\DeviceStockMutation;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteDetail;
use App\Models\InvoiceClient;
use App\Models\InvoiceClientDetail;
use App\Models\ProjectProfitLost;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Subkon;
use App\DTOs\ClientManagement\DeliveryNoteData;
use App\DTOs\Invoice\InvoiceClientSaveData;
use App\DTOs\ProfitLost\ProfitLostFilterData;
use App\Repositories\ProfitLost\ProfitLostRepository;
use App\Services\ClientManagement\DeliveryNoteService;
use App\Services\Invoice\InvoiceClientService;
use App\Services\SubkonManagement\DeviceStockService as SubkonDeviceStockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DeviceStockFifoScenarioTest extends TestCase
{
    use DatabaseTransactions;

    protected DeliveryNoteService $deliveryNoteService;
    protected InvoiceClientService $invoiceClientService;
    protected SubkonDeviceStockService $subkonDeviceStockService;
    protected Company $company;
    protected Client $client;
    protected DeviceStockCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deliveryNoteService = app(DeliveryNoteService::class);
        $this->invoiceClientService = app(InvoiceClientService::class);
        $this->subkonDeviceStockService = app(SubkonDeviceStockService::class);

        $this->company = Company::first() ?? Company::create(['name' => 'PT Test Company', 'alias' => 'TEST']);
        $this->client = Client::first() ?? Client::create(['name' => 'Client Test', 'address' => 'Jakarta']);
        $this->category = DeviceStockCategory::first() ?? DeviceStockCategory::create(['name' => 'General Category']);
    }

    /**
     * Skenario 1: Data barang qty > 0 tanpa PO / device_stock_history
     * Menguji mekanisme fallback HPP ketika barang memiliki stok master tapi tidak ada layer history.
     */
    public function test_scenario_1_stock_qty_greater_than_zero_without_po_or_history()
    {
        // 1. Buat master barang dengan qty = 5 dan harga beli 100.000
        $stock = DeviceStock::create([
            'category_id'     => $this->category->id,
            'name'            => 'Barang Skenario 1 ' . time(),
            'code'            => 'SC1-' . time(),
            'qty'             => 5,
            'buy_price'       => 100000,
            'buy_price_base'  => 100000,
            'sell_price'      => 150000,
            'sell_price_base' => 150000,
            'currency_code'   => 'IDR',
            'exchange_rate'   => 1.0,
        ]);

        // Pastikan tidak ada record di device_stock_histories untuk mensimulasikan data lama tanpa history
        DeviceStockHistory::where('device_stock_id', $stock->id)->delete();
        DeviceStockMutation::where('device_stock_id', $stock->id)->delete();

        $this->assertEquals(0, DeviceStockHistory::where('device_stock_id', $stock->id)->count());
        $this->assertEquals(5, $stock->fresh()->qty);

        // 2. Buat Surat Jalan untuk 2 unit barang ini
        $data = new DeliveryNoteData(
            company_id: $this->company->id,
            client_po_id: null,
            invoice_client_id: null,
            client_id: $this->client->id,
            pic: 'Budi',
            phone: '08123456789',
            address: 'Jakarta',
            date: now()->format('Y-m-d'),
            number: 'SJ-SC1-' . time(),
            description: 'Pengiriman Skenario 1',
            qty: 2,
            information: 'Test Info',
            reference_type: null,
            reference_id: null,
            delivery_note_details: [
                [
                    'device_stock_id' => $stock->id,
                    'description'     => $stock->name,
                    'qty'             => 2,
                ]
            ]
        );

        $dn = $this->deliveryNoteService->createDeliveryNote($data);

        // 3. Verifikasi:
        $this->assertNotNull($dn);
        $detail = DeliveryNoteDetail::where('delivery_note_id', $dn->id)->first();
        $this->assertNotNull($detail);

        // HPP harus terhitung via fallback: 2 unit * 100.000 = 200.000 (BUKAN Rp 0!)
        $this->assertEquals(200000.0, (float) $detail->cogs_amount_base);

        // Stok master harus berkurang dari 5 menjadi 3
        $this->assertEquals(3, $stock->fresh()->qty);

        // Mutasi OUT tercatat
        $mutation = DeviceStockMutation::where('reference_type', DeliveryNote::class)
            ->where('reference_id', $dn->id)
            ->where('type', 'OUT')
            ->first();
        $this->assertNotNull($mutation);
        $this->assertEquals(-2, $mutation->qty_change);
    }

    /**
     * Skenario 2: Data barang qty > 0 dengan PO dan device_stock_history ada
     * Menguji alur normal FIFO: PO Supplier di-post stock, menghasilkan layer history, dipotong oleh Surat Jalan & Invoice.
     */
    public function test_scenario_2_stock_qty_greater_than_zero_with_po_and_history()
    {
        // 1. Buat master barang awal dengan qty = 0
        $stock = DeviceStock::create([
            'category_id'     => $this->category->id,
            'name'            => 'Barang Skenario 2 ' . time(),
            'code'            => 'SC2-' . time(),
            'qty'             => 0,
            'buy_price'       => 0,
            'buy_price_base'  => 0,
            'sell_price'      => 200000,
            'sell_price_base' => 200000,
            'currency_code'   => 'IDR',
            'exchange_rate'   => 1.0,
        ]);

        $subkon = Subkon::first() ?? Subkon::create(['name' => 'Supplier Alpha', 'status' => 'active']);

        // 2. Buat PO Supplier untuk 5 unit @ Rp 150.000
        $po = PurchaseOrder::create([
            'company_id'      => $this->company->id,
            'subkon_id'       => $subkon->id,
            'po_number'       => 'PO-SUPP-' . time(),
            'po_type'         => 'supplier',
            'date_po'         => now()->format('Y-m-d'),
            'date_subkon'     => now()->format('Y-m-d'),
            'currency_code'   => 'IDR',
            'exchange_rate'   => 1.0,
            'is_stock_posted' => false,
            'job_name'        => 'Pengadaan Device Stock',
            'status'          => 'open',
        ]);

        PurchaseOrderDetail::create([
            'purchase_order_id' => $po->id,
            'reference_type'    => DeviceStock::class,
            'reference_id'      => $stock->id,
            'name'              => $stock->name,
            'qty'               => 5,
            'price'             => 150000,
            'price_base'        => 150000,
        ]);

        // Post stock PO
        $this->subkonDeviceStockService->processSupplierPoItems($po);

        // Verifikasi layer history terbentuk
        $stockFresh = $stock->fresh();
        $this->assertEquals(5, $stockFresh->qty);
        $layer = DeviceStockHistory::where('device_stock_id', $stock->id)->where('qty', '>', 0)->first();
        $this->assertNotNull($layer);
        $this->assertEquals(5, $layer->qty);
        $this->assertEquals(150000.0, (float) $layer->buy_price_base);

        // 3. Buat Surat Jalan untuk 3 unit
        $data = new DeliveryNoteData(
            company_id: $this->company->id,
            client_po_id: null,
            invoice_client_id: null,
            client_id: $this->client->id,
            pic: 'Andi',
            phone: '08111111111',
            address: 'Surabaya',
            date: now()->format('Y-m-d'),
            number: 'SJ-SC2-' . time(),
            description: 'Pengiriman Skenario 2',
            qty: 3,
            information: 'Pengiriman PO',
            reference_type: null,
            reference_id: null,
            delivery_note_details: [
                [
                    'device_stock_id' => $stock->id,
                    'description'     => $stock->name,
                    'qty'             => 3,
                ]
            ]
        );

        $dn = $this->deliveryNoteService->createDeliveryNote($data);

        // 4. Verifikasi Surat Jalan
        $detail = DeliveryNoteDetail::where('delivery_note_id', $dn->id)->first();
        $this->assertNotNull($detail);
        // HPP FIFO: 3 unit * 150.000 = 450.000
        $this->assertEquals(450000.0, (float) $detail->cogs_amount_base);

        // Sisa stok master & layer berkurang menjadi 2 (5 - 3 = 2)
        $this->assertEquals(2, $stock->fresh()->qty);
        $this->assertEquals(2, $layer->fresh()->qty);

        // 5. Buat Invoice Client yang merujuk ke Surat Jalan ini
        $invDto = new InvoiceClientSaveData(
            invoice_number: 'INV-SC2-' . time(),
            description: 'Invoice Penjualan Barang',
            invoice_date: now()->format('Y-m-d'),
            client_po_id: null,
            nominal_exclude_ppn: 600000,
            nominal_include_ppn: 666000,
            tax_ppn: 66000,
            pph: 0,
            dpp_other: 0,
            kdp: null,
            withholding_agent: 'NON_PEMUNGUT',
            send_invoice_normal: null,
            send_invoice_revision: null,
            address_po: 'Surabaya',
            invoice_client_details: [
                [
                    'device_stock_id'          => $stock->id,
                    'delivery_note_detail_id'  => $detail->id,
                    'name'                     => $stock->name,
                    'qty'                      => 3,
                    'price'                    => 200000,
                ]
            ],
            company_id: $this->company->id,
            invoice_document: null,
            document_imei_iccid: null,
            account_source_id: null,
            type_device: DeviceStock::class,
            term: 30,
            client_id: $this->client->id,
            currency_code: 'IDR',
            delivery_note_id: $dn->id,
            pic: 'Andi',
            category: 'rutin',
        );

        $invoice = $this->invoiceClientService->createInvoice($invDto);

        // Verifikasi Invoice Client mengadopsi HPP dari Surat Jalan dan mengunci Surat Jalan
        $this->assertNotNull($invoice);
        $invDetail = $invoice->invoice_client_details()->first();
        $this->assertEquals(450000.0, (float) $invDetail->cogs_amount_base);
        $this->assertEquals($invoice->id, $dn->fresh()->invoice_client_id);
    }

    /**
     * Skenario 3: Data barang qty > 0 dengan device_stock_history kosong
     * Menguji bahwa stok tetap dapat keluar dan HPP dihitung via fallback modal master.
     */
    public function test_scenario_3_stock_qty_greater_than_zero_with_history_empty()
    {
        // Master barang memiliki qty = 4 dan modal 80.000
        $stock = DeviceStock::create([
            'category_id'     => $this->category->id,
            'name'            => 'Barang Skenario 3 ' . time(),
            'code'            => 'SC3-' . time(),
            'qty'             => 4,
            'buy_price'       => 80000,
            'buy_price_base'  => 80000,
            'sell_price'      => 120000,
            'sell_price_base' => 120000,
            'currency_code'   => 'IDR',
            'exchange_rate'   => 1.0,
        ]);

        // Kosongkan history
        DeviceStockHistory::where('device_stock_id', $stock->id)->delete();
        DeviceStockMutation::where('device_stock_id', $stock->id)->delete();

        $this->assertEquals(0, DeviceStockHistory::where('device_stock_id', $stock->id)->count());

        // Buat Surat Jalan untuk 4 unit sekaligus (menghabiskan stok fisik)
        $data = new DeliveryNoteData(
            company_id: $this->company->id,
            client_po_id: null,
            invoice_client_id: null,
            client_id: $this->client->id,
            pic: 'Cici',
            phone: '08133333333',
            address: 'Semarang',
            date: now()->format('Y-m-d'),
            number: 'SJ-SC3-' . time(),
            description: 'Pengiriman Skenario 3',
            qty: 4,
            information: 'Pengiriman Habis Stok',
            reference_type: null,
            reference_id: null,
            delivery_note_details: [
                [
                    'device_stock_id' => $stock->id,
                    'description'     => $stock->name,
                    'qty'             => 4,
                ]
            ]
        );

        $dn = $this->deliveryNoteService->createDeliveryNote($data);

        $detail = DeliveryNoteDetail::where('delivery_note_id', $dn->id)->first();
        // HPP Fallback: 4 unit * 80.000 = 320.000 (BUKAN Rp 0)
        $this->assertEquals(320000.0, (float) $detail->cogs_amount_base);

        // Stok master sekarang menjadi 0
        $this->assertEquals(0, $stock->fresh()->qty);
    }

    /**
     * Skenario 4: Data barang qty = 0
     * Menguji bahwa Surat Jalan ditolak dan melempar Exception karena stok tidak mencukupi.
     */
    public function test_scenario_4_stock_qty_equals_zero()
    {
        // Master barang dengan qty = 0
        $stock = DeviceStock::create([
            'category_id'     => $this->category->id,
            'name'            => 'Barang Skenario 4 ' . time(),
            'code'            => 'SC4-' . time(),
            'qty'             => 0,
            'buy_price'       => 50000,
            'buy_price_base'  => 50000,
            'sell_price'      => 90000,
            'sell_price_base' => 90000,
            'currency_code'   => 'IDR',
            'exchange_rate'   => 1.0,
        ]);

        $this->assertEquals(0, $stock->qty);

        // Coba buat Surat Jalan untuk 1 unit
        $data = new DeliveryNoteData(
            company_id: $this->company->id,
            client_po_id: null,
            invoice_client_id: null,
            client_id: $this->client->id,
            pic: 'Doni',
            phone: '08144444444',
            address: 'Medan',
            date: now()->format('Y-m-d'),
            number: 'SJ-SC4-' . time(),
            description: 'Pengiriman Skenario 4 Gagal',
            qty: 1,
            information: 'Pengiriman Barang Kosong',
            reference_type: null,
            reference_id: null,
            delivery_note_details: [
                [
                    'device_stock_id' => $stock->id,
                    'description'     => $stock->name,
                    'qty'             => 1,
                ]
            ]
        );

        // Harapkan exception karena stok tidak cukup
        $this->expectException(\Exception::class);
        $this->deliveryNoteService->createDeliveryNote($data);
    }

    /**
     * Uji tambahan: Pembuatan DeviceStock via DeviceStockService dengan stok awal
     * otomatis membentuk layer DeviceStockHistory dan mutasi IN (Saldo Awal).
     */
    public function test_create_stock_auto_generates_initial_history_layer()
    {
        $deviceStockService = app(\App\Services\DeviceStock\DeviceStockService::class);
        $dto = new \App\DTOs\DeviceStock\DeviceStockData(
            category_id: $this->category->id,
            name: 'Device Auto Saldo Awal ' . time(),
            code: 'AUTO-' . time(),
            qty: 10,
            buy_price: 250000,
            sell_price: 350000,
            currency_code: 'IDR',
        );

        $stock = $deviceStockService->createStock($dto);

        // Verifikasi stok master
        $this->assertNotNull($stock);
        $this->assertEquals(10, $stock->qty);
        $this->assertEquals(250000.0, (float) $stock->buy_price_base);

        // Verifikasi layer history otomatis terbuat
        $layer = DeviceStockHistory::where('device_stock_id', $stock->id)->first();
        $this->assertNotNull($layer);
        $this->assertEquals(10, $layer->qty);
        $this->assertEquals(250000.0, (float) $layer->buy_price_base);

        // Verifikasi mutasi IN saldo awal
        $mutation = DeviceStockMutation::where('device_stock_id', $stock->id)
            ->where('type', 'IN')
            ->first();
        $this->assertNotNull($mutation);
        $this->assertEquals(10, $mutation->qty_change);
        $this->assertStringContainsString('Saldo Awal', $mutation->note);
    }

    /**
     * Uji kasus spesifik user:
     * 1. Barang A belum ada history sama sekali (qty = 10 @ 100.000).
     * 2. Buat Surat Jalan #1 (qty = 4).
     * 3. Tambah PO Supplier (qty = 15 @ 120.000) & Post Stock.
     * 4. Buat Surat Jalan #2 (qty = 8).
     * Membuktikan bahwa alur FIFO berjalan murni: sisa stok lama dihabiskan dulu sebelum menyerap stok PO baru.
     */
    public function test_user_case_untracked_stock_then_sj_then_po_then_sj()
    {
        // 1. Barang A (stok master = 10, modal = 100.000, history dihapus manual)
        $stock = DeviceStock::create([
            'category_id'     => $this->category->id,
            'name'            => 'Barang User Case ' . time(),
            'code'            => 'UC-' . time(),
            'qty'             => 10,
            'buy_price'       => 100000,
            'buy_price_base'  => 100000,
            'sell_price'      => 150000,
            'sell_price_base' => 150000,
            'currency_code'   => 'IDR',
            'exchange_rate'   => 1.0,
        ]);

        DeviceStockHistory::where('device_stock_id', $stock->id)->delete();
        DeviceStockMutation::where('device_stock_id', $stock->id)->delete();

        $this->assertEquals(0, DeviceStockHistory::where('device_stock_id', $stock->id)->count());

        // 2. Buat Surat Jalan #1 (kirim 4 unit)
        $dataSj1 = new DeliveryNoteData(
            company_id: $this->company->id,
            client_po_id: null,
            invoice_client_id: null,
            client_id: $this->client->id,
            pic: 'PIC 1',
            phone: '08123456789',
            address: 'Jakarta',
            date: now()->format('Y-m-d'),
            number: 'SJ-UC-1-' . time(),
            description: 'Kirim Pertama',
            qty: 4,
            information: '',
            reference_type: null,
            reference_id: null,
            delivery_note_details: [
                [
                    'device_stock_id' => $stock->id,
                    'description'     => $stock->name,
                    'qty'             => 4,
                ]
            ]
        );

        $sj1 = $this->deliveryNoteService->createDeliveryNote($dataSj1);
        $detail1 = DeliveryNoteDetail::where('delivery_note_id', $sj1->id)->first();

        // Verifikasi SJ #1: HPP = 4 * 100.000 = 400.000. Sisa stok master = 6.
        $this->assertEquals(400000.0, (float) $detail1->cogs_amount_base);
        $this->assertEquals(6, $stock->fresh()->qty);

        // 2b. Buat Invoice Client #1 untuk SJ #1 (Jual 4 unit @ Rp 150.000)
        $invDto1 = new InvoiceClientSaveData(
            invoice_number: 'INV-UC-1-' . time(),
            description: 'Invoice Penjualan Pertama',
            invoice_date: now()->format('Y-m-d'),
            client_po_id: null,
            nominal_exclude_ppn: 600000,
            nominal_include_ppn: 666000,
            tax_ppn: 66000,
            pph: 0,
            dpp_other: 0,
            kdp: null,
            withholding_agent: 'NON_PEMUNGUT',
            send_invoice_normal: null,
            send_invoice_revision: null,
            address_po: 'Jakarta',
            invoice_client_details: [
                [
                    'device_stock_id'         => $stock->id,
                    'delivery_note_detail_id' => $detail1->id,
                    'name'                    => $stock->name,
                    'qty'                     => 4,
                    'price'                   => 150000,
                ]
            ],
            company_id: $this->company->id,
            invoice_document: null,
            document_imei_iccid: null,
            account_source_id: null,
            type_device: DeviceStock::class,
            term: 30,
            client_id: $this->client->id,
            currency_code: 'IDR',
            delivery_note_id: $sj1->id,
            pic: 'PIC 1',
            category: 'rutin',
        );

        $inv1 = $this->invoiceClientService->createInvoice($invDto1);
        $this->assertNotNull($inv1);

        // Verifikasi Laba Rugi untuk Invoice #1
        $profitLostRepo = app(ProfitLostRepository::class);
        $filter = new ProfitLostFilterData(
            year: 'all',
            category: null,
            type: null,
            startDate: null,
            endDate: null,
            id: null,
            companyId: null
        );

        $plQuery1 = ProjectProfitLost::query();
        $profitLostRepo->applySupplierListQuery($plQuery1, $filter);
        $plRow1 = $plQuery1->where('project_profit_lost.orderable_id', $inv1->id)->first();

        $this->assertNotNull($plRow1, 'Data Laba Rugi Invoice 1 harus ditemukan');
        $this->assertEquals(4, (int) $plRow1->total_qty_sold);
        $this->assertEquals(600000.0, (float) $plRow1->sell_value);
        // Nilai HPP FIFO di Laba Rugi untuk Invoice 1
        $this->assertEquals(400000.0, (float) $plRow1->purchase_value);
        // Laba Kotor = 600.000 - 400.000 = 200.000
        $this->assertEquals(200000.0, (float) $plRow1->profit_lost_supplier);
        $this->assertEquals('Terbit Surat Jalan & FIFO Posted', $plRow1->delivery_status);

        // 3. Admin buat PO Supplier baru (15 unit @ Rp 120.000) & Post Stock
        $subkon = Subkon::first() ?? Subkon::create(['name' => 'Supplier Beta', 'status' => 'active']);
        $po = PurchaseOrder::create([
            'company_id'      => $this->company->id,
            'subkon_id'       => $subkon->id,
            'po_number'       => 'PO-UC-' . time(),
            'po_type'         => 'supplier',
            'job_name'        => 'Pengadaan Stok Tambahan',
            'date_po'         => now()->format('Y-m-d'),
            'date_subkon'     => now()->format('Y-m-d'),
            'currency_code'   => 'IDR',
            'exchange_rate'   => 1.0,
            'is_stock_posted' => false,
            'status'          => 'open',
        ]);

        PurchaseOrderDetail::create([
            'purchase_order_id' => $po->id,
            'reference_type'    => DeviceStock::class,
            'reference_id'      => $stock->id,
            'name'              => $stock->name,
            'qty'               => 15,
            'price'             => 120000,
            'price_base'        => 120000,
        ]);

        $this->subkonDeviceStockService->processSupplierPoItems($po);

        // Stok master sekarang: 6 (sisa lama) + 15 (PO baru) = 21 unit
        $this->assertEquals(21, $stock->fresh()->qty);

        // 4. Buat Surat Jalan #2 (kirim 8 unit)
        // Menurut FIFO: 6 unit dari saldo lama (@ 100.000) + 2 unit dari PO baru (@ 120.000)
        // Total HPP yang diharapkan = (6 * 100.000) + (2 * 120.000) = 600.000 + 240.000 = 840.000
        $dataSj2 = new DeliveryNoteData(
            company_id: $this->company->id,
            client_po_id: null,
            invoice_client_id: null,
            client_id: $this->client->id,
            pic: 'PIC 2',
            phone: '08123456789',
            address: 'Jakarta',
            date: now()->format('Y-m-d'),
            number: 'SJ-UC-2-' . time(),
            description: 'Kirim Kedua',
            qty: 8,
            information: '',
            reference_type: null,
            reference_id: null,
            delivery_note_details: [
                [
                    'device_stock_id' => $stock->id,
                    'description'     => $stock->name,
                    'qty'             => 8,
                ]
            ]
        );

        $sj2 = $this->deliveryNoteService->createDeliveryNote($dataSj2);
        $detail2 = DeliveryNoteDetail::where('delivery_note_id', $sj2->id)->first();

        // Verifikasi SJ #2: HPP harus tepat 840.000!
        $this->assertEquals(840000.0, (float) $detail2->cogs_amount_base);

        // Sisa stok master akhir: 21 - 8 = 13 unit
        $this->assertEquals(13, $stock->fresh()->qty);

        // 4b. Buat Invoice Client #2 untuk SJ #2 (Jual 8 unit @ Rp 160.000 = 1.280.000)
        $invDto2 = new InvoiceClientSaveData(
            invoice_number: 'INV-UC-2-' . time(),
            description: 'Invoice Penjualan Kedua',
            invoice_date: now()->format('Y-m-d'),
            client_po_id: null,
            nominal_exclude_ppn: 1280000,
            nominal_include_ppn: 1420800,
            tax_ppn: 140800,
            pph: 0,
            dpp_other: 0,
            kdp: null,
            withholding_agent: 'NON_PEMUNGUT',
            send_invoice_normal: null,
            send_invoice_revision: null,
            address_po: 'Jakarta',
            invoice_client_details: [
                [
                    'device_stock_id'         => $stock->id,
                    'delivery_note_detail_id' => $detail2->id,
                    'name'                    => $stock->name,
                    'qty'                     => 8,
                    'price'                   => 160000,
                ]
            ],
            company_id: $this->company->id,
            invoice_document: null,
            document_imei_iccid: null,
            account_source_id: null,
            type_device: DeviceStock::class,
            term: 30,
            client_id: $this->client->id,
            currency_code: 'IDR',
            delivery_note_id: $sj2->id,
            pic: 'PIC 2',
            category: 'rutin',
        );

        $inv2 = $this->invoiceClientService->createInvoice($invDto2);
        $this->assertNotNull($inv2);

        // Verifikasi Laba Rugi untuk Invoice #2
        $plQuery2 = ProjectProfitLost::query();
        $profitLostRepo->applySupplierListQuery($plQuery2, $filter);
        $plRow2 = $plQuery2->where('project_profit_lost.orderable_id', $inv2->id)->first();

        $this->assertNotNull($plRow2, 'Data Laba Rugi Invoice 2 harus ditemukan');
        $this->assertEquals(8, (int) $plRow2->total_qty_sold);
        $this->assertEquals(1280000.0, (float) $plRow2->sell_value);
        // Nilai HPP FIFO di Laba Rugi untuk Invoice 2 harus persis Rp 840.000
        $this->assertEquals(840000.0, (float) $plRow2->purchase_value);
        // Laba Kotor = 1.280.000 - 840.000 = 440.000
        $this->assertEquals(440000.0, (float) $plRow2->profit_lost_supplier);
        $this->assertEquals('Terbit Surat Jalan & FIFO Posted', $plRow2->delivery_status);
    }

    /**
     * Uji Artisan command fifo:sync-legacy-hpp:
     * Memastikan query HANYA memanggil invoice yang memiliki rincian dengan cogs_amount_base atau cogs_amount = 0.
     * Invoice yang sudah memiliki nilai HPP tidak dipanggil sama sekali.
     */
    public function test_command_sync_legacy_invoice_hpp_only_queries_invoices_with_zero_cogs()
    {
        $stock = DeviceStock::create([
            'category_id'     => $this->category->id,
            'name'            => 'Barang Test Command ' . time(),
            'code'            => 'CMD-' . time(),
            'qty'             => 10,
            'buy_price'       => 50000,
            'buy_price_base'  => 50000,
            'sell_price'      => 80000,
            'sell_price_base' => 80000,
            'currency_code'   => 'IDR',
            'exchange_rate'   => 1.0,
        ]);

        // Invoice A: Sudah punya HPP valid (cogs_amount_base = 100.000)
        $invA = InvoiceClient::create([
            'name'                        => 'invoice',
            'address_po'                  => 'Jakarta',
            'invoice_number'              => 'INV-CMD-VALID-' . time(),
            'description'                 => 'Invoice A Valid HPP',
            'invoice_date'                => now()->format('Y-m-d'),
            'type_device'                 => DeviceStock::class,
            'company_id'                  => $this->company->id,
            'client_id'                   => $this->client->id,
            'price_total_exclude_ppn'     => 160000,
            'price_total_exclude_ppn_base'=> 160000,
            'price_total_include_ppn'     => 177600,
            'price_total'                 => 177600,
            'status'                      => 'Unpaid',
        ]);

        $detailA = InvoiceClientDetail::create([
            'invoice_client_id' => $invA->id,
            'device_stock_id'   => $stock->id,
            'name'              => $stock->name,
            'qty'               => 2,
            'price'             => 80000,
            'price_base'        => 80000,
            'cogs_amount'       => 100000,
            'cogs_amount_base'  => 100000,
        ]);

        // Invoice B: Belum punya HPP (cogs_amount_base = 0)
        $invB = InvoiceClient::create([
            'name'                        => 'invoice',
            'address_po'                  => 'Jakarta',
            'invoice_number'              => 'INV-CMD-ZERO-' . time(),
            'description'                 => 'Invoice B Zero HPP',
            'invoice_date'                => now()->format('Y-m-d'),
            'type_device'                 => DeviceStock::class,
            'company_id'                  => $this->company->id,
            'client_id'                   => $this->client->id,
            'price_total_exclude_ppn'     => 240000,
            'price_total_exclude_ppn_base'=> 240000,
            'price_total_include_ppn'     => 266400,
            'price_total'                 => 266400,
            'status'                      => 'Unpaid',
        ]);

        $detailB = InvoiceClientDetail::create([
            'invoice_client_id' => $invB->id,
            'device_stock_id'   => $stock->id,
            'name'              => $stock->name,
            'qty'               => 3,
            'price'             => 80000,
            'price_base'        => 80000,
            'cogs_amount'       => 0,
            'cogs_amount_base'  => 0,
        ]);

        // Jalankan command khusus untuk invoice B
        \Illuminate\Support\Facades\Artisan::call('fifo:sync-legacy-hpp', [
            '--invoice' => $invB->id,
        ]);

        // Verifikasi detail Invoice B sekarang sudah terisi HPP: 3 unit * 50.000 = 150.000
        $this->assertEquals(150000.0, (float) $detailB->fresh()->cogs_amount_base);

        // Invoice A tetap tidak terganggu
        $this->assertEquals(100000.0, (float) $detailA->fresh()->cogs_amount_base);
    }
}
