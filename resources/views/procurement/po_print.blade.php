<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أمر شراء رسمي - {{ $po->po_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 12mm 15mm;
        }

        * {
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.5;
            padding: 20px;
        }

        .num {
            font-family: 'Outfit', sans-serif;
        }

        .print-actions-bar {
            background: #0f172a;
            color: #fff;
            padding: 12px 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .btn-print {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #fff;
            border: none;
            padding: 8px 22px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-print:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-close {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 18px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }

        .document-container {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
        }

        /* HEADER */
        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .company-brand h1 {
            font-size: 24px;
            font-weight: 800;
            color: #0284c7;
            letter-spacing: -0.5px;
        }

        .company-brand p {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .doc-type-badge {
            text-align: left;
        }

        .doc-type-badge h2 {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
        }

        .status-tag {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 6px;
        }

        .status-approved { background: #dbeafe; color: #1e40af; }
        .status-received { background: #dcfce7; color: #166534; }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        /* META CARDS */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .meta-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 18px;
        }

        .meta-card h3 {
            font-size: 13px;
            font-weight: 700;
            color: #0284c7;
            margin-bottom: 10px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 6px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 4px 0;
            font-size: 12.5px;
        }

        .meta-table .lbl {
            color: #64748b;
            width: 38%;
            font-weight: 600;
        }

        .meta-table .val {
            color: #0f172a;
            font-weight: 700;
        }

        /* ITEMS TABLE */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .items-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-align: right;
            padding: 10px 14px;
            font-size: 12px;
        }

        .items-table th:first-child { border-radius: 0 6px 0 0; }
        .items-table th:last-child { border-radius: 6px 0 0 0; }

        .items-table td {
            padding: 11px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12.5px;
            vertical-align: middle;
        }

        .items-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        /* TOTALS */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        .totals-table {
            width: 320px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 7px 12px;
            font-size: 13px;
        }

        .totals-table .t-lbl {
            color: #64748b;
            font-weight: 600;
            text-align: right;
        }

        .totals-table .t-val {
            text-align: left;
            font-weight: 700;
            color: #0f172a;
        }

        .grand-total-box {
            background: #0284c7;
            color: #ffffff !important;
            font-size: 15px !important;
            border-radius: 6px;
        }

        .grand-total-box td {
            color: #ffffff !important;
            padding: 10px 12px;
        }

        /* NOTES */
        .notes-box {
            background: #f8fafc;
            border-right: 4px solid #0284c7;
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 30px;
            font-size: 12.5px;
            color: #334155;
        }

        /* SIGNATURES */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 35px;
            padding-top: 20px;
            border-top: 1px dashed #cbd5e1;
            text-align: center;
        }

        .sig-box h4 {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 45px;
        }

        .sig-line {
            border-top: 1px solid #94a3b8;
            margin: 0 auto;
            width: 80%;
            font-size: 11.5px;
            color: #0f172a;
            padding-top: 4px;
            font-weight: 700;
        }

        .doc-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .print-actions-bar {
                display: none !important;
            }
            .document-container {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- Print Control Bar -->
    <div class="print-actions-bar">
        <div>
            <strong style="font-size: 15px;">نظام إدارة المشتريات والموردين ERP</strong>
            <span style="opacity: 0.6; margin: 0 10px;">|</span>
            <span style="font-size: 13px; color: #93c5fd;">معاينة طباعة أمر الشراء: <span class="num">{{ $po->po_number }}</span></span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>طباعة / تصدير PDF</span>
            </button>
            <a href="javascript:window.close()" class="btn-close">إغلاق النافذة</a>
        </div>
    </div>

    <!-- Official A4 Document -->
    <div class="document-container">

        <!-- Header -->
        <div class="doc-header">
            <div class="company-brand">
                <h1>تقنية الفلاتر للتجارة والمقاولات</h1>
                <p>FilterTech Operations Cloud & Procurement ERP</p>
                <p>الرقم الضريبي: <span class="num">310999888700003</span> | السجل التجاري: <span class="num">1010887766</span></p>
            </div>
            <div class="doc-type-badge">
                <h2>أمر شراء رسمي (PO)</h2>
                <div class="num" style="font-size: 16px; font-weight: 800; color: #0284c7; margin-top: 4px;">{{ $po->po_number }}</div>
                @if($po->status === 'approved')
                    <span class="status-tag status-approved">معتمد رسمياً (Approved)</span>
                @elseif($po->status === 'received')
                    <span class="status-tag status-received">مستلم بالمستودع (Received)</span>
                @elseif($po->status === 'draft')
                    <span class="status-tag status-draft">مسودة قيد المراجعة (Draft)</span>
                @else
                    <span class="status-tag status-cancelled">{{ $po->status }}</span>
                @endif
            </div>
        </div>

        <!-- Meta Grid -->
        <div class="meta-grid">
            <!-- Supplier Details -->
            <div class="meta-card">
                <h3>بيانات المورد والشريك التجاري</h3>
                <table class="meta-table">
                    <tr>
                        <td class="lbl">اسم المورد:</td>
                        <td class="val">{{ $po->supplier?->name }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">كود المورد:</td>
                        <td class="val num">{{ $po->supplier?->supplier_code }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">المسؤول:</td>
                        <td class="val">{{ $po->supplier?->contact_person ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">رقم الهاتف:</td>
                        <td class="val num">{{ $po->supplier?->phone }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">الرقم الضريبي:</td>
                        <td class="val num">{{ $po->supplier?->tax_number ?? 'غير مسجل' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">العنوان:</td>
                        <td class="val">{{ $po->supplier?->address ?? 'المملكة العربية السعودية' }}</td>
                    </tr>
                </table>
            </div>

            <!-- Order Details -->
            <div class="meta-card">
                <h3>تفاصيل أمر الشراء والتسليم</h3>
                <table class="meta-table">
                    <tr>
                        <td class="lbl">تاريخ الأمر:</td>
                        <td class="val num">{{ $po->order_date?->format('Y-m-d') ?? date('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">مستودع الاستلام:</td>
                        <td class="val">{{ $po->warehouse?->name }} ({{ $po->warehouse?->code }})</td>
                    </tr>
                    <tr>
                        <td class="lbl">محرر الأمر:</td>
                        <td class="val">{{ $po->creator?->name ?? 'مدير النظام' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">معتمد الأمر:</td>
                        <td class="val">{{ $po->approver?->name ?? 'في انتظار الاعتماد' }}</td>
                    </tr>
                    @if($po->vendor_invoice_number)
                    <tr>
                        <td class="lbl">رقم فاتورة المورد:</td>
                        <td class="val num">{{ $po->vendor_invoice_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="lbl">شروط الدفع:</td>
                        <td class="val">آجل 30 يوماً بعد الفحص المخزني</td>
                    </tr>
                    <tr>
                        <td class="lbl">طريقة التوريد:</td>
                        <td class="val">توصيل لمستودع المشتري مع بطاقة وزن وبيان فحص</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">بيان الصنف والمواصفات</th>
                    <th style="width: 15%; text-align: center;">الكمية المطلوبة</th>
                    <th style="width: 15%; text-align: left;">سعر الوحدة</th>
                    <th style="width: 20%; text-align: left;">الإجمالي (ريال)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($po->items as $idx => $item)
                <tr>
                    <td class="num" style="text-align: center; color: #64748b;">{{ $idx + 1 }}</td>
                    <td>
                        <strong style="color: #0f172a; font-size: 13px;">{{ $item->product?->name_ar }}</strong>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                            كود الصنف (SKU): <span class="num">{{ $item->product?->sku }}</span> | الوحدة: {{ $item->product?->unit ?? 'قطعة' }}
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <span class="num" style="font-weight: 800; font-size: 14px; color: #0284c7;">{{ $item->ordered_qty }}</span>
                    </td>
                    <td style="text-align: left;" class="num">{{ number_format($item->unit_price, 2) }} ر.س</td>
                    <td style="text-align: left;" class="num"><strong style="color: #0f172a;">{{ number_format($item->total_price, 2) }} ر.س</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">لا توجد أصناف مسجلة في هذا الأمر.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Totals -->
        @php
            $subtotal = (float) $po->total_amount;
            $taxAmount = $po->tax_amount !== null ? (float)$po->tax_amount : round($subtotal * 0.15, 2);
            $taxRate = $subtotal > 0 ? round(($taxAmount / $subtotal) * 100, 1) : 15;
            $grandTotal = $subtotal + $taxAmount;
            $totalOrderedQty = $po->items->sum('ordered_qty');
        @endphp
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="t-lbl">إجمالي الكميات:</td>
                    <td class="t-val num">{{ number_format($totalOrderedQty) }} قطعة</td>
                </tr>
                <tr>
                    <td class="t-lbl">المجموع قبل الضريبة:</td>
                    <td class="t-val num">{{ number_format($subtotal, 2) }} ر.س</td>
                </tr>
                <tr>
                    <td class="t-lbl">ضريبة القيمة المضافة ({{ $taxRate }}%):</td>
                    <td class="t-val num">{{ number_format($taxAmount, 2) }} ر.س</td>
                </tr>
                <tr class="grand-total-box">
                    <td class="t-lbl" style="color: #fff; font-weight: 800;">الإجمالي الكلي المعتمد:</td>
                    <td class="t-val num" style="color: #fff; font-weight: 800;">{{ number_format($grandTotal, 2) }} ر.س</td>
                </tr>
            </table>
        </div>

        @if($po->notes)
        <div class="notes-box">
            <strong>ملاحظات وشروط التوريد:</strong>
            <p style="margin-top: 4px;">{{ $po->notes }}</p>
        </div>
        @endif

        <!-- Signatures & Seals -->
        <div class="signatures-grid">
            <div class="sig-box">
                <h4>مُعد الطلب (قسم المشتريات)</h4>
                <div class="sig-line">{{ $po->creator?->name ?? 'مسؤول المشتريات' }}</div>
            </div>
            <div class="sig-box">
                <h4>مدير إدارة العمليات والمالية</h4>
                <div class="sig-line">{{ $po->approver?->name ?? 'الاعتماد المالي' }}</div>
            </div>
            <div class="sig-box">
                <h4>استلام ومطابقة المستودع (GRN)</h4>
                <div class="sig-line">أمين المستودع المركزي</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="doc-footer">
            <p>تم استخراج هذا المستند إلكترونياً من خلال نظام «تقنية الفلاتر ERP» ويُعد سارياً للمطابقة والفحص المخزني والفوترة.</p>
            <p>تاريخ الاستخراج: <span class="num">{{ date('Y-m-d H:i') }}</span></p>
        </div>

    </div>

</body>
</html>
