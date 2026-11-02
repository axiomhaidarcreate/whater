<?php

use App\Http\Controllers\Api\V1\AccountingController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceModelController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\ProcurementController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SystemSettingController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| All application API routes are versioned under /api/v1.
| Standard JSON response envelope and strict RBAC server-side policies.
|
*/

Route::prefix('v1')->group(function () {

    // 1. مسارات المصادقة العامة (Public Auth)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('api.v1.auth.login');
    });

    // 2. إعدادات النظام العامة
    Route::get('/settings/public', [SystemSettingController::class, 'publicSettings'])
        ->name('api.v1.settings.public');

    // 3. المسارات المحمية برمز الوصول (Protected Routes - Sanctum)
    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

        // الملف الشخصي وإدارة الجلسة
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::post('/fcm-token', [AuthController::class, 'registerFcmToken'])->name('api.v1.auth.fcm_token');
        });

        // إدارة إعدادات النظام للمدير
        Route::prefix('settings')->middleware('permission:settings.manage')->group(function () {
            Route::get('/', [SystemSettingController::class, 'index'])->name('api.v1.settings.index');
            Route::put('/{key}', [SystemSettingController::class, 'update'])->name('api.v1.settings.update');
        });

        // 4. المنتجات والكتالوج (Catalog & Products)
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->middleware('permission:catalog.view')->name('api.v1.products.index');
            Route::get('/{id}', [ProductController::class, 'show'])->middleware('permission:catalog.view')->name('api.v1.products.show');
            Route::post('/', [ProductController::class, 'store'])->middleware('permission:catalog.manage')->name('api.v1.products.store');
        });

        // 5. موديلات الأجهزة وقائمة المكونات (Device Models & BOM)
        Route::prefix('device-models')->group(function () {
            Route::get('/', [DeviceModelController::class, 'index'])->middleware('permission:catalog.view')->name('api.v1.device_models.index');
            Route::get('/{id}', [DeviceModelController::class, 'show'])->middleware('permission:catalog.view')->name('api.v1.device_models.show');
            Route::post('/', [DeviceModelController::class, 'store'])->middleware('permission:catalog.manage')->name('api.v1.device_models.store');
        });

        // 6. المستودعات وسيارات الفنيين (Warehouses & Van Stocks)
        Route::prefix('warehouses')->group(function () {
            Route::get('/', [WarehouseController::class, 'index'])->middleware('permission:inventory.view')->name('api.v1.warehouses.index');
            Route::get('/{id}/stock', [WarehouseController::class, 'stock'])->middleware('permission:inventory.view')->name('api.v1.warehouses.stock');
        });

        // 7. المخزون ودفتر الحركات والتحويل والجرد (Inventory & Ledger)
        Route::prefix('inventory')->group(function () {
            Route::get('/movements', [InventoryController::class, 'movements'])->middleware('permission:inventory.view')->name('api.v1.inventory.movements');
            Route::post('/transfer', [InventoryController::class, 'transfer'])->middleware('permission:inventory.transfer')->name('api.v1.inventory.transfer');
            Route::post('/adjust', [InventoryController::class, 'adjust'])->middleware('permission:inventory.adjust')->name('api.v1.inventory.adjust');
            Route::post('/movements/{id}/reverse', [InventoryController::class, 'reverse'])->middleware('permission:inventory.adjust')->name('api.v1.inventory.reverse');
        });

        // 8. المشتريات والموردين وأوامر الشراء والاستلام (Procurement & GRN)
        Route::prefix('procurement')->group(function () {
            Route::get('/suppliers', [ProcurementController::class, 'suppliers'])->middleware('permission:suppliers.view')->name('api.v1.procurement.suppliers');
            Route::post('/suppliers', [ProcurementController::class, 'storeSupplier'])->middleware('permission:suppliers.manage')->name('api.v1.procurement.store_supplier');
            Route::get('/purchase-orders', [ProcurementController::class, 'purchaseOrders'])->middleware('permission:procurement.view')->name('api.v1.procurement.purchase_orders');
            Route::post('/purchase-orders', [ProcurementController::class, 'storePurchaseOrder'])->middleware('permission:procurement.create')->name('api.v1.procurement.store_po');
            Route::post('/purchase-orders/{id}/approve', [ProcurementController::class, 'approvePurchaseOrder'])->middleware('permission:procurement.approve')->name('api.v1.procurement.approve_po');
            Route::post('/purchase-orders/{id}/receive', [ProcurementController::class, 'receiveGoods'])->middleware('permission:procurement.receive')->name('api.v1.procurement.receive_goods');
        });

        // 9. العملاء والعناوين (CRM & Customers)
        Route::prefix('customers')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\CustomerController::class, 'index'])->middleware('permission:customers.view')->name('api.v1.customers.index');
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\CustomerController::class, 'show'])->middleware('permission:customers.view')->name('api.v1.customers.show');
            Route::post('/', [\App\Http\Controllers\Api\V1\CustomerController::class, 'store'])->middleware('permission:customers.create')->name('api.v1.customers.store');
            Route::post('/{id}/addresses', [\App\Http\Controllers\Api\V1\CustomerController::class, 'addAddress'])->middleware('permission:customers.update')->name('api.v1.customers.add_address');
        });

        // 10. المبيعات وأوامر البيع (Sales Orders)
        Route::prefix('sales-orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SalesOrderController::class, 'index'])->middleware('permission:sales.view')->name('api.v1.sales_orders.index');
            Route::post('/', [\App\Http\Controllers\Api\V1\SalesOrderController::class, 'store'])->middleware('permission:sales.create')->name('api.v1.sales_orders.store');
        });

        // 11. الفواتير وسندات القبض (Invoices & Payments)
        Route::prefix('invoices')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\InvoiceController::class, 'index'])->middleware('permission:sales.view')->name('api.v1.invoices.index');
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\InvoiceController::class, 'show'])->middleware('permission:sales.view')->name('api.v1.invoices.show');
            Route::post('/', [\App\Http\Controllers\Api\V1\InvoiceController::class, 'store'])->middleware('permission:invoices.issue')->name('api.v1.invoices.store');
            Route::post('/{id}/pay', [\App\Http\Controllers\Api\V1\InvoiceController::class, 'pay'])->middleware('permission:payments.record')->name('api.v1.invoices.pay');
            Route::post('/{id}/cancel', [\App\Http\Controllers\Api\V1\InvoiceController::class, 'cancel'])->middleware('permission:invoices.issue')->name('api.v1.invoices.cancel');
            Route::get('/{id}/print', [\App\Http\Controllers\Api\V1\InvoiceController::class, 'print'])->middleware('permission:sales.view')->name('api.v1.invoices.print');
        });

        // 12. محرك أعمار الفلاتر ومراحل الأجهزة (Filter Lifecycle Engine)
        Route::prefix('customer-devices')->group(function () {
            Route::get('/{id}/stages', [\App\Http\Controllers\Api\V1\DeviceLifecycleController::class, 'stages'])->name('api.v1.customer_devices.stages');
            Route::post('/{id}/install', [\App\Http\Controllers\Api\V1\DeviceLifecycleController::class, 'install'])->name('api.v1.customer_devices.install');
        });

        Route::prefix('stages')->group(function () {
            Route::post('/{id}/replace', [\App\Http\Controllers\Api\V1\DeviceLifecycleController::class, 'replaceStage'])->name('api.v1.stages.replace');
            Route::post('/{id}/postpone', [\App\Http\Controllers\Api\V1\DeviceLifecycleController::class, 'postpone'])->name('api.v1.stages.postpone');
            Route::post('/{id}/exempt', [\App\Http\Controllers\Api\V1\DeviceLifecycleController::class, 'exempt'])->name('api.v1.stages.exempt');
        });

        // 13. مركز الاتصال والمتابعة والتلي سيلز (Call Center & Telesales CRM)
        Route::prefix('call-center')->group(function () {
            Route::get('/due-filters', [\App\Http\Controllers\Api\V1\CallCenterController::class, 'dueFilters'])
                ->middleware('permission:telesales.view')
                ->name('api.v1.call_center.due_filters');

            Route::post('/interactions', [\App\Http\Controllers\Api\V1\CallCenterController::class, 'recordInteraction'])
                ->middleware('permission:telesales.interact')
                ->name('api.v1.call_center.record_interaction');

            Route::get('/customers/{customer}/devices-summary', [\App\Http\Controllers\Api\V1\CallCenterController::class, 'customerDevicesSummary'])
                ->middleware('permission:telesales.view')
                ->name('api.v1.call_center.customer_devices_summary');

            Route::get('/interactions', [\App\Http\Controllers\Api\V1\CallCenterController::class, 'interactionsHistory'])
                ->middleware('permission:telesales.view')
                ->name('api.v1.call_center.interactions');
        });

        // 14. أوامر وتذاكر الصيانة والعمليات الميدانية (Maintenance Tickets & Field Service)
        Route::prefix('tickets')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MaintenanceTicketController::class, 'index'])
                ->name('api.v1.tickets.index');

            Route::get('/{id}', [\App\Http\Controllers\Api\V1\MaintenanceTicketController::class, 'show'])
                ->name('api.v1.tickets.show');

            Route::post('/{id}/assign', [\App\Http\Controllers\Api\V1\MaintenanceTicketController::class, 'assign'])
                ->middleware('permission:tickets.assign')
                ->name('api.v1.tickets.assign');

            Route::post('/{id}/check-in', [\App\Http\Controllers\Api\V1\MaintenanceTicketController::class, 'checkIn'])
                ->name('api.v1.tickets.check_in');

            Route::post('/{id}/attachments', [\App\Http\Controllers\Api\V1\MaintenanceTicketController::class, 'uploadAttachment'])
                ->name('api.v1.tickets.upload_attachment');

            Route::post('/{id}/parts', [\App\Http\Controllers\Api\V1\MaintenanceTicketController::class, 'addSparePart'])
                ->name('api.v1.tickets.add_spare_part');

            Route::post('/{id}/complete', [\App\Http\Controllers\Api\V1\MaintenanceTicketController::class, 'complete'])
                ->name('api.v1.tickets.complete');

            Route::post('/{id}/approve', [\App\Http\Controllers\Api\V1\MaintenanceTicketController::class, 'approve'])
                ->middleware('permission:tickets.approve')
                ->name('api.v1.tickets.approve');

            Route::post('/{id}/rework', [\App\Http\Controllers\Api\V1\MaintenanceTicketController::class, 'rework'])
                ->middleware('permission:tickets.rework')
                ->name('api.v1.tickets.rework');
        });

        // 15. مسارات اختبار قواعد الصلاحيات والعزل في الخادم (RBAC Verification)
        Route::prefix('test-rbac')->group(function () {
            Route::get('/technician-only', function (Request $request) {
                return response()->json([
                    'success' => true,
                    'message' => 'مرحباً بالفني الميداني، تم التحقق من الصلاحية في الخادم بنجاح.',
                    'user' => $request->user()->name,
                ]);
            })->middleware('role:technician');

            Route::get('/call-center-only', function (Request $request) {
                return response()->json([
                    'success' => true,
                    'message' => 'مرحباً بموظف خدمة العملاء.',
                    'user' => $request->user()->name,
                ]);
            })->middleware('role:call_center');

            Route::get('/sales-only', function (Request $request) {
                return response()->json([
                    'success' => true,
                    'message' => 'مرحباً بمسؤول المبيعات.',
                    'user' => $request->user()->name,
                ]);
            })->middleware('role:sales_rep,sales_manager');

            Route::get('/customer-only', function (Request $request) {
                return response()->json([
                    'success' => true,
                    'message' => 'مرحباً بالعميل، هذه بيانات أجهزتك الخاصة.',
                    'user' => $request->user()->name,
                ]);
            })->middleware('role:customer');
        });

        // 16. الوحدة المحاسبية — شجرة الحسابات والقيود اليومية (Accounting Module)
        Route::prefix('accounting')->group(function () {

            // ── شجرة الحسابات ──
            Route::get('/accounts', [AccountingController::class, 'chartOfAccounts'])
                ->middleware('permission:accounting.view')
                ->name('api.v1.accounting.chart');

            Route::get('/accounts/posting', [AccountingController::class, 'postingAccounts'])
                ->middleware('permission:accounting.view')
                ->name('api.v1.accounting.posting_accounts');

            Route::get('/accounts/{id}', [AccountingController::class, 'showAccount'])
                ->middleware('permission:accounting.view')
                ->name('api.v1.accounting.account_show');

            Route::post('/accounts', [AccountingController::class, 'storeAccount'])
                ->middleware('permission:accounting.manage')
                ->name('api.v1.accounting.account_store');

            Route::put('/accounts/{id}', [AccountingController::class, 'updateAccount'])
                ->middleware('permission:accounting.manage')
                ->name('api.v1.accounting.account_update');

            // ── دفتر الأستاذ ──
            Route::get('/accounts/{id}/ledger', [AccountingController::class, 'accountLedger'])
                ->middleware('permission:accounting.view')
                ->name('api.v1.accounting.account_ledger');

            // ── القيود اليومية ──
            Route::get('/journal-entries', [AccountingController::class, 'indexJournalEntries'])
                ->middleware('permission:accounting.view')
                ->name('api.v1.accounting.journal_index');

            Route::get('/journal-entries/{id}', [AccountingController::class, 'showJournalEntry'])
                ->middleware('permission:accounting.view')
                ->name('api.v1.accounting.journal_show');

            Route::post('/journal-entries', [AccountingController::class, 'storeJournalEntry'])
                ->middleware('permission:accounting.entries')
                ->name('api.v1.accounting.journal_store');

            Route::post('/journal-entries/{id}/post', [AccountingController::class, 'postJournalEntry'])
                ->middleware('permission:accounting.entries')
                ->name('api.v1.accounting.journal_post');

            Route::post('/journal-entries/{id}/reverse', [AccountingController::class, 'reverseJournalEntry'])
                ->middleware('permission:accounting.entries')
                ->name('api.v1.accounting.journal_reverse');

            // ── ميزان المراجعة ──
            Route::get('/reports/trial-balance', [AccountingController::class, 'trialBalance'])
                ->middleware('permission:accounting.view')
                ->name('api.v1.accounting.trial_balance');
        });

    });

});
