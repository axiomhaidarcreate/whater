<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بطاقة صنف - {{ $product->name_ar }} ({{ $product->sku }})</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 15mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cairo', sans-serif; }
        body { background: #f8fafc; color: #0f172a; padding: 25px; font-size: 13px; }
        .num { font-family: 'Outfit', sans-serif; }
        .actions-bar {
            background: #0f172a; color: #fff; padding: 12px 20px; border-radius: 10px;
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;
        }
        .btn-print {
            background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: none;
            padding: 8px 22px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer;
        }
        .doc-card {
            max-width: 820px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 35px 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .header {
            display: flex; justify-content: space-between; border-bottom: 2px solid #10b981;
            padding-bottom: 20px; margin-bottom: 25px;
        }
        .title h1 { color: #10b981; font-size: 22px; font-weight: 800; }
        .grid-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; }
        .info-box h3 { font-size: 13px; color: #10b981; border-bottom: 1px dashed #cbd5e1; padding-bottom: 6px; margin-bottom: 12px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 6px 0; font-size: 13px; }
        .info-table .lbl { color: #64748b; width: 40%; font-weight: 600; }
        .info-table .val { font-weight: 700; color: #0f172a; }
        .barcode-section {
            background: #f1f5f9; border-radius: 10px; padding: 20px; text-align: center;
            margin-top: 25px; border: 1px dashed #94a3b8;
        }
        .stock-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .stock-table th { background: #0f172a; color: #fff; padding: 8px 12px; font-size: 12px; text-align: right; }
        .stock-table td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
        @media print {
            .actions-bar { display: none !important; }
            body { background: #fff; padding: 0; }
            .doc-card { border: none; box-shadow: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="actions-bar">
        <div><strong>بطاقة صنف رسمية (Product Specification Sheet)</strong> | كود: <span class="num">{{ $product->sku }}</span></div>
        <div style="display: flex; gap: 10px;">
            <button class="btn-print" onclick="window.print()">🖨️ طباعة البطاقة والباركود</button>
            <button onclick="window.close()" style="background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px; cursor: pointer;">إغلاق</button>
        </div>
    </div>

    <div class="doc-card">
        <div class="header">
            <div class="title">
                <h1>تقنية الفلاتر للتجارة والمقاولات</h1>
                <p style="color: #64748b; font-size: 12px; margin-top: 3px;">FilterTech ERP - Product Catalog</p>
            </div>
            <div style="text-align: left;">
                <div style="font-size: 18px; font-weight: 800; color: #0f172a;">بطاقة تعريف صنف</div>
                <div class="num" style="color: #10b981; font-size: 15px; font-weight: 700; margin-top: 4px;">{{ $product->sku }}</div>
            </div>
        </div>

        <div class="grid-info">
            <div class="info-box">
                <h3>البيانات الأساسية للصنف</h3>
                <table class="info-table">
                    <tr><td class="lbl">اسم الصنف (عربي):</td><td class="val">{{ $product->name_ar }}</td></tr>
                    @if($product->name_en)<tr><td class="lbl">اسم الصنف (إنجليزي):</td><td class="val num">{{ $product->name_en }}</td></tr>@endif
                    <tr><td class="lbl">كود SKU:</td><td class="val num">{{ $product->sku }}</td></tr>
                    <tr><td class="lbl">التصنيف:</td><td class="val">{{ $product->category?->name_ar ?? 'غير مصنف' }}</td></tr>
                    <tr><td class="lbl">نوع الصنف:</td><td class="val">
                        @php
                        $types = [
                            'water_device' => 'جهاز مياه / محطة تحلية',
                            'filter_cartridge' => 'فلتر مياه / شمعة فلتر',
                            'membrane' => 'غشاء ممبرين تناضح عكسي',
                            'pump' => 'مضخة مياه',
                            'spare_part' => 'قطعة غيار / وصلات',
                            'service' => 'خدمة صيانة / فحص',
                        ];
                        @endphp
                        {{ $types[$product->type] ?? $product->type }}
                    </td></tr>
                    <tr><td class="lbl">حالة الصنف:</td><td class="val">{{ $product->is_compound ? 'صنف مركب (تجميعي)' : 'صنف بسيط (غير مركب)' }}</td></tr>
                    <tr><td class="lbl">تاريخ الإضافة:</td><td class="val num">{{ $product->created_at?->format('Y-m-d') }}</td></tr>
                </table>
            </div>

            <div class="info-box">
                <h3>بيانات التسعير والمخزون</h3>
                <table class="info-table">
                    <tr><td class="lbl">سعر التكلفة:</td><td class="val num">{{ number_format($product->cost_price, 2) }} ر.س</td></tr>
                    <tr><td class="lbl">سعر البيع:</td><td class="val num" style="color: #10b981;">{{ number_format($product->selling_price, 2) }} ر.س</td></tr>
                    <tr><td class="lbl">هامش الربح التقديري:</td><td class="val num">{{ number_format(max(0, $product->selling_price - $product->cost_price), 2) }} ر.س</td></tr>
                    <tr><td class="lbl">وحدة القياس:</td><td class="val">{{ $product->unit ?? 'قطعة' }}</td></tr>
                    <tr><td class="lbl">إجمالي الرصيد بالمستودعات:</td><td class="val num" style="color: #0284c7; font-size: 15px;">{{ $product->stockBalances->sum('quantity') }} {{ $product->unit ?? 'قطعة' }}</td></tr>
                    <tr><td class="lbl">حد إعادة الطلب:</td><td class="val num">{{ $product->reorder_level ?? 5 }} {{ $product->unit ?? 'قطعة' }}</td></tr>
                </table>
            </div>
        </div>

        @if($product->stockBalances->count() > 0)
        <div style="margin-top: 15px;">
            <h3 style="font-size: 13px; color: #0f172a; font-weight: 700; margin-bottom: 8px;">توزيع الأرصدة في المستودعات:</h3>
            <table class="stock-table">
                <thead>
                    <tr>
                        <th>المستودع</th>
                        <th>كود المستودع</th>
                        <th style="text-align: center;">الكمية المتوفرة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product->stockBalances as $sb)
                    <tr>
                        <td>{{ $sb->warehouse?->name ?? 'مستودع' }}</td>
                        <td class="num">{{ $sb->warehouse?->code ?? '-' }}</td>
                        <td class="num" style="text-align: center; font-weight: 700; color: #10b981;">{{ $sb->quantity }} {{ $product->unit ?? 'قطعة' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="barcode-section">
            <div style="display: flex; justify-content: center; align-items: center; gap: 40px; flex-wrap: wrap;">
                <div>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode('Product: ' . $product->name_ar . ' | SKU: ' . $product->sku . ' | Price: ' . $product->selling_price . ' SAR') }}" alt="QR Code" style="width: 100px; height: 100px; display: block; margin: 0 auto 6px;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 600;">رمز الاستجابة السريعة (QR)</div>
                </div>
                <div>
                    @php $barcodeVal = $product->barcode ?: $product->sku; @endphp
                    <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text={{ urlencode($barcodeVal) }}&scale=2&height=12&includetext=true" alt="Barcode" style="height: 55px; max-width: 250px; display: block; margin: 0 auto 6px;" onerror="this.style.display='none';">
                    <div style="font-size: 11px; color: #64748b; font-weight: 600;">الباركود الخطي (Code128): <span class="num">{{ $barcodeVal }}</span></div>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 12px;">
            تاريخ استخراج البطاقة: <span class="num">{{ date('Y-m-d H:i') }}</span> | نظام تقنية الفلاتر ERP
        </div>
    </div>
</body>
</html>

