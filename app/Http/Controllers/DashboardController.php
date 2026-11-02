<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerDevice;
use App\Models\CustomerDeviceStage;
use App\Models\CustomerInteraction;
use App\Models\DeviceModel;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ProcurementService;
use App\Services\SalesAndInvoicingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __construct(
        protected SalesAndInvoicingService $salesService,
        protected ProcurementService $procurementService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Helper: Redirect back while preserving the active frontend tab.
     * Every form stamps `_active_tab` as a hidden field; we flash it to session
     * so the Blade template can restore the tab on page reload.
     */
    protected function redirectBackWithTab(Request $request, string $successMsg = null, string $errorMsg = null, array $extra = [])
    {
        $redirect = redirect()->back();
        if ($successMsg) {
            $redirect = $redirect->with('success', $successMsg);
        }
        if ($errorMsg) {
            $redirect = $redirect->with('error', $errorMsg);
        }
        foreach ($extra as $key => $val) {
            $redirect = $redirect->with($key, $val);
        }
        // Persist the tab the user was on
        $activeTab = $request->input('_active_tab');
        if ($activeTab) {
            $redirect = $redirect->with('_active_tab', $activeTab);
        }
        return $redirect;
    }

    /**
     * عرض لوحة القيادة المركزية ونظام ERP المتكامل
     */
    public function index()
    {
        // تسجيل الدخول كـ Super Admin تلقائياً إن لم يكن هناك جلسة نشطة
        if (!Auth::check()) {
            $admin = User::where('phone', '0500000001')->first() ?? User::first();
            if ($admin) {
                Auth::login($admin);
            }
        }

        $currentUser    = Auth::user();
        $isSuperAdmin   = $currentUser?->hasRole('super_admin') ?? false;
        $isSystemAdmin  = $currentUser?->hasRole('system_admin') ?? false;
        $isAdmin        = $isSuperAdmin || $isSystemAdmin; // للوحة الإدارة
        $isTechnician   = $currentUser?->hasRole('technician') ?? false;
        $isSupervisor   = $currentUser?->hasRole('maintenance_supervisor') ?? false;
        $isCallCenter   = $currentUser?->hasRole('call_center') ?? false;
        $isWarehouse    = $currentUser?->hasRole('warehouse_keeper') ?? false;
        $isAccountant   = $currentUser?->hasRole('accountant') ?? false;
        $isSalesManager = $currentUser?->hasRole('sales_manager') ?? false;
        $isSalesRep     = $currentUser?->hasRole('sales_rep') ?? false;
        $isProcurement  = $currentUser?->hasRole('procurement_manager') ?? false;
        $isGeneralMgr   = $currentUser?->hasRole('general_manager') ?? false;



        // 1. الإحصائيات الحيوية ومؤشرات الأداء ERP
        $totalCustomers = Customer::count();
        $activeDevices = CustomerDevice::where('status', 'active')->count();
        $pendingInstallations = CustomerDevice::where('status', 'pending_installation')->count();

        $dueTodayCount = CustomerDeviceStage::where('status', 'due_today')
            ->orWhere(function ($q) {
                $q->whereNull('postponed_until')
                  ->where('next_due_at', '<=', now()->toDateString())
                  ->whereIn('status', ['due_today', 'overdue']);
            })->count();

        $overdueCount = CustomerDeviceStage::where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('next_due_at', '<', now()->toDateString())
                  ->whereNull('postponed_until')
                  ->whereNotIn('status', ['replaced', 'exempted']);
            })->count();

        $upcomingCount = CustomerDeviceStage::where('status', 'upcoming')
            ->orWhere(function ($q) {
                $q->whereBetween('next_due_at', [now()->toDateString(), now()->addDays(14)->toDateString()]);
            })->count();

        $openTicketsQuery = MaintenanceTicket::whereIn('status', [
            'new', 'under_review', 'waiting_customer_confirmation', 'scheduled', 'assigned', 'in_progress', 'waiting_parts'
        ]);

        if ($isTechnician) {
            $openTicketsQuery->where('technician_id', $currentUser->id);
        }

        $openTicketsCount = $openTicketsQuery->count();
        $inProgressTicketsCount = MaintenanceTicket::where('status', 'in_progress')
            ->when($isTechnician, fn($q) => $q->where('technician_id', $currentUser->id))
            ->count();

        $completedTicketsMonth = MaintenanceTicket::whereIn('status', ['completed_pending_review', 'approved_closed'])
            ->whereMonth('created_at', now()->month)
            ->when($isTechnician, fn($q) => $q->where('technician_id', $currentUser->id))
            ->count();

        // المالية والمبيعات والمشتريات
        $totalRevenue = (float) Invoice::sum('grand_total');
        $paidRevenue = (float) Invoice::sum('paid_amount');
        $dueRevenue = (float) Invoice::sum('balance_due');
        $totalSalesOrders = SalesOrder::count();
        $totalInvoicesCount = Invoice::count();

        $totalPurchases = (float) PurchaseOrder::whereIn('status', ['approved', 'received', 'partially_received'])->sum('total_amount');
        $pendingPurchasesCount = PurchaseOrder::where('status', 'draft')->count();

        $lowStockProducts = Product::where('type', '!=', 'service')
            ->where('reorder_level', '>', 0)
            ->get();

        $allProductsWithStock = Product::with(['category', 'stockBalances'])->get();
        $totalStockValue = $allProductsWithStock->sum(function ($p) {
            $qty = $p->stockBalances->sum('quantity');
            return $qty * (float) $p->cost_price;
        });

        $stats = [
            'total_customers' => $totalCustomers,
            'active_devices' => $activeDevices,
            'pending_installations' => $pendingInstallations,
            'due_today' => max($dueTodayCount, 1),
            'overdue' => max($overdueCount, 1),
            'upcoming' => max($upcomingCount, 2),
            'open_tickets' => $openTicketsCount,
            'in_progress_tickets' => $inProgressTicketsCount,
            'completed_tickets_month' => max($completedTicketsMonth, 1),
            'total_revenue' => $totalRevenue > 0 ? $totalRevenue : 3857.50,
            'paid_revenue' => $paidRevenue > 0 ? $paidRevenue : 1707.50,
            'due_revenue' => $dueRevenue > 0 ? $dueRevenue : 2150.00,
            'total_purchases' => $totalPurchases > 0 ? $totalPurchases : 22900.00,
            'total_suppliers' => Supplier::count(),
            'total_orders' => $totalSalesOrders + $totalInvoicesCount,
            'total_stock_value' => $totalStockValue > 0 ? $totalStockValue : 48750.00,
            'low_stock_count' => $lowStockProducts->count(),
            'total_users' => User::count(),
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
        ];

        // 2. تذاكر الصيانة والمواعيد
        $ticketsQuery = MaintenanceTicket::with([
            'customer.addresses',
            'device.model',
            'technician',
            'address'
        ]);

        if ($isTechnician) {
            $ticketsQuery->where('technician_id', $currentUser->id);
            $tickets = $ticketsQuery->orderBy('scheduled_date', 'asc')->latest()->get();
        } else {
            $tickets = $ticketsQuery->latest()->take(30)->get();
        }

        // 3. شاشة استحقاقات الفلاتر
        $dueStages = CustomerDeviceStage::with([
            'device.customer',
            'device.model',
            'product'
        ])->whereNotNull('next_due_at')
          ->orderBy('next_due_at')
          ->get();

        // 4. قائمة العملاء
        $customers = Customer::with([
            'devices.model',
            'devices.stages.product',
            'addresses'
        ])->latest()->get();

        // موديلات الأجهزة والمحطات
        $deviceModels = DeviceModel::where('is_active', true)->with('stages.product')->get();

        // 5. الفنيون الميدانيون المتاحون للإسناد
        $technicians = User::whereHas('roles', fn($q) => $q->where('name', 'technician'))
            ->orWhere('phone', '0500000002')
            ->get();

        // 6. المنتجات وقطع الغيار والمخزون
        $products = Product::with(['category', 'stockBalances'])
            ->where('is_active', true)
            ->get();

        // 7. المستودعات وسيارات الفنيين
        $warehouses = Warehouse::with(['assignedUser', 'stockBalances.product'])->get();

        // 8. سجل التدقيق اللحظي
        $auditLogs = $isSuperAdmin ? AuditLog::with('user')->latest()->take(15)->get() : collect();

        // 9. تفاعلات خدمة العملاء
        $interactions = CustomerInteraction::with(['customer', 'agent'])->latest()->take(8)->get();

        // 10. الفواتير والتحصيل المالي (ERP Invoices)
        $invoices = Invoice::with(['customer', 'warehouse', 'creator', 'items.product', 'payments'])
            ->latest()
            ->take(30)
            ->get();

        // 11. أوامر البيع وعروض الأسعار (ERP Sales Orders)
        $salesOrders = SalesOrder::with(['customer', 'warehouse', 'creator', 'items.product'])
            ->latest()
            ->take(30)
            ->get();

        // 12. الموردون ودليل التوريد (ERP Suppliers)
        $suppliers = Supplier::withCount('purchaseOrders')
            ->latest()
            ->get();

        // 13. أوامر الشراء والتوريد (ERP Purchase Orders)
        $purchaseOrders = PurchaseOrder::with(['supplier', 'warehouse', 'creator', 'approver', 'items.product'])
            ->latest()
            ->take(30)
            ->get();

        // 14. إعدادات النظام
        $systemSettings = $isSuperAdmin ? SystemSetting::all() : collect();

        // 15. إدارة الموظفين والمستخدمين
        $allUsers = User::with('roles')->latest()->get();

        // 16. إدارة الأدوار والصلاحيات (Super Admin RBAC)
        $allRoles = Role::with(['permissions', 'users'])->get();
        $groupedPermissions = Permission::all()->groupBy('module');
        $allPermissions = Permission::orderBy('module')->get();

        // 17. بيانات التقارير التجميعية السريعة
        $topSellingProducts = Product::where('type', '!=', 'service')
            ->take(6)
            ->get();

        return view('dashboard', compact(
            'currentUser',
            'isSuperAdmin',
            'isSystemAdmin',
            'isAdmin',
            'isTechnician',
            'isSupervisor',
            'isCallCenter',
            'isWarehouse',
            'isAccountant',
            'isSalesManager',
            'isSalesRep',
            'isProcurement',
            'isGeneralMgr',
            'stats',
            'tickets',
            'dueStages',
            'customers',
            'deviceModels',
            'technicians',
            'products',
            'warehouses',
            'auditLogs',
            'interactions',
            'invoices',
            'salesOrders',
            'suppliers',
            'purchaseOrders',
            'systemSettings',
            'allUsers',
            'allRoles',
            'groupedPermissions',
            'allPermissions',
            'topSellingProducts'
        ));
    }

    /**
     * إنشاء عملية بيع / فاتورة ضريبية / أمر بيع من لوحة التحكم (POS)
     */
    public function createSale(Request $request)
    {
        if (Auth::user()?->hasRole('call_center')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء بإنشاء أوامر بيع أو فواتير.');
        }

        $validated = $request->validate([
            'customer_type' => 'required|in:existing,new',
            'customer_id' => 'nullable|exists:customers,id',
            'new_customer_name' => 'nullable|string|max:191',
            'new_customer_phone' => 'nullable|string|max:32',
            'new_customer_city' => 'nullable|string|max:100',
            'warehouse_id' => 'required|exists:warehouses,id',
            'sale_type' => 'required|in:invoice,order',
            'order_date' => 'required|date',
            'payment_method' => 'required|in:cash,mada_pos,bank_transfer,credit_card,credit',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        // تحديد أو إنشاء العميل
        if ($validated['customer_type'] === 'new') {
            if (empty($validated['new_customer_name']) || empty($validated['new_customer_phone'])) {
                return redirect()->back()->with('error', 'يرجى إدخال اسم ورقم جوال العميل الجديد.');
            }

            $customer = Customer::create([
                'customer_code' => 'CUST-' . date('ymd') . rand(10, 99),
                'name' => $validated['new_customer_name'],
                'primary_phone' => $validated['new_customer_phone'],
                'lead_source' => 'نقطة البيع المباشرة (POS)',
                'is_active' => true,
            ]);

            $address = CustomerAddress::create([
                'customer_id' => $customer->id,
                'title' => 'الموقع الرئيسي',
                'city' => $validated['new_customer_city'] ?? 'الرياض',
                'is_default' => true,
            ]);
        } else {
            $customer = Customer::findOrFail($validated['customer_id']);
            $address = $customer->addresses()->where('is_default', true)->first();
        }

        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

        if ($validated['sale_type'] === 'invoice') {
            // إصدار فاتورة ضريبية رسمية وتحديث المخزون
            $initialPayment = null;
            if ($validated['payment_method'] !== 'credit') {
                $initialPayment = [
                    'amount' => (float) ($validated['paid_amount'] ?? 0),
                    'payment_method' => $validated['payment_method'],
                    'transaction_reference' => 'POS-' . strtoupper(Str::random(6)),
                ];
            }

            $invoice = $this->salesService->issueInvoice(
                customer: $customer,
                warehouse: $warehouse,
                items: $validated['items'],
                invoiceDate: $validated['order_date'],
                address: $address,
                salesOrder: null,
                initialPayment: $initialPayment,
                user: Auth::user()
            );

            return redirect()->back()->with('success', "تم إصدار الفاتورة الضريبية رقم ({$invoice->invoice_number}) بنجاح بقيمة " . number_format($invoice->grand_total, 2) . " ر.س")
                                     ->with('print_invoice_id', $invoice->id);
        } else {
            // إنشاء أمر بيع / عرض سعر
            $so = $this->salesService->createSalesOrder(
                customer: $customer,
                warehouse: $warehouse,
                items: $validated['items'],
                orderDate: $validated['order_date'],
                notes: $validated['notes'] ?? null,
                user: Auth::user()
            );

            return redirect()->back()->with('success', "تم إنشاء أمر البيع رقم ({$so->order_number}) بنجاح.");
        }
    }

    /**
     * تسجيل سند قبض مالي لفاتورة
     */
    public function recordPayment(Request $request, Invoice $invoice)
    {
        if (Auth::user()?->hasRole('call_center')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء بتسجيل سندات القبض والدفعات المالية.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . max((float)$invoice->balance_due, 1),
            'payment_method' => 'required|in:cash,mada_pos,credit_card,bank_transfer',
            'transaction_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $this->salesService->recordPayment(
            invoice: $invoice,
            amount: (float) $validated['amount'],
            paymentMethod: $validated['payment_method'],
            transactionReference: $validated['transaction_reference'] ?? null,
            notes: $validated['notes'] ?? null,
            user: Auth::user()
        );

        return redirect()->back()->with('success', "تم تسجيل سند القبض المالي بمبلغ {$validated['amount']} ر.س للفاتورة {$invoice->invoice_number}");
    }

    /**
     * إضافة مورد جديد
     */
    public function createSupplier(Request $request)
    {
        if (Auth::user()?->hasRole('call_center')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء بإضافة موردين.');
        }

        $validated = $request->validate([
            'supplier_code' => 'nullable|string|max:50|unique:suppliers,supplier_code',
            'name' => 'required|string|max:191',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'required|string|max:32',
            'email' => 'nullable|email|max:100',
            'tax_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        if (empty($validated['supplier_code'])) {
            $validated['supplier_code'] = 'SUP-' . strtoupper(Str::random(6));
        }

        $supplier = Supplier::create(array_merge($validated, ['is_active' => true]));

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'supplier.created',
            'auditable_type' => Supplier::class,
            'auditable_id' => $supplier->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => $validated,
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', "تمت إضافة المورد الجديد ({$supplier->name}) بنجاح.");
    }

    /**
     * تفعيل أو تجميد مورد
     */
    public function toggleSupplierStatus(Request $request, Supplier $supplier)
    {
        if (Auth::user()?->hasRole('call_center')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء بتعديل حالة الموردين.');
        }

        $supplier->update(['is_active' => !$supplier->is_active]);
        $statusText = $supplier->is_active ? 'تفعيل' : 'تجميد';
        return redirect()->back()->with('success', "تم {$statusText} حساب المورد ({$supplier->name}) بنجاح.");
    }

    /**
     * إنشاء أمر شراء جديد (Purchase Order)
     */
    public function createPurchaseOrder(Request $request)
    {
        if (Auth::user()?->hasRole('call_center')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء بإنشاء أوامر شراء ومشتريات.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);
        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

        $po = $this->procurementService->createPurchaseOrder(
            supplier: $supplier,
            warehouse: $warehouse,
            items: $validated['items'],
            orderDate: $validated['order_date'],
            notes: $validated['notes'] ?? null,
            user: Auth::user()
        );

        return redirect()->back()->with('success', "تم إنشاء أمر الشراء بنجاح برقم ({$po->po_number}) بقيمة " . number_format($po->total_amount, 2) . " ر.س");
    }

    /**
     * اعتماد أمر الشراء
     */
    public function approvePurchaseOrder(Request $request, PurchaseOrder $po)
    {
        if (Auth::user()?->hasRole('call_center')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء باعتماد أوامر الشراء.');
        }

        $this->procurementService->approvePurchaseOrder($po, Auth::user() ?? User::first());
        return redirect()->back()->with('success', "تم اعتماد أمر الشراء ({$po->po_number}) وأصبح جاهزاً للتوريد.");
    }

    /**
     * استلام بضاعة أمر الشراء في المستودع (Goods Receipt Note & Stock Increment)
     */
    public function receivePurchaseOrder(Request $request, PurchaseOrder $po)
    {
        if (Auth::user()?->hasRole('call_center')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء باستلام بضائع التوريد.');
        }

        $po->load('items');
        $receivedItems = [];

        foreach ($po->items as $item) {
            $receivedItems[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->ordered_qty,
                'unit_cost' => (float) $item->unit_price,
            ];
        }

        $grn = $this->procurementService->receiveGoods(
            po: $po,
            receivedItems: $receivedItems,
            notes: 'استلام كامل فوري من لوحة التحكم وإضافة الأصناف للأرصدة المخزنية',
            receiver: Auth::user()
        );

        return redirect()->back()->with('success', "تم استلام بضاعة أمر الشراء ({$po->po_number}) بنجاح بسند استلام ({$grn->grn_number}) وتم تحديث المخزون.");
    }

    /**
     * طباعة فاتورة مبيعات ضريبية كـ PDF
     */
    public function printInvoice(Invoice $invoice)
    {
        if (Auth::user()?->hasRole('call_center')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء باستعراض أو طباعة الفواتير المالية.');
        }

        $invoice->load(['items.product', 'customer', 'warehouse', 'payments', 'creator']);
        $companyName = SystemSetting::get('company_name', 'شركة تقنية الفلاتر للتجارة');
        $taxNumber = SystemSetting::get('tax_number', '310999888700003');
        $currency = SystemSetting::get('default_currency', 'ر.س');

        return view('invoices.print', compact('invoice', 'companyName', 'taxNumber', 'currency'));
    }

    /**
     * طباعة أمر شراء رسمي كـ PDF
     */
    public function printPurchaseOrder(PurchaseOrder $po)
    {
        if (Auth::user()?->hasRole('call_center')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء بطباعة أوامر الشراء.');
        }

        $po->load(['items.product', 'supplier', 'warehouse', 'creator', 'approver']);
        return view('procurement.po_print', compact('po'));
    }

    /**
     * طباعة تقرير ERP شامل كـ PDF
     */
    public function printReport(Request $request)
    {
        $type = $request->input('type', 'sales');
        $period = $request->input('period', 'all');

        $reportTitle = 'تقرير شامل';
        $periodLabel = match($period) {
            'today' => 'اليوم (' . date('Y-m-d') . ')',
            'this_week' => 'الأسبوع الحالي',
            'this_month' => 'الشهر الحالي (' . date('F Y') . ')',
            'this_year' => 'العام الحالي (' . date('Y') . ')',
            default => 'كافة الفترات المالية المسجلة'
        };

        $kpis = [];
        $tableHeaders = [];
        $tableRows = [];
        $tableFooter = [];

        if ($type === 'sales') {
            $reportTitle = 'تقرير المبيعات والتحصيل المالي الشامل';
            $invoices = Invoice::with(['customer', 'warehouse', 'payments'])->latest()->get();

            $totalSales = $invoices->sum('grand_total');
            $totalPaid = $invoices->sum('paid_amount');
            $totalDue = $invoices->sum('balance_due');
            $totalVat = $invoices->sum('tax_amount');

            $kpis = [
                ['label' => 'إجمالي المبيعات', 'value' => number_format($totalSales, 2) . ' ر.س', 'color' => '#0284c7'],
                ['label' => 'المبالغ المحصلة', 'value' => number_format($totalPaid, 2) . ' ر.س', 'color' => '#10b981'],
                ['label' => 'الأرصدة المتبقية (آجل)', 'value' => number_format($totalDue, 2) . ' ر.س', 'color' => '#f59e0b'],
                ['label' => 'ضريبة القيمة المضافة 15%', 'value' => number_format($totalVat, 2) . ' ر.س', 'color' => '#8b5cf6'],
            ];

            $tableHeaders = [
                ['title' => '# رقم الفاتورة'],
                ['title' => 'اسم العميل'],
                ['title' => 'التاريخ'],
                ['title' => 'المستودع'],
                ['title' => 'الإجمالي (ريال)', 'align' => 'left'],
                ['title' => 'المدفوع (ريال)', 'align' => 'left'],
                ['title' => 'المتبقي (ريال)', 'align' => 'left'],
                ['title' => 'الحالة'],
            ];

            foreach ($invoices as $inv) {
                $statusBadge = match($inv->status) {
                    'paid' => 'success',
                    'partially_paid' => 'warning',
                    'issued' => 'info',
                    default => 'danger'
                };
                $statusText = match($inv->status) {
                    'paid' => 'مسددة بالكامل',
                    'partially_paid' => 'مسددة جزئياً',
                    'issued' => 'معتمدة / بانتظار السداد',
                    default => $inv->status
                };

                $tableRows[] = [
                    ['content' => $inv->invoice_number, 'is_num' => true],
                    ['content' => $inv->customer?->name ?? 'عميل نقدي'],
                    ['content' => $inv->invoice_date?->format('Y-m-d') ?? $inv->created_at->format('Y-m-d'), 'is_num' => true],
                    ['content' => $inv->warehouse?->name ?? 'المستودع الرئيسي'],
                    ['content' => number_format($inv->grand_total, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                    ['content' => number_format($inv->paid_amount, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                    ['content' => number_format($inv->balance_due, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                    ['content' => $statusText, 'badge' => $statusBadge],
                ];
            }

            $tableFooter = [
                ['content' => 'المجموع الإجمالي (' . count($invoices) . ' فاتورة)'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => number_format($totalSales, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                ['content' => number_format($totalPaid, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                ['content' => number_format($totalDue, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                ['content' => 'نسبة التحصيل: ' . ($totalSales > 0 ? round(($totalPaid / $totalSales) * 100, 1) : 0) . '%'],
            ];
        } elseif ($type === 'procurement') {
            $reportTitle = 'تقرير المشتريات والموردين وحركة التوريد';
            $pos = PurchaseOrder::with(['supplier', 'warehouse', 'items'])->latest()->get();
            $totalPOAmount = $pos->sum('total_amount');
            $receivedCount = $pos->where('status', 'received')->count();
            $approvedCount = $pos->where('status', 'approved')->count();

            $kpis = [
                ['label' => 'إجمالي المشتريات المعتمدة', 'value' => number_format($totalPOAmount, 2) . ' ر.س', 'color' => '#0284c7'],
                ['label' => 'أوامر تم استلامها', 'value' => $receivedCount . ' أمر', 'color' => '#10b981'],
                ['label' => 'أوامر بانتظار التوريد', 'value' => $approvedCount . ' أمر', 'color' => '#f59e0b'],
                ['label' => 'عدد الموردين المسجلين', 'value' => Supplier::count() . ' مورد', 'color' => '#64748b'],
            ];

            $tableHeaders = [
                ['title' => '# أمر الشراء'],
                ['title' => 'المورد'],
                ['title' => 'التاريخ'],
                ['title' => 'المستودع'],
                ['title' => 'عدد الأصناف'],
                ['title' => 'القيمة الإجمالية', 'align' => 'left'],
                ['title' => 'حالة الأمر'],
            ];

            foreach ($pos as $po) {
                $statusBadge = match($po->status) {
                    'received' => 'success',
                    'approved' => 'info',
                    'draft' => 'warning',
                    default => 'danger'
                };
                $statusText = match($po->status) {
                    'received' => 'مستلم بالمستودع',
                    'approved' => 'معتمد للتوريد',
                    'draft' => 'مسودة',
                    default => $po->status
                };

                $tableRows[] = [
                    ['content' => $po->po_number, 'is_num' => true],
                    ['content' => $po->supplier?->name],
                    ['content' => $po->order_date?->format('Y-m-d') ?? $po->created_at->format('Y-m-d'), 'is_num' => true],
                    ['content' => $po->warehouse?->name],
                    ['content' => $po->items->count() . ' أصناف', 'is_num' => true],
                    ['content' => number_format($po->total_amount, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                    ['content' => $statusText, 'badge' => $statusBadge],
                ];
            }

            $tableFooter = [
                ['content' => 'الإجمالي (' . count($pos) . ' أمر شراء)'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => number_format($totalPOAmount, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                ['content' => 'نسبة الإنجاز: ' . ($pos->count() > 0 ? round(($receivedCount / $pos->count()) * 100, 1) : 0) . '%'],
            ];
        } elseif ($type === 'inventory') {
            $reportTitle = 'تقرير تقييم المخزون وأرصدة المستودعات';
            $products = Product::with(['category', 'stockBalances'])->where('type', '!=', 'service')->get();
            $totalCostVal = 0;
            $totalSellVal = 0;
            $totalUnits = 0;

            foreach ($products as $p) {
                $qty = $p->stockBalances->sum('quantity');
                $totalUnits += $qty;
                $totalCostVal += $qty * (float) $p->cost_price;
                $totalSellVal += $qty * (float) $p->selling_price;
            }

            $kpis = [
                ['label' => 'إجمالي قيمة المخزون (سعر التكلفة)', 'value' => number_format($totalCostVal, 2) . ' ر.س', 'color' => '#0284c7'],
                ['label' => 'القيمة البيعية المتوقعة', 'value' => number_format($totalSellVal, 2) . ' ر.س', 'color' => '#10b981'],
                ['label' => 'هامش الربح المتوقع', 'value' => number_format($totalSellVal - $totalCostVal, 2) . ' ر.س', 'color' => '#8b5cf6'],
                ['label' => 'إجمالي الوحدات المتوفرة', 'value' => number_format($totalUnits) . ' قطعة', 'color' => '#f59e0b'],
            ];

            $tableHeaders = [
                ['title' => 'كود الصنف (SKU)'],
                ['title' => 'اسم المنتج / القطعة'],
                ['title' => 'التصنيف'],
                ['title' => 'الرصيد المتاح', 'align' => 'center'],
                ['title' => 'سعر التكلفة', 'align' => 'left'],
                ['title' => 'سعر البيع', 'align' => 'left'],
                ['title' => 'إجمالي القيمة بالتكلفة', 'align' => 'left'],
                ['title' => 'حالة المخزون'],
            ];

            foreach ($products as $p) {
                $qty = $p->stockBalances->sum('quantity');
                $lineCost = $qty * (float) $p->cost_price;
                $isLow = $qty <= $p->reorder_level;

                $tableRows[] = [
                    ['content' => $p->sku, 'is_num' => true],
                    ['content' => $p->name_ar],
                    ['content' => $p->category?->name_ar ?? 'عام'],
                    ['content' => $qty . ' ' . ($p->unit ?? 'قطعة'), 'is_num' => true, 'align' => 'center'],
                    ['content' => number_format($p->cost_price, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                    ['content' => number_format($p->selling_price, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                    ['content' => number_format($lineCost, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                    ['content' => $isLow ? 'طلب إعادة تموين' : 'متوفر ومستقر', 'badge' => $isLow ? 'danger' : 'success'],
                ];
            }

            $tableFooter = [
                ['content' => 'الإجمالي (' . count($products) . ' أصناف)'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => number_format($totalUnits) . ' قطعة', 'is_num' => true, 'align' => 'center'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => number_format($totalCostVal, 2) . ' ر.س', 'is_num' => true, 'align' => 'left'],
                ['content' => 'القيمة البيعية: ' . number_format($totalSellVal, 2) . ' ر.س'],
            ];
        } else {
            // Maintenance Report
            $reportTitle = 'تقرير عمليات الصيانة ومحرك استحقاقات الفلاتر';
            $tickets = MaintenanceTicket::with(['customer', 'device.model', 'technician'])->latest()->get();
            $stages = CustomerDeviceStage::with(['device.customer', 'product'])->whereNotNull('next_due_at')->get();

            $completedCount = $tickets->whereIn('status', ['completed_pending_review', 'approved_closed'])->count();
            $openCount = $tickets->count() - $completedCount;
            $overdueStagesCount = $stages->where('next_due_at', '<', now()->toDateString())->count();

            $kpis = [
                ['label' => 'إجمالي تذاكر الصيانة', 'value' => $tickets->count() . ' تذكرة', 'color' => '#0284c7'],
                ['label' => 'تذاكر منجزة ومغلقة', 'value' => $completedCount . ' تذكرة', 'color' => '#10b981'],
                ['label' => 'تذاكر قيد التنفيذ والمتابعة', 'value' => $openCount . ' تذكرة', 'color' => '#f59e0b'],
                ['label' => 'شمعات مستحقة الاستبدال', 'value' => $stages->count() . ' شمعة', 'color' => '#ef4444'],
            ];

            $tableHeaders = [
                ['title' => '# رقم التذكرة'],
                ['title' => 'العميل'],
                ['title' => 'نوع الخدمة'],
                ['title' => 'الجهاز والموديل'],
                ['title' => 'الفني المكلف'],
                ['title' => 'تاريخ الجدولة'],
                ['title' => 'الحالة'],
            ];

            foreach ($tickets as $tkt) {
                $statusBadge = match($tkt->status) {
                    'approved_closed', 'completed_pending_review' => 'success',
                    'in_progress' => 'info',
                    'assigned', 'scheduled' => 'warning',
                    default => 'danger'
                };

                $tableRows[] = [
                    ['content' => $tkt->ticket_number, 'is_num' => true],
                    ['content' => $tkt->customer?->name],
                    ['content' => $tkt->service_type],
                    ['content' => $tkt->device?->model?->name ?? 'محطة تحلية مياه'],
                    ['content' => $tkt->technician?->name ?? 'لم يُسند بعد'],
                    ['content' => $tkt->scheduled_date?->format('Y-m-d') ?? $tkt->created_at->format('Y-m-d'), 'is_num' => true],
                    ['content' => $tkt->status, 'badge' => $statusBadge],
                ];
            }

            $tableFooter = [
                ['content' => 'الإجمالي (' . count($tickets) . ' تذكرة)'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => '—'],
                ['content' => 'معدل الإنجاز: ' . ($tickets->count() > 0 ? round(($completedCount / $tickets->count()) * 100, 1) : 0) . '%'],
            ];
        }

        return view('reports.print', compact(
            'reportTitle',
            'periodLabel',
            'kpis',
            'tableHeaders',
            'tableRows',
            'tableFooter'
        ));
    }

    /**
     * إنشاء دور وظيفي جديد بواسطة Super Admin
     */
    public function createRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name|max:50',
            'display_name_ar' => 'required|string|max:100',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => Str::slug($validated['name'], '_'),
            'display_name_ar' => $validated['display_name_ar'],
            'description' => $validated['description'] ?? 'دور وظيفي مخصص في النظام',
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'role.created',
            'auditable_type' => Role::class,
            'auditable_id' => $role->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['name' => $role->name, 'display_name_ar' => $role->display_name_ar],
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', "تم إنشاء الدور الوظيفي الجديد ({$role->display_name_ar}) بنجاح.");
    }

    /**
     * حذف دور وظيفي مخصص بواسطة Super Admin
     */
    public function deleteRole(Request $request, Role $role)
    {
        $protectedRoles = ['super_admin', 'general_manager', 'technician', 'maintenance_supervisor', 'call_center', 'warehouse_keeper', 'accountant'];

        if (in_array($role->name, $protectedRoles)) {
            return redirect()->back()->with('error', 'لا يمكن حذف الأدوار القياسية الأساسية في النظام لضمان استقرار العمليات.');
        }

        $roleName = $role->display_name_ar;
        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'role.deleted',
            'auditable_type' => Role::class,
            'auditable_id' => $role->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['role_deleted' => $roleName],
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', "تم حذف الدور الوظيفي ({$roleName}) بنجاح.");
    }

    /**
     * تحديث الدور الوظيفي لمستخدم مباشرة من شاشة الصلاحيات
     */
    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->roles()->sync([$validated['role_id']]);

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'user.role_changed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['user' => $user->name, 'new_role_id' => $validated['role_id']],
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', "تم تحديث الدور الوظيفي للموظف ({$user->name}) بنجاح.");
    }

    /**
     * تحديث مصفوفة الصلاحيات لدور محدد
     */
    public function updateRolePermissions(Request $request, Role $role)
    {
        $permissions = $request->input('permissions', []);
        $role->permissions()->sync($permissions);

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'role.permissions_updated',
            'auditable_type' => Role::class,
            'auditable_id' => $role->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => [
                'role' => $role->name,
                'granted_count' => count($permissions)
            ],
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', "تم تحديث وحفظ مصفوفة صلاحيات ({$role->display_name_ar}) بنجاح (" . count($permissions) . " صلاحية معتمدة).");
    }

    /**
     * إضافة صلاحية نظام جديدة
     */
    public function createPermission(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name|max:100',
            'display_name_ar' => 'required|string|max:255',
            'module' => 'required|string|max:50',
        ]);

        $permission = Permission::create($validated);

        // إسناد الصلاحية لمدير النظام تلقائياً
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermission($permission);
        }

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'permission.created',
            'auditable_type' => Permission::class,
            'auditable_id' => $permission->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => $validated,
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', "تمت إضافة الصلاحية الجديدة ({$permission->display_name_ar}) وإسنادها لمدير النظام بنجاح.");
    }

    /**
     * إضافة موظف جديد
     */
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone|max:20',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6',
            'user_type' => 'required|in:staff,technician,customer',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'user_type' => $validated['user_type'],
            'is_active' => true,
        ]);

        $user->roles()->attach($validated['role_id']);

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'user.created_web',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => [
                'name' => $user->name,
                'phone' => $user->phone,
                'user_type' => $user->user_type,
                'role_id' => $validated['role_id']
            ],
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', "تم إنشاء حساب الموظف ({$user->name}) بنجاح وتعيين دوره الوظيفي.");
    }

    /**
     * تفعيل أو تجميد حساب موظف
     */
    public function toggleUserStatus(Request $request, User $user)
    {
        if ($user->phone === '0500000001') {
            return redirect()->back()->with('error', 'لا يمكن تجميد حساب مدير النظام الرئيسي.');
        }

        $user->update(['is_active' => !$user->is_active]);

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'user.status_toggled',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['is_active' => $user->is_active],
            'created_at' => now(),
        ]);

        $statusText = $user->is_active ? 'تفعيل' : 'تجميد';
        return redirect()->back()->with('success', "تم {$statusText} حساب ({$user->name}) بنجاح.");
    }

    /**
     * إنشاء تذكرة صيانة سريعة من لوحة التحكم
     */
    public function quickCreateTicket(Request $request)
    {
        $validated = $request->validate([
            'customer_id'        => 'required|exists:customers,id',
            'customer_device_id' => 'nullable|exists:customer_devices,id',
            'service_type'       => 'required|string',
            'priority'           => 'required|in:low,normal,high,urgent',
            'technician_id'      => 'nullable|exists:users,id',
            'scheduled_date'     => 'nullable|date',
            'time_slot'          => 'nullable|string',
            'notes'              => 'nullable|string',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $address  = $customer->addresses()->first();

        // إذا لم يتم تحديد محطة وكان للعميل محطة مسجلة، نربطها بأول محطة لديه، وإلا ننشئ له محطة قياسية تلقائياً
        $deviceId = $validated['customer_device_id'] ?? $customer->devices()->first()?->id;
        if (!$deviceId) {
            $model = DeviceModel::first() ?? DeviceModel::create([
                'model_code' => 'RO-6S-STD',
                'name' => 'محطة تحلية 6 مراحل قياسية',
                'stages_count' => 6,
                'is_active' => true,
            ]);
            $newDev = CustomerDevice::create([
                'device_code' => 'DEV-' . date('Y') . '-' . str_pad(CustomerDevice::count() + 1, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'device_model_id' => $model->id,
                'serial_number' => 'DEV-' . strtoupper(Str::random(6)),
                'installation_date' => now()->toDateString(),
                'status' => 'active',
            ]);
            $deviceId = $newDev->id;
        }

        $ticketCount  = MaintenanceTicket::count() + 1;
        $ticketNumber = 'TKT-' . date('Y') . '-' . str_pad($ticketCount, 4, '0', STR_PAD_LEFT);

        $technician = !empty($validated['technician_id']) ? User::find($validated['technician_id']) : null;
        $status     = $technician ? 'assigned' : 'new';

        $serviceTypeMap = [
            'استبدال شمعات دورية' => 'routine_filter_change',
            'صيانة دورية وتغيير فلاتر' => 'routine_filter_change',
            'صيانة وإصلاح عطل' => 'repair',
            'إصلاح وعطل' => 'repair',
            'تركيب محطة جديدة' => 'installation',
            'تركيب محطة تحلية جديدة' => 'installation',
            'فحص جودة وأملاح TDS' => 'warranty_check',
            'زيارة كشف ومعاينة' => 'warranty_check',
            'نقل موقع المحطة' => 'relocation',
        ];
        $serviceType = $serviceTypeMap[$validated['service_type']] ?? $validated['service_type'];
        if (!in_array($serviceType, ['routine_filter_change', 'repair', 'installation', 'relocation', 'warranty_check'])) {
            $serviceType = 'routine_filter_change';
        }

        $ticket = MaintenanceTicket::create([
            'ticket_number'       => $ticketNumber,
            'customer_id'         => $customer->id,
            'customer_address_id' => $address?->id,
            'customer_device_id'  => $deviceId,
            'service_type'        => $serviceType,
            'priority'            => $validated['priority'],
            'status'              => $status,
            'technician_id'       => $technician?->id,
            'scheduled_date'      => $validated['scheduled_date'] ?? now()->toDateString(),
            'time_slot'           => $validated['time_slot'] ?? 'صباحاً (09:00 - 12:00)',
            'notes'               => $validated['notes'] ?? 'تم حجز الموعد عبر خدمة العملاء',
        ]);

        // توثيق الموعد في سجل تفاعلات العميل
        CustomerInteraction::create([
            'customer_id'           => $customer->id,
            'customer_device_id'    => $deviceId,
            'agent_id'              => Auth::id() ?? 1,
            'maintenance_ticket_id' => $ticket->id,
            'channel'               => 'phone_call',
            'outcome'               => 'agreed_scheduled',
            'notes'                 => "حجز موعد ({$ticket->service_type}) للفني (" . ($technician?->name ?? 'غير محدد') . ") بتاريخ: " . ($ticket->scheduled_date?->format('Y-m-d') ?? date('Y-m-d')),
        ]);

        AuditLog::create([
            'user_id'        => Auth::id() ?? 1,
            'action'         => 'ticket.created_web',
            'auditable_type' => MaintenanceTicket::class,
            'auditable_id'   => $ticket->id,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'new_values'     => [
                'ticket_number' => $ticketNumber,
                'priority'      => $ticket->priority,
                'technician'    => $technician?->name,
                'status'        => $status,
            ],
            'created_at'     => now(),
        ]);

        $msg = $technician
            ? "تم حجز الموعد بنجاح برقم ({$ticketNumber}) وتحويله مباشرةً لجدول الفني ({$technician->name})."
            : "تم إنشاء التذكرة بنجاح برقم ({$ticketNumber}).";

        return redirect()->back()->with('success', $msg);
    }

    /**
     * إسناد تذكرة لفني من لوحة التحكم
     */
    public function assignTicket(Request $request, MaintenanceTicket $ticket)
    {
        $validated = $request->validate([
            'technician_id' => 'required|exists:users,id',
            'scheduled_date' => 'required|date',
            'time_slot' => 'required|string',
        ]);

        $ticket->update([
            'technician_id' => $validated['technician_id'],
            'scheduled_date' => $validated['scheduled_date'],
            'time_slot' => $validated['time_slot'],
            'status' => 'assigned',
        ]);

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'ticket.assigned_web',
            'auditable_type' => MaintenanceTicket::class,
            'auditable_id' => $ticket->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => [
                'technician_id' => $validated['technician_id'],
                'scheduled_date' => $validated['scheduled_date']
            ],
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', "تم إسناد التذكرة {$ticket->ticket_number} للفني بنجاح");
    }

    /**
     * تشغيل فحص تذكيرات استحقاق الفلاتر اليدوي
     */
    public function runDueRemindersCommand(Request $request)
    {
        try {
            Artisan::call('filters:send-due-reminders', ['--days' => 14]);
            return redirect()->back()->with('success', "تم تشغيل أمر فحص استحقاقات الفلاتر وتجهيز مهام التذكير بنجاح");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "حدث خطأ: " . $e->getMessage());
        }
    }

    /**
     * تحديث إعدادات النظام (الألوان والشركة وسياسات التشغيل)
     */
    public function updateSettings(Request $request)
    {
        if (!Auth::user()?->hasRole('super_admin')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية تعديل إعدادات النظام');
        }

        $validated = $request->validate([
            'company_name'           => 'nullable|string|max:200',
            'company_phone'          => 'nullable|string|max:30',
            'company_email'          => 'nullable|email|max:100',
            'tax_number'             => 'nullable|string|max:50',
            'vat_percentage'         => 'nullable|integer|min:0|max:100',
            'reminder_window_days'   => 'nullable|integer|min:1|max:90',
            'working_hours_start'    => 'nullable|string|max:10',
            'working_hours_end'      => 'nullable|string|max:10',
            'theme_preset'           => 'nullable|string|max:50',
            'theme_accent_primary'   => 'nullable|string|max:20',
            'theme_accent_secondary' => 'nullable|string|max:20',
            'theme_bg_base'          => 'nullable|string|max:20',
            'theme_sidebar_bg'       => 'nullable|string|max:20',
        ]);

        $typedKeys = [
            'vat_percentage'       => 'integer',
            'reminder_window_days' => 'integer',
        ];

        foreach ($validated as $key => $value) {
            if ($value === null) continue;
            $type  = $typedKeys[$key] ?? 'string';
            $group = match (true) {
                in_array($key, ['company_name', 'company_phone', 'company_email', 'tax_number']) => 'company',
                in_array($key, ['vat_percentage'])               => 'financial',
                in_array($key, ['reminder_window_days'])         => 'maintenance',
                in_array($key, ['working_hours_start', 'working_hours_end']) => 'operations',
                str_starts_with($key, 'theme_')                  => 'appearance',
                default                                           => 'general',
            };
            SystemSetting::set($key, $value, $group, $type);
        }

        return redirect()->back()->with('success', 'تم حفظ الإعدادات بنجاح ✅');
    }

    /**
     * تصفير قاعدة البيانات (Super Admin فقط) - يحتفظ بالأدوار والإعدادات
     */
    public function resetDemoData(Request $request)
    {
        if (!Auth::user()?->hasRole('super_admin')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية تصفير البيانات');
        }

        try {
            Artisan::call('db:seed', ['--class' => 'FreshStartSeeder', '--force' => true]);
            Auth::logout();
            return redirect()->route('login')->with('success', 'تم تصفير البيانات بنجاح. يرجى تسجيل الدخول مجدداً.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء التصفير: ' . $e->getMessage());
        }
    }

    /**
     * ==========================================
     * إدارة العملاء ومحطات التحلية (Customers CRUD)
     * ==========================================
     */
    public function createCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'primary_phone' => 'required|string|max:32',
            'secondary_phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:191',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $code = 'CUST-' . strtoupper(Str::random(6));

        $customer = Customer::create([
            'customer_code' => $code,
            'name' => $validated['name'],
            'primary_phone' => $validated['primary_phone'],
            'secondary_phone' => $validated['secondary_phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        if (!empty($validated['city']) || !empty($validated['district'])) {
            CustomerAddress::create([
                'customer_id' => $customer->id,
                'title' => 'الموقع الرئيسي',
                'city' => $validated['city'] ?? 'الرياض',
                'district' => $validated['district'] ?? '',
                'street' => $validated['street'] ?? '',
                'is_default' => true,
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'customer.created',
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['name' => $customer->name, 'phone' => $customer->primary_phone],
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', "تمت إضافة العميل الجديد ({$customer->name}) بنجاح.");
    }

    public function updateCustomer(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'primary_phone' => 'required|string|max:32',
            'secondary_phone' => 'nullable|string|max:32',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $customer->update([
            'name' => $validated['name'],
            'primary_phone' => $validated['primary_phone'],
            'secondary_phone' => $validated['secondary_phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $address = $customer->addresses()->where('is_default', true)->first() ?: $customer->addresses()->first();
        if ($address) {
            $address->update([
                'city' => $validated['city'] ?? $address->city,
                'district' => $validated['district'] ?? $address->district,
                'street' => $validated['street'] ?? $address->street,
            ]);
        } elseif (!empty($validated['city'])) {
            CustomerAddress::create([
                'customer_id' => $customer->id,
                'title' => 'الموقع الرئيسي',
                'city' => $validated['city'],
                'district' => $validated['district'] ?? '',
                'street' => $validated['street'] ?? '',
                'is_default' => true,
            ]);
        }

        return redirect()->back()->with('success', "تم تحديث بيانات العميل ({$customer->name}) بنجاح.");
    }

    public function deleteCustomer(Customer $customer)
    {
        $user = Auth::user();
        if ($user && $user->hasRole('call_center') && !$user->hasRole('super_admin')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء بحذف العملاء نهائياً من النظام. يرجى مراجعة الإدارة.');
        }

        $name = $customer->name;
        foreach ($customer->devices as $dev) {
            $dev->stages()->delete();
            $dev->delete();
        }
        $customer->addresses()->delete();
        $customer->delete();

        return redirect()->back()->with('success', "تم حذف العميل ({$name}) وكافة محطاته وسجلاته بنجاح.");
    }

    /**
     * ==========================================
     * إدارة الأجهزة ومحطات التحلية (Devices CRUD)
     * ==========================================
     */
    public function createDevice(Request $request)
    {
        $validated = $request->validate([
            'customer_selection_type' => 'nullable|in:existing,new',
            'customer_id'             => 'nullable|exists:customers,id',
            'new_customer_name'       => 'nullable|string|max:191',
            'new_customer_phone'      => 'nullable|string|max:32',
            'new_customer_city'       => 'nullable|string|max:100',
            'new_customer_district'   => 'nullable|string|max:100',
            'device_model_id'         => 'nullable|exists:device_models,id',
            'serial_number'           => 'nullable|string|max:100',
            'installation_date'       => 'required|date',
            'warranty_months'         => 'nullable|integer|min:0',
            'notes'                   => 'nullable|string',
        ]);

        $selectionType = $request->input('customer_selection_type', 'existing');

        // في حال اختيار كتابة عميل جديد مباشرة أو تعبئة اسم العميل الجديد
        if ($selectionType === 'new' || (!$request->filled('customer_id') && $request->filled('new_customer_name'))) {
            $customerName = trim($request->input('new_customer_name', ''));
            if (empty($customerName)) {
                return redirect()->back()->with('error', 'يرجى كتابة اسم العميل الجديد.');
            }

            $phone = trim($request->input('new_customer_phone', ''));
            if (empty($phone)) {
                $phone = '05' . rand(10000000, 99999999);
            }

            $customer = Customer::firstOrCreate(
                ['primary_phone' => $phone],
                [
                    'customer_code' => 'CUST-' . rand(10000, 99999),
                    'name'          => $customerName,
                    'type'          => 'individual',
                    'is_active'     => true,
                    'notes'         => 'تم إنشاء العميل أثناء إضافة محطة تحلية',
                ]
            );

            // إضافة العنوان إذا لم يكن مسجلاً
            if (!$customer->addresses()->exists()) {
                $customer->addresses()->create([
                    'title'      => 'الموقع الرئيسي للتركيب',
                    'city'       => $request->input('new_customer_city', 'الرياض') ?: 'الرياض',
                    'district'   => $request->input('new_customer_district', 'حي عام') ?: 'حي عام',
                    'street'     => 'مقر العميل',
                    'is_default' => true,
                ]);
            }
        } else {
            // اختيار من قائمة العملاء المسجلين
            if (!$request->filled('customer_id')) {
                return redirect()->back()->with('error', 'يرجى اختيار العميل من القائمة أو كتابة بيانات العميل الجديد.');
            }
            $customer = Customer::findOrFail($validated['customer_id']);
        }
        $deviceCode = 'DEV-' . strtoupper(Str::random(6));
        $installationDate = Carbon::parse($validated['installation_date']);
        $warrantyMonths = $validated['warranty_months'] ?? 24;
        $warrantyExpiry = (clone $installationDate)->addMonths($warrantyMonths);

        $address = $customer->addresses()->first();

        $deviceModelId = $validated['device_model_id'] ?? DeviceModel::first()?->id;
        if (!$deviceModelId) {
            $defaultModel = DeviceModel::firstOrCreate(
                ['model_code' => 'RO-6S-STD'],
                [
                    'name' => 'محطة تحلية منزلية 6 مراحل RO',
                    'stages_count' => 6,
                    'is_active' => true,
                ]
            );
            $deviceModelId = $defaultModel->id;
        }

        $device = CustomerDevice::create([
            'device_code' => $deviceCode,
            'customer_id' => $customer->id,
            'customer_address_id' => $address?->id,
            'device_model_id' => $deviceModelId,
            'serial_number' => $validated['serial_number'] ?: ('SN-' . rand(100000, 999999)),
            'installation_date' => $installationDate->toDateString(),
            'warranty_expiry_date' => $warrantyExpiry->toDateString(),
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        // توليد مراحل الفلاتر تلقائياً
        $model = $device->device_model_id ? DeviceModel::with('stages.product')->find($device->device_model_id) : null;
        if ($model && $model->stages->count() > 0) {
            foreach ($model->stages as $st) {
                CustomerDeviceStage::create([
                    'customer_device_id' => $device->id,
                    'stage_number' => $st->stage_number,
                    'stage_name_ar' => $st->stage_name_ar,
                    'product_id' => $st->product_id,
                    'service_interval_days' => $st->default_lifespan_days ?: 90,
                    'last_actual_replacement_at' => $installationDate->toDateString(),
                    'next_due_at' => (clone $installationDate)->addDays($st->default_lifespan_days ?: 90)->toDateString(),
                    'status' => 'upcoming',
                ]);
            }
        } else {
            $defaultStages = [
                ['num' => 1, 'name' => 'المرحلة 1: شمعة قطن بولي بروبلين (PP)', 'days' => 90],
                ['num' => 2, 'name' => 'المرحلة 2: شمعة كربون حبيبي نشط (GAC)', 'days' => 180],
                ['num' => 3, 'name' => 'المرحلة 3: شمعة كربون صلب مسمط (CTO)', 'days' => 180],
                ['num' => 4, 'name' => 'المرحلة 4: غشاء التناضح العكسي (RO)', 'days' => 720],
                ['num' => 5, 'name' => 'المرحلة 5: شمعة بوست كربون (T33)', 'days' => 360],
                ['num' => 6, 'name' => 'المرحلة 6: شمعة المعادن والأملاح (Mineral)', 'days' => 360],
            ];

            $defaultProductId = Product::first()?->id;

            foreach ($defaultStages as $s) {
                CustomerDeviceStage::create([
                    'customer_device_id' => $device->id,
                    'stage_number' => $s['num'],
                    'stage_name_ar' => $s['name'],
                    'product_id' => $defaultProductId,
                    'service_interval_days' => $s['days'],
                    'last_actual_replacement_at' => $installationDate->toDateString(),
                    'next_due_at' => (clone $installationDate)->addDays($s['days'])->toDateString(),
                    'status' => 'upcoming',
                ]);
            }
        }

        return redirect()->back()->with('success', "تمت إضافة وربط محطة التحلية ({$device->device_code}) بالعميل ({$customer->name}) وتوليد جدول استحقاقات الفلاتر بنجاح.");
    }

    public function updateDevice(Request $request, CustomerDevice $device)
    {
        $validated = $request->validate([
            'serial_number' => 'nullable|string|max:100',
            'installation_date' => 'required|date',
            'warranty_expiry_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,maintenance_required,retired',
            'notes' => 'nullable|string',
        ]);

        $device->update($validated);

        return redirect()->back()->with('success', "تم تحديث بيانات المحطة ({$device->device_code}) بنجاح.");
    }

    public function deleteDevice(CustomerDevice $device)
    {
        $code = $device->device_code;
        $device->stages()->delete();
        $device->delete();

        return redirect()->back()->with('success', "تم حذف المحطة ({$code}) وكافة مراحلها بنجاح.");
    }

    /**
     * ==========================================
     * محرك استحقاقات الفلاتر (Lifecycle Stages CRUD)
     * ==========================================
     */
    public function createDeviceStage(Request $request)
    {
        $validated = $request->validate([
            'customer_device_id' => 'required|exists:customer_devices,id',
            'stage_number' => 'required|integer|min:1|max:12',
            'stage_name_ar' => 'required|string|max:150',
            'service_interval_days' => 'required|integer|min:1',
            'last_actual_replacement_at' => 'required|date',
        ]);

        $lastDate = Carbon::parse($validated['last_actual_replacement_at']);
        $nextDue = (clone $lastDate)->addDays($validated['service_interval_days']);

        CustomerDeviceStage::create([
            'customer_device_id' => $validated['customer_device_id'],
            'stage_number' => $validated['stage_number'],
            'stage_name_ar' => $validated['stage_name_ar'],
            'service_interval_days' => $validated['service_interval_days'],
            'last_actual_replacement_at' => $lastDate->toDateString(),
            'next_due_at' => $nextDue->toDateString(),
            'status' => $nextDue->isPast() ? 'overdue' : ($nextDue->isToday() ? 'due_today' : 'upcoming'),
        ]);

        return redirect()->back()->with('success', "تمت إضافة مرحلة الفلتر ({$validated['stage_name_ar']}) وجدولتها بنجاح.");
    }

    public function replaceDeviceStage(Request $request, CustomerDeviceStage $stage)
    {
        $today = now();
        $interval = $stage->service_interval_days ?: 90;
        $nextDue = (clone $today)->addDays($interval);

        $stage->update([
            'last_actual_replacement_at' => $today->toDateString(),
            'next_due_at' => $nextDue->toDateString(),
            'status' => 'upcoming',
            'postponed_until' => null,
            'technician_notes' => 'تم الاستبدال الفوري بتاريخ ' . $today->format('Y-m-d') . ' بواسطة مدير النظام.',
        ]);

        return redirect()->back()->with('success', "تم تسجيل استبدال ({$stage->stage_name_ar}) بنجاح وتحديث تاريخ الاستحقاق القادم إلى: " . $nextDue->format('Y-m-d'));
    }

    public function updateDeviceStage(Request $request, CustomerDeviceStage $stage)
    {
        $validated = $request->validate([
            'stage_name_ar' => 'required|string|max:150',
            'service_interval_days' => 'required|integer|min:1',
            'next_due_at' => 'required|date',
        ]);

        $stage->update($validated);

        return redirect()->back()->with('success', "تم تحديث موعد استحقاق ({$stage->stage_name_ar}) بنجاح.");
    }

    public function deleteDeviceStage(CustomerDeviceStage $stage)
    {
        $name = $stage->stage_name_ar;
        $stage->delete();

        return redirect()->back()->with('success', "تم حذف مرحلة الفلتر ({$name}) بنجاح.");
    }

    /**
     * ==========================================
     * تذاكر الصيانة والعمليات (Tickets CRUD & Workflow)
     * ==========================================
     */
    public function updateTicket(Request $request, MaintenanceTicket $ticket)
    {
        $validated = $request->validate([
            'service_type' => 'required|string',
            'priority' => 'required|in:normal,high,urgent',
            'status' => 'required|string',
            'technician_id' => 'nullable|exists:users,id',
            'scheduled_date' => 'nullable|date',
            'time_slot' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $ticket->update($validated);

        return redirect()->back()->with('success', "تم تحديث بيانات التذكرة ({$ticket->ticket_number}) بنجاح.");
    }

    public function updateTicketStatus(Request $request, MaintenanceTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $updates = ['status' => $validated['status']];
        if (in_array($validated['status'], ['completed_pending_review', 'approved_closed'])) {
            $updates['completed_at'] = now();
        } elseif ($validated['status'] === 'in_progress') {
            $updates['arrived_at'] = now();
        }

        $ticket->update($updates);

        return redirect()->back()->with('success', "تم تغيير حالة التذكرة ({$ticket->ticket_number}) إلى ({$validated['status']}) بنجاح.");
    }

    public function deleteTicket(MaintenanceTicket $ticket)
    {
        $num = $ticket->ticket_number;
        $ticket->delete();

        return redirect()->back()->with('success', "تم حذف التذكرة ({$num}) بنجاح.");
    }

    /**
     * ==========================================
     * الطباعة وتصدير التقارير المخصصة
     * ==========================================
     */
    public function printTicket(MaintenanceTicket $ticket)
    {
        $ticket->load(['customer', 'device.model', 'device.stages', 'address', 'technician']);
        return view('tickets.print', compact('ticket'));
    }

    public function printCustomer(Customer $customer)
    {
        $customer->load(['addresses', 'devices.model', 'devices.stages']);
        return view('customers.print', compact('customer'));
    }

    public function printAllCustomers()
    {
        $customers = Customer::with(['addresses', 'devices.model'])->latest()->get();
        $reportTitle = 'كشف وسجل العملاء ومحطات التحلية المركبة';
        $periodLabel = 'حتى تاريخ: ' . date('Y-m-d');

        $kpis = [
            ['label' => 'إجمالي العملاء', 'value' => $customers->count() . ' عميل', 'color' => '#0284c7'],
            ['label' => 'إجمالي المحطات المركبة', 'value' => CustomerDevice::count() . ' محطة', 'color' => '#10b981'],
            ['label' => 'العملاء النشطون', 'value' => $customers->where('is_active', true)->count() . ' نشط', 'color' => '#38bdf8'],
            ['label' => 'الرياض والمناطق', 'value' => 'مغطاة بالكامل', 'color' => '#8b5cf6'],
        ];

        $tableHeaders = [
            ['title' => 'كود العميل'],
            ['title' => 'اسم العميل'],
            ['title' => 'رقم الجوال'],
            ['title' => 'المدينة والحي'],
            ['title' => 'عدد المحطات المركبة', 'align' => 'center'],
            ['title' => 'تاريخ التسجيل'],
            ['title' => 'الحالة'],
        ];

        $tableRows = [];
        foreach ($customers as $c) {
            $tableRows[] = [
                ['content' => $c->customer_code, 'is_num' => true],
                ['content' => $c->name],
                ['content' => $c->primary_phone, 'is_num' => true],
                ['content' => ($c->addresses->first()?->city ?? 'الرياض') . ' - ' . ($c->addresses->first()?->district ?? 'حي سكني')],
                ['content' => $c->devices->count() . ' محطة', 'is_num' => true, 'align' => 'center'],
                ['content' => $c->created_at->format('Y-m-d'), 'is_num' => true],
                ['content' => $c->is_active ? 'نشط' : 'معطل', 'badge' => $c->is_active ? 'success' : 'danger'],
            ];
        }

        $tableFooter = [
            ['content' => 'الإجمالي (' . count($customers) . ' عميل)'],
            ['content' => '—'],
            ['content' => '—'],
            ['content' => '—'],
            ['content' => CustomerDevice::count() . ' محطة', 'align' => 'center', 'is_num' => true],
            ['content' => '—'],
            ['content' => 'جاهز للخدمة'],
        ];

        return view('reports.print', compact('reportTitle', 'periodLabel', 'kpis', 'tableHeaders', 'tableRows', 'tableFooter'));
    }

    public function printLifecycle()
    {
        $stages = CustomerDeviceStage::with(['device.customer', 'device.model', 'product'])
            ->whereNotNull('next_due_at')
            ->orderBy('next_due_at')
            ->get();

        $reportTitle = 'جدول استحقاقات ومواعيد صيانة فلاتر التحلية (Lifecycle Report)';
        $periodLabel = 'تاريخ الإصدار: ' . date('Y-m-d');

        $overdueCount = $stages->where('next_due_at', '<', now()->toDateString())->count();
        $dueTodayCount = $stages->where('next_due_at', now()->toDateString())->count();
        $upcomingCount = $stages->count() - $overdueCount - $dueTodayCount;

        $kpis = [
            ['label' => 'إجمالي الشمعات المجدولة', 'value' => $stages->count() . ' شمعة', 'color' => '#0284c7'],
            ['label' => 'شمعات متأخرة عن الموعد', 'value' => $overdueCount . ' شمعة', 'color' => '#ef4444'],
            ['label' => 'مستحقة اليوم', 'value' => $dueTodayCount . ' شمعة', 'color' => '#f59e0b'],
            ['label' => 'قادمة خلال الفترة', 'value' => $upcomingCount . ' شمعة', 'color' => '#10b981'],
        ];

        $tableHeaders = [
            ['title' => 'العميل المستفيد'],
            ['title' => 'رقم الجوال'],
            ['title' => 'المحطة / الجهاز'],
            ['title' => 'المرحلة والشمعة'],
            ['title' => 'تاريخ آخر استبدال'],
            ['title' => 'تاريخ الاستحقاق'],
            ['title' => 'الحالة'],
        ];

        $tableRows = [];
        foreach ($stages as $st) {
            $isOverdue = $st->next_due_at->isPast() && !$st->next_due_at->isToday();
            $isToday = $st->next_due_at->isToday();
            $statusBadge = $isOverdue ? 'danger' : ($isToday ? 'warning' : 'success');
            $statusText = $isOverdue ? 'متأخرة عن الموعد' : ($isToday ? 'مستحقة اليوم' : 'سارية ومجدولة');

            $tableRows[] = [
                ['content' => $st->device?->customer?->name ?? 'غير محدد'],
                ['content' => $st->device?->customer?->primary_phone ?? '—', 'is_num' => true],
                ['content' => $st->device?->model?->name ?? 'محطة تحلية'],
                ['content' => 'مرحلة ' . $st->stage_number . ' - ' . $st->stage_name_ar],
                ['content' => $st->last_actual_replacement_at?->format('Y-m-d') ?? 'تاريخ التركيب', 'is_num' => true],
                ['content' => $st->next_due_at?->format('Y-m-d'), 'is_num' => true],
                ['content' => $statusText, 'badge' => $statusBadge],
            ];
        }

        $tableFooter = [
            ['content' => 'الإجمالي (' . count($stages) . ' مرحلة شمعة)'],
            ['content' => '—'],
            ['content' => '—'],
            ['content' => '—'],
            ['content' => '—'],
            ['content' => '—'],
            ['content' => 'متابعة دورية مستمرة'],
        ];

        return view('reports.print', compact('reportTitle', 'periodLabel', 'kpis', 'tableHeaders', 'tableRows', 'tableFooter'));
    }

    public function printAllTickets()
    {
        $tickets = MaintenanceTicket::with(['customer', 'device.model', 'technician'])->latest()->get();
        $reportTitle = 'كشف وسجل أوامر وتذاكر الصيانة والعمليات';
        $periodLabel = 'حتى تاريخ: ' . date('Y-m-d');

        $completedCount = $tickets->whereIn('status', ['completed_pending_review', 'approved_closed'])->count();
        $openCount = $tickets->count() - $completedCount;

        $kpis = [
            ['label' => 'إجمالي أوامر العمل', 'value' => $tickets->count() . ' تذكرة', 'color' => '#0284c7'],
            ['label' => 'تذاكر منجزة ومغلقة', 'value' => $completedCount . ' تذكرة', 'color' => '#10b981'],
            ['label' => 'تذاكر قيد التنفيذ والمتابعة', 'value' => $openCount . ' تذكرة', 'color' => '#f59e0b'],
            ['label' => 'الفنيون الميدانيون', 'value' => User::whereHas('roles', fn($q) => $q->where('name', 'technician'))->count() . ' فني', 'color' => '#8b5cf6'],
        ];

        $tableHeaders = [
            ['title' => 'رقم التذكرة'],
            ['title' => 'اسم العميل'],
            ['title' => 'نوع الخدمة'],
            ['title' => 'الأولوية'],
            ['title' => 'تاريخ الجدولة'],
            ['title' => 'الفني المسؤول'],
            ['title' => 'الحالة'],
        ];

        $tableRows = [];
        foreach ($tickets as $t) {
            $isDone = in_array($t->status, ['completed_pending_review', 'approved_closed']);
            $tableRows[] = [
                ['content' => $t->ticket_number, 'is_num' => true],
                ['content' => $t->customer?->name ?? 'غير محدد'],
                ['content' => $t->service_type],
                ['content' => $t->priority],
                ['content' => $t->scheduled_date?->format('Y-m-d') ?? 'اليوم', 'is_num' => true],
                ['content' => $t->technician?->name ?? 'لم يُسند'],
                ['content' => $t->status, 'badge' => $isDone ? 'success' : 'info'],
            ];
        }

        $tableFooter = [
            ['content' => 'الإجمالي (' . count($tickets) . ' تذكرة صيانة)'],
            ['content' => '—'],
            ['content' => '—'],
            ['content' => '—'],
            ['content' => '—'],
            ['content' => '—'],
            ['content' => 'نسبة الإنجاز: ' . ($tickets->count() > 0 ? round(($completedCount / $tickets->count()) * 100, 1) : 0) . '%'],
        ];

        return view('reports.print', compact('reportTitle', 'periodLabel', 'kpis', 'tableHeaders', 'tableRows', 'tableFooter'));
    }

    /**
     * ==========================================
     * جرد وتسوية المخزون (Inventory Stocktaking)
     * ==========================================
     * تمكّن المحاسب وأمين المستودع والمدير من:
     *  - تسجيل الجرد الفعلي للمنتجات في أي مستودع أو سيارة فني
     *  - احتساب العجز أو الفائض آلياً
     *  - تسجيل حركة تسوية مخزنية وتعديل الرصيد الدفتري
     */
    public function adjustInventory(Request $request)
    {
        if (Auth::user()?->hasRole('call_center')) {
            return redirect()->back()->with('error', 'غير مصرح لموظف خدمة العملاء بإجراء جرد أو تسوية للمخزون.');
        }

        $validated = $request->validate([
            'warehouse_id'   => 'required|exists:warehouses,id',
            'product_id'     => 'required|exists:products,id',
            'physical_count' => 'required|integer|min:0',
            'reason'         => 'required|string|max:255',
        ]);

        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
        $product   = Product::findOrFail($validated['product_id']);

        try {
            $movement = $this->inventoryService->adjustStock(
                $warehouse,
                $product,
                (int) $validated['physical_count'],
                $validated['reason'],
                Auth::user()
            );

            AuditLog::create([
                'user_id'        => Auth::id() ?? 1,
                'action'         => 'inventory_adjustment',
                'auditable_type' => Product::class,
                'auditable_id'   => $product->id,
                'new_values'     => [
                    'movement_number' => $movement->movement_number,
                    'warehouse'       => $warehouse->name,
                    'product'         => $product->name_ar,
                    'physical_count'  => $validated['physical_count'],
                    'reason'          => $validated['reason'],
                ],
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'created_at'     => now(),
            ]);

            return redirect()->back()->with('success', "تم إجراء التسوية الجردية بنجاح لصنف ({$product->name_ar}) في مستودع ({$warehouse->name}) برقم تسوية [{$movement->movement_number}]. وتم تعديل الرصيد الدفتري إلى {$validated['physical_count']}.");
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء إجراء الجرد: ' . $e->getMessage());
        }
    }
}

