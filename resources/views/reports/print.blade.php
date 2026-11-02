<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle }} - نظام تقنية الفلاتر ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
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
            font-size: 12.5px;
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
            max-width: 900px;
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
            padding-bottom: 18px;
            margin-bottom: 20px;
        }

        .company-brand h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0284c7;
        }

        .company-brand p {
            font-size: 11.5px;
            color: #64748b;
        }

        .report-meta {
            text-align: left;
        }

        .report-meta h2 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }

        .report-period {
            font-size: 12px;
            color: #0284c7;
            font-weight: 700;
            margin-top: 4px;
        }

        /* KPI SUMMARY ROW */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .kpi-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
            text-align: center;
        }

        .kpi-label {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .kpi-val {
            font-size: 18px;
            font-weight: 800;
            color: #0284c7;
        }

        /* TABLES */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 12px;
        }

        table.data-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-align: right;
            padding: 9px 12px;
        }

        table.data-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        table.data-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        table.data-table tfoot td {
            background: #e2e8f0;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #cbd5e1;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .doc-footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
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
            <strong style="font-size: 15px;">نظام تقارير ERP الشامل</strong>
            <span style="opacity: 0.6; margin: 0 10px;">|</span>
            <span style="font-size: 13px; color: #93c5fd;">{{ $reportTitle }}</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>طباعة / تصدير PDF</span>
            </button>
            <a href="javascript:window.close()" class="btn-close">إغلاق</a>
        </div>
    </div>

    <!-- Official Document Container -->
    <div class="document-container">

        <!-- Header -->
        <div class="doc-header">
            <div class="company-brand">
                <h1>تقنية الفلاتر للتجارة والتشغيل</h1>
                <p>FilterTech ERP Operations & Financial Analytics Report</p>
                <p>الرقم الضريبي الموحد: <span class="num">310999888700003</span></p>
            </div>
            <div class="report-meta">
                <h2>{{ $reportTitle }}</h2>
                <div class="report-period">الفترة: {{ $periodLabel ?? 'كافة الفترات المالية المعتمدة' }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                    تاريخ الاستخراج: <span class="num">{{ date('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- KPI Summaries -->
        <div class="kpi-row">
            @foreach($kpis as $kpi)
            <div class="kpi-box">
                <div class="kpi-label">{{ $kpi['label'] }}</div>
                <div class="kpi-val num" style="color: {{ $kpi['color'] ?? '#0284c7' }};">
                    {{ $kpi['value'] }}
                </div>
                @if(!empty($kpi['sub']))
                <div style="font-size: 10.5px; color: #94a3b8; margin-top: 2px;">{{ $kpi['sub'] }}</div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Report Table -->
        <table class="data-table">
            <thead>
                <tr>
                    @foreach($tableHeaders as $th)
                    <th style="{{ isset($th['align']) ? 'text-align: ' . $th['align'] . ';' : '' }}">{{ $th['title'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($tableRows as $row)
                <tr>
                    @foreach($row as $cell)
                    <td style="{{ isset($cell['align']) ? 'text-align: ' . $cell['align'] . ';' : '' }}" class="{{ !empty($cell['is_num']) ? 'num' : '' }}">
                        @if(!empty($cell['badge']))
                            <span class="badge-status badge-{{ $cell['badge'] }}">{{ $cell['content'] }}</span>
                        @else
                            {!! $cell['content'] !!}
                        @endif
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($tableHeaders) }}" style="text-align: center; color: #94a3b8; padding: 25px;">لا توجد بيانات مسجلة في هذا التقرير.</td>
                </tr>
                @endforelse
            </tbody>
            @if(!empty($tableFooter))
            <tfoot>
                <tr>
                    @foreach($tableFooter as $footCell)
                    <td style="{{ isset($footCell['align']) ? 'text-align: ' . $footCell['align'] . ';' : '' }}" class="{{ !empty($footCell['is_num']) ? 'num' : '' }}">
                        {{ $footCell['content'] }}
                    </td>
                    @endforeach
                </tr>
            </tfoot>
            @endif
        </table>

        <!-- Sign-off & Footer -->
        <div class="doc-footer">
            <div>المشغل: {{ auth()->user()?->name ?? 'مدير النظام العام' }}</div>
            <div>نظام إدارة الموارد المؤسسية تقنية الفلاتر (ERP Suite)</div>
            <div>صفحة 1 من 1</div>
        </div>

    </div>

</body>
</html>
