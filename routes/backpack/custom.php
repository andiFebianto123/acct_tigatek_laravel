<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SpkCrudController;
use App\Http\Controllers\Admin\RoleCrudController;
use App\Http\Controllers\Admin\AssetCrudController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ClientCrudController;
use App\Http\Controllers\Admin\SubkonCrudController;
use App\Http\Controllers\Admin\VoucherCrudController;
use App\Http\Controllers\Admin\ClientPoCrudController;
use App\Http\Controllers\Admin\QuotationCrudController;
use App\Http\Controllers\Admin\ClientQuotationCrudController;
use App\Http\Controllers\Admin\AccountUserCrudController;
use App\Http\Controllers\Admin\ProjectListCrudController;
use App\Http\Controllers\Admin\BalanceSheetCrudController;
use App\Http\Controllers\Admin\CastAccountsCrudController;
use App\Http\Controllers\Admin\InvoiceClientCrudController;
use App\Http\Controllers\Admin\PurchaseOrderCrudController;
use App\Http\Controllers\Admin\SettingSystemCrudController;
use App\Http\Controllers\Admin\StatusProjectCrudController;
use App\Http\Controllers\Admin\ExpenseAccountCrudController;
use App\Http\Controllers\Admin\QuotationCheckCrudController;
use App\Http\Controllers\Admin\StatusQuotaionCrudController;
use App\Http\Controllers\Admin\VoucherPaymentCrudController;
use App\Http\Controllers\Admin\CastAccountsLoanCrudController;
use App\Http\Controllers\Admin\ProfitLostAccountCrudController;
use App\Http\Controllers\Admin\ProjectListReportCrudController;
use App\Http\Controllers\Admin\ProjectSystemSetupCrudController;
use App\Http\Controllers\Admin\MonitoringTrackerCrudController;
use App\Http\Controllers\Admin\VoucherPaymentPlanCrudController;


// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group(['middleware' => 'web', 'prefix' => config('backpack.base.route_prefix'), 'namespace' => 'App\Http\Controllers'], function () {
    Route::get('login', 'Auth\LoginController@showLoginForm')->name('backpack.auth.login');
    Route::post('login', 'Auth\LoginController@login');
    Route::get('logout', 'Auth\LoginController@logout')->name('backpack.auth.logout');
    Route::post('logout', 'Auth\LoginController@logout');

    Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('backpack.auth.register');
    Route::post('register', 'Auth\RegisterController@register');
});

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('tag', 'TagCrudController');
    Route::crud('dashboard', 'DashboardController');
    Route::get('dashboard/get-chart', [DashboardController::class, 'totalAlldashboard']);
    Route::prefix('setting')->group(function () {
        Route::crud('permission', 'PermissionCrudController');
        Route::crud('role', 'RoleCrudController');
        Route::post('role/get-user-role', [RoleCrudController::class, 'getUserRole']);
        Route::crud('user', 'UserCrudController');
        Route::crud('account', 'AccountUserCrudController');
        Route::post('account/updated', [AccountUserCrudController::class, 'update_personal']);
        Route::post('account/updated_password', [AccountUserCrudController::class, 'update_password']);
        Route::crud('system', 'SettingSystemCrudController');
        Route::post('system/updated-logo', [SettingSystemCrudController::class, 'updateLogo']);
        Route::post('system/updated-system', [SettingSystemCrudController::class, 'updateSystem']);
        Route::post('system/updated-company', [SettingSystemCrudController::class, 'updateCompany']);
    });
    Route::prefix('vendor')->group(function () {
        Route::crud('subkon', 'SubkonCrudController');
        Route::post('subkon/export-pdf', [SubkonCrudController::class, 'exportPdf']);
        Route::post('subkon/export-excel', [SubkonCrudController::class, 'exportExcel']);
        Route::post('select2-subkon-id', 'PurchaseOrderCrudController@select2SubkonId')->name('select2-subkon-id');
        Route::crud('purchase-order', 'PurchaseOrderCrudController');
        Route::get('purchase-order/total', [PurchaseOrderCrudController::class, 'total_price']);
        Route::crud('purchase-order-tab', 'PurchaseOrderTabController');
        Route::crud('spk-trans', 'SpkCrudController');
        Route::get('spk-trans/total', [SpkCrudController::class, 'total_price']);
        Route::post('spk-trans/export-pdf', [SpkCrudController::class, 'exportPdf']);
        Route::post('spk-trans/export-excel', [SpkCrudController::class, 'exportExcel']);
        Route::post('download-po', 'PurchaseOrderCrudController@exportExcel');
        Route::post('download-po-pdf', 'PurchaseOrderCrudController@exportPdf');
    });

    Route::prefix('client')->group(function () {
        Route::crud('client-list', 'ClientCrudController');
        Route::post('client-list/export-pdf', [ClientCrudController::class, 'exportPdf']);
        Route::post('client-list/export-excel', [ClientCrudController::class, 'exportExcel']);
        Route::match(['get', 'post', 'put'], 'select2-client', 'ClientPoCrudController@select2Client');
        Route::crud('po', 'ClientPoCrudController');
        Route::get('po/total-without-po', [ClientPoCrudController::class, 'select_count_without_po']);
        Route::post('po/export-pdf', [ClientPoCrudController::class, 'exportPdf']);
        Route::post('po/export-excel', [ClientPoCrudController::class, 'exportExcel']);
        Route::get('po/total', [ClientPoCrudController::class, 'countAllPPn']);
        Route::get('po/get-quotations', [ClientPoCrudController::class, 'getQuotations']);
        Route::get('po/get-quotation-details', [ClientPoCrudController::class, 'getQuotationDetails']);

        Route::crud('quotation', 'ClientQuotationCrudController');
        // Route::match(['get', 'post'], 'quotation/select2-client', 'ClientQuotationCrudController@select2Client');
        // Route::post('quotation/select2-client', [ClientQuotationCrudController::class, 'select2Client']);
        Route::post('quotation/export-pdf', [ClientQuotationCrudController::class, 'exportPdf']);
        Route::post('quotation/export-excel', [ClientQuotationCrudController::class, 'exportExcel']);
        Route::get('quotation/total', [ClientQuotationCrudController::class, 'countAllPPn']);
        Route::get('quotation/total-without-po', 'ClientQuotationCrudController@totalWithoutPo');
    });
    Route::post('invoice-client/export-pdf', [InvoiceClientCrudController::class, 'exportPdf']);
    Route::post('invoice-client/export-excel', [InvoiceClientCrudController::class, 'exportExcel']);
    Route::get('invoice-client/{id}/print', [InvoiceClientCrudController::class, 'printInvoice']);
    Route::match(['get', 'post', 'put'], 'invoice-client/select2-client-po', [InvoiceClientCrudController::class, 'select2ClientPo']);
    Route::match(['get', 'post', 'put'], 'invoice-client/get-client-po', [InvoiceClientCrudController::class, 'selectedClientPo']);
    Route::get('invoice-client/total', [InvoiceClientCrudController::class, 'total_price']);
    Route::delete('invoice-client/void-payment/{id}', [InvoiceClientCrudController::class, 'voidPayment']);
    Route::crud('invoice-client', 'InvoiceClientCrudController');

    Route::prefix('cash-flow')->group(function () {
        Route::crud('cast-accounts', 'CastAccountsCrudController');
        Route::post('cast-accounts/export-pdf', [CastAccountsCrudController::class, 'exportPdf']);
        Route::post('cast-accounts/export-excel', [CastAccountsCrudController::class, 'exportExcel']);
        Route::post('cast-accounts/export-trans-pdf', [CastAccountsCrudController::class, 'exportTransPdf']);
        Route::post('cast-accounts/export-trans-excel', [CastAccountsCrudController::class, 'exportTransExcel']);
        Route::post('cast-accounts/save-bank', [CastAccountsCrudController::class, 'saveBankAjax']);
        Route::post('cast-accounts-transaction', [CastAccountsCrudController::class, 'storeTransaction']);
        Route::get('cast-accounts-show', [CastAccountsCrudController::class, 'showTransaction']);
        Route::get('cast-accounts-datatable', [CastAccountsCrudController::class, 'getTransactionDataTable']);
        Route::get('cast-accounts-select-to-account', [CastAccountsCrudController::class, 'getSelectToAccount']);
        Route::post('cast-accounts-select-to-invoice', [CastAccountsCrudController::class, 'select2Invoice']);
        Route::get('cast-accounts/get-invoice', [CastAccountsCrudController::class, 'get_invoice_ajax']);
        Route::post('cast-accounts-move-transaction', [CastAccountsCrudController::class, 'storeMoveTransfer']);
        Route::delete('cast-accounts/delete-transaction/{id}', [CastAccountsCrudController::class, 'destroyTransaction']);
        Route::delete('cast-accounts/delete-transaction-void/{id}', [CastAccountsCrudController::class, 'destroyTransactionVoid']);

        Route::crud('cast-account-loan', 'CastAccountsLoanCrudController');
        Route::post('cast-account-loan/export-pdf', [CastAccountsLoanCrudController::class, 'exportPdf']);
        Route::post('cast-account-loan/export-excel', [CastAccountsLoanCrudController::class, 'exportExcel']);
        Route::post('cast-account-loan/export-trans-pdf', [CastAccountsLoanCrudController::class, 'exportTransPdf']);
        Route::post('cast-account-loan/export-trans-excel', [CastAccountsLoanCrudController::class, 'exportTransExcel']);
        Route::post('cast-account-loan-transaction', [CastAccountsLoanCrudController::class, 'storeTransaction']);
        Route::post('cast-account-loan-move-transaction', [CastAccountsLoanCrudController::class, 'storeMoveTransaction']);
        Route::get('cast-account-loan-show', [CastAccountsLoanCrudController::class, 'showTransaction']);
        Route::post('cast-account-loan/loan-transaction-flag-select2', [CastAccountsLoanCrudController::class, 'loan_transaction_flag_select2']);
        Route::get('cast-account-loan/get-loan-balance', [CastAccountsLoanCrudController::class, 'get_loan_balance_ajax']);
    });

    Route::prefix('finance-report')->group(function () {
        Route::crud('expense-account', 'ExpenseAccountCrudController');
        Route::get('expense-account-ledger', [ExpenseAccountCrudController::class, 'getLedgerDataTable']);
        Route::post('expense-account-ledger-pdf', [ExpenseAccountCrudController::class, 'exportLedgerPdf']);
        Route::post('expense-account-ledger-excel', [ExpenseAccountCrudController::class, 'exportLedgerExcel']);
        Route::post('expense-account/export-pdf', [ExpenseAccountCrudController::class, 'exportPdf']);
        Route::post('expense-account/export-excel', [ExpenseAccountCrudController::class, 'exportExcel']);
        Route::crud('profit-lost', 'ProfitLostAccountCrudController');
        Route::post('profit-lost/store-project', [ProfitLostAccountCrudController::class, 'storeProject']);
        Route::get('profit-lost/{id}/detail', [ProfitLostAccountCrudController::class, 'detail']);
        Route::crud('balance-sheet', 'BalanceSheetCrudController');
        Route::get('balance-sheet-ledger', [BalanceSheetCrudController::class, 'getLedgerDataTable']);
        Route::post('balance-sheet-ledger-pdf', [BalanceSheetCrudController::class, 'exportLedgerPdf']);
        Route::post('balance-sheet-ledger-excel', [BalanceSheetCrudController::class, 'exportLedgerExcel']);
        Route::post('balance-sheet/export-pdf', [BalanceSheetCrudController::class, 'exportPdf']);
        Route::post('balance-sheet/export-excel', [BalanceSheetCrudController::class, 'exportExcel']);
        Route::get('show-total-account', [BalanceSheetCrudController::class, 'showTotalAccount']);
        Route::crud('list-asset', 'AssetCrudController');
        Route::post('list-asset/export-pdf', [AssetCrudController::class, 'exportPdf']);
        Route::post('list-asset/export-excel', [AssetCrudController::class, 'exportExcel']);
        Route::post('select2-account-id', [AssetCrudController::class, 'select2account']);
        Route::post('profit-lost/select2-po', [ProfitLostAccountCrudController::class, 'select2Po']);
        Route::post('profit-lost/select2-account', [ProfitLostAccountCrudController::class, 'select2Account']);
        Route::get('profit-lost/get_client_selected_ajax', [ProfitLostAccountCrudController::class, 'get_client_selected_ajax']);
        Route::get('profit-lost/total', [ProfitLostAccountCrudController::class, 'get_total_excl_ppn_final_profit']);
        Route::get('profit-lost/report-total', [ProfitLostAccountCrudController::class, 'total_report_account_profit_lost_ajax']);
        Route::post('profit-lost/export-pdf', [ProfitLostAccountCrudController::class, 'exportPdf']);
        Route::post('profit-lost/export-excel', [ProfitLostAccountCrudController::class, 'exportExcel']);
        Route::post('profit-lost/export-detail-pdf', [ProfitLostAccountCrudController::class, 'exportDetailPdf']);
        Route::post('profit-lost/export-detail-excel', [ProfitLostAccountCrudController::class, 'exportDetailExcel']);
        Route::post('profit-lost/export-consolidation-pdf', [ProfitLostAccountCrudController::class, 'exportConsolidationPdf']);
        Route::post('profit-lost/export-consolidation-excel', [ProfitLostAccountCrudController::class, 'exportConsolidationExcel']);
    });

    Route::crud('voucher-payment-plan', VoucherPaymentPlanCrudController::class);
    Route::get('voucher-payment-plan/datatable-voucher', [VoucherPaymentPlanCrudController::class, 'datatableVoucher']);
    Route::post('voucher-payment-plan/single-store', [VoucherPaymentPlanCrudController::class, 'storeSingle']);
    Route::post('voucher-payment-plan/total', [VoucherPaymentPlanCrudController::class, 'total_voucher']);
    Route::post('voucher-payment-plan/{id}/approve', [VoucherPaymentPlanCrudController::class, 'approvedStore']);
    Route::post('voucher-payment-plan/export-pdf', [VoucherPaymentPlanCrudController::class, 'exportPdf']);
    Route::post('voucher-payment-plan/export-excel', [VoucherPaymentPlanCrudController::class, 'exportExcel']);
    Route::post('voucher-payment-plan/bulk-approve', [VoucherPaymentPlanCrudController::class, 'bulkApprove']);
    Route::post('voucher-payment-plan/bulk-delete', [VoucherPaymentPlanCrudController::class, 'bulkDelete']);

    Route::prefix('fa')->group(function () {
        Route::match(['post', 'put'], 'voucher/select2-work-code', [VoucherCrudController::class, 'select2WorkCode']);
        Route::match(['post', 'put'], 'voucher/select2-subkon', [VoucherCrudController::class, 'select2Subkon']);
        Route::match(['post', 'put'], 'voucher/select2-po-spk', [VoucherCrudController::class, 'select2_no_po_spk']);
        Route::crud('voucher', 'VoucherCrudController');
        Route::post('voucher/{id}/approve', [VoucherCrudController::class, 'approvedStore']);
        Route::get('voucher/total', [VoucherCrudController::class, 'total_voucher']);
        Route::post('voucher/export-pdf', [VoucherCrudController::class, 'exportPdf']);
        Route::post('voucher/export-excel', [VoucherCrudController::class, 'exportExcel']);
        Route::delete('voucher/void-payment/{id}', [VoucherCrudController::class, 'voidPayment']);
        Route::crud('voucher-payment', 'VoucherPaymentCrudController');
        Route::get('voucher-payment/datatable-voucher', [VoucherPaymentCrudController::class, 'datatableVoucher']);
        Route::post('voucher-payment/single-store', [VoucherPaymentCrudController::class, 'storeSingle']);
        Route::post('voucher-payment/total', [VoucherPaymentCrudController::class, 'total_voucher']);
        Route::post('voucher-payment/export-pdf', [VoucherPaymentCrudController::class, 'exportPdf']);
        Route::post('voucher-payment/export-excel', [VoucherPaymentCrudController::class, 'exportExcel']);
        Route::delete('voucher-payment/void-payment/{id}', [VoucherPaymentCrudController::class, 'voidPayment']);

        Route::post('voucher-payment/{id}/approve', [VoucherPaymentCrudController::class, 'approvedStore']);
        Route::post('voucher-payment/total', [VoucherPaymentCrudController::class, 'total_voucher']);
        Route::get('voucher/get_client_selected_ajax', [VoucherCrudController::class, 'clientSelectedAjax']);
        Route::get('voucher/get_account_source_selected_ajax', [VoucherCrudController::class, 'castAccountSelectedAjax']);
        Route::get('voucher/{id}/print', [VoucherCrudController::class, 'print']);
    });

    Route::prefix('monitoring')->group(function () {
        Route::crud('project-system-setup', 'ProjectSystemSetupCrudController');
        Route::post('project-system-setup/export-pdf', [ProjectSystemSetupCrudController::class, 'exportPdf']);
        Route::post('project-system-setup/export-excel', [ProjectSystemSetupCrudController::class, 'exportExcel']);
        Route::crud('project-list', 'ProjectListCrudController');
        Route::post('project-list/export-pdf', [ProjectListCrudController::class, 'exportPdf']);
        Route::post('project-list/export-excel', [ProjectListCrudController::class, 'exportExcel']);
        Route::crud('project-report', 'ProjectListReportCrudController');
        Route::post('project-report/export-pdf', [ProjectListReportCrudController::class, 'exportPdf']);
        Route::post('project-report/export-excel', [ProjectListReportCrudController::class, 'exportExcel']);
        Route::crud('quotation', 'QuotationCrudController');
        Route::post('quotation/export-pdf', [QuotationCrudController::class, 'exportPdf']);
        Route::post('quotation/export-excel', [QuotationCrudController::class, 'exportExcel']);
        Route::crud('quotation-status', 'StatusQuotaionCrudController');
        Route::post('quotation-status/export-pdf', [StatusQuotaionCrudController::class, 'exportPdf']);
        Route::post('quotation-status/export-excel', [StatusQuotaionCrudController::class, 'exportExcel']);
        Route::crud('quotation-check', 'QuotationCheckCrudController');
        Route::post('quotation-check/export-pdf', [QuotationCheckCrudController::class, 'exportPdf']);
        Route::post('quotation-check/export-excel', [QuotationCheckCrudController::class, 'exportExcel']);
        Route::crud('project-status', 'StatusProjectCrudController');
        Route::get('project-status/resume-total', [StatusProjectCrudController::class, 'resumeTotal']);
        Route::post('project-status/export-pdf', [StatusProjectCrudController::class, 'exportPdf']);
        Route::post('project-status/export-excel', [StatusProjectCrudController::class, 'exportExcel']);
        Route::post('project-status/export-resume-pdf', [StatusProjectCrudController::class, 'exportResumePdf']);
        Route::post('project-status/export-resume-excel', [StatusProjectCrudController::class, 'exportResumeExcel']);
        Route::crud('tracker', 'MonitoringTrackerCrudController');
        Route::post('tracker/select2-project', [MonitoringTrackerCrudController::class, 'select2Project']);
        Route::post('tracker/select2-client', [MonitoringTrackerCrudController::class, 'select2Client']);
        Route::post('tracker/export-pdf', [MonitoringTrackerCrudController::class, 'exportPdf']);
        Route::post('tracker/export-excel', [MonitoringTrackerCrudController::class, 'exportExcel']);
    });


    Route::post('account/select2-account', [CastAccountsCrudController::class, 'account_select2']);
    Route::post('account/select2-account-child', [CastAccountsCrudController::class, 'account_child_select2']);
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
