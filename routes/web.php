<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

// مسارات المصادقة وتسجيل الدخول
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::get('/switch-role/{phone}', [WebAuthController::class, 'switchRole'])->name('role.switch');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// لوحة التحكم الرئيسية والعمليات الميدانية
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);

// العمليات السريعة للوحة التحكم
Route::post('/tickets/quick-create', [DashboardController::class, 'quickCreateTicket'])->name('tickets.quick-create');
Route::post('/tickets/{ticket}/assign', [DashboardController::class, 'assignTicket'])->name('tickets.assign');
Route::post('/tickets/{ticket}/update', [DashboardController::class, 'updateTicket'])->name('tickets.update');
Route::post('/tickets/{ticket}/status', [DashboardController::class, 'updateTicketStatus'])->name('tickets.status');
Route::delete('/tickets/{ticket}', [DashboardController::class, 'deleteTicket'])->name('tickets.delete');
Route::get('/tickets/{ticket}/print', [DashboardController::class, 'printTicket'])->name('tickets.print');
Route::get('/tickets/print-all', [DashboardController::class, 'printAllTickets'])->name('tickets.print-all');
Route::post('/reminders/run', [DashboardController::class, 'runDueRemindersCommand'])->name('reminders.run');

// إدارة العملاء ومحطات التحلية (Customers CRUD & Profiles)
Route::post('/customers/create', [DashboardController::class, 'createCustomer'])->name('customers.create');
Route::post('/customers/{customer}/update', [DashboardController::class, 'updateCustomer'])->name('customers.update');
Route::delete('/customers/{customer}', [DashboardController::class, 'deleteCustomer'])->name('customers.delete');
Route::get('/customers/{customer}/print', [DashboardController::class, 'printCustomer'])->name('customers.print');
Route::get('/customers/print-all', [DashboardController::class, 'printAllCustomers'])->name('customers.print-all');

// إدارة أجهزة ومحطات التحلية (Devices CRUD)
Route::post('/devices/create', [DashboardController::class, 'createDevice'])->name('devices.create');
Route::post('/devices/{device}/update', [DashboardController::class, 'updateDevice'])->name('devices.update');
Route::delete('/devices/{device}', [DashboardController::class, 'deleteDevice'])->name('devices.delete');

// محرك استحقاقات الفلاتر (Lifecycle Stages)
Route::post('/lifecycle/stages/create', [DashboardController::class, 'createDeviceStage'])->name('lifecycle.stages.create');
Route::post('/lifecycle/stages/{stage}/replace', [DashboardController::class, 'replaceDeviceStage'])->name('lifecycle.stages.replace');
Route::post('/lifecycle/stages/{stage}/update', [DashboardController::class, 'updateDeviceStage'])->name('lifecycle.stages.update');
Route::delete('/lifecycle/stages/{stage}', [DashboardController::class, 'deleteDeviceStage'])->name('lifecycle.stages.delete');
Route::get('/lifecycle/print', [DashboardController::class, 'printLifecycle'])->name('lifecycle.print');

// شاشة المبيعات ونقاط البيع (ERP Sales & Invoicing)
Route::post('/sales/create', [DashboardController::class, 'createSale'])->name('sales.create');
Route::post('/sales/invoices/{invoice}/payment', [DashboardController::class, 'recordPayment'])->name('sales.invoices.payment');

// شاشة الموردين وإدارة المشتريات (Suppliers & Procurement)
Route::post('/suppliers/create', [DashboardController::class, 'createSupplier'])->name('suppliers.create');
Route::post('/suppliers/{supplier}/toggle', [DashboardController::class, 'toggleSupplierStatus'])->name('suppliers.toggle');
Route::post('/procurement/orders/create', [DashboardController::class, 'createPurchaseOrder'])->name('procurement.orders.create');
Route::post('/procurement/orders/{po}/approve', [DashboardController::class, 'approvePurchaseOrder'])->name('procurement.orders.approve');
Route::post('/procurement/orders/{po}/receive', [DashboardController::class, 'receivePurchaseOrder'])->name('procurement.orders.receive');

// جرد وتسوية المخزون (Inventory Stocktaking & Adjustment)
Route::post('/inventory/adjust', [DashboardController::class, 'adjustInventory'])->name('inventory.adjust');

// الطباعة وتصدير PDF لكل شيء (PDF Printing & Reports)
Route::get('/invoices/{invoice}/print', [DashboardController::class, 'printInvoice'])->name('invoices.print');
Route::get('/procurement/orders/{po}/print', [DashboardController::class, 'printPurchaseOrder'])->name('procurement.orders.print');
Route::get('/reports/print', [DashboardController::class, 'printReport'])->name('reports.print');

// إدارة الصلاحيات والأدوار والمستخدمين (Super Admin RBAC)
Route::post('/admin/users/create', [DashboardController::class, 'createUser'])->name('admin.users.create');
Route::post('/admin/users/{user}/role', [DashboardController::class, 'updateUserRole'])->name('admin.users.role');
Route::post('/admin/users/{user}/toggle-status', [DashboardController::class, 'toggleUserStatus'])->name('admin.users.toggle');
Route::post('/admin/roles/create', [DashboardController::class, 'createRole'])->name('admin.roles.create');
Route::delete('/admin/roles/{role}', [DashboardController::class, 'deleteRole'])->name('admin.roles.delete');
Route::post('/admin/roles/{role}/permissions', [DashboardController::class, 'updateRolePermissions'])->name('admin.roles.permissions');
Route::post('/admin/permissions/create', [DashboardController::class, 'createPermission'])->name('admin.permissions.create');

// إعدادات النظام (الألوان، الشركة، التشغيل)
Route::post('/admin/settings/update', [DashboardController::class, 'updateSettings'])->name('admin.settings.update');
Route::post('/admin/reset-demo-data', [DashboardController::class, 'resetDemoData'])->name('admin.reset-demo');
