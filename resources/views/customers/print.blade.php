<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملف العميل وسجل المحطات - {{ $customer->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 12mm 15mm; }
        * { box-sizing: border-box; font-family: 'Cairo', sans-serif; margin: 0; padding: 0; }
        body { background: #fff; color: #0f172a; padding: 20px; font-size: 13px; line-height: 1.5; }
        .num { font-family: 'Outfit', sans-serif; }
        
        .print-actions-bar {
            background: #0f172a; color: #fff; padding: 12px 24px; border-radius: 10px;
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;
        }
        .btn-print {
            background: #0284c7; color: #fff; border: none; padding: 8px 20px; font-size: 13px;
            font-weight: 700; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-close {
            background: rgba(255,255,255,0.1); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 16px; font-size: 13px; border-radius: 6px; cursor: pointer; text-decoration: none;
        }

        .header {
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 3px solid #0284c7; padding-bottom: 15px; margin-bottom: 20px;
        }
        .company-brand h1 { font-size: 22px; font-weight: 800; color: #0284c7; }
        .company-brand p { font-size: 11px; color: #64748b; margin-top: 3px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .badge-active { background: #dcfce7; color: #166534; }

        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; background: #f8fafc; }
        .card-title {
            font-size: 13px; font-weight: 700; color: #0284c7; border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px; margin-bottom: 8px;
        }
        .card table { width: 100%; border-collapse: collapse; }
        .card table td { padding: 4px 0; font-size: 12px; }
        .card table td.label { color: #64748b; width: 35%; }
        .card table td.val { font-weight: 600; color: #0f172a; }

        .device-box {
            border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px; margin-bottom: 20px;
        }
        .device-header {
            display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px; margin-bottom: 12px;
        }
        table.stage-table { width: 100%; border-collapse: collapse; }
        table.stage-table th {
            background: #f1f5f9; color: #334155; font-weight: 700; padding: 6px 10px; font-size: 11.5px;
            border: 1px solid #cbd5e1; text-align: right;
        }
        table.stage-table td {
            padding: 6px 10px; font-size: 11.5px; border: 1px solid #e2e8f0; vertical-align: middle;
        }

        @media print {
            .print-actions-bar { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="print-actions-bar">
        <div>
            <strong>بطاقة العميل وسجل محطات التحلية</strong> | كود: <span class="num">{{ $customer->customer_code }}</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn-print" onclick="window.print()">🖨️ طباعة المستند (Print / PDF)</button>
            <a href="javascript:window.close()" class="btn-close">إغلاق النافذة</a>
        </div>
    </div>

    <div class="header">
        <div class="company-brand">
            <h1>شركة مياه مارس (Mars Water ERP)</h1>
            <p>سجل بيانات العميل ومحطات التحلية وجدول صيانة الفلاتر</p>
        </div>
        <div style="text-align: left;">
            <div style="font-size: 12px; color: #64748b;">تاريخ إصدار التقرير:</div>
            <div class="num" style="font-size: 16px; font-weight: 700;">{{ date('Y-m-d') }}</div>
            <span class="badge badge-active">عميل معتمد</span>
        </div>
    </div>

    <div class="meta-grid">
        <div class="card">
            <div class="card-title">المعلومات الشخصية والاتصال</div>
            <table>
                <tr><td class="label">اسم العميل:</td><td class="val" style="font-size: 14px; font-weight: 800;">{{ $customer->name }}</td></tr>
                <tr><td class="label">رقم الجوال الأساسي:</td><td class="val num">{{ $customer->primary_phone }}</td></tr>
                <tr><td class="label">رقم الجوال البديل:</td><td class="val num">{{ $customer->secondary_phone ?? '—' }}</td></tr>
                <tr><td class="label">تاريخ التسجيل:</td><td class="val num">{{ $customer->created_at->format('Y-m-d') }}</td></tr>
            </table>
        </div>

        <div class="card">
            <div class="card-title">بيانات العنوان والموقع</div>
            <table>
                <tr><td class="label">المدينة:</td><td class="val">{{ $customer->addresses->first()?->city ?? 'الرياض' }}</td></tr>
                <tr><td class="label">الحي السكني:</td><td class="val">{{ $customer->addresses->first()?->district ?? 'غير محدد' }}</td></tr>
                <tr><td class="label">الشارع والعنوان:</td><td class="val">{{ $customer->addresses->first()?->street ?? $customer->notes ?? 'حسب الموقع المعتمد' }}</td></tr>
                <tr><td class="label">إجمالي المحطات:</td><td class="val num" style="color: #0284c7; font-weight: 800;">{{ $customer->devices->count() }} محطة تحلية</td></tr>
            </table>
        </div>
    </div>

    <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 12px;">سجل محطات التحلية المركبة وجدول استحقاق الشمعات:</h2>

    @forelse($customer->devices as $device)
    <div class="device-box">
        <div class="device-header">
            <div>
                <strong style="color: #0284c7; font-size: 14px;">محطة: {{ $device->model?->name ?? 'محطة تحلية منزلية' }}</strong>
                <span class="num" style="color: #64748b; font-size: 12px; margin-right: 10px;">كود الجهاز: {{ $device->device_code }}</span>
            </div>
            <div style="font-size: 12px;">
                <span>الرقم التسلسلي: <strong class="num">{{ $device->serial_number ?? 'غير مسجل' }}</strong></span> | 
                <span>التركيب: <strong class="num">{{ $device->installation_date?->format('Y-m-d') ?? '—' }}</strong></span>
            </div>
        </div>

        <table class="stage-table">
            <thead>
                <tr>
                    <th>المرحلة</th>
                    <th>اسم الشمعة</th>
                    <th>دورة الاستبدال</th>
                    <th>آخر استبدال</th>
                    <th>تاريخ الاستحقاق القادم</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($device->stages as $st)
                <tr>
                    <td class="num" style="font-weight: 700; color: #0284c7; text-align: center;">{{ $st->stage_number }}</td>
                    <td style="font-weight: 600;">{{ $st->stage_name_ar }}</td>
                    <td class="num">{{ $st->service_interval_days }} يوم</td>
                    <td class="num">{{ $st->last_actual_replacement_at?->format('Y-m-d') ?? 'تاريخ التركيب' }}</td>
                    <td class="num" style="font-weight: 700; color: #0284c7;">{{ $st->next_due_at?->format('Y-m-d') ?? '—' }}</td>
                    <td>
                        @if($st->isOverdue())
                            <span style="color: #dc2626; font-weight: 700;">متأخرة</span>
                        @elseif($st->isDueToday())
                            <span style="color: #d97706; font-weight: 700;">مستحقة اليوم</span>
                        @else
                            <span style="color: #16a34a; font-weight: 700;">سارية</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 10px;">لا توجد مراحل مسجلة لهذا الجهاز</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @empty
    <div style="text-align: center; padding: 30px; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b;">
        لا توجد محطات تحلية مركبة لهذا العميل حتى الآن.
    </div>
    @endforelse

</body>
</html>
