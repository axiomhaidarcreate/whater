<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة مبيعات - {{ $invoice->invoice_number }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            direction: rtl;
            color: #1e293b;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 13px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-info h1 {
            margin: 0;
            color: #0284c7;
            font-size: 22px;
        }
        .company-info p {
            margin: 4px 0;
            color: #64748b;
        }
        .invoice-badge {
            text-align: left;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
        }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-partially_paid { background: #fef9c3; color: #854d0e; }
        .badge-issued { background: #e0f2fe; color: #0369a1; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        .card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
        }
        .card h3 {
            margin-top: 0;
            margin-bottom: 8px;
            color: #334155;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
        }
        .card table {
            width: 100%;
        }
        .card td {
            padding: 4px 0;
        }
        .label {
            color: #64748b;
            width: 35%;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items-table th {
            background: #f1f5f9;
            color: #334155;
            text-align: right;
            padding: 10px;
            font-weight: 600;
            border-bottom: 2px solid #cbd5e1;
        }
        table.items-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-box {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .totals-table {
            width: 320px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .grand-total-row {
            background: #0284c7;
            color: #fff;
            font-size: 15px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
<body>

    <div class="no-print" style="background: #0f172a; color: #fff; padding: 12px 24px; border-radius: 10px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <strong style="font-size: 14px;">فاتورة ضريبية إلكترونية معتمدة: {{ $invoice->invoice_number }}</strong>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" style="background: linear-gradient(135deg, #0284c7, #38bdf8); color: #fff; border: none; padding: 8px 20px; font-weight: bold; border-radius: 6px; cursor: pointer; font-size: 13px;">
                🖨️ طباعة الفاتورة / حفظ كـ PDF
            </button>
            <button onclick="window.close()" style="background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                إغلاق
            </button>
        </div>
    </div>

    <div class="header">
        <div class="company-info">
            <h1>{{ $companyName }}</h1>
            <p>الرقم الضريبي: <strong>{{ $taxNumber }}</strong></p>
            <p>حلول تنقية ومعالجة المياه المنزلية والتجارية والصيانة الدورية</p>
        </div>
        <div class="invoice-badge">
            <h2 style="margin: 0 0 8px 0; color: #0f172a;">فاتورة ضريبية</h2>
            <p style="margin: 0 0 8px 0; font-family: monospace; font-size: 14px;">{{ $invoice->invoice_number }}</p>
            <span class="badge badge-{{ $invoice->status }}">
                {{ match($invoice->status) {
                    'paid' => 'مدفوعة بالكامل',
                    'partially_paid' => 'مدفوعة جزئياً',
                    'issued' => 'معتمدة بانتظار السداد',
                    'cancelled' => 'ملغاة',
                    default => $invoice->status
                } }}
            </span>
        </div>
    </div>

    <div class="meta-grid">
        <div class="card">
            <h3>بيانات العميل</h3>
            <table>
                <tr>
                    <td class="label">اسم العميل:</td>
                    <td><strong>{{ $invoice->customer->name }}</strong></td>
                </tr>
                <tr>
                    <td class="label">رقم الجوال:</td>
                    <td dir="ltr" style="text-align: right;">{{ $invoice->customer->primary_phone }}</td>
                </tr>
                <tr>
                    <td class="label">العنوان:</td>
                    <td>{{ $invoice->address?->full_address ?? 'العنوان الافتراضي' }}</td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h3>تفاصيل الفاتورة</h3>
            <table>
                <tr>
                    <td class="label">تاريخ الإصدار:</td>
                    <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <td class="label">مستودع الصرف:</td>
                    <td>{{ $invoice->warehouse->name }}</td>
                </tr>
                <tr>
                    <td class="label">المحرر:</td>
                    <td>{{ $invoice->creator->name }}</td>
                </tr>
            </table>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">الصنف والبيان</th>
                <th style="width: 10%; text-align: center;">الكمية</th>
                <th style="width: 15%; text-align: left;">سعر الوحدة</th>
                <th style="width: 10%; text-align: left;">الخصم</th>
                <th style="width: 15%; text-align: left;">الإجمالي (شامل الضريبة)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->product->name_ar }}</strong>
                    <div style="font-size: 11px; color: #64748b;">
                        كود: {{ $item->product->sku }}
                        @if($item->serial_number) | الرقم التسلسلي: {{ $item->serial_number }} @endif
                    </div>
                </td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: left;">{{ number_format($item->unit_price, 2) }} {{ $currency }}</td>
                <td style="text-align: left;">{{ number_format($item->discount_amount, 2) }} {{ $currency }}</td>
                <td style="text-align: left;"><strong>{{ number_format($item->total_price, 2) }} {{ $currency }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <table class="totals-table">
            <tr>
                <td class="label">المجموع قبل الضريبة:</td>
                <td style="text-align: left;">{{ number_format($invoice->subtotal, 2) }} {{ $currency }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
            <tr>
                <td class="label">إجمالي الخصم:</td>
                <td style="text-align: left; color: #dc2626;">-{{ number_format($invoice->discount_amount, 2) }} {{ $currency }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">ضريبة القيمة المضافة (15%):</td>
                <td style="text-align: left;">{{ number_format($invoice->tax_amount, 2) }} {{ $currency }}</td>
            </tr>
            <tr class="grand-total-row">
                <td style="padding: 10px;">الإجمالي الصافي:</td>
                <td style="text-align: left; padding: 10px;">{{ number_format($invoice->grand_total, 2) }} {{ $currency }}</td>
            </tr>
            <tr>
                <td class="label">المدفوع:</td>
                <td style="text-align: left; color: #166534; font-weight: bold;">{{ number_format($invoice->paid_amount, 2) }} {{ $currency }}</td>
            </tr>
            <tr>
                <td class="label">المتبقي المطلوب سداده:</td>
                <td style="text-align: left; color: #b91c1c; font-weight: bold;">{{ number_format($invoice->balance_due, 2) }} {{ $currency }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>شكراً لتعاملكم مع {{ $companyName }}. للاستفسار أو الصيانة يرجى الاتصال على مركز الخدمة الموحد.</p>
        <p>حررت هذه الفاتورة إلكترونياً من خلال نظام إدارة العمليات «تقنية الفلاتر» ولا تتطلب ختماً ورقياً.</p>
    </div>

</body>
</html>
