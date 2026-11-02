<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقنية الفلاتر | تسجيل الدخول إلى النظام المركزي</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-base: #060b17;
            --accent-cyan: #38bdf8;
            --accent-blue: #0284c7;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }

        body {
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(at 10% 20%, rgba(2, 132, 199, 0.35) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(14, 165, 233, 0.25) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(15, 23, 42, 0.9) 0px, transparent 70%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: var(--text-main);
        }

        .login-wrapper {
            max-width: 980px;
            width: 100%;
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7), 0 0 40px rgba(56, 189, 248, 0.15);
        }

        @media (max-width: 860px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }
            .brand-side {
                display: none;
            }
        }

        /* Brand Side */
        .brand-side {
            background: linear-gradient(145deg, rgba(2, 132, 199, 0.25), rgba(15, 23, 42, 0.9));
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-left: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(56, 189, 248, 0.4);
        }

        .brand-icon svg {
            width: 30px;
            height: 30px;
            fill: #ffffff;
        }

        .brand-title h1 {
            font-size: 1.45rem;
            font-weight: 800;
            background: linear-gradient(to left, #ffffff, #7dd3fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-title p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .features-list {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            margin: 2.5rem 0;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
        }

        .feature-bullet {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(56, 189, 248, 0.15);
            color: var(--accent-cyan);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-text h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
        }

        .feature-text p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.2rem;
        }

        /* Form Side */
        .form-side {
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.35rem;
        }

        .form-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: var(--text-muted);
        }

        .form-control {
            width: 100%;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: #ffffff;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
            background: rgba(15, 23, 42, 0.9);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            border: none;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 700;
            padding: 0.85rem;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 4px 18px rgba(2, 132, 199, 0.4);
            transition: all 0.2s ease;
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(56, 189, 248, 0.5);
        }

        /* Quick Switcher for Demo */
        .quick-roles-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .quick-roles-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--accent-cyan);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .roles-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .role-chip {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.45rem 0.65rem;
            font-size: 0.775rem;
            color: var(--text-main);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
        }

        .role-chip:hover {
            background: rgba(56, 189, 248, 0.15);
            border-color: var(--accent-cyan);
            transform: scale(1.02);
        }

        .role-chip span.badge {
            font-size: 0.65rem;
            background: rgba(2, 132, 199, 0.3);
            color: #7dd3fc;
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <!-- Brand Info Side -->
        <div class="brand-side">
            <div>
                <div class="brand-header">
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                        </svg>
                    </div>
                    <div class="brand-title">
                        <h1>تقنية الفلاتر | FilterTech</h1>
                        <p>نظام التشغيل والإدارة الميدانية لمحطات التحلية</p>
                    </div>
                </div>

                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-bullet">👑</div>
                        <div class="feature-text">
                            <h4>صلاحيات Super Admin المركزية</h4>
                            <p>تحكم شامل في جميع العمليات، المبيعات، الصيانة، المخزون، والمستخدمين.</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-bullet">💧</div>
                        <div class="feature-text">
                            <h4>محرك أعمار الفلاتر الذكي</h4>
                            <p>تتبع فترات استحقاق كل شمعة بدقة بدون طفحان شهور وجدولة آلية للتذكيرات.</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-bullet">🛠️</div>
                        <div class="feature-text">
                            <h4>إدارة الصيانة وبوابات الجودة الميدانية</h4>
                            <p>فحص نسبة الأملاح TDS، توقيع العميل، والصرف التلقائي من سيارة الفني.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div style="font-size: 0.75rem; color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.06); padding-top: 1rem;">
                نظام محمي ومشفر بالكامل &copy; {{ date('Y') }} تقنية الفلاتر
            </div>
        </div>

        <!-- Form Side -->
        <div class="form-side">
            <h2 class="form-title">تسجيل الدخول للنظام</h2>
            <p class="form-subtitle">أدخل رقم الجوال وكلمة المرور للدخول بحسابك وصلاحياتك</p>

            @if($errors->any())
            <div class="alert-error">
                {{ $errors->first() }}
            </div>
            @endif

            @if(session('success'))
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #6ee7b7; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.25rem;">
                {{ session('success') }}
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>رقم الجوال المسجل</label>
                    <input type="text" name="phone" class="form-control" placeholder="05XXXXXXXX" value="{{ old('phone', '0500000001') }}" required autofocus>
                </div>

                <div class="form-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" value="Admin@123456" required>
                </div>

                <button type="submit" class="btn-submit">
                    دخول إلى لوحة التحكم
                </button>
            </form>

            <!-- Quick Demo Accounts Switcher -->
            <div class="quick-roles-section">
                <div class="quick-roles-title">
                    <span>⚡ الدخول السريع المباشر للأدوار (تجربة الصلاحيات بنقرة واحدة):</span>
                </div>

                <div class="roles-grid">
                    <a href="{{ route('role.switch', '0500000001') }}" class="role-chip">
                        <strong>👑 مدير النظام</strong>
                        <span class="badge">Super Admin</span>
                    </a>

                    <a href="{{ route('role.switch', '0500000008') }}" class="role-chip">
                        <strong>🔧 مشرف الصيانة</strong>
                        <span class="badge">Supervisor</span>
                    </a>

                    <a href="{{ route('role.switch', '0500000003') }}" class="role-chip">
                        <strong>📞 خدمة العملاء</strong>
                        <span class="badge">Call Center</span>
                    </a>

                    <a href="{{ route('role.switch', '0500000002') }}" class="role-chip">
                        <strong>🚙 فني الميدان</strong>
                        <span class="badge">Technician</span>
                    </a>

                    <a href="{{ route('role.switch', '0500000005') }}" class="role-chip">
                        <strong>📦 أمين المخزن</strong>
                        <span class="badge">Warehouse</span>
                    </a>

                    <a href="{{ route('role.switch', '0500000006') }}" class="role-chip">
                        <strong>💰 المحاسب المالي</strong>
                        <span class="badge">Accountant</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
