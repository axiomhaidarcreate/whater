<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أمر عمل صيانة ميداني - {{ $ticket->ticket_number }}</title>
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
        .ticket-badge { text-align: left; }
        .ticket-number { font-size: 20px; font-weight: 800; color: #0f172a; }
        .badge {
            display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-top: 4px;
        }
        .badge-urgent { background: #fee2e2; color: #991b1b; }
        .badge-high { background: #fef3c7; color: #92400e; }
        .badge-normal { background: #e0f2fe; color: #0369a1; }

        .meta-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;
        }
        .card {
            border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; background: #f8fafc;
        }
        .card-title {
            font-size: 13px; font-weight: 700; color: #0284c7; border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;
        }
        .card table { width: 100%; border-collapse: collapse; }
        .card table td { padding: 4px 0; font-size: 12px; }
        .card table td.label { color: #64748b; width: 35%; }
        .card table td.val { font-weight: 600; color: #0f172a; }

        .section-title { font-size: 14px; font-weight: 700; color: #0f172a; margin: 15px 0 8px; }
        table.data-table {
            width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #cbd5e1;
        }
        table.data-table th {
            background: #f1f5f9; color: #334155; font-weight: 700; padding: 8px 10px; font-size: 12px;
            border: 1px solid #cbd5e1; text-align: right;
        }
        table.data-table td {
            padding: 8px 10px; font-size: 12px; border: 1px solid #e2e8f0; vertical-align: middle;
        }

        .checklist-box {
            border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 20px; background: #fff;
        }
        .checklist-item {
            display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;
        }
        .checkbox { width: 16px; height: 16px; border: 2px solid #94a3b8; border-radius: 3px; display: inline-block; }

        .signature-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;
        }
        .signature-box {
            border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px; height: 110px; display: flex;
            flex-direction: column; justify-content: space-between;
        }
        .signature-box .title { font-weight: 700; font-size: 12px; color: #475569; }
        .signature-line { border-bottom: 1px dashed #94a3b8; margin-top: 25px; }

        @media print {
            .print-actions-bar { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="print-actions-bar">
        <div>
            <strong>أمر عمل صيانة ومهمة ميدانية</strong> | تذكرة رقم: <span class="num">{{ $ticket->ticket_number }}</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn-print" onclick="window.print()">🖨️ طباعة المستند (Print / PDF)</button>
            <a href="javascript:window.close()" class="btn-close">إغلاق النافذة</a>
        </div>
    </div>

    <div class="header">
        <div class="company-brand">
            <h1>شركة مياه مارس (Mars Water ERP)</h1>
            <p>نظام إدارة محطات التحلية والمبيعات والصيانة الميدانية</p>
            <p>الهاتف: 920000000 | الرياض، المملكة العربية السعودية</p>
        </div>
        <div class="ticket-badge">
            <div style="font-size: 12px; color: #64748b;">أمر عمل صيانة رقم:</div>
            <div class="ticket-number num">{{ $ticket->ticket_number }}</div>
            <div>
                <span class="badge badge-{{ $ticket->priority }}">{{ match($ticket->priority) { 'urgent' => 'عاجل وطارئ', 'high' => 'أولوية مرتفعة', default => 'عادي' } }}</span>
                <span class="badge" style="background: #f1f5f9; color: #334155;">{{ $ticket->service_type }}</span>
            </div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="card">
            <div class="card-header-flex card-title">
                <span>بيانات العميل والموقع</span>
                <span class="num" style="font-size: 11px; color: #64748b;">{{ $ticket->customer?->customer_code }}</span>
            </div>
            <table>
                <tr><td class="label">اسم العميل:</td><td class="val">{{ $ticket->customer?->name }}</td></tr>
                <tr><td class="label">رقم الجوال:</td><td class="val num">{{ $ticket->customer?->primary_phone }}</td></tr>
                <tr><td class="label">المدينة والحي:</td><td class="val">{{ $ticket->address?->city ?? 'الرياض' }} - {{ $ticket->address?->district ?? 'سكني' }}</td></tr>
                <tr><td class="label">تفاصيل العنوان:</td><td class="val">{{ $ticket->address?->street ?? $ticket->customer?->notes ?? 'حسب الموقع الجغرافي المسجل' }}</td></tr>
            </table>
        </div>

        <div class="card">
            <div class="card-header-flex card-title">
                <span>بيانات المهمة والجدولة الميدانية</span>
                <span style="font-size: 11px; color: #0284c7; font-weight: 700;">{{ $ticket->status }}</span>
            </div>
            <table>
                <tr><td class="label">تاريخ الزيارة:</td><td class="val num">{{ $ticket->scheduled_date?->format('Y-m-d') ?? 'اليوم' }}</td></tr>
                <tr><td class="label">الفترة المحددة:</td><td class="val">{{ $ticket->time_slot ?? 'صباحاً (09:00 - 01:00)' }}</td></tr>
                <tr><td class="label">الفني المسؤول:</td><td class="val" style="color: #0284c7; font-weight: 700;">{{ $ticket->technician?->name ?? 'لم يُحدد بعد' }} ({{ $ticket->technician?->phone ?? '—' }})</td></tr>
                <tr><td class="label">تاريخ الإنشاء:</td><td class="val num">{{ $ticket->created_at->format('Y-m-d H:i') }}</td></tr>
            </table>
        </div>
    </div>

    @if($ticket->device)
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-title">بيانات محطة التحلية والجهاز المستهدف</div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; font-size: 12px;">
            <div><span style="color: #64748b;">كود المحطة:</span> <strong class="num">{{ $ticket->device->device_code }}</strong></div>
            <div><span style="color: #64748b;">الموديل:</span> <strong>{{ $ticket->device->model?->name ?? 'محطة تحلية منزلية' }}</strong></div>
            <div><span style="color: #64748b;">الرقم التسلسلي:</span> <strong class="num">{{ $ticket->device->serial_number ?? 'غير مسجل' }}</strong></div>
            <div><span style="color: #64748b;">تاريخ التركيب:</span> <strong class="num">{{ $ticket->device->installation_date?->format('Y-m-d') ?? '—' }}</strong></div>
        </div>
    </div>
    @endif

    <div class="section-title">قائمة فحص واستبدال الشمعات (Service Checklist):</div>
    <div class="checklist-box">
        @if($ticket->device && $ticket->device->stages->count() > 0)
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                @foreach($ticket->device->stages as $st)
                <div class="checklist-item">
                    <span class="checkbox"></span>
                    <span><strong>مرحلة {{ $st->stage_number }}:</strong> {{ $st->stage_name_ar }}</span>
                    <span style="font-size: 11px; color: #64748b; margin-right: auto;" class="num">الموعد: {{ $st->next_due_at?->format('Y-m-d') }}</span>
                </div>
                @endforeach
            </div>
        @else
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div class="checklist-item"><span class="checkbox"></span><span>المرحلة 1: شمعة قطن بولي بروبلين (PP)</span></div>
                <div class="checklist-item"><span class="checkbox"></span><span>المرحلة 2: شمعة كربون حبيبي نشط (GAC)</span></div>
                <div class="checklist-item"><span class="checkbox"></span><span>المرحلة 3: شمعة كربون صلب مسمط (CTO)</span></div>
                <div class="checklist-item"><span class="checkbox"></span><span>المرحلة 4: غشاء التناضح العكسي (RO Membrane)</span></div>
                <div class="checklist-item"><span class="checkbox"></span><span>المرحلة 5: شمعة بوست كربون (Post Carbon)</span></div>
                <div class="checklist-item"><span class="checkbox"></span><span>المرحلة 6: شمعة المعادن والأملاح (Mineral)</span></div>
            </div>
        @endif
    </div>

    <div class="card" style="margin-bottom: 20px;">
        <div class="card-title">ملاحظات العمل الميداني وقياسات الجودة:</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 15px; margin-top: 8px;">
            <div style="border: 1px dashed #cbd5e1; padding: 10px; border-radius: 6px; text-align: center;">
                <div style="font-size: 11px; color: #64748b;">أملاح الدخول (Input TDS)</div>
                <div class="num" style="font-size: 18px; font-weight: 700; color: #0284c7; margin-top: 5px;">{{ $ticket->tds_input ?? '.........' }} PPM</div>
            </div>
            <div style="border: 1px dashed #cbd5e1; padding: 10px; border-radius: 6px; text-align: center;">
                <div style="font-size: 11px; color: #64748b;">أملاح الخروج (Output TDS)</div>
                <div class="num" style="font-size: 18px; font-weight: 700; color: #10b981; margin-top: 5px;">{{ $ticket->tds_output ?? '.........' }} PPM</div>
            </div>
            <div style="font-size: 12px; color: #334155; padding: 4px;">
                <strong>الملاحظات المسبقة:</strong> {{ $ticket->notes ?: 'لا توجد ملاحظات إضافية.' }}
            </div>
        </div>
    </div>

    <div class="signature-grid">
        <div class="signature-box">
            <span class="title">إقرار الفني المنفذ:</span>
            <div style="font-size: 11px; color: #64748b;">أقر بأنه تم إنجاز أعمال الصيانة وفحص الضغط ونسبة الأملاح حسب الأصول.</div>
            <div class="signature-line"></div>
            <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b;">
                <span>اسم الفني: {{ $ticket->technician?->name ?? '________________' }}</span>
                <span>التوقيع: ________________</span>
            </div>
        </div>

        <div class="signature-box">
            <span class="title">توقيع واستلام العميل:</span>
            <div style="font-size: 11px; color: #64748b;">أقر باستلام الجهاز يعمل بحالة ممتازة وبالمعايير المحددة.</div>
            <div class="signature-line"></div>
            <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b;">
                <span>اسم العميل: {{ $ticket->customer?->name }}</span>
                <span>التوقيع: ________________</span>
            </div>
        </div>
    </div>

</body>
</html>
