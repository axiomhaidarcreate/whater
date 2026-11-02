<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقنية الفلاتر | نظام إدارة الموارد المؤسسية (ERP) ولوحة التحكم المركزية</title>
    <!-- Google Fonts: Cairo & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-base: #f0f4f8;
            --bg-sidebar: #1a2e28;
            --bg-sidebar-hover: #243d35;
            --bg-card: #ffffff;
            --bg-card-hover: #f8fffe;
            --border-glass: rgba(30, 126, 106, 0.15);
            --border-glow: rgba(30, 126, 106, 0.35);
            --accent-cyan: #1e7e6a;
            --accent-blue: #1e7e6a;
            --accent-teal: #2d9e80;
            --accent-emerald: #26a67a;
            --accent-amber: #f59e0b;
            --accent-rose: #ef4444;
            --accent-purple: #7c3aed;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --text-dim: #94a3b8;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }

        .num {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
        }

        .glass {
            background: var(--bg-card);
            border: 1px solid rgba(30, 126, 106, 0.1);
            border-radius: 14px;
            transition: all 0.25s ease;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
        }

        .glass:hover {
            border-color: var(--border-glow);
            box-shadow: 0 2px 12px rgba(30, 126, 106, 0.1), 0 6px 24px rgba(0,0,0,0.08);
        }

        /* FIXED RIGHT SIDEBAR */
        aside.sidebar {
            width: var(--sidebar-width);
            background: var(--bg-sidebar);
            border-left: 1px solid rgba(255, 255, 255, 0.06);
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            z-index: 150;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(45, 158, 128, 0.3) transparent;
        }

        .sidebar-brand {
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            background: rgba(0, 0, 0, 0.15);
        }

        .brand-logo-sq {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1e7e6a, #2d9e80);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(30, 126, 106, 0.5);
            flex-shrink: 0;
        }

        .brand-logo-sq svg {
            width: 24px;
            height: 24px;
            fill: #ffffff;
        }

        .brand-info h1 {
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .brand-info p {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.45);
        }

        /* User Profile in Sidebar */
        .sidebar-user {
            margin: 0.85rem 1rem;
            padding: 0.85rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #1e7e6a, #26a67a);
            color: #ffffff;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
            position: relative;
        }

        .user-avatar::after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #26a67a;
            border: 2px solid var(--bg-sidebar);
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 0.875rem;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 6px;
            margin-top: 0.2rem;
            white-space: nowrap;
        }

        .role-super-admin { background: rgba(245, 158, 11, 0.25); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
        .role-supervisor { background: rgba(45, 158, 128, 0.25); color: #6ee7c7; border: 1px solid rgba(45, 158, 128, 0.4); }
        .role-technician { background: rgba(38, 166, 122, 0.25); color: #6edfc4; border: 1px solid rgba(38, 166, 122, 0.4); }
        .role-call-center { background: rgba(124, 58, 237, 0.25); color: #c4b5fd; border: 1px solid rgba(124, 58, 237, 0.4); }
        .role-warehouse { background: rgba(6, 182, 212, 0.25); color: #67e8f9; border: 1px solid rgba(6, 182, 212, 0.4); }
        .role-accountant { background: rgba(239, 68, 68, 0.25); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); }

        /* Navigation Links */
        .sidebar-nav {
            padding: 0.4rem 0.75rem;
            flex: 1;
        }

        .nav-section-title {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: rgba(255,255,255,0.35);
            padding: 0.75rem 0.75rem 0.35rem 0.75rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.85rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            margin-bottom: 0.2rem;
            cursor: pointer;
            border: none;
            background: transparent;
            width: 100%;
            text-align: right;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.07);
        }

        .nav-link.active {
            color: #ffffff;
            background: var(--accent-cyan);
            border-right: none;
            font-weight: 700;
            box-shadow: 0 3px 12px rgba(30, 126, 106, 0.4);
        }

        .nav-badge {
            margin-right: auto;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            font-size: 0.7rem;
            padding: 0.1rem 0.45rem;
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
        }

        .nav-link.active .nav-badge {
            background: rgba(255,255,255,0.25);
        }

        /* Quick Switcher Box */
        .sidebar-quick-switch {
            padding: 0.85rem 1rem;
            background: rgba(0, 0, 0, 0.15);
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .switch-title {
            font-size: 0.725rem;
            font-weight: 700;
            color: rgba(45, 158, 128, 0.9);
            margin-bottom: 0.45rem;
        }

        .switch-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.35rem;
        }

        .switch-btn {
            display: block;
            text-align: center;
            padding: 0.35rem 0.4rem;
            font-size: 0.7rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 6px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .switch-btn:hover {
            background: rgba(30, 126, 106, 0.25);
            color: #ffffff;
            border-color: rgba(30, 126, 106, 0.5);
        }

        .sidebar-footer {
            padding: 0.85rem 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .btn-logout {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ffffff;
        }

        /* MAIN WRAPPER */
        .main-wrapper {
            margin-right: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* TOP NAVBAR */
        .top-navbar {
            height: 64px;
            background: #ffffff;
            border-bottom: 1px solid rgba(30, 126, 106, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
        }

        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .breadcrumbs strong {
            color: var(--text-main);
            font-weight: 700;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .status-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            background: rgba(30, 126, 106, 0.08);
            border: 1px solid rgba(30, 126, 106, 0.2);
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--accent-cyan);
            font-weight: 600;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent-emerald);
            box-shadow: 0 0 8px var(--accent-emerald);
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(38, 166, 122, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(38, 166, 122, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(38, 166, 122, 0); }
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 1.1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e7e6a, #2d9e80);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(30, 126, 106, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 126, 106, 0.45);
        }

        .btn-success {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
        }

        .btn-warning {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            color: #ffffff;
        }

        .btn-outline {
            background: rgba(30, 126, 106, 0.05);
            border: 1px solid rgba(30, 126, 106, 0.2);
            color: var(--accent-cyan);
        }

        .btn-outline:hover {
            background: rgba(30, 126, 106, 0.1);
            border-color: var(--accent-cyan);
        }

        /* Content Area */
        .content-area {
            padding: 1.75rem 2rem;
            flex: 1;
            background: var(--bg-base);
        }

        .alert-banner {
            background: rgba(30, 126, 106, 0.08);
            border: 1px solid rgba(30, 126, 106, 0.3);
            color: #1e7e6a;
            padding: 0.85rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            font-weight: 600;
        }

        /* KPI Metrics Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.15rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color, #1e7e6a);
            border-radius: 4px 4px 0 0;
        }

        .kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .kpi-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(30, 126, 106, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .kpi-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
            margin-bottom: 0.35rem;
        }

        .kpi-footer {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.725rem;
            color: var(--text-muted);
        }

        .badge-trend {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.45rem;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.7rem;
        }

        .trend-up { background: rgba(30, 126, 106, 0.1); color: #1e7e6a; }
        .trend-alert { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* Sub-tab Pills Switcher */
        .sub-nav-pills {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(30, 126, 106, 0.05);
            padding: 0.35rem;
            border-radius: 10px;
            border: 1px solid rgba(30, 126, 106, 0.12);
            margin-bottom: 1.25rem;
            width: fit-content;
        }

        .sub-pill {
            padding: 0.45rem 1rem;
            border-radius: 8px;
            font-size: 0.825rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            border: none;
            background: transparent;
            transition: all 0.2s ease;
        }

        .sub-pill.active {
            background: var(--accent-cyan);
            color: #ffffff;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(30, 126, 106, 0.35);
        }

        /* Tab Content Panes */
        .tab-pane {
            display: none;
            animation: fadeIn 0.3s ease forwards;
        }

        .tab-pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .content-grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 1100px) {
            .content-grid-2 { grid-template-columns: 1fr; }
        }

        .table-card {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-title {
            font-size: 1.05rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: var(--text-main);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            font-size: 0.875rem;
        }

        th {
            padding: 0.85rem 1rem;
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 2px solid rgba(30, 126, 106, 0.1);
            background: rgba(30, 126, 106, 0.03);
            white-space: nowrap;
        }

        td {
            padding: 0.95rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
            color: var(--text-main);
        }

        tr:hover td {
            background: rgba(30, 126, 106, 0.03);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-due-today { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); }
        .badge-overdue { background: rgba(220, 38, 38, 0.12); color: #b91c1c; border: 1px solid rgba(220, 38, 38, 0.3); }
        .badge-upcoming { background: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.2); }
        .badge-active { background: rgba(30, 126, 106, 0.1); color: #1e7e6a; border: 1px solid rgba(30, 126, 106, 0.2); }
        .badge-assigned { background: rgba(30, 126, 106, 0.08); color: #2d9e80; border: 1px solid rgba(30, 126, 106, 0.15); }
        .badge-in-progress { background: rgba(124, 58, 237, 0.1); color: #7c3aed; border: 1px solid rgba(124, 58, 237, 0.2); }
        .badge-urgent { background: rgba(239, 68, 68, 0.12); color: #dc2626; font-weight: 700; border: 1px solid rgba(239, 68, 68, 0.25); }

        /* RBAC Module Styles */
        .rbac-module-card {
            background: rgba(30, 126, 106, 0.03);
            border: 1px solid rgba(30, 126, 106, 0.1);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .rbac-module-header {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--accent-cyan);
            margin-bottom: 0.85rem;
            border-bottom: 1px solid rgba(30, 126, 106, 0.1);
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .permissions-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 0.75rem;
        }

        .perm-checkbox-item {
            background: rgba(30, 126, 106, 0.03);
            border: 1px solid rgba(30, 126, 106, 0.08);
            border-radius: 8px;
            padding: 0.65rem 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .perm-checkbox-item:hover {
            background: rgba(30, 126, 106, 0.06);
            border-color: rgba(30, 126, 106, 0.2);
        }

        .perm-checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent-cyan);
            cursor: pointer;
        }

        .perm-label { font-size: 0.85rem; font-weight: 600; color: var(--text-main); }
        .perm-code { font-size: 0.7rem; color: var(--text-dim); font-family: 'Outfit', sans-serif; }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(26, 46, 40, 0.65);
            backdrop-filter: blur(6px);
            z-index: 200;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow-y: auto;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: #ffffff;
            border: 1px solid rgba(30, 126, 106, 0.15);
            border-radius: 18px;
            max-width: 680px;
            width: 100%;
            padding: 2rem;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: modalPop 0.25s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content.modal-lg { max-width: 880px; }

        @keyframes modalPop {
            from { transform: scale(0.92); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(30, 126, 106, 0.1);
            padding-bottom: 1rem;
        }

        .close-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.25rem;
            line-height: 1;
        }

        .close-btn:hover { color: #ef4444; }

        .form-group { margin-bottom: 1.15rem; }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: var(--text-muted);
        }

        .form-control {
            width: 100%;
            background: #f8fafc;
            border: 1px solid rgba(30, 126, 106, 0.15);
            border-radius: 8px;
            padding: 0.65rem 0.85rem;
            color: var(--text-main);
            font-size: 0.875rem;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 0 2px rgba(30, 126, 106, 0.12);
            background: #ffffff;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        /* POS Item Row */
        .pos-item-box {
            background: rgba(30, 126, 106, 0.03);
            border: 1px solid rgba(30, 126, 106, 0.1);
            border-radius: 10px;
            padding: 0.85rem;
            margin-bottom: 0.75rem;
        }

        footer {
            border-top: 1px solid rgba(30, 126, 106, 0.08);
            padding: 1.25rem 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-dim);
            background: #ffffff;
        }

        /* ================= RESPONSIVE SYSTEM (Mobile, Tablet, Desktop) ================= */
        .mobile-toggle-btn {
            display: none;
        }

        .mobile-close-sidebar-btn {
            display: none;
        }

        .sidebar-backdrop {
            display: none;
        }

        .top-nav-start {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }

        .top-nav-end {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        .header-user-tag {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(30, 126, 106, 0.06);
            border: 1px solid rgba(30, 126, 106, 0.15);
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .header-user-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent-emerald);
            box-shadow: 0 0 6px var(--accent-emerald);
        }

        /* Large Tablet / Small Laptop / Mobile (Screens <= 1024px) */
        @media (max-width: 1024px) {
            body {
                overflow-x: hidden;
            }

            body.sidebar-open-lock {
                overflow: hidden;
            }

            aside.sidebar {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                width: 290px;
                max-width: 85vw;
                transform: translateX(100%);
                transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
                z-index: 1000;
                box-shadow: -10px 0 35px rgba(0, 0, 0, 0.3);
            }

            aside.sidebar.mobile-open {
                transform: translateX(0);
            }

            .sidebar-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(26, 46, 40, 0.5);
                backdrop-filter: blur(5px);
                -webkit-backdrop-filter: blur(5px);
                z-index: 999;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.25s ease;
            }

            .sidebar-backdrop.active {
                opacity: 1;
                pointer-events: auto;
            }

            .mobile-toggle-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                border-radius: 10px;
                background: rgba(30, 126, 106, 0.1);
                border: 1px solid rgba(30, 126, 106, 0.2);
                color: var(--accent-cyan);
                cursor: pointer;
                flex-shrink: 0;
                transition: all 0.2s ease;
            }

            .mobile-toggle-btn:hover {
                background: rgba(30, 126, 106, 0.2);
                color: #1e7e6a;
            }

            .mobile-close-sidebar-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 32px;
                height: 32px;
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.15);
                color: rgba(255,255,255,0.6);
                cursor: pointer;
                margin-right: auto;
                transition: all 0.2s ease;
            }

            .mobile-close-sidebar-btn:hover {
                color: #ffffff;
                background: rgba(239, 68, 68, 0.25);
                border-color: rgba(239, 68, 68, 0.5);
            }

            .main-wrapper {
                margin-right: 0 !important;
                width: 100% !important;
                min-width: 0 !important;
            }

            .top-navbar {
                padding: 0 1rem;
                height: 62px;
            }

            .content-area {
                padding: 1.25rem 1rem;
            }
        }

        /* Mobile Screens (Android & iPhone - Screens <= 768px) */
        @media (max-width: 768px) {
            .content-area {
                padding: 1rem 0.65rem;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 0.65rem !important;
                margin-bottom: 1.25rem !important;
            }

            .kpi-card {
                padding: 0.85rem 0.75rem !important;
                border-radius: 12px !important;
            }

            .kpi-value {
                font-size: 1.35rem !important;
            }

            .kpi-title {
                font-size: 0.725rem !important;
            }

            .table-card {
                padding: 1rem 0.75rem !important;
                border-radius: 14px !important;
                margin-bottom: 1rem !important;
            }

            .card-header-flex {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0.75rem !important;
            }

            .card-header-flex > div:last-child {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .card-header-flex .btn {
                flex: 1 1 auto;
                justify-content: center;
            }

            .form-row, .form-row-3 {
                grid-template-columns: 1fr !important;
                gap: 0.75rem !important;
            }

            .form-control, select.form-control, textarea.form-control {
                font-size: 16px !important; /* Prevents iOS auto-zoom on inputs */
                padding: 0.65rem 0.75rem !important;
            }

            .btn {
                min-height: 40px;
                font-size: 0.825rem;
            }

            .table-responsive {
                border-radius: 10px;
                margin-bottom: 0.5rem;
                -webkit-overflow-scrolling: touch;
            }

            .table-responsive table {
                min-width: 620px;
            }

            table th, table td {
                padding: 0.55rem 0.6rem !important;
                font-size: 0.8rem !important;
            }

            .modal {
                padding: 0.4rem;
                align-items: flex-end;
            }

            .modal-content, .modal-content.modal-lg {
                width: 100% !important;
                max-width: 100% !important;
                padding: 1.25rem 1rem !important;
                border-radius: 16px 16px 0 0 !important;
                max-height: 92vh !important;
            }

            .sub-nav-pills {
                width: 100% !important;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Extra Small Devices (Screens <= 440px) */
        @media (max-width: 440px) {
            .kpi-grid {
                grid-template-columns: 1fr !important;
            }

            .breadcrumb-home, .breadcrumb-sep {
                display: none;
            }

            .breadcrumbs strong {
                font-size: 0.85rem;
            }

            .header-user-tag .header-user-name {
                max-width: 80px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .permissions-checkbox-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>

    <!-- ================= FIXED RIGHT SIDEBAR ================= -->
    <aside class="sidebar">
        <div>
            <!-- Brand -->
            <div class="sidebar-brand">
                <div class="brand-logo-sq">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                </div>
                <div class="brand-info">
                    <h1>تقنية الفلاتر ERP</h1>
                    <p>منظومة إدارة المياه والمؤسسات</p>
                </div>
                <button type="button" class="mobile-close-sidebar-btn" onclick="toggleMobileSidebar()" aria-label="إغلاق القائمة">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Current User Profile -->
            <div class="sidebar-user">
                <div class="user-avatar">
                    {{ mb_substr($currentUser?->name ?? 'م', 0, 1) }}
                </div>
                <div class="user-details">
                    <div class="user-name">{{ $currentUser?->name ?? 'مدير النظام' }}</div>
                    @if($isSuperAdmin)
                        <span class="user-role-badge role-super-admin">👑 مالك النظام</span>
                    @elseif($isSystemAdmin)
                        <span class="user-role-badge" style="background:rgba(139,92,246,.2);color:#c084fc;border:1px solid rgba(139,92,246,.4);">🛡️ مدير النظام</span>
                    @elseif($isGeneralMgr)
                        <span class="user-role-badge" style="background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.35);">🏢 المدير التنفيذي</span>
                    @elseif($isSalesManager)
                        <span class="user-role-badge" style="background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.35);">📈 مدير المبيعات</span>
                    @elseif($isSalesRep)
                        <span class="user-role-badge" style="background:rgba(16,185,129,.1);color:#6ee7b7;border:1px solid rgba(16,185,129,.2);">🛒 موظف مبيعات</span>
                    @elseif($isProcurement)
                        <span class="user-role-badge" style="background:rgba(6,182,212,.15);color:#22d3ee;border:1px solid rgba(6,182,212,.35);">🏭 مدير المشتريات</span>
                    @elseif($isSupervisor)
                        <span class="user-role-badge role-supervisor">🔧 مدير الصيانة</span>
                    @elseif($isTechnician)
                        <span class="user-role-badge role-technician">🚙 فني ميداني</span>
                    @elseif($isCallCenter)
                        <span class="user-role-badge role-call-center">📞 خدمة عملاء</span>
                    @elseif($isWarehouse)
                        <span class="user-role-badge role-warehouse">🏭 أمين مخزن</span>
                    @elseif($isAccountant)
                        <span class="user-role-badge role-accountant">💰 محاسب مالي</span>
                    @else
                        <span class="user-role-badge role-supervisor">مستخدم</span>
                    @endif
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="sidebar-nav">
                @if(!$isTechnician)
                <div class="nav-section-title">التشغيل والميدان</div>

                <button class="nav-link active" onclick="switchNavTab('overview', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>لوحة المؤشرات العامة</span>
                </button>

                <!-- شاشة المواعيد للعملاء والفنيين -->
                <button class="nav-link" onclick="switchNavTab('appointments', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>المواعيد</span>
                </button>

                <button class="nav-link" onclick="switchNavTab('lifecycle', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    <span>استحقاقات الفلاتر</span>
                </button>

                <button class="nav-link" onclick="switchNavTab('tickets', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>تذاكر الصيانة والعمليات</span>
                </button>

                <button class="nav-link" onclick="switchNavTab('customers', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>العملاء ومحطات التحلية</span>
                </button>
                @else
                <div class="nav-section-title">المهام الميدانية</div>

                <button class="nav-link active" onclick="switchNavTab('overview', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>مواعيدي الميدانية</span>
                </button>
                @endif

                @if(!$isCallCenter && !$isTechnician)
                <div class="nav-section-title">إدارة الـ ERP والمالية</div>

                <!-- شاشة المبيعات -->
                <button class="nav-link" onclick="switchNavTab('sales', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>المبيعات</span>
                </button>

                <!-- شاشة المشتريات -->
                <button class="nav-link" onclick="switchNavTab('suppliers', this); if(typeof switchSuppliersSubTab==='function') switchSuppliersSubTab('orders');">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>المشتريات</span>
                </button>

                <!-- شاشة الموردين -->
                <button class="nav-link" onclick="switchNavTab('suppliers', this); if(typeof switchSuppliersSubTab==='function') switchSuppliersSubTab('directory');">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>الموردين</span>
                </button>

                <!-- شاشة المخزون -->
                <button class="nav-link" onclick="switchNavTab('inventory', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span>المخزون</span>
                </button>

                <!-- شاشة التقارير -->
                <button class="nav-link" onclick="switchNavTab('reports', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>التقارير</span>
                </button>
                @endif

                @if($isSuperAdmin || $isAccountant)
                <button class="nav-link" onclick="switchNavTab('invoices', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
                    <span>دفتر الفواتير والتحصيل</span>
                    <span class="nav-badge">{{ count($invoices) }}</span>
                </button>
                @endif

                @if($isSuperAdmin)
                <div class="nav-section-title">الإدارة والصلاحيات (Super Admin)</div>

                <!-- شاشة الصلاحيات المطلقة -->
                <button class="nav-link" onclick="switchNavTab('roles', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>شاشة الصلاحيات والأدوار</span>
                    <span class="nav-badge" style="background: #f59e0b; color: #000;">{{ count($allRoles) }}</span>
                </button>

                <button class="nav-link" onclick="switchNavTab('users', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>إدارة الموظفين والمستخدمين</span>
                    <span class="nav-badge" style="background: #0284c7;">{{ count($allUsers) }}</span>
                </button>

                <button class="nav-link" onclick="switchNavTab('audit', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>سجل التدقيق الأمني (Audit)</span>
                </button>

                <button class="nav-link" onclick="switchNavTab('settings', this)">
                    <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>إعدادات وسياسات النظام</span>
                </button>
                @endif
            </nav>

            <!-- Quick Role Switcher -->
            <div class="sidebar-quick-switch">
                <div class="switch-title">
                    <span>⚡ تجربة الدور بنقرة واحدة:</span>
                </div>
                <div class="switch-grid">
                    <a href="{{ route('role.switch', '0500000001') }}" class="switch-btn" title="Super Admin">👑 المدير العام</a>
                    <a href="{{ route('role.switch', '0500000008') }}" class="switch-btn" title="Supervisor">🔧 مشرف صيانة</a>
                    <a href="{{ route('role.switch', '0500000002') }}" class="switch-btn" title="Technician">🚙 فني ميدان</a>
                    <a href="{{ route('role.switch', '0500000003') }}" class="switch-btn" title="Call Center">📞 خدمة عملاء</a>
                    <a href="{{ route('role.switch', '0500000005') }}" class="switch-btn" title="Warehouse">📦 أمين مخزن</a>
                    <a href="{{ route('role.switch', '0500000006') }}" class="switch-btn" title="Accountant">💰 المحاسب</a>
                </div>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>تسجيل الخروج</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Mobile Backdrop for Sidebar Drawer -->
    <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleMobileSidebar()"></div>

    <!-- ================= MAIN VIEWPORT ================= -->
    <div class="main-wrapper">

        <!-- Top Header Navbar -->
        <header class="top-navbar">
            <div class="top-nav-start">
                <button type="button" class="mobile-toggle-btn" id="mobileMenuBtn" onclick="toggleMobileSidebar()" aria-label="فتح القائمة">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="breadcrumbs">
                    <span class="breadcrumb-home">الرئيسية</span>
                    <span class="breadcrumb-sep">/</span>
                    <strong id="activeBreadcrumb">{{ $isTechnician ? 'مواعيدي الميدانية' : 'لوحة المؤشرات والتشغيل العام' }}</strong>
                </div>
            </div>

            <div class="top-nav-end">
                <div class="header-user-tag">
                    <span class="header-user-dot"></span>
                    <span class="header-user-name">{{ $currentUser?->name ?? 'مدير النظام' }}</span>
                </div>
            </div>
        </header>

        <!-- Main Viewport Body -->
        <div class="content-area">

            <!-- Flash Success Message -->
            @if(session('success'))
            <div class="alert-banner">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
                @if(session('print_invoice_id'))
                <a href="{{ route('invoices.print', session('print_invoice_id')) }}" target="_blank" class="btn btn-primary" style="padding: 0.35rem 0.85rem; font-size: 0.8rem;">
                    🖨️ طباعة الفاتورة الآن PDF
                </a>
                @endif
            </div>
            @endif

            @if(session('error'))
            <div class="alert-banner" style="background: rgba(239, 68, 68, 0.15); border-color: #ef4444; color: #fca5a5;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
            @endif

            <!-- ================= TAB PANES ================= -->

            <!-- TAB 1: OVERVIEW (المؤشرات العامة) -->
            <div id="tab-overview" class="tab-pane active">
                <!-- 1. Top ERP KPI Cards Grid (يظهر فقط في شاشة المؤشرات العامة) -->
                <section class="kpi-grid">
                    @if($isTechnician)
                    <div class="glass kpi-card" style="--card-color: #0284c7;">
                        <div class="kpi-header">
                            <span class="kpi-title">مواعيدي المجدولة اليوم</span>
                            <div class="kpi-icon" style="color: #38bdf8;">📅</div>
                        </div>
                        <div class="kpi-value num" style="color: #38bdf8;">{{ $tickets->filter(fn($t) => $t->scheduled_date && \Carbon\Carbon::parse($t->scheduled_date)->isToday())->count() }}</div>
                        <div class="kpi-footer">
                            <span class="badge-trend trend-up">زيارات اليوم الميدانية</span>
                            <span>التزام بالمواعيد</span>
                        </div>
                    </div>

                    <div class="glass kpi-card" style="--card-color: #f59e0b;">
                        <div class="kpi-header">
                            <span class="kpi-title">مواعيد قيد التنفيذ بالموقع</span>
                            <div class="kpi-icon" style="color: #fbbf24;">🚗</div>
                        </div>
                        <div class="kpi-value num" style="color: #fbbf24;">{{ $tickets->where('status', 'in_progress')->count() }}</div>
                        <div class="kpi-footer">
                            <span class="badge-trend trend-alert">جاري العمل بالموقع</span>
                            <span>إجراءات الصيانة</span>
                        </div>
                    </div>

                    <div class="glass kpi-card" style="--card-color: #3b82f6;">
                        <div class="kpi-header">
                            <span class="kpi-title">إجمالي المواعيد المسندة لي</span>
                            <div class="kpi-icon" style="color: #60a5fa;">📋</div>
                        </div>
                        <div class="kpi-value num">{{ $tickets->count() }}</div>
                        <div class="kpi-footer">
                            <span class="badge-trend trend-up">من خدمة العملاء</span>
                            <span>جدول المهام</span>
                        </div>
                    </div>

                    <div class="glass kpi-card" style="--card-color: #10b981;">
                        <div class="kpi-header">
                            <span class="kpi-title">مواعيد منجزة بنجاح</span>
                            <div class="kpi-icon" style="color: #34d399;">✅</div>
                        </div>
                        <div class="kpi-value num" style="color: #34d399;">{{ $tickets->whereIn('status', ['completed_pending_review', 'approved_closed'])->count() }}</div>
                        <div class="kpi-footer">
                            <span class="badge-trend trend-up">إنجاز عالي الجودة</span>
                            <span>جاهزة للاعتماد</span>
                        </div>
                    </div>
                    @elseif(!$isCallCenter)
                    <div class="glass kpi-card" style="--card-color: #10b981;">
                        <div class="kpi-header">
                            <span class="kpi-title">إجمالي المبيعات والإيرادات</span>
                            <div class="kpi-icon" style="color: #34d399;">💰</div>
                        </div>
                        <div class="kpi-value num">{{ number_format($stats['total_revenue'], 2) }} <span style="font-size: 1rem;">ر.س</span></div>
                        <div class="kpi-footer">
                            <span class="badge-trend trend-up">{{ number_format($stats['paid_revenue'], 2) }} محصل</span>
                            <span class="num">{{ number_format($stats['due_revenue'], 2) }} آجل</span>
                        </div>
                    </div>

                    <div class="glass kpi-card" style="--card-color: #0284c7;">
                        <div class="kpi-header">
                            <span class="kpi-title">المشتريات والموردين</span>
                            <div class="kpi-icon" style="color: #38bdf8;">🏢</div>
                        </div>
                        <div class="kpi-value num">{{ number_format($stats['total_purchases'], 2) }} <span style="font-size: 1rem;">ر.س</span></div>
                        <div class="kpi-footer">
                            <span class="badge-trend trend-up">{{ $stats['total_suppliers'] }} موردين</span>
                            <span>أوامر توريد معتمدة</span>
                        </div>
                    </div>
                    @else
                    <div class="glass kpi-card" style="--card-color: #10b981;">
                        <div class="kpi-header">
                            <span class="kpi-title">تذاكر الصيانة والعمليات الجارية</span>
                            <div class="kpi-icon" style="color: #34d399;">🎫</div>
                        </div>
                        <div class="kpi-value num">{{ $stats['open_tickets'] }}</div>
                        <div class="kpi-footer">
                            <span class="badge-trend trend-up">{{ $stats['completed_tickets_month'] }} منجزة هذا الشهر</span>
                            <span>متابعة وتسكين فوري</span>
                        </div>
                    </div>

                    <div class="glass kpi-card" style="--card-color: #a855f7;">
                        <div class="kpi-header">
                            <span class="kpi-title">سجل تفاعلات خدمة العملاء</span>
                            <div class="kpi-icon" style="color: #c084fc;">📞</div>
                        </div>
                        <div class="kpi-value num">{{ count($interactions) }}</div>
                        <div class="kpi-footer">
                            <span class="badge-trend trend-up">تواصل مستمر</span>
                            <span>استجابة ورضا العميل</span>
                        </div>
                    </div>
                    @endif

                    @if(!$isTechnician)
                    <div class="glass kpi-card" style="--card-color: #38bdf8;">
                        <div class="kpi-header">
                            <span class="kpi-title">إجمالي العملاء والمحطات</span>
                            <div class="kpi-icon" style="color: #38bdf8;">👥</div>
                        </div>
                        <div class="kpi-value num">{{ $stats['total_customers'] }}</div>
                        <div class="kpi-footer"><span class="badge-trend trend-up">{{ $stats['active_devices'] }} محطة نشطة</span><span>تحت الخدمة</span></div>
                    </div>

                    <div class="glass kpi-card" style="--card-color: #f43f5e;">
                        <div class="kpi-header">
                            <span class="kpi-title">استحقاقات الفلاتر اليوم</span>
                            <div class="kpi-icon" style="color: #fb7185;">💧</div>
                        </div>
                        <div class="kpi-value num" style="color: #fb7185;">{{ $stats['due_today'] + $stats['overdue'] }}</div>
                        <div class="kpi-footer"><span class="badge-trend trend-alert">{{ $stats['overdue'] }} متأخرة</span><span>تتطلب تواصل وزيارة</span></div>
                    </div>
                    @endif

                    @if($isSuperAdmin)
                    <div class="glass kpi-card" style="--card-color: #f59e0b;">
                        <div class="kpi-header">
                            <span class="kpi-title">التحكم المركزي والصلاحيات</span>
                            <div class="kpi-icon" style="color: #fbbf24;">👑</div>
                        </div>
                        <div class="kpi-value num">{{ $stats['total_roles'] }} <span style="font-size: 0.9rem; color: var(--text-dim);">أدوار</span></div>
                        <div class="kpi-footer"><span class="badge-trend trend-up">{{ $stats['total_permissions'] }} صلاحية نظام</span><span>{{ $stats['total_users'] }} موظف</span></div>
                    </div>
                    @endif
                </section>

                <div class="content-grid-2" @if($isTechnician) style="grid-template-columns: 1fr;" @endif>
                    <div class="glass table-card">
                        @if($isTechnician)
                        <div class="card-header-flex">
                            <div>
                                <h2 class="card-title">
                                    <span style="color: #38bdf8;">📅</span>
                                    <span>المواعيد الميدانية المسندة لي من خدمة العملاء</span>
                                    <span class="badge badge-active" style="margin-right: 0.5rem; font-size: 0.8rem;">{{ $tickets->count() }} موعد</span>
                                </h2>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    كافة بيانات المواعيد المحجوزة من خدمة العملاء (بيانات العميل، الاتصال، العنوان، المحطة، الملاحظات، والإجراء الميداني)
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="{{ route('tickets.print-all') }}" target="_blank" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                                    🖨️ طباعة جدول المواعيد بالكامل (PDF)
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive" style="margin-top: 1rem;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="min-width: 130px;">رقم الموعد والتاريخ</th>
                                        <th style="min-width: 180px;">العميل والتواصل</th>
                                        <th style="min-width: 170px;">العنوان والموقع</th>
                                        <th style="min-width: 150px;">المحطة والخدمة</th>
                                        <th style="min-width: 220px;">ملاحظات خدمة العملاء</th>
                                        <th style="min-width: 100px;">الحالة</th>
                                        <th style="min-width: 160px; text-align: center;">إجراءات الفني</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tickets as $ticket)
                                    <tr style="{{ $ticket->priority === 'urgent' ? 'background: rgba(239, 68, 68, 0.05);' : '' }}">
                                        <td>
                                            <div class="num" style="color: #38bdf8; font-weight: 800; font-size: 0.95rem;">{{ $ticket->ticket_number }}</div>
                                            <div style="font-size: 0.75rem; color: #cbd5e1; margin-top: 0.25rem;">
                                                📅 <span class="num">{{ $ticket->scheduled_date?->format('Y-m-d') ?? 'تاريخ غير محدد' }}</span>
                                            </div>
                                            @if($ticket->time_slot)
                                            <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 0.15rem;">
                                                ⏰ {{ $ticket->time_slot }}
                                            </div>
                                            @endif
                                            <div style="margin-top: 0.3rem;">
                                                @if($ticket->priority === 'urgent')
                                                    <span class="badge badge-urgent" style="font-size: 0.65rem;">🚨 عاجل جداً</span>
                                                @elseif($ticket->priority === 'high')
                                                    <span class="badge badge-due-today" style="font-size: 0.65rem;">أولوية مرتفعة</span>
                                                @else
                                                    <span class="badge badge-upcoming" style="font-size: 0.65rem;">عادي</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 800; font-size: 0.95rem; color: #f8fafc;">
                                                {{ $ticket->customer?->name ?? 'عميل غير مسجل' }}
                                            </div>
                                            @if($ticket->customer?->primary_phone)
                                            @php
                                                $cleanPhone = preg_replace('/[^0-9]/', '', $ticket->customer->primary_phone);
                                                if (str_starts_with($cleanPhone, '05')) {
                                                    $cleanPhone = '966' . substr($cleanPhone, 1);
                                                }
                                            @endphp
                                            <div style="display: flex; gap: 0.35rem; margin-top: 0.4rem; flex-wrap: wrap;">
                                                <a href="tel:{{ $ticket->customer->primary_phone }}" class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; border-color: #38bdf8; color: #38bdf8;" title="اتصال مباشر بالعميل">
                                                    📞 <span class="num">{{ $ticket->customer->primary_phone }}</span>
                                                </a>
                                                <a href="https://wa.me/{{ $cleanPhone }}?text={{ urlencode('السلام عليكم ورحمة الله، معك فني الصيانة بخصوص الموعد رقم ' . $ticket->ticket_number) }}" target="_blank" class="btn" style="background: #25D366; color: #fff; padding: 0.2rem 0.55rem; font-size: 0.75rem; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;" title="محادثة واتساب سريعة">
                                                    <span>💬 واتساب</span>
                                                </a>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $addressObj = $ticket->address ?? $ticket->customer?->addresses->first();
                                                $addrText = $addressObj?->short_address ?? $addressObj?->district ?? $ticket->customer?->city ?? 'العنوان لدى خدمة العملاء';
                                            @endphp
                                            <div style="font-size: 0.85rem; color: #e2e8f0; font-weight: 600;">
                                                📍 {{ $addrText }}
                                            </div>
                                            @if($addressObj && ($addressObj->city || $addressObj->district))
                                            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.2rem;">
                                                {{ $addressObj->city }} - {{ $addressObj->district }}
                                            </div>
                                            @endif
                                            @if($addressObj && $addressObj->latitude && $addressObj->longitude)
                                            <a href="https://maps.google.com/?q={{ $addressObj->latitude }},{{ $addressObj->longitude }}" target="_blank" style="display: inline-block; font-size: 0.7rem; color: #38bdf8; text-decoration: underline; margin-top: 0.3rem;">
                                                🗺️ فتح الموقع على خرائط Google
                                            </a>
                                            @elseif($addrText !== 'العنوان لدى خدمة العملاء')
                                            <a href="https://maps.google.com/?q={{ urlencode($addrText) }}" target="_blank" style="display: inline-block; font-size: 0.7rem; color: #38bdf8; text-decoration: underline; margin-top: 0.3rem;">
                                                🗺️ بحث في الخريطة
                                            </a>
                                            @endif
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: #38bdf8; font-size: 0.85rem;">
                                                💧 {{ $ticket->service_type }}
                                            </div>
                                            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
                                                محطة: {{ $ticket->device?->model?->name ?? 'محطة تحلية مياه' }}
                                            </div>
                                            @if($ticket->device?->device_code || $ticket->device?->serial_number)
                                            <div style="font-size: 0.7rem; color: var(--text-dim);" class="num">
                                                كود: {{ $ticket->device?->device_code ?? $ticket->device?->serial_number }}
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($ticket->notes))
                                            <div style="background: rgba(56, 189, 248, 0.08); border-right: 3px solid #38bdf8; padding: 0.5rem 0.75rem; border-radius: 6px; font-size: 0.8rem; color: #bae6fd; line-height: 1.4;">
                                                <strong>📝 ملاحظات خدمة العملاء:</strong>
                                                <div style="margin-top: 0.2rem;">{{ $ticket->notes }}</div>
                                            </div>
                                            @else
                                            <span style="color: var(--text-dim); font-size: 0.75rem;">لا توجد ملاحظات إضافية</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($ticket->status === 'in_progress')
                                                <span class="badge badge-in-progress" style="display: inline-block;">🚗 بالموقع</span>
                                            @elseif($ticket->status === 'assigned')
                                                <span class="badge badge-assigned" style="display: inline-block;">مجدولة مسندة</span>
                                            @elseif($ticket->status === 'completed_pending_review')
                                                <span class="badge badge-active" style="display: inline-block; background: #059669;">✅ منجزة</span>
                                            @elseif($ticket->status === 'approved_closed')
                                                <span class="badge badge-active" style="display: inline-block;">مغلقة ومعتمدة</span>
                                            @else
                                                <span class="badge badge-upcoming" style="display: inline-block;">{{ $ticket->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 0.35rem; align-items: stretch;">
                                                @if($ticket->status === 'assigned' || $ticket->status === 'open')
                                                <form action="{{ route('tickets.status', $ticket->id) }}" method="POST" onsubmit="return confirm('تأكيد بدء التوجه / الوصول إلى موقع العميل؟');">
                                                    @csrf
                                                    <input type="hidden" name="status" value="in_progress">
                                                    <button type="submit" class="btn" style="width: 100%; background: #f59e0b; color: #000; font-weight: 700; font-size: 0.75rem; padding: 0.35rem 0.5rem; justify-content: center;">
                                                        🚗 بدأت التوجه / بالموقع
                                                    </button>
                                                </form>
                                                @elseif($ticket->status === 'in_progress')
                                                <form action="{{ route('tickets.status', $ticket->id) }}" method="POST" onsubmit="return confirm('تأكيد إنهاء أعمال الصيانة والزيارة بنجاح؟');">
                                                    @csrf
                                                    <input type="hidden" name="status" value="completed_pending_review">
                                                    <button type="submit" class="btn" style="width: 100%; background: #10b981; color: #fff; font-weight: 700; font-size: 0.75rem; padding: 0.35rem 0.5rem; justify-content: center;">
                                                        ✅ تم إنهاء الزيارة بنجاح
                                                    </button>
                                                </form>
                                                @endif
                                                <a href="{{ route('tickets.print', $ticket->id) }}" target="_blank" class="btn btn-outline" style="font-size: 0.72rem; padding: 0.25rem 0.5rem; justify-content: center;" title="طباعة كشف وأمر العمل">
                                                    🖨️ أمر العمل (PDF)
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 3rem 1rem;">
                                            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎉</div>
                                            <div style="font-size: 1.1rem; font-weight: 700; color: #f8fafc;">لا توجد أي مواعيد معلقة مسندة لك حالياً</div>
                                            <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.25rem;">
                                                سيقوم فريق خدمة العملاء بإسناد المواعيد والزيارات الجديدة لك وستظهر هنا فوراً بكامل بياناتها.
                                            </p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="card-header-flex">
                            <h2 class="card-title">
                                <span style="color: #38bdf8;">●</span>
                                <span>تذاكر الصيانة والعمليات الجارية</span>
                            </h2>
                            <button class="btn btn-primary" onclick="openNewTicketModal()" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">+ تذكرة سريعة</button>
                        </div>

                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>رقم التذكرة</th>
                                        <th>العميل</th>
                                        <th>نوع الخدمة</th>
                                        <th>الأولوية</th>
                                        <th>الفني</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tickets as $ticket)
                                    <tr>
                                        <td>
                                            <span class="num" style="color: #38bdf8; font-weight: 700;">{{ $ticket->ticket_number }}</span>
                                            <div style="font-size: 0.7rem; color: var(--text-dim);" class="num">{{ $ticket->scheduled_date?->format('Y-m-d') }}</div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700;">{{ $ticket->customer?->name ?? 'غير محدد' }}</div>
                                            <div style="font-size: 0.75rem; color: var(--text-dim);" class="num">{{ $ticket->customer?->primary_phone }}</div>
                                        </td>
                                        <td>{{ $ticket->service_type }}</td>
                                        <td>
                                            @if($ticket->priority === 'urgent')
                                                <span class="badge badge-urgent">عاجل</span>
                                            @elseif($ticket->priority === 'high')
                                                <span class="badge badge-due-today">مرتفع</span>
                                            @else
                                                <span class="badge badge-upcoming">عادي</span>
                                            @endif
                                        </td>
                                        <td>{{ $ticket->technician?->name ?? 'غير مسندة' }}</td>
                                        <td>
                                            @if($ticket->status === 'in_progress')
                                                <span class="badge badge-in-progress">في الموقع</span>
                                            @elseif($ticket->status === 'assigned')
                                                <span class="badge badge-assigned">مسندة</span>
                                            @elseif($ticket->status === 'approved_closed')
                                                <span class="badge badge-active">مغلقة</span>
                                            @else
                                                <span class="badge badge-upcoming">{{ $ticket->status }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" style="text-align: center; color: var(--text-dim);">لا توجد تذاكر حالياً</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>

                    @if(!$isTechnician)
                    <!-- Side Feed -->
                    @if(!$isCallCenter)
                    <div class="glass table-card">
                        <h2 class="card-title" style="margin-bottom: 1rem;">
                            <span style="color: #10b981;">⚡</span>
                            <span>أحدث المعاملات المالية والمبيعات</span>
                        </h2>
                        <div>
                            @forelse($invoices->take(5) as $inv)
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.85rem;">{{ $inv->customer?->name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-dim);" class="num">{{ $inv->invoice_number }} | {{ $inv->created_at->format('Y-m-d') }}</div>
                                </div>
                                <div style="text-align: left;">
                                    <div class="num" style="font-weight: 800; color: #10b981;">{{ number_format($inv->grand_total, 2) }} ر.س</div>
                                    <a href="{{ route('invoices.print', $inv->id) }}" target="_blank" style="font-size: 0.7rem; color: #38bdf8; text-decoration: none;">🖨️ طباعة PDF</a>
                                </div>
                            </div>
                            @empty
                            <p style="color: var(--text-dim); font-size: 0.8rem;">لا توجد معاملات مسجلة</p>
                            @endforelse
                        </div>

                        <div style="margin-top: 1.5rem; text-align: center;">
                            <button class="btn btn-outline" onclick="switchNavTab('sales', document.querySelector('[onclick*=\"switchNavTab(\\\'sales\\\'\"]'))" style="width: 100%; justify-content: center;">
                                الانتقال لشاشة المبيعات الكاملة &larr;
                            </button>
                        </div>
                    </div>
                    @else
                    <div class="glass table-card">
                        <h2 class="card-title" style="margin-bottom: 1rem;">
                            <span style="color: #38bdf8;">📞</span>
                            <span>أحدث اتصالات ومتابعات خدمة العملاء</span>
                        </h2>
                        <div>
                            @forelse($interactions->take(5) as $interaction)
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.85rem;">{{ $interaction->customer?->name ?? 'عميل غير مسجل' }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-dim);">{{ Str::limit($interaction->notes ?? 'متابعة هاتفية دورية واستحقاقات فلاتر', 40) }}</div>
                                </div>
                                <div style="text-align: left;">
                                    <span class="badge badge-active" style="font-size: 0.7rem;">{{ $interaction->interaction_type ?? 'اتصال' }}</span>
                                    <div style="font-size: 0.7rem; color: var(--text-dim); margin-top: 0.2rem;" class="num">{{ $interaction->created_at?->format('Y-m-d') }}</div>
                                </div>
                            </div>
                            @empty
                            <p style="color: var(--text-dim); font-size: 0.8rem; text-align: center; padding: 1rem 0;">لا توجد سجلات تواصل مسجلة حديثاً</p>
                            @endforelse
                        </div>

                        <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
                            <button class="btn btn-primary" onclick="switchNavTab('appointments', document.querySelector('[onclick*=\"switchNavTab(\\\'appointments\\\'\"]'))" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #0284c7, #0369a1); font-weight: 700; padding: 0.6rem; font-size: 0.85rem;">
                                📅 + إضافة وحجز موعد لفني ميداني
                            </button>
                            <button class="btn btn-outline" onclick="switchNavTab('lifecycle', document.querySelector('[onclick*=\"switchNavTab(\\\'lifecycle\\\'\"]'))" style="width: 100%; justify-content: center; font-size: 0.8rem;">
                                متابعة استحقاقات الفلاتر &larr;
                            </button>
                        </div>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            @if(!$isTechnician)
            <!-- ================= TAB: APPOINTMENTS (إضافة وجدولة المواعيد للعملاء والفنيين) ================= -->
            <div id="tab-appointments" class="tab-pane">
                <div class="glass table-card" style="margin-bottom: 1.5rem;">
                    <div class="card-header-flex">
                        <div>
                            <h2 class="card-title">
                                <span style="color: #38bdf8;">📅</span>
                                <span>إضافة وجدولة المواعيد (حجز مواعيد العملاء للفنيين)</span>
                            </h2>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                حجز مواعيد الصيانة والزيارات الميدانية للعملاء وتعيين الفنيين مع تحديد التاريخ والفترة الزمنية
                            </p>
                        </div>

                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button class="btn btn-primary" onclick="openNewTicketModal()">
                                + حجز موعد جديد (نافذة سريعة)
                            </button>
                            <a href="{{ route('tickets.print-all') }}" target="_blank" class="btn btn-outline" style="font-size: 0.8rem;">
                                🖨️ طباعة كشف المواعيد PDF
                            </a>
                        </div>
                    </div>
                </div>

                <!-- شبكة الشاشة: نموذج إضافة الموعد المباشر + جدول المواعيد المجدولة للفنيين -->
                <div class="content-grid-2" style="align-items: start;">
                    
                    <!-- البطاقة الأولى: نموذج إضافة المواعيد المباشر -->
                    <div class="glass table-card" style="border-top: 3px solid #38bdf8;">
                        <h3 style="font-size: 1rem; font-weight: 700; color: #38bdf8; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>✍️</span>
                            <span>إضافة موعد جديد لعميل وفني</span>
                        </h3>

                        <form action="{{ route('tickets.quick-create') }}" method="POST">
                            @csrf
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="font-weight: 700; color: #38bdf8; display: block; margin-bottom: 0.35rem;">1. اختيار العميل المستفيد:</label>
                                <select name="customer_id" class="form-control" required onchange="filterCustomerDevicesInEmbedded(this.value)">
                                    <option value="">-- ابحث أو اختر العميل --</option>
                                    @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->primary_phone }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="font-weight: 700; display: block; margin-bottom: 0.35rem;">2. محطة التحلية المستهدفة (اختياري):</label>
                                <select name="customer_device_id" id="embeddedDeviceId" class="form-control">
                                    <option value="">-- كشف ومعاينة / بدون تحديد محطة --</option>
                                    @foreach($customers as $c)
                                        @foreach($c->devices as $d)
                                        <option value="{{ $d->id }}" data-customer-id="{{ $c->id }}">#{{ $d->device_code }} - {{ $d->model?->name }} ({{ $c->name }})</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="font-weight: 700; color: #10b981; display: block; margin-bottom: 0.35rem;">3. الفني الميداني المكلف بالموعد:</label>
                                <select name="technician_id" class="form-control" required>
                                    <option value="">-- اختر الفني الميداني المسؤول --</option>
                                    @foreach($technicians as $t)
                                    <option value="{{ $t->id }}">🚙 {{ $t->name }} ({{ $t->phone }})</option>
                                    @endforeach
                                </select>
                                <small style="font-size: 0.7rem; color: #10b981; display: block; margin-top: 0.25rem;">
                                    ✓ يُسند الموعد فوراً لتطبيق الفني في الميدان
                                </small>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-weight: 700; display: block; margin-bottom: 0.35rem;">تاريخ الموعد:</label>
                                    <input type="date" name="scheduled_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-weight: 700; display: block; margin-bottom: 0.35rem;">الفترة الزمنية:</label>
                                    <select name="time_slot" class="form-control">
                                        <option value="صباحاً (09:00 - 12:00)">صباحاً (09:00 - 12:00)</option>
                                        <option value="ظهراً (12:00 - 04:00)">ظهراً (12:00 - 04:00)</option>
                                        <option value="مساءً (04:00 - 08:00)">مساءً (04:00 - 08:00)</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1.3fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-weight: 700; display: block; margin-bottom: 0.35rem;">نوع الخدمة / الزيارة:</label>
                                    <select name="service_type" class="form-control" required>
                                        <option value="صيانة دورية وتغيير فلاتر">صيانة دورية وتغيير فلاتر</option>
                                        <option value="صيانة وإصلاح عطل">صيانة وإصلاح عطل</option>
                                        <option value="تركيب محطة جديدة">تركيب محطة جديدة</option>
                                        <option value="فحص جودة وأملاح TDS">فحص جودة وأملاح TDS</option>
                                        <option value="زيارة كشف ومعاينة">زيارة كشف ومعاينة</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-weight: 700; display: block; margin-bottom: 0.35rem;">الأولوية:</label>
                                    <select name="priority" class="form-control" required>
                                        <option value="normal">عادي</option>
                                        <option value="high">مرتفع</option>
                                        <option value="urgent">عاجل وطارئ</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label style="font-weight: 700; display: block; margin-bottom: 0.35rem;">ملاحظات وتعليمات الموعد:</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات العميل، موقع البيت، الشمعات المطلوبة..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #0284c7, #0369a1); font-weight: 700; padding: 0.75rem; font-size: 0.95rem;">
                                📅 تأكيد وإضافة الموعد للفني فوراً
                            </button>
                        </form>
                    </div>

                    <!-- البطاقة الثانية: جدول مواعيد العملاء والفنيين -->
                    <div class="glass table-card">
                        <div class="card-header-flex" style="margin-bottom: 1rem;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 0.5rem;">
                                <span>📋</span>
                                <span>جدول المواعيد المجدولة للفنيين</span>
                                <span class="badge badge-active" style="font-size: 0.75rem;">{{ count($tickets->whereNotNull('scheduled_date')) }} موعد مسجل</span>
                            </h3>
                        </div>

                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>رقم الموعد</th>
                                        <th>العميل المستفيد</th>
                                        <th>الفني المكلف</th>
                                        <th>تاريخ الموعد والفترة</th>
                                        <th>نوع الخدمة</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tickets as $appt)
                                    <tr>
                                        <td class="num" style="font-weight: 700; color: #38bdf8;">
                                            {{ $appt->ticket_number }}
                                        </td>
                                        <td>
                                            <div style="font-weight: 700;">{{ $appt->customer?->name ?? 'عميل غير مسجل' }}</div>
                                            <div class="num" style="font-size: 0.75rem; color: var(--text-dim);">{{ $appt->customer?->primary_phone }}</div>
                                        </td>
                                        <td>
                                            @if($appt->technician)
                                            <span style="font-weight: 700; color: #10b981;">🚙 {{ $appt->technician->name }}</span>
                                            @else
                                            <span style="color: #f59e0b;">⚠️ بانتظار إسناد فني</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($appt->scheduled_date)
                                            <div class="num" style="font-weight: 700;">{{ $appt->scheduled_date->format('Y-m-d') }}</div>
                                            <div style="font-size: 0.75rem; color: var(--text-dim);">{{ $appt->time_slot ?? 'خلال اليوم' }}</div>
                                            @else
                                            <span style="color: var(--text-dim);">غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span style="font-size: 0.85rem;">{{ $appt->service_type }}</span>
                                            @if($appt->priority === 'urgent')
                                            <span class="badge badge-overdue" style="font-size: 0.65rem; margin-right: 0.2rem;">عاجل</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($appt->status === 'assigned')
                                                <span class="badge badge-assigned">مسند للفني</span>
                                            @elseif($appt->status === 'in_progress')
                                                <span class="badge badge-in-progress">الفني في الموقع</span>
                                            @elseif($appt->status === 'completed_pending_review' || $appt->status === 'approved_closed')
                                                <span class="badge badge-active">تم الإنجاز</span>
                                            @else
                                                <span class="badge badge-upcoming">{{ $appt->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.35rem; align-items: center;">
                                                <a href="{{ route('tickets.print', $appt->id) }}" target="_blank" class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" title="طباعة أمر العمل والموعد">
                                                    🖨️
                                                </a>
                                                <button type="button" class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; color: #38bdf8;" onclick="openEditTicketModal({{ $appt->id }}, '{{ addslashes($appt->customer?->name ?? '') }}', '{{ addslashes($appt->notes ?? '') }}', '{{ $appt->priority }}', '{{ $appt->service_type }}', '{{ $appt->scheduled_date?->format('Y-m-d') ?? '' }}', '{{ addslashes($appt->time_slot ?? '') }}')" title="تعديل الموعد">
                                                    ✏️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-dim); padding: 2rem;">
                                            لا توجد مواعيد مسجلة حالياً. استخدم النموذج على اليمين لإضافة موعد جديد.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 2: LIFECYCLE (استحقاقات الفلاتر والمواعيد) ================= -->
            <div id="tab-lifecycle" class="tab-pane">
                <div class="glass table-card">
                    <div class="card-header-flex">
                        <div>
                            <h2 class="card-title">
                                <span style="color: #38bdf8;">💧</span>
                                <span>محرك أعمار الفلاتر وجدول الاستحقاقات الدورية (Lifecycle Monitor)</span>
                            </h2>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                حساب ذكي لمواعيد استبدال الشمعات والممبرين بحسب جدول الاستهلاك والأيام المقدرة
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button class="btn btn-primary" onclick="openNewStageModal()">
                                + جدولة استحقاق / مرحلة جديدة
                            </button>
                            <form action="{{ route('reminders.run') }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn btn-outline" style="font-size: 0.8rem;">
                                    🔄 فحص وإرسال التذكيرات
                                </button>
                            </form>
                            <a href="{{ route('lifecycle.print') }}" target="_blank" class="btn btn-outline" style="font-size: 0.8rem;">
                                🖨️ طباعة جدول الاستحقاقات PDF
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>العميل المستفيد</th>
                                    <th>محطة التحلية</th>
                                    <th>رقم المرحلة والشمعة</th>
                                    <th>آخر استبدال</th>
                                    <th>الاستحقاق القادم</th>
                                    <th>الحالة الزمنية</th>
                                    <th>العمليات التشغيلية</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dueStages as $stage)
                                <tr>
                                    <td>
                                        <div style="font-weight: 700;">{{ $stage->device?->customer?->name ?? 'غير محدد' }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-dim);" class="num">{{ $stage->device?->customer?->primary_phone }}</div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;">{{ $stage->device?->model?->name ?? 'محطة تحلية' }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-dim);" class="num">SN: {{ $stage->device?->serial_number ?? $stage->device?->device_code }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-assigned num">مرحلة {{ $stage->stage_number }}</span>
                                        <span style="font-weight: 600; margin-right: 0.35rem;">{{ $stage->stage_name_ar }}</span>
                                        <div style="font-size: 0.7rem; color: var(--text-dim);">كل {{ $stage->service_interval_days }} يوم</div>
                                    </td>
                                    <td class="num" style="color: var(--text-muted);">{{ $stage->last_actual_replacement_at?->format('Y-m-d') ?? 'تاريخ التركيب' }}</td>
                                    <td class="num" style="font-weight: 700; color: #38bdf8;">{{ $stage->next_due_at?->format('Y-m-d') }}</td>
                                    <td>
                                        @if($stage->isOverdue())
                                            <span class="badge badge-overdue">متأخرة عن الموعد</span>
                                        @elseif($stage->isDueToday())
                                            <span class="badge badge-due-today">مستحقة اليوم</span>
                                        @else
                                            <span class="badge badge-upcoming">قادمة قريباً</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                            <!-- استبدال فوري -->
                                            <form action="{{ route('lifecycle.stages.replace', $stage->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('تأكيد تسجيل استبدال الشمعة اليوم وتحديث الموعد القادم تلقائياً؟');">
                                                @csrf
                                                <button type="submit" class="btn btn-primary" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; background: #10b981;" title="تسجيل استبدال فوري اليوم">
                                                    ⚡ تم الاستبدال
                                                </button>
                                            </form>

                                            <!-- فتح تذكرة صيانة -->
                                            <button class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" onclick="openCreateTicketForStage({{ $stage->device?->customer_id }}, {{ $stage->customer_device_id }}, '{{ addslashes($stage->stage_name_ar) }}')" title="جدولة تذكرة صيانة">
                                                📋 تذكرة
                                            </button>

                                            <!-- تعديل الموعد -->
                                            <button class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" onclick="openEditStageModal({{ $stage->id }}, '{{ addslashes($stage->stage_name_ar) }}', {{ $stage->service_interval_days }}, '{{ $stage->next_due_at?->format('Y-m-d') }}')" title="تعديل موعد الاستحقاق">
                                                📅 تعديل
                                            </button>

                                            <!-- حذف -->
                                            <form action="{{ route('lifecycle.stages.delete', $stage->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('هل أنت متأكد من حذف هذه المرحلة؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline" style="padding: 0.2rem 0.4rem; font-size: 0.75rem; color: #ef4444; border-color: rgba(239,68,68,0.3);" title="حذف المرحلة">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2.5rem 1rem; color: var(--text-dim);">
                                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">💧</div>
                                        <div style="font-weight: 700; font-size: 1rem; color: var(--text-main); margin-bottom: 0.25rem;">لا توجد استحقاقات فلاتر مجدولة حالياً</div>
                                        <div style="font-size: 0.85rem; margin-bottom: 1.25rem;">يمكنك إضافة محطة تحلية للعميل أو جدولة مراحل الفلاتر يدوياً الآن</div>
                                        <button class="btn btn-primary" onclick="openNewDeviceModal()">+ إضافة محطة تحلية للعميل وتوليد الفلاتر تلقائياً</button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 3: TICKETS (تذاكر الصيانة والعمليات) ================= -->
            <div id="tab-tickets" class="tab-pane">
                <div class="glass table-card">
                    <div class="card-header-flex">
                        <div>
                            <h2 class="card-title"><span style="color: #38bdf8;">📋</span><span>جدول تذاكر الصيانة والعمليات الميدانية</span></h2>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">تتبع مسار التذاكر من البلاغ وحتى الإغلاق المعتمد وطباعة أوامر العمل</p>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button class="btn btn-primary" onclick="openNewTicketModal()">+ فتح تذكرة جديدة</button>
                            <a href="{{ route('tickets.print-all') }}" target="_blank" class="btn btn-outline" style="font-size: 0.8rem;">
                                🖨️ طباعة كشف التذاكر PDF
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>رقم التذكرة</th>
                                    <th>العميل المستفيد</th>
                                    <th>نوع الخدمة</th>
                                    <th>الأولوية</th>
                                    <th>الموعد والفترة</th>
                                    <th>الفني المكلف</th>
                                    <th>الحالة</th>
                                    <th>العمليات والإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $t)
                                <tr>
                                    <td>
                                        <div class="num" style="color: #38bdf8; font-weight: 700;">{{ $t->ticket_number }}</div>
                                        <div style="font-size: 0.7rem; color: var(--text-dim);" class="num">{{ $t->created_at->format('Y-m-d') }}</div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700;">{{ $t->customer?->name ?? 'غير محدد' }}</div>
                                        <div class="num" style="font-size: 0.75rem; color: var(--text-dim);">{{ $t->customer?->primary_phone }}</div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600;">{{ $t->service_type }}</span>
                                    </td>
                                    <td>
                                        @if($t->priority === 'urgent')
                                            <span class="badge badge-urgent">عاجل وطارئ</span>
                                        @elseif($t->priority === 'high')
                                            <span class="badge badge-due-today">مرتفع</span>
                                        @else
                                            <span class="badge badge-upcoming">عادي</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="num">{{ $t->scheduled_date?->format('Y-m-d') ?? 'اليوم' }}</div>
                                        <div style="font-size: 0.7rem; color: var(--text-dim);">{{ $t->time_slot ?? 'صباحاً' }}</div>
                                    </td>
                                    <td>
                                        @if($t->technician)
                                            <span style="font-weight: 600; color: #38bdf8;">{{ $t->technician->name }}</span>
                                        @else
                                            <span style="color: var(--text-dim); font-size: 0.8rem;">لم يُسند بعد</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('tickets.status', $t->id) }}" method="POST" style="margin: 0;">
                                            @csrf
                                            <select name="status" class="form-control" style="padding: 0.2rem 0.4rem; font-size: 0.75rem; width: auto;" onchange="this.form.submit()">
                                                <option value="new" {{ $t->status === 'new' ? 'selected' : '' }}>جديدة (بلاغ)</option>
                                                <option value="assigned" {{ $t->status === 'assigned' ? 'selected' : '' }}>مسندة لفني</option>
                                                <option value="in_progress" {{ $t->status === 'in_progress' ? 'selected' : '' }}>قيد التنفيذ بالموقع</option>
                                                <option value="completed_pending_review" {{ $t->status === 'completed_pending_review' ? 'selected' : '' }}>منجزة وبانتظار الاعتماد</option>
                                                <option value="approved_closed" {{ $t->status === 'approved_closed' ? 'selected' : '' }}>مغلقة ومعتمدة</option>
                                                <option value="cancelled" {{ $t->status === 'cancelled' ? 'selected' : '' }}>ملغية</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                            <!-- طباعة أمر عمل PDF -->
                                            <a href="{{ route('tickets.print', $t->id) }}" target="_blank" class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" title="طباعة أمر العمل كـ PDF">
                                                🖨️ طباعة
                                            </a>

                                            <!-- إسناد لفني -->
                                            <button class="btn btn-primary" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" onclick="openAssignModal({{ $t->id }}, '{{ $t->ticket_number }}')" title="تعيين أو تحويل لفني">
                                                👤 إسناد
                                            </button>

                                            <!-- تعديل التذكرة -->
                                            <button class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" onclick="openEditTicketModal({{ $t->id }}, '{{ $t->ticket_number }}', '{{ $t->service_type }}', '{{ $t->priority }}', '{{ $t->status }}', '{{ $t->technician_id }}', '{{ $t->scheduled_date?->format('Y-m-d') }}', '{{ addslashes($t->notes ?? '') }}')" title="تعديل بيانات التذكرة">
                                                ✏️ تعديل
                                            </button>

                                            <!-- حذف التذكرة -->
                                            <form action="{{ route('tickets.delete', $t->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('هل أنت متأكد من حذف التذكرة رقم {{ $t->ticket_number }}؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline" style="padding: 0.2rem 0.4rem; font-size: 0.75rem; color: #ef4444; border-color: rgba(239,68,68,0.3);" title="حذف التذكرة">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2.5rem 1rem; color: var(--text-dim);">
                                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📋</div>
                                        <div style="font-weight: 700; font-size: 1rem; color: var(--text-main); margin-bottom: 0.25rem;">لا توجد تذاكر صيانة حالياً</div>
                                        <div style="font-size: 0.85rem; margin-bottom: 1.25rem;">يمكنك فتح تذكرة جديدة لأي عميل أو محطة في أي وقت</div>
                                        <button class="btn btn-primary" onclick="openNewTicketModal()">+ فتح تذكرة صيانة جديدة</button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 4: CUSTOMERS (العملاء ومحطات التحلية) ================= -->
            <div id="tab-customers" class="tab-pane">
                <div class="glass table-card">
                    <div class="card-header-flex">
                        <div>
                            <h2 class="card-title"><span style="color: #38bdf8;">👥</span><span>ملفات العملاء ومحطات التحلية المركبة</span></h2>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">بيانات العملاء، العناوين الجغرافية، وسجل الأجهزة والمحطات</p>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button class="btn btn-primary" onclick="openNewCustomerModal()">
                                + إضافة عميل جديد
                            </button>
                            <button class="btn btn-outline" style="border-color: #38bdf8; color: #38bdf8;" onclick="openNewDeviceModal()">
                                + إضافة محطة تحلية / جهاز للعميل
                            </button>
                            <a href="{{ route('customers.print-all') }}" target="_blank" class="btn btn-outline" style="font-size: 0.8rem;">
                                🖨️ طباعة كشف العملاء والمحطات PDF
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>كود العميل</th>
                                    <th>الاسم</th>
                                    <th>رقم الجوال</th>
                                    <th>المدينة والحي</th>
                                    <th>المحطات المركبة</th>
                                    <th>الحالة</th>
                                    <th>العمليات والإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $c)
                                <tr>
                                    <td class="num" style="color: #38bdf8; font-weight: 700;">{{ $c->customer_code }}</td>
                                    <td>
                                        <div style="font-weight: 700; font-size: 0.95rem;">{{ $c->name }}</div>
                                        @if($c->notes)
                                        <div style="font-size: 0.72rem; color: var(--text-dim);">{{ Str::limit($c->notes, 35) }}</div>
                                        @endif
                                    </td>
                                    <td class="num">{{ $c->primary_phone }}</td>
                                    <td>
                                        <span>{{ $c->addresses->first()?->city ?? 'الرياض' }}</span>
                                        @if($c->addresses->first()?->district)
                                        <span style="font-size: 0.75rem; color: var(--text-dim);"> - {{ $c->addresses->first()?->district }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline" style="padding: 0.2rem 0.6rem; font-size: 0.75rem; color: #10b981; border-color: rgba(16,185,129,0.3);" onclick="toggleCustomerDevicesSection({{ $c->id }})">
                                            🔍 {{ $c->devices->count() }} محطة تحلية
                                        </button>
                                    </td>
                                    <td><span class="badge badge-active">{{ $c->is_active ? 'نشط' : 'معطل' }}</span></td>
                                    <td>
                                        <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                            <!-- إضافة جهاز لهذا العميل -->
                                            <button class="btn btn-primary" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" onclick="openNewDeviceModalForCustomer({{ $c->id }}, '{{ addslashes($c->name) }}')" title="إضافة جهاز تحلية جديد لهذا العميل">
                                                + جهاز
                                            </button>

                                            <!-- طباعة بطاقة العميل PDF -->
                                            <a href="{{ route('customers.print', $c->id) }}" target="_blank" class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" title="طباعة بطاقة العميل وسجل محطاته">
                                                🖨️ بطاقة
                                            </a>

                                            <!-- حجز موعد لفني -->
                                            <button type="button" class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; color: #38bdf8; border-color: rgba(56,189,248,0.4);" onclick="openNewTicketModalForCustomer({{ $c->id }}, '{{ addslashes($c->name) }}')" title="حجز موعد زيارة فني لهذا العميل">
                                                📅 موعد
                                            </button>

                                            <!-- تعديل بيانات العميل -->
                                            <button class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" onclick="openEditCustomerModal({{ $c->id }}, '{{ addslashes($c->name) }}', '{{ $c->primary_phone }}', '{{ $c->secondary_phone ?? '' }}', '{{ addslashes($c->addresses->first()?->city ?? 'الرياض') }}', '{{ addslashes($c->addresses->first()?->district ?? '') }}', '{{ addslashes($c->addresses->first()?->street ?? '') }}', '{{ addslashes($c->notes ?? '') }}')" title="تعديل العميل">
                                                ✏️ تعديل
                                            </button>

                                            @if(!$isCallCenter)
                                            <!-- حذف العميل (محظور على خدمة العملاء) -->
                                            <form action="{{ route('customers.delete', $c->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('هل أنت متأكد من حذف العميل ({{ $c->name }}) وكافة أجهزته وسجلاته؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline" style="padding: 0.2rem 0.4rem; font-size: 0.75rem; color: #ef4444; border-color: rgba(239,68,68,0.3);" title="حذف العميل">
                                                    🗑️
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                <!-- صف تفصيلي لأجهزة ومحطات هذا العميل (قابل للإظهار والإخفاء) -->
                                <tr id="custDevicesRow_{{ $c->id }}" style="display: none; background: rgba(255,255,255,0.02);">
                                    <td colspan="7" style="padding: 1rem 1.5rem;">
                                        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 1rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 0.5rem;">
                                                <strong style="color: #38bdf8;">💧 محطات التحلية المركبة للعميل ({{ $c->name }}):</strong>
                                                <button class="btn btn-primary" style="padding: 0.2rem 0.6rem; font-size: 0.75rem;" onclick="openNewDeviceModalForCustomer({{ $c->id }}, '{{ addslashes($c->name) }}')">
                                                    + إضافة محطة جديدة لهذا العميل
                                                </button>
                                            </div>

                                            @forelse($c->devices as $dev)
                                            <div style="border: 1px solid rgba(255,255,255,0.06); border-radius: 6px; padding: 0.75rem; margin-bottom: 0.75rem; background: rgba(255,255,255,0.02);">
                                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                                    <div>
                                                        <span class="num" style="font-weight: 700; color: #10b981;">#{{ $dev->device_code }}</span>
                                                        <span style="font-weight: 700; margin-right: 0.5rem;">{{ $dev->model?->name ?? 'محطة تحلية' }}</span>
                                                        <span class="num" style="color: var(--text-dim); font-size: 0.8rem;">(SN: {{ $dev->serial_number ?? 'غير مسجل' }})</span>
                                                    </div>
                                                    <div style="font-size: 0.8rem; color: var(--text-dim);">
                                                        <span>تاريخ التركيب: <strong class="num">{{ $dev->installation_date?->format('Y-m-d') ?? '—' }}</strong></span>
                                                        <span style="margin: 0 0.5rem;">|</span>
                                                        <span>نهاية الضمان: <strong class="num">{{ $dev->warranty_expiry_date?->format('Y-m-d') ?? '—' }}</strong></span>
                                                    </div>
                                                    <div style="display: flex; gap: 0.35rem;">
                                                        <!-- إضافة مرحلة شمعة لهذا الجهاز -->
                                                        <button class="btn btn-outline" style="padding: 0.2rem 0.45rem; font-size: 0.75rem;" onclick="openNewStageModalForDevice({{ $dev->id }}, '{{ $dev->device_code }}', '{{ addslashes($dev->model?->name ?? 'محطة تحلية') }}')" title="إضافة مرحلة شمعة جديدة">
                                                            + مرحلة
                                                        </button>

                                                        <!-- تعديل الجهاز -->
                                                        <button class="btn btn-outline" style="padding: 0.2rem 0.45rem; font-size: 0.75rem;" onclick="openEditDeviceModal({{ $dev->id }}, '{{ $dev->device_code }}', '{{ $dev->serial_number }}', '{{ $dev->installation_date?->format('Y-m-d') }}', '{{ $dev->warranty_expiry_date?->format('Y-m-d') }}', '{{ $dev->status }}', '{{ addslashes($dev->notes ?? '') }}')" title="تعديل بيانات المحطة">
                                                            ✏️
                                                        </button>

                                                        <!-- حذف الجهاز -->
                                                        <form action="{{ route('devices.delete', $dev->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('تأكيد حذف محطة التحلية ({{ $dev->device_code }}) وكافة مراحل الفلاتر المرتبطة بها؟');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline" style="padding: 0.2rem 0.4rem; font-size: 0.75rem; color: #ef4444; border-color: rgba(239,68,68,0.3);" title="حذف المحطة">
                                                                🗑️
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>

                                                <!-- قائمة مراحل هذا الجهاز -->
                                                <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    @forelse($dev->stages as $st)
                                                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 4px; padding: 0.35rem 0.6rem; font-size: 0.75rem;">
                                                        <span style="color: #38bdf8; font-weight: 700;">م{{ $st->stage_number }}:</span>
                                                        <span>{{ $st->stage_name_ar }}</span>
                                                        <span class="num" style="color: var(--text-dim); margin-right: 0.35rem;">({{ $st->next_due_at?->format('m/d') }})</span>
                                                    </div>
                                                    @empty
                                                    <div style="font-size: 0.75rem; color: var(--text-dim);">لا توجد مراحل مسجلة. يمكنك الضغط على "+ مرحلة" لإضافة شمعة.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                            @empty
                                            <div style="text-align: center; color: var(--text-dim); padding: 1rem; font-size: 0.85rem;">
                                                لا توجد محطات تحلية مركبة لهذا العميل حتى الآن. اضغط "+ إضافة محطة جديدة" لإضافة أول جهاز وربطه به.
                                            </div>
                                            @endforelse
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2.5rem 1rem; color: var(--text-dim);">
                                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">👥</div>
                                        <div style="font-weight: 700; font-size: 1rem; color: var(--text-main); margin-bottom: 0.25rem;">لا يوجد عملاء مسجلون حالياً</div>
                                        <div style="font-size: 0.85rem; margin-bottom: 1.25rem;">ابدأ بإضافة أول عميل ومحطة تحلية في النظام</div>
                                        <button class="btn btn-primary" onclick="openNewCustomerModal()">+ إضافة عميل جديد الآن</button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @if(!$isCallCenter && !$isTechnician)
            <!-- ================= TAB 5: SALES SCREEN (شاشة المبيعات الجديدة) ================= -->
            <div id="tab-sales" class="tab-pane">
                <div class="card-header-flex">
                    <div>
                        <h2 class="card-title">
                            <span style="color: #10b981;">🛒</span>
                            <span>شاشة المبيعات ونقاط البيع والفواتير الضريبية (Sales & POS Module)</span>
                        </h2>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            إصدار فواتير بيع معتمدة، نقاط بيع سريعة (POS)، خصم مخزني لحظي، وطباعة فواتير PDF ضريبية رسمية
                        </p>
                    </div>

                    <div style="display: flex; gap: 0.75rem;">
                        <button class="btn btn-success" onclick="openNewSaleModal()">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>+ إنشاء فاتورة بيع جديدة / نقطة بيع POS</span>
                        </button>
                    </div>
                </div>

                <!-- Sub Navigation Pills for Sales -->
                <div class="sub-nav-pills">
                    <button class="sub-pill active" onclick="switchSalesSubTab('invoices', this)">الفواتير الضريبية الصادرة ({{ count($invoices) }})</button>
                    <button class="sub-pill" onclick="switchSalesSubTab('orders', this)">أوامر البيع وعروض الأسعار ({{ count($salesOrders) }})</button>
                </div>

                <!-- SUB-PANE 1: TAX INVOICES -->
                <div id="sales-sub-invoices" class="glass table-card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th># رقم الفاتورة</th>
                                    <th>العميل</th>
                                    <th>التاريخ</th>
                                    <th>المستودع المصروف منه</th>
                                    <th>الإجمالي الصافي</th>
                                    <th>المدفوع</th>
                                    <th>المتبقي</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات والطباعة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $inv)
                                <tr>
                                    <td class="num" style="color: #38bdf8; font-weight: 800;">{{ $inv->invoice_number }}</td>
                                    <td>
                                        <div style="font-weight: 700;">{{ $inv->customer?->name }}</div>
                                        <div class="num" style="font-size: 0.75rem; color: var(--text-dim);">{{ $inv->customer?->primary_phone }}</div>
                                    </td>
                                    <td class="num">{{ $inv->invoice_date?->format('Y-m-d') ?? $inv->created_at->format('Y-m-d') }}</td>
                                    <td>{{ $inv->warehouse?->name ?? 'المستودع الرئيسي' }}</td>
                                    <td class="num" style="font-weight: 800; color: #ffffff;">{{ number_format($inv->grand_total, 2) }} ر.س</td>
                                    <td class="num" style="color: #34d399; font-weight: 700;">{{ number_format($inv->paid_amount, 2) }} ر.س</td>
                                    <td class="num" style="color: {{ $inv->balance_due > 0 ? '#fb7185' : 'var(--text-dim)' }}; font-weight: 700;">
                                        {{ number_format($inv->balance_due, 2) }} ر.س
                                    </td>
                                    <td>
                                        @if($inv->status === 'paid' || $inv->balance_due <= 0.001)
                                            <span class="badge badge-active">مدفوعة بالكامل</span>
                                        @elseif($inv->status === 'partially_paid')
                                            <span class="badge badge-upcoming">مدفوعة جزئياً</span>
                                        @else
                                            <span class="badge badge-due-today">معلقة / آجل</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.4rem; align-items: center;">
                                            <a href="{{ route('invoices.print', $inv->id) }}" target="_blank" class="btn btn-outline" style="padding: 0.3rem 0.65rem; font-size: 0.75rem; color: #38bdf8;" title="طباعة الفاتورة كـ PDF">
                                                🖨️ طباعة PDF
                                            </a>
                                            @if($inv->balance_due > 0.01)
                                            <button class="btn btn-success" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;" onclick="openPaymentModal({{ $inv->id }}, '{{ $inv->invoice_number }}', {{ $inv->balance_due }})">
                                                💵 تحصيل دفعة
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" style="text-align: center; color: var(--text-dim); padding: 30px;">لا توجد فواتير مبيعات مسجلة حتى الآن. انقر على (+ إنشاء فاتورة بيع جديدة) للبدء.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SUB-PANE 2: SALES ORDERS -->
                <div id="sales-sub-orders" class="glass table-card" style="display: none;">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th># رقم أمر البيع</th>
                                    <th>العميل</th>
                                    <th>تاريخ الطلب</th>
                                    <th>المستودع المخصص</th>
                                    <th>عدد الأصناف</th>
                                    <th>المبلغ الإجمالي</th>
                                    <th>الحالة</th>
                                    <th>المحرر</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesOrders as $so)
                                <tr>
                                    <td class="num" style="color: #38bdf8; font-weight: 800;">{{ $so->order_number }}</td>
                                    <td style="font-weight: 700;">{{ $so->customer?->name }}</td>
                                    <td class="num">{{ $so->order_date?->format('Y-m-d') ?? $so->created_at->format('Y-m-d') }}</td>
                                    <td>{{ $so->warehouse?->name }}</td>
                                    <td class="num">{{ $so->items->count() }} صنف</td>
                                    <td class="num" style="font-weight: 800; color: #10b981;">{{ number_format($so->grand_total, 2) }} ر.س</td>
                                    <td>
                                        @if($so->status === 'confirmed')
                                            <span class="badge badge-active">مؤكد</span>
                                        @elseif($so->status === 'invoiced')
                                            <span class="badge badge-assigned">تمت فوترته</span>
                                        @else
                                            <span class="badge badge-upcoming">مسودة</span>
                                        @endif
                                    </td>
                                    <td>{{ $so->creator?->name ?? 'مندوب المبيعات' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="8" style="text-align: center; color: var(--text-dim); padding: 30px;">لا توجد أوامر بيع مسجلة حالياً.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 6: SUPPLIERS SCREEN (شاشة الموردين الجديدة) ================= -->
            <div id="tab-suppliers" class="tab-pane">
                <div class="card-header-flex">
                    <div>
                        <h2 class="card-title">
                            <span style="color: #0284c7;">🏢</span>
                            <span>شاشة الموردين وإدارة المشتريات والتوريد (Suppliers & Procurement ERP)</span>
                        </h2>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            دليل الموردين، إصدار أوامر الشراء الرسمية (PO)، اعتمادات التوريد، والاستلام المخزني الفوري
                        </p>
                    </div>

                    <div style="display: flex; gap: 0.75rem;">
                        <button class="btn btn-outline" onclick="openNewSupplierModal()">
                            + إضافة مورد جديد
                        </button>
                        <button class="btn btn-primary" onclick="openNewPurchaseOrderModal()">
                            + إضافة مشتريات (أمر شراء)
                        </button>
                    </div>
                </div>

                <!-- Sub Navigation Pills for Suppliers -->
                <div class="sub-nav-pills">
                    <button class="sub-pill active" onclick="switchSuppliersSubTab('directory', this)">دليل الموردين وشركاء التوريد ({{ count($suppliers) }})</button>
                    <button class="sub-pill" onclick="switchSuppliersSubTab('orders', this)">أوامر الشراء والتوريد ({{ count($purchaseOrders) }})</button>
                </div>

                <!-- SUB-PANE 1: SUPPLIERS DIRECTORY -->
                <div id="suppliers-sub-directory" class="glass table-card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>كود المورد</th>
                                    <th>اسم الشركة / المورد</th>
                                    <th>المسؤول</th>
                                    <th>رقم الجوال</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الرقم الضريبي</th>
                                    <th>أوامر الشراء</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suppliers as $s)
                                <tr>
                                    <td class="num" style="color: #38bdf8; font-weight: 800;">{{ $s->supplier_code }}</td>
                                    <td>
                                        <div style="font-weight: 700; color: #ffffff;">{{ $s->name }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-dim);">{{ $s->address ?? 'المملكة العربية السعودية' }}</div>
                                    </td>
                                    <td>{{ $s->contact_person ?? '—' }}</td>
                                    <td class="num">{{ $s->phone }}</td>
                                    <td class="num" style="color: var(--text-muted);">{{ $s->email ?? '—' }}</td>
                                    <td class="num">{{ $s->tax_number ?? 'غير مسجل' }}</td>
                                    <td class="num" style="color: #38bdf8; font-weight: 700;">{{ $s->purchase_orders_count }} أوامر</td>
                                    <td>
                                        @if($s->is_active)
                                            <span class="badge badge-active">نشط بالخدمة</span>
                                        @else
                                            <span class="badge badge-overdue">مجمّد</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.4rem;">
                                            <button class="btn btn-outline" style="padding: 0.25rem 0.65rem; font-size: 0.75rem;" onclick="openNewPurchaseOrderForSupplier({{ $s->id }})">
                                                + طلب شراء
                                            </button>
                                            <form action="{{ route('suppliers.toggle', $s->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: {{ $s->is_active ? '#fb7185' : '#34d399' }};">
                                                    {{ $s->is_active ? 'تجميد' : 'تفعيل' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" style="text-align: center; color: var(--text-dim); padding: 30px;">لا يوجد موردون مسجلون. اضغط على (+ إضافة مورد جديد) لإضافة أول مورد.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SUB-PANE 2: PURCHASE ORDERS -->
                <div id="suppliers-sub-orders" class="glass table-card" style="display: none;">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th># رقم أمر الشراء</th>
                                    <th>المورد</th>
                                    <th>التاريخ</th>
                                    <th>المستودع المستلم</th>
                                    <th>عدد الأصناف</th>
                                    <th>القيمة الإجمالية</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات والطباعة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseOrders as $po)
                                <tr>
                                    <td class="num" style="color: #38bdf8; font-weight: 800;">{{ $po->po_number }}</td>
                                    <td style="font-weight: 700;">{{ $po->supplier?->name }}</td>
                                    <td class="num">{{ $po->order_date?->format('Y-m-d') ?? $po->created_at->format('Y-m-d') }}</td>
                                    <td>{{ $po->warehouse?->name }}</td>
                                    <td class="num">{{ $po->items->count() }} صنف</td>
                                    <td class="num" style="font-weight: 800; color: #10b981;">{{ number_format($po->total_amount, 2) }} ر.س</td>
                                    <td>
                                        @if($po->status === 'received')
                                            <span class="badge badge-active">مستلم بالمستودع</span>
                                        @elseif($po->status === 'approved')
                                            <span class="badge badge-assigned">معتمد للتوريد</span>
                                        @elseif($po->status === 'draft')
                                            <span class="badge badge-upcoming">مسودة قيد الاعتماد</span>
                                        @else
                                            <span class="badge badge-overdue">{{ $po->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.4rem; align-items: center;">
                                            <a href="{{ route('procurement.orders.print', $po->id) }}" target="_blank" class="btn btn-outline" style="padding: 0.3rem 0.65rem; font-size: 0.75rem; color: #38bdf8;" title="طباعة أمر الشراء كـ PDF">
                                                🖨️ طباعة PDF
                                            </a>

                                            @if($po->status === 'draft' && $isSuperAdmin)
                                            <form action="{{ route('procurement.orders.approve', $po->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-primary" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;">
                                                    ✅ اعتماد
                                                </button>
                                            </form>
                                            @endif

                                            @if($po->status === 'approved')
                                            <form action="{{ route('procurement.orders.receive', $po->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-success" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;">
                                                    📥 استلام بالمستودع
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="8" style="text-align: center; color: var(--text-dim); padding: 30px;">لا توجد أوامر شراء مسجلة حالياً.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 7: INVENTORY ================= -->
            <div id="tab-inventory" class="tab-pane">
                <div class="glass table-card">
                    <div class="card-header-flex">
                        <div>
                            <h2 class="card-title"><span style="color: #38bdf8;">📦</span><span>المخزون المركزي وسيارات الصيانة المتنقلة</span></h2>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">تتبع كميات الأجهزة والشمعات وقطع الغيار في كل مستودع وسيارة فني</p>
                        </div>

                        @if($isSuperAdmin || $isAccountant || $isWarehouse)
                        <div>
                            <button class="btn btn-primary" onclick="openStockAuditModal()" style="display: flex; align-items: center; gap: 0.4rem; background: linear-gradient(135deg, #0284c7, #0369a1);">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                <span>📋 إجراء جرد مخزني وتسوية</span>
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>كود الصنف</th>
                                    <th>المنتج / القطعة</th>
                                    <th>النوع والتصنيف</th>
                                    <th>سعر التكلفة</th>
                                    <th>سعر البيع</th>
                                    <th>حد الطلب</th>
                                    <th>الرصيد الكلي المتاح</th>
                                    <th>الحالة المخزنية</th>
                                    <th>إجراءات الجرد</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $prod)
                                <tr>
                                    <td class="num" style="color: #38bdf8; font-weight: 700;">{{ $prod->sku }}</td>
                                    <td>
                                        <div style="font-weight: 700;">{{ $prod->name_ar }}</div>
                                        <div style="font-size: 0.725rem; color: var(--text-dim);">{{ $prod->category?->name_ar }}</div>
                                    </td>
                                    <td><span class="badge badge-assigned">{{ $prod->type }}</span></td>
                                    <td class="num">{{ number_format($prod->cost_price, 2) }} ر.س</td>
                                    <td class="num" style="color: #10b981; font-weight: 700;">{{ number_format($prod->selling_price, 2) }} ر.س</td>
                                    <td class="num">{{ $prod->reorder_level }} {{ $prod->unit ?? 'قطعة' }}</td>
                                    <td class="num" style="font-size: 1.05rem; font-weight: 800; color: #ffffff;">
                                        {{ $prod->stockBalances->sum('quantity') }}
                                    </td>
                                    <td>
                                        @if($prod->stockBalances->sum('quantity') <= $prod->reorder_level && $prod->type !== 'service')
                                            <span class="badge badge-due-today">مخزون منخفض</span>
                                        @else
                                            <span class="badge badge-active">متوفر ومستقر</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isSuperAdmin || $isAccountant || $isWarehouse)
                                        <button class="btn btn-outline" style="padding: 0.2rem 0.6rem; font-size: 0.75rem; color: #38bdf8; border-color: rgba(56,189,248,0.35);" onclick="openStockAuditModalForProduct({{ $prod->id }}, '{{ addslashes($prod->name_ar) }}')" title="تسجيل جرد وتسوية لهذا الصنف">
                                            📋 جرد الصنف
                                        </button>
                                        @else
                                        <span style="color: var(--text-dim); font-size: 0.75rem;">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 8: ERP REPORTS & PDF PRINT (شاشة التقارير الشاملة) ================= -->
            <div id="tab-reports" class="tab-pane">
                <div class="card-header-flex">
                    <div>
                        <h2 class="card-title">
                            <span style="color: #a855f7;">📊</span>
                            <span>شاشة تقارير الـ ERP والتحليلات وطباعة المستندات PDF</span>
                        </h2>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            تقارير مالية وتشغيلية متقدمة مع إمكانية الطباعة الفورية والتصدير بصيغة PDF لجميع أركان النظام
                        </p>
                    </div>

                    <!-- Instant PDF Print Button -->
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <button class="btn btn-primary" onclick="printCurrentSelectedReport()">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>🖨️ طباعة هذا التتقرير كـ PDF</span>
                        </button>
                    </div>
                </div>

                <!-- Report Modules Cards Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="glass kpi-card" style="cursor: pointer; --card-color: #10b981; border: 2px solid rgba(16,185,129,0.3);" onclick="selectReportModule('sales', this)">
                        <div class="kpi-header">
                            <span class="kpi-title">1. تقرير المبيعات والتحصيل</span>
                            <div class="kpi-icon" style="color: #34d399;">💰</div>
                        </div>
                        <div class="kpi-value num">{{ number_format($stats['total_revenue'], 2) }} <span style="font-size: 0.8rem;">ر.س</span></div>
                        <div class="kpi-footer"><span class="badge-trend trend-up">شامل الفواتير والضرائب</span></div>
                    </div>

                    <div class="glass kpi-card" style="cursor: pointer; --card-color: #0284c7;" onclick="selectReportModule('procurement', this)">
                        <div class="kpi-header">
                            <span class="kpi-title">2. تقرير المشتريات والموردين</span>
                            <div class="kpi-icon" style="color: #38bdf8;">🏢</div>
                        </div>
                        <div class="kpi-value num">{{ number_format($stats['total_purchases'], 2) }} <span style="font-size: 0.8rem;">ر.س</span></div>
                        <div class="kpi-footer"><span class="badge-trend trend-up">{{ $stats['total_suppliers'] }} موردين</span></div>
                    </div>

                    <div class="glass kpi-card" style="cursor: pointer; --card-color: #f59e0b;" onclick="selectReportModule('inventory', this)">
                        <div class="kpi-header">
                            <span class="kpi-title">3. تقرير المخزون وقيمة المواد</span>
                            <div class="kpi-icon" style="color: #fbbf24;">📦</div>
                        </div>
                        <div class="kpi-value num">{{ number_format($stats['total_stock_value'], 2) }} <span style="font-size: 0.8rem;">ر.س</span></div>
                        <div class="kpi-footer"><span class="badge-trend trend-up">{{ count($products) }} صنف مسجل</span></div>
                    </div>

                    <div class="glass kpi-card" style="cursor: pointer; --card-color: #38bdf8;" onclick="selectReportModule('maintenance', this)">
                        <div class="kpi-header">
                            <span class="kpi-title">4. تقرير الصيانة واستحقاق الفلاتر</span>
                            <div class="kpi-icon" style="color: #38bdf8;">🔧</div>
                        </div>
                        <div class="kpi-value num">{{ $stats['open_tickets'] + $stats['completed_tickets_month'] }} <span style="font-size: 0.8rem;">عملية</span></div>
                        <div class="kpi-footer"><span class="badge-trend trend-alert">{{ $stats['due_today'] }} شمعة مستحقة</span></div>
                    </div>
                </div>

                <!-- Report Live Filter & Preview Box -->
                <div class="glass table-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <label style="font-weight: 700; color: #38bdf8;">الفترة الزمنية المحددة للتقرير:</label>
                            <select id="reportPeriodSelector" class="form-control" style="width: auto; min-width: 200px;">
                                <option value="all">كافة الفترات والعمليات</option>
                                <option value="today">اليوم الحالي</option>
                                <option value="this_week">الأسبوع الحالي</option>
                                <option value="this_month" selected>الشهر الحالي</option>
                                <option value="this_year">العام الحالي</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            <a id="btnDirectReportPrint" href="{{ route('reports.print', ['type' => 'sales', 'period' => 'this_month']) }}" target="_blank" class="btn btn-success">
                                🖨️ فتح ومعاينة التقرير الجاهز للطباعة A4 PDF
                            </a>
                        </div>
                    </div>

                    <h3 id="reportPreviewTitle" style="font-size: 1rem; font-weight: 700; color: #ffffff; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 0.5rem;">
                        معاينة حية: تقرير المبيعات والتحصيل المالي الشامل
                    </h3>

                    <!-- Report Preview Table -->
                    <div class="table-responsive">
                        <table id="reportPreviewTable">
                            <thead>
                                <tr>
                                    <th># المرجع</th>
                                    <th>الطرف / العميل</th>
                                    <th>التاريخ</th>
                                    <th>البيان والمحتوى</th>
                                    <th>الإجمالي الصافي</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices->take(8) as $inv)
                                <tr>
                                    <td class="num" style="color: #38bdf8; font-weight: 700;">{{ $inv->invoice_number }}</td>
                                    <td style="font-weight: 700;">{{ $inv->customer?->name }}</td>
                                    <td class="num">{{ $inv->created_at->format('Y-m-d') }}</td>
                                    <td>فاتورة ضريبية رسمية ({{ $inv->items->count() }} أصناف)</td>
                                    <td class="num" style="color: #10b981; font-weight: 800;">{{ number_format($inv->grand_total, 2) }} ر.س</td>
                                    <td><span class="badge badge-active">{{ $inv->status }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- ================= TAB 9: INVOICES (LEDGER) ================= -->
            @if($isSuperAdmin || $isAccountant)
            <div id="tab-invoices" class="tab-pane">
                <div class="glass table-card">
                    <div class="card-header-flex">
                        <div>
                            <h2 class="card-title"><span style="color: #38bdf8;">💰</span><span>دفتر الفواتير والتحصيل المالي والقيود</span></h2>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">أرشفة كاملة لكافة الحركات المالية وسندات القبض</p>
                        </div>
                        <button class="btn btn-success" onclick="openNewSaleModal()">+ فاتورة جديدة</button>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>العميل</th>
                                    <th>التاريخ</th>
                                    <th>المستودع</th>
                                    <th>الإجمالي</th>
                                    <th>المدفوع</th>
                                    <th>المتبقي</th>
                                    <th>الحالة</th>
                                    <th>طباعة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $inv)
                                <tr>
                                    <td class="num" style="color: #38bdf8; font-weight: 700;">{{ $inv->invoice_number }}</td>
                                    <td>{{ $inv->customer?->name }}</td>
                                    <td class="num">{{ $inv->invoice_date?->format('Y-m-d') ?? $inv->created_at->format('Y-m-d') }}</td>
                                    <td>{{ $inv->warehouse?->name }}</td>
                                    <td class="num" style="font-weight: 700;">{{ number_format($inv->grand_total, 2) }} ر.س</td>
                                    <td class="num" style="color: #34d399;">{{ number_format($inv->paid_amount, 2) }} ر.س</td>
                                    <td class="num" style="color: {{ $inv->balance_due > 0 ? '#fb7185' : 'inherit' }};">{{ number_format($inv->balance_due, 2) }} ر.س</td>
                                    <td><span class="badge badge-active">{{ $inv->status }}</span></td>
                                    <td>
                                        <a href="{{ route('invoices.print', $inv->id) }}" target="_blank" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                            🖨️ PDF
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- ================= SUPER ADMIN ONLY TABS ================= -->
            @if($isSuperAdmin)

            <!-- TAB 10: USERS & EMPLOYEES MANAGEMENT -->
            <div id="tab-users" class="tab-pane">
                <div class="glass table-card">
                    <div class="card-header-flex">
                        <div>
                            <h2 class="card-title">
                                <span style="color: #38bdf8;">👥</span>
                                <span>إدارة الموظفين وفريق العمل المركزي</span>
                            </h2>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                تحكم كامل بإضافة الموظفين، تحديد أدوارهم الوظيفية، وتفعيل أو تجميد الحسابات
                            </p>
                        </div>
                        <button class="btn btn-success" onclick="openNewUserModal()">
                            + إضافة موظف جديد
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>الموظف</th>
                                    <th>رقم الجوال</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>نوع الحساب</th>
                                    <th>الدور الوظيفي</th>
                                    <th>الحالة</th>
                                    <th>تغيير الدور</th>
                                    <th>إجراء الإدارة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allUsers as $u)
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                                            <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(56,189,248,0.15); color: #38bdf8; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                                                {{ mb_substr($u->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div style="font-weight: 700;">{{ $u->name }}</div>
                                                <div style="font-size: 0.725rem; color: var(--text-dim);" class="num">ID: #{{ $u->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="num" style="color: #38bdf8;">{{ $u->phone }}</td>
                                    <td class="num" style="color: var(--text-muted);">{{ $u->email }}</td>
                                    <td>
                                        <span class="badge" style="background: rgba(255,255,255,0.05); color: #fff;">{{ $u->user_type }}</span>
                                    </td>
                                    <td>
                                        @foreach($u->roles as $r)
                                            <span class="badge badge-assigned">{{ $r->display_name_ar ?? $r->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($u->is_active)
                                            <span class="badge badge-active">نشط بالخدمة</span>
                                        @else
                                            <span class="badge badge-overdue">مجمّد وموقوف</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($u->phone !== '0500000001')
                                        <form action="{{ route('admin.users.role', $u->id) }}" method="POST" style="display: flex; gap: 0.35rem;">
                                            @csrf
                                            <select name="role_id" class="form-control" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; width: auto;" onchange="this.form.submit()">
                                                @foreach($allRoles as $rl)
                                                <option value="{{ $rl->id }}" {{ $u->roles->contains('id', $rl->id) ? 'selected' : '' }}>
                                                    {{ $rl->display_name_ar }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </form>
                                        @else
                                        <span style="font-size: 0.75rem; color: #fbbf24;">المدير العام</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($u->phone !== '0500000001')
                                        <form action="{{ route('admin.users.toggle', $u->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.65rem; font-size: 0.75rem; color: {{ $u->is_active ? '#fb7185' : '#34d399' }};">
                                                {{ $u->is_active ? 'تجميد الحساب' : 'إعادة التفعيل' }}
                                            </button>
                                        </form>
                                        @else
                                        <span style="font-size: 0.75rem; color: #fbbf24;">حساب المالك الأساسي</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 11: ROLES & PERMISSIONS ABSOLUTE CONTROL (شاشة الصلاحيات المطلقة) -->
            <div id="tab-roles" class="tab-pane">
                <div class="glass table-card">
                    <div class="card-header-flex">
                        <div>
                            <h2 class="card-title">
                                <span style="color: #f59e0b;">👑</span>
                                <span>شاشة الصلاحيات المطلقة والتحكم الكامل بالأدوار (Super Admin RBAC Matrix)</span>
                            </h2>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                بصفتك Super Admin، تملك هنا السيطرة المطلقة على كل وظيفة وصلاحية وزر في النظام لكل الأدوار والموظفين
                            </p>
                        </div>

                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <button class="btn btn-outline" onclick="openNewRoleModal()">
                                + إضافة دور وظيفي مخصص
                            </button>
                            <button class="btn btn-primary" onclick="openNewPermissionModal()">
                                + استحداث صلاحية نظام جديدة
                            </button>
                        </div>
                    </div>

                    <!-- Role Permissions Interactive Matrix Box -->
                    <div style="background: #ffffff; padding: 1.5rem; border-radius: 14px; border: 1px solid rgba(30,126,106,0.15); margin-bottom: 2rem; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid rgba(30,126,106,0.1); padding-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap;">
                                <label style="font-weight: 800; color: #1e7e6a; font-size: 0.95rem;">اختر الدور الوظيفي للتحكم بصلاحياته:</label>
                                <select id="roleSelector" class="form-control" style="width: auto; min-width: 280px; font-weight: 700; color: #1e293b;" onchange="loadRolePermissions(this.value)">
                                    @foreach($allRoles as $role)
                                        @if($role->name !== 'super_admin')
                                        <option value="{{ $role->id }}" data-role-name="{{ $role->name }}">
                                            {{ $role->display_name_ar }} ({{ $role->name }}) - {{ $role->permissions->count() }} صلاحية
                                        </option>
                                        @endif
                                    @endforeach
                                </select>

                                <!-- Quick Bulk Action Buttons -->
                                <button type="button" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;" onclick="toggleAllRolePermissions(true)">
                                    ✅ تحديد كافة الصلاحيات
                                </button>
                                <button type="button" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; color: #fb7185;" onclick="toggleAllRolePermissions(false)">
                                    ❌ إلغاء تحديد الكل
                                </button>
                            </div>

                            <button type="button" class="btn btn-success" onclick="submitRolePermissionsForm()" style="font-size: 0.9rem; padding: 0.55rem 1.4rem;">
                                💾 حفظ واعتماد الصلاحيات المحددة فورياً
                            </button>
                        </div>

                        <!-- Permissions by Modules -->
                        <form id="rolePermissionsForm" method="POST" action="">
                            @csrf
                            
                            @foreach($groupedPermissions as $module => $perms)
                            <div class="rbac-module-card">
                                <div class="rbac-module-header">
                                    <span>
                                        📁 وحدة النظام: <strong>{{ match($module) {
                                            'users' => 'المستخدمون وفريق العمل',
                                            'customers' => 'العملاء والعناوين',
                                            'sales' => 'المبيعات ونقاط البيع',
                                            'invoices' => 'الفواتير والتحصيل المالي',
                                            'procurement' => 'المشتريات والموردين',
                                            'inventory' => 'المخزون والمستودعات',
                                            'maintenance' => 'الصيانة والعمليات الميدانية',
                                            'reports' => 'التقارير والتحليلات',
                                            'settings' => 'إعدادات وسياسات المنظومة',
                                            'catalog' => 'الكتالوج والمنتجات',
                                            default => $module
                                        } }}</strong> ({{ count($perms) }} صلاحية)
                                    </span>

                                    <button type="button" class="btn btn-outline" style="padding: 0.2rem 0.6rem; font-size: 0.7rem;" onclick="toggleModulePerms('{{ $module }}')">
                                        تحديد / إلغاء تحديد هذه الوحدة
                                    </button>
                                </div>

                                <div class="permissions-checkbox-grid">
                                    @foreach($perms as $perm)
                                    <label class="perm-checkbox-item">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" class="perm-chk perm-mod-{{ $module }}" data-perm-id="{{ $perm->id }}">
                                        <div>
                                            <div class="perm-label">{{ $perm->display_name_ar }}</div>
                                            <div class="perm-code">{{ $perm->name }}</div>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </form>
                    </div>

                    <!-- Roles Directory Table -->
                    <div class="glass table-card" style="padding: 1.25rem;">
                        <h3 style="font-size: 1rem; font-weight: 700; color: #38bdf8; margin-bottom: 0.85rem;">
                            📋 قائمة الأدوار المعتمدة في النظام وتوزيع الصلاحيات ({{ count($allRoles) }} أدوار)
                        </h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>كود الدور (Role Key)</th>
                                        <th>المسمى العربي للدور</th>
                                        <th>الوصف والمسؤوليات</th>
                                        <th>عدد الصلاحيات الممنوحة</th>
                                        <th>عدد الموظفين</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allRoles as $r)
                                    <tr>
                                        <td class="num" style="color: #38bdf8; font-weight: 700;">{{ $r->name }}</td>
                                        <td style="font-weight: 700; color: #ffffff;">{{ $r->display_name_ar }}</td>
                                        <td style="font-size: 0.8rem; color: var(--text-muted);">{{ $r->description }}</td>
                                        <td>
                                            <span class="badge badge-active num">{{ $r->permissions->count() }} صلاحية</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-assigned num">{{ $r->users->count() }} موظف</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 12: AUDIT LOGS -->
            <div id="tab-audit" class="tab-pane">
                <div class="glass table-card">
                    <div class="card-header-flex">
                        <div>
                            <h2 class="card-title"><span style="color: #38bdf8;">🛡️</span><span>سجل التدقيق الأمني (Audit Log)</span></h2>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">توثيق غير قابل للتعديل لجميع الحركات والعمليات</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>الوقت</th>
                                    <th>المستخدم</th>
                                    <th>الإجراء</th>
                                    <th>الهدف</th>
                                    <th>IP</th>
                                    <th>التفاصيل والتغييرات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($auditLogs as $log)
                                <tr>
                                    <td class="num" style="font-size: 0.775rem; color: var(--text-muted);">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                    <td style="font-weight: 700;">{{ $log->user?->name ?? 'مدير النظام' }}</td>
                                    <td><span class="badge badge-assigned num">{{ $log->action }}</span></td>
                                    <td style="font-size: 0.8rem; color: var(--text-dim);">{{ class_basename($log->auditable_type ?? 'Entity') }} #{{ $log->auditable_id }}</td>
                                    <td class="num" style="font-size: 0.8rem;">{{ $log->ip_address }}</td>
                                    <td style="font-size: 0.75rem; font-family: monospace; color: #7dd3fc;">{{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 13: SETTINGS -->
            <div id="tab-settings" class="tab-pane">

                @php
                    $sCompany = \App\Models\SystemSetting::get('company_name', '');
                    $sPhone   = \App\Models\SystemSetting::get('company_phone', '');
                    $sEmail   = \App\Models\SystemSetting::get('company_email', '');
                    $sTax     = \App\Models\SystemSetting::get('tax_number', '');
                    $sVat     = \App\Models\SystemSetting::get('vat_percentage', 15);
                    $sRemDays = \App\Models\SystemSetting::get('reminder_window_days', 14);
                    $sWStart  = \App\Models\SystemSetting::get('working_hours_start', '08:00');
                    $sWEnd    = \App\Models\SystemSetting::get('working_hours_end', '20:00');
                    $sPreset  = \App\Models\SystemSetting::get('theme_preset', 'ocean_blue');
                    $sPrimary = \App\Models\SystemSetting::get('theme_accent_primary', '#38bdf8');
                    $sSecondary = \App\Models\SystemSetting::get('theme_accent_secondary', '#10b981');
                    $sBgBase   = \App\Models\SystemSetting::get('theme_bg_base', '#060b17');
                    $sSidebar  = \App\Models\SystemSetting::get('theme_sidebar_bg', '#0a1124');
                @endphp

                <style>
                    /* ════ Settings Tab Styles ════ */
                    .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
                    @media(max-width:900px){ .settings-grid{ grid-template-columns:1fr; } }
                    .settings-section { background: var(--bg-card); border:1px solid var(--border-glass); border-radius:16px; padding:1.5rem; }
                    .settings-section-title {
                        font-size: 1rem; font-weight: 800; color: var(--accent-cyan);
                        display: flex; align-items: center; gap: 0.6rem;
                        margin-bottom: 1.25rem; padding-bottom: 0.75rem;
                        border-bottom: 1px solid var(--border-glass);
                    }
                    .settings-row { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1rem; }
                    .settings-row:last-child { margin-bottom:0; }
                    .settings-label { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); }
                    .settings-input {
                        background: rgba(255,255,255,0.04); border: 1px solid var(--border-glass);
                        border-radius: 10px; padding: 0.6rem 0.9rem; color: var(--text-main);
                        font-size: 0.875rem; font-family: 'Cairo', sans-serif;
                        transition: border-color 0.2s ease; width: 100%;
                    }
                    .settings-input:focus { outline: none; border-color: var(--accent-cyan); box-shadow: 0 0 0 3px rgba(56,189,248,0.15); }
                    .settings-input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
                    /* Theme Presets */
                    .theme-presets-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.75rem; margin-bottom: 1.25rem; }
                    @media(max-width:700px){ .theme-presets-grid{ grid-template-columns: repeat(3,1fr); } }
                    .theme-preset-btn {
                        cursor: pointer; border-radius: 12px; padding: 0.75rem 0.5rem;
                        border: 2px solid transparent; text-align: center; transition: all 0.25s ease;
                        background: rgba(255,255,255,0.04); display: flex; flex-direction: column;
                        align-items: center; gap: 0.4rem;
                    }
                    .theme-preset-btn:hover { border-color: var(--accent-cyan); transform: translateY(-2px); }
                    .theme-preset-btn.active { border-color: var(--accent-cyan); background: rgba(56,189,248,0.1); }
                    .theme-swatch { width: 40px; height: 40px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.2); }
                    .theme-preset-name { font-size: 0.7rem; font-weight: 700; color: var(--text-muted); }
                    /* Color Pickers Row */
                    .color-pickers-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
                    @media(max-width:600px){ .color-pickers-grid{ grid-template-columns:1fr; } }
                    .color-pick-wrap {
                        background: rgba(255,255,255,0.03); border:1px solid var(--border-glass);
                        border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 0.85rem;
                    }
                    .color-pick-wrap input[type="color"] {
                        width: 48px; height: 48px; border: none; border-radius: 10px;
                        cursor: pointer; padding: 2px; background: transparent;
                    }
                    .color-pick-info { flex: 1; }
                    .color-pick-info label { font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; }
                    .color-pick-info span { font-size: 0.825rem; font-weight: 700; color: var(--text-main); }
                    /* Save Button */
                    .btn-save-settings {
                        background: linear-gradient(135deg, var(--accent-cyan), var(--accent-emerald));
                        color: #000; font-weight: 800; border: none; border-radius: 10px;
                        padding: 0.75rem 2.5rem; font-size: 0.9rem; cursor: pointer;
                        transition: all 0.25s ease; font-family: 'Cairo',sans-serif;
                        box-shadow: 0 4px 20px rgba(56,189,248,0.4);
                    }
                    .btn-save-settings:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(56,189,248,0.5); }
                    .btn-danger-settings {
                        background: rgba(244,63,94,0.15); color: #fb7185; border: 1px solid rgba(244,63,94,0.4);
                        font-weight: 800; border-radius: 10px; padding: 0.75rem 2rem;
                        font-size: 0.875rem; cursor: pointer; transition: all 0.25s; font-family:'Cairo',sans-serif;
                    }
                    .btn-danger-settings:hover { background: rgba(244,63,94,0.3); border-color: #f43f5e; }
                    .settings-full-width { grid-column: 1/-1; }
                </style>

                <form action="{{ route('admin.settings.update') }}" method="POST" id="settingsForm">
                    @csrf

                    <!-- Page Title -->
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <h2 style="font-size:1.4rem; font-weight:900; color:var(--text-main); margin:0;">⚙️ إعدادات وتخصيص النظام</h2>
                            <p style="color:var(--text-muted); font-size:0.825rem; margin-top:0.25rem;">تخصيص ألوان النظام، بيانات الشركة، وسياسات التشغيل</p>
                        </div>
                        @if($isSuperAdmin)
                        <button type="submit" class="btn-save-settings">💾 حفظ جميع الإعدادات</button>
                        @endif
                    </div>

                    <div class="settings-grid">

                        <!-- ───── قسم الثيمات الجاهزة ───── -->
                        <div class="settings-section settings-full-width">
                            <div class="settings-section-title">
                                🎨 ثيم وألوان النظام — اختر من الثيمات الجاهزة أو خصص بنفسك
                            </div>

                            <!-- الثيمات الجاهزة -->
                            <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.85rem;">ثيمات جاهزة — انقر لتطبيق فوري:</p>
                            <div class="theme-presets-grid">
                                <div class="theme-preset-btn {{ $sPreset === 'ocean_blue' ? 'active' : '' }}"
                                     onclick="applyThemePreset('ocean_blue','#38bdf8','#10b981','#060b17','#0a1124')" title="أزرق المحيط">
                                    <div class="theme-swatch" style="background:linear-gradient(135deg,#38bdf8,#0284c7);"></div>
                                    <span class="theme-preset-name">أزرق المحيط</span>
                                </div>
                                <div class="theme-preset-btn {{ $sPreset === 'emerald_night' ? 'active' : '' }}"
                                     onclick="applyThemePreset('emerald_night','#10b981','#14b8a6','#071209','#0a1f12')" title="ليلة الزمرد">
                                    <div class="theme-swatch" style="background:linear-gradient(135deg,#10b981,#14b8a6);"></div>
                                    <span class="theme-preset-name">ليلة الزمرد</span>
                                </div>
                                <div class="theme-preset-btn {{ $sPreset === 'purple_galaxy' ? 'active' : '' }}"
                                     onclick="applyThemePreset('purple_galaxy','#a855f7','#ec4899','#0c0714','#120920')" title="مجرة البنفسج">
                                    <div class="theme-swatch" style="background:linear-gradient(135deg,#a855f7,#ec4899);"></div>
                                    <span class="theme-preset-name">مجرة البنفسج</span>
                                </div>
                                <div class="theme-preset-btn {{ $sPreset === 'sunset_amber' ? 'active' : '' }}"
                                     onclick="applyThemePreset('sunset_amber','#f59e0b','#ef4444','#130d03','#1f1508')" title="غروب العنبر">
                                    <div class="theme-swatch" style="background:linear-gradient(135deg,#f59e0b,#ef4444);"></div>
                                    <span class="theme-preset-name">غروب العنبر</span>
                                </div>
                                <div class="theme-preset-btn {{ $sPreset === 'crimson_dark' ? 'active' : '' }}"
                                     onclick="applyThemePreset('crimson_dark','#f43f5e','#f97316','#150507','#200810')" title="القرمزي الداكن">
                                    <div class="theme-swatch" style="background:linear-gradient(135deg,#f43f5e,#f97316);"></div>
                                    <span class="theme-preset-name">قرمزي داكن</span>
                                </div>
                            </div>

                            <!-- محددات الألوان المخصصة -->
                            <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.85rem;">أو خصص الألوان يدوياً:</p>
                            <div class="color-pickers-grid">
                                <div class="color-pick-wrap">
                                    <input type="color" id="cpPrimary" name="theme_accent_primary" value="{{ $sPrimary }}"
                                           onchange="updateColorPreview(this, 'cpPrimaryVal')" oninput="updateColorPreview(this, 'cpPrimaryVal')">
                                    <div class="color-pick-info">
                                        <label>اللون الأساسي (Primary)</label>
                                        <span id="cpPrimaryVal">{{ $sPrimary }}</span>
                                    </div>
                                </div>
                                <div class="color-pick-wrap">
                                    <input type="color" id="cpSecondary" name="theme_accent_secondary" value="{{ $sSecondary }}"
                                           onchange="updateColorPreview(this, 'cpSecondaryVal')" oninput="updateColorPreview(this, 'cpSecondaryVal')">
                                    <div class="color-pick-info">
                                        <label>اللون الثانوي (Secondary)</label>
                                        <span id="cpSecondaryVal">{{ $sSecondary }}</span>
                                    </div>
                                </div>
                                <div class="color-pick-wrap">
                                    <input type="color" id="cpBgBase" name="theme_bg_base" value="{{ $sBgBase }}"
                                           onchange="updateColorPreview(this, 'cpBgBaseVal')" oninput="updateColorPreview(this, 'cpBgBaseVal')">
                                    <div class="color-pick-info">
                                        <label>خلفية النظام الرئيسية</label>
                                        <span id="cpBgBaseVal">{{ $sBgBase }}</span>
                                    </div>
                                </div>
                                <div class="color-pick-wrap">
                                    <input type="color" id="cpSidebar" name="theme_sidebar_bg" value="{{ $sSidebar }}"
                                           onchange="updateColorPreview(this, 'cpSidebarVal')" oninput="updateColorPreview(this, 'cpSidebarVal')">
                                    <div class="color-pick-info">
                                        <label>خلفية القائمة الجانبية</label>
                                        <span id="cpSidebarVal">{{ $sSidebar }}</span>
                                    </div>
                                </div>
                            </div>
                            <!-- hidden preset field -->
                            <input type="hidden" name="theme_preset" id="themePresetInput" value="{{ $sPreset }}">
                        </div>

                        <!-- ───── بيانات الشركة ───── -->
                        <div class="settings-section">
                            <div class="settings-section-title">🏢 بيانات المنشأة والشركة</div>

                            <div class="settings-row">
                                <label class="settings-label">اسم المنشأة التجاري</label>
                                <input class="settings-input" type="text" name="company_name" value="{{ $sCompany }}"
                                       placeholder="اسم الشركة" {{ !$isSuperAdmin ? 'readonly' : '' }}>
                            </div>
                            <div class="settings-row">
                                <label class="settings-label">هاتف خدمة العملاء</label>
                                <input class="settings-input" type="text" name="company_phone" value="{{ $sPhone }}"
                                       placeholder="920000000" {{ !$isSuperAdmin ? 'readonly' : '' }}>
                            </div>
                            <div class="settings-row">
                                <label class="settings-label">البريد الإلكتروني الرسمي</label>
                                <input class="settings-input" type="email" name="company_email" value="{{ $sEmail }}"
                                       placeholder="info@company.sa" {{ !$isSuperAdmin ? 'readonly' : '' }}>
                            </div>
                            <div class="settings-row">
                                <label class="settings-label">الرقم الضريبي (VAT Number)</label>
                                <input class="settings-input" type="text" name="tax_number" value="{{ $sTax }}"
                                       placeholder="300XXXXXXXXXX" {{ !$isSuperAdmin ? 'readonly' : '' }}>
                            </div>
                            <div class="settings-row">
                                <label class="settings-label">نسبة ضريبة القيمة المضافة (%)</label>
                                <input class="settings-input" type="number" name="vat_percentage" value="{{ $sVat }}"
                                       min="0" max="100" {{ !$isSuperAdmin ? 'readonly' : '' }}>
                            </div>
                        </div>

                        <!-- ───── إعدادات التشغيل ───── -->
                        <div class="settings-section">
                            <div class="settings-section-title">🕐 سياسات التشغيل والصيانة</div>

                            <div class="settings-row">
                                <label class="settings-label">نافذة التنبيه المسبق للفلاتر (أيام)</label>
                                <input class="settings-input" type="number" name="reminder_window_days" value="{{ $sRemDays }}"
                                       min="1" max="90" {{ !$isSuperAdmin ? 'readonly' : '' }}>
                            </div>
                            <div class="settings-row">
                                <label class="settings-label">بداية الدوام الميداني</label>
                                <input class="settings-input" type="time" name="working_hours_start" value="{{ $sWStart }}"
                                       {{ !$isSuperAdmin ? 'readonly' : '' }}>
                            </div>
                            <div class="settings-row">
                                <label class="settings-label">نهاية الدوام الميداني</label>
                                <input class="settings-input" type="time" name="working_hours_end" value="{{ $sWEnd }}"
                                       {{ !$isSuperAdmin ? 'readonly' : '' }}>
                            </div>

                            @if($isSuperAdmin)
                            <!-- تصفير البيانات -->
                            <div style="margin-top:2rem; padding-top:1.25rem; border-top:1px solid rgba(244,63,94,0.2);">
                                <p style="font-size:0.8rem; color:#fb7185; font-weight:700; margin-bottom:0.75rem;">⚠️ منطقة الخطر — تصفير البيانات</p>
                                <p style="font-size:0.75rem; color:var(--text-dim); margin-bottom:1rem;">
                                    سيتم حذف كل البيانات التشغيلية (عملاء، مبيعات، فواتير، مخزون، صيانة) مع الإبقاء على الأدوار والصلاحيات فقط.
                                </p>
                                <button type="button" class="btn-danger-settings"
                                        onclick="if(confirm('⚠️ تأكيد: سيتم حذف جميع البيانات التشغيلية نهائياً. هل أنت متأكد؟')){
                                            document.getElementById('resetDemoForm').submit();}">
                                    🗑️ تصفير البيانات التجريبية
                                </button>
                            </div>
                            @endif
                        </div>

                        <!-- ───── معلومات الإصدار ───── -->
                        <div class="settings-section settings-full-width" style="background:rgba(56,189,248,0.04); border-color:rgba(56,189,248,0.15);">
                            <div style="display:flex; flex-wrap:wrap; gap:2rem; align-items:center; justify-content:space-between;">
                                <div>
                                    <div style="font-size:0.75rem; color:var(--text-dim); font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.35rem;">إصدار النظام</div>
                                    <div style="font-size:1.1rem; font-weight:900; color:var(--text-main);">تقنية الفلاتر ERP <span style="color:var(--accent-cyan);">v2.0</span></div>
                                    <div style="font-size:0.775rem; color:var(--text-muted); margin-top:0.25rem;">نظام إدارة موارد مؤسسية شامل — مبني بـ Laravel 12 + Blade</div>
                                </div>
                                <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                                    @foreach($systemSettings->groupBy('group') as $group => $items)
                                    <span style="background:rgba(255,255,255,0.06); border:1px solid var(--border-glass); border-radius:8px; padding:0.35rem 0.85rem; font-size:0.725rem; color:var(--text-muted); font-weight:700;">
                                        {{ $group }}: {{ $items->count() }} إعداد
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div><!-- /settings-grid -->

                </form>

                <!-- hidden form for reset demo data -->
                @if($isSuperAdmin)
                <form action="{{ route('admin.reset-demo') }}" method="POST" id="resetDemoForm" style="display:none;">
                    @csrf
                </form>
                @endif

            </div>

            @endif

        </div>

        <footer>
            <p>تقنية الفلاتر &copy; {{ date('Y') }} — جميع الحقوق محفوظة | نظام إدارة الموارد المؤسسية الشامل (ERP) ومحرك استحقاقات الفلاتر الذكي</p>
        </footer>
    </div>

    <!-- ================= ALL MODALS ================= -->

    @if(!$isCallCenter)
    <!-- MODAL 1: Point of Sale & New Invoice (شاشة نقطة البيع وفاتورة مبيعات) -->
    <div id="newSaleModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #10b981; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🛒</span>
                    <span>إصدار فاتورة بيع جديدة / نقطة بيع POS</span>
                </h3>
                <button class="close-btn" onclick="closeModal('newSaleModal')">&times;</button>
            </div>

            <form action="{{ route('sales.create') }}" method="POST" id="posSaleForm">
                @csrf
                
                <!-- Customer Selection -->
                <div class="form-row">
                    <div class="form-group">
                        <label>نوع العميل:</label>
                        <select name="customer_type" id="saleCustomerType" class="form-control" onchange="toggleCustomerType(this.value)" required>
                            <option value="existing">عميل مسجل في النظام</option>
                            <option value="new">+ عميل جديد فوري</option>
                        </select>
                    </div>

                    <div class="form-group" id="existingCustomerBox">
                        <label>اختر العميل:</label>
                        <select name="customer_id" class="form-control">
                            @foreach($customers as $cust)
                            <option value="{{ $cust->id }}">{{ $cust->name }} ({{ $cust->primary_phone }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- New Customer Fields (Hidden by default) -->
                <div id="newCustomerFields" style="display: none; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid rgba(255,255,255,0.08);">
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>اسم العميل الجديد:</label>
                            <input type="text" name="new_customer_name" class="form-control" placeholder="مثال: عبد الله خالد">
                        </div>
                        <div class="form-group">
                            <label>رقم الجوال:</label>
                            <input type="text" name="new_customer_phone" class="form-control" placeholder="05XXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label>المدينة والحي:</label>
                            <input type="text" name="new_customer_city" class="form-control" placeholder="الرياض - حي النرجس">
                        </div>
                    </div>
                </div>

                <!-- Warehouse & Sale Type & Date -->
                <div class="form-row-3">
                    <div class="form-group">
                        <label>مستودع الصرف:</label>
                        <select name="warehouse_id" class="form-control" required>
                            @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>نوع المعاملة:</label>
                        <select name="sale_type" class="form-control" required>
                            <option value="invoice">فاتورة ضريبية فورية (مع خصم المخزون)</option>
                            <option value="order">أمر بيع / عرض سعر مسودة</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>تاريخ المعاملة:</label>
                        <input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <!-- Dynamic Items Selector -->
                <div style="margin-top: 1rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                        <label style="font-weight: 700; color: #38bdf8;">الأصناف والمبيعات المضافة:</label>
                        <button type="button" class="btn btn-outline" style="padding: 0.25rem 0.65rem; font-size: 0.75rem;" onclick="addPosItemRow()">
                            + إضافة صنف إضافي
                        </button>
                    </div>

                    <div id="posItemsContainer">
                        <div class="pos-item-box" id="posRow_0">
                            <div class="form-row" style="grid-template-columns: 3fr 1fr 1fr 1fr auto; align-items: flex-end;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>الصنف:</label>
                                    <select name="items[0][product_id]" class="form-control pos-prod-select" onchange="updatePosItemPrice(0, this)" required>
                                        <option value="">اختر الصنف...</option>
                                        @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-price="{{ (float)$p->selling_price }}">
                                            {{ $p->name_ar }} ({{ $p->sku }}) — {{ number_format($p->selling_price, 2) }} ر.س
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>سعر الوحدة:</label>
                                    <input type="number" step="0.01" name="items[0][unit_price]" id="posUnitPrice_0" class="form-control num" placeholder="0.00" oninput="calculatePosTotals()" required>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>الكمية:</label>
                                    <input type="number" name="items[0][quantity]" id="posQty_0" class="form-control num" value="1" min="1" oninput="calculatePosTotals()" required>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>الخصم:</label>
                                    <input type="number" step="0.01" name="items[0][discount]" id="posDisc_0" class="form-control num" value="0.00" oninput="calculatePosTotals()">
                                </div>
                                <button type="button" class="close-btn" style="color: #fb7185; padding-bottom: 8px;" onclick="removePosItemRow(0)">&times;</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="form-row-3" style="background: rgba(15,23,42,0.6); padding: 1rem; border-radius: 10px; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label>طريقة السداد:</label>
                        <select name="payment_method" id="posPaymentMethod" class="form-control" onchange="handlePaymentMethodChange(this.value)" required>
                            <option value="mada_pos">شبكة مدى (Mada POS)</option>
                            <option value="cash">نقداً (Cash)</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="credit_card">بطاقة ائتمانية</option>
                            <option value="credit">آجل (ذمم مدينة)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>المبلغ المدفوع الآن:</label>
                        <input type="number" step="0.01" name="paid_amount" id="posPaidAmount" class="form-control num" value="0.00">
                    </div>

                    <div class="form-group">
                        <label>ملاحظات إضافية:</label>
                        <input type="text" name="notes" class="form-control" placeholder="اختياري...">
                    </div>
                </div>

                <!-- Totals Live Box -->
                <div style="background: rgba(2, 132, 199, 0.1); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">المجموع قبل الضريبة: <span id="posSubtotalDisp" class="num" style="color: #fff; font-weight: 700;">0.00</span> ر.س</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 3px;">ضريبة القيمة المضافة (15%): <span id="posTaxDisp" class="num" style="color: #fff; font-weight: 700;">0.00</span> ر.س</div>
                    </div>
                    <div style="text-align: left;">
                        <div style="font-size: 0.85rem; color: #38bdf8; font-weight: 700;">الإجمالي الكلي النهائي:</div>
                        <div id="posGrandTotalDisp" class="num" style="font-size: 1.8rem; font-weight: 800; color: #34d399;">0.00 ر.س</div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('newSaleModal')">إلغاء</button>
                    <button type="submit" class="btn btn-success" style="font-size: 0.95rem; padding: 0.6rem 1.8rem;">
                        ✓ تأكيد العملية وإصدار الفاتورة فورياً
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Record Payment Receipt (سند قبض مالي) -->
    <div id="recordPaymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #10b981;">💵 تسجيل سند قبض مالي لفاتورة</h3>
                <button class="close-btn" onclick="closeModal('recordPaymentModal')">&times;</button>
            </div>

            <form id="recordPaymentForm" method="POST" action="">
                @csrf
                <div style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.85rem;">
                    رقم الفاتورة: <strong id="payInvoiceNum" style="color: #38bdf8;" class="num"></strong>
                    | المتبقي المستحق: <strong id="payBalanceDue" style="color: #fb7185;" class="num"></strong> ر.س
                </div>

                <div class="form-group">
                    <label>المبلغ المراد تحصيله (ريال):</label>
                    <input type="number" step="0.01" name="amount" id="payAmountInput" class="form-control num" required>
                </div>

                <div class="form-group">
                    <label>طريقة السداد:</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="mada_pos">شبكة مدى (Mada POS)</option>
                        <option value="cash">نقداً (Cash)</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="credit_card">بطاقة ائتمانية</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>رقم الحوالة / مرجع العملية:</label>
                    <input type="text" name="transaction_reference" class="form-control" placeholder="مثال: TXN-987654">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('recordPaymentModal')">إلغاء</button>
                    <button type="submit" class="btn btn-success">تأكيد استلام المبلغ وتحديث الفاتورة</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Add Supplier (إضافة مورد جديد) -->
    <div id="newSupplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #0284c7;">+ إضافة مورد وشركة توريد جديدة</h3>
                <button class="close-btn" onclick="closeModal('newSupplierModal')">&times;</button>
            </div>

            <form action="{{ route('suppliers.create') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>اسم شركة المورد:</label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: شركة النقاء لقطع الغيار" required>
                    </div>

                    <div class="form-group">
                        <label>كود المورد (اختياري - يولد تلقائياً):</label>
                        <input type="text" name="supplier_code" class="form-control" placeholder="SUP-XXXX">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>الشخص المسؤول للتواصل:</label>
                        <input type="text" name="contact_person" class="form-control" placeholder="مثال: المهندس أحمد">
                    </div>

                    <div class="form-group">
                        <label>رقم الجوال:</label>
                        <input type="text" name="phone" class="form-control" placeholder="05XXXXXXXX" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>البريد الإلكتروني:</label>
                        <input type="email" name="email" class="form-control" placeholder="supplier@example.com">
                    </div>

                    <div class="form-group">
                        <label>الرقم الضريبي للمنشأة:</label>
                        <input type="text" name="tax_number" class="form-control" placeholder="3XXXXXXXXXXXX03">
                    </div>
                </div>

                <div class="form-group">
                    <label>العنوان والمستودع:</label>
                    <input type="text" name="address" class="form-control" placeholder="المدينة، الحي، الشارع...">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('newSupplierModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ واعتماد المورد</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: New Purchase Order (إضافة مشتريات وتوريد PO) -->
    <div id="newPurchaseOrderModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #0284c7;">📦 + إضافة مشتريات وتوريد رسمي (Purchase Order)</h3>
                <button class="close-btn" onclick="closeModal('newPurchaseOrderModal')">&times;</button>
            </div>

            <form action="{{ route('procurement.orders.create') }}" method="POST">
                @csrf
                <div class="form-row-3">
                    <div class="form-group">
                        <label>المورد المعتمد:</label>
                        <select name="supplier_id" id="poSupplierSelector" class="form-control" required>
                            @foreach($suppliers as $sp)
                            <option value="{{ $sp->id }}">{{ $sp->name }} ({{ $sp->supplier_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>مستودع الاستلام:</label>
                        <select name="warehouse_id" class="form-control" required>
                            @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>تاريخ الأمر:</label>
                        <input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div style="margin-top: 1rem; margin-bottom: 1rem;">
                    <label style="font-weight: 700; color: #38bdf8; display: block; margin-bottom: 0.5rem;">الأصناف والمشتريات المطلوبة من المورد:</label>
                    
                    <div class="pos-item-box">
                        <div class="form-row" style="grid-template-columns: 3fr 1.5fr 1.5fr;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>الصنف:</label>
                                <select name="items[0][product_id]" class="form-control" required>
                                    @foreach($products as $pr)
                                    <option value="{{ $pr->id }}">{{ $pr->name_ar }} ({{ $pr->sku }}) - تكلفة: {{ number_format($pr->cost_price, 2) }} ر.س</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>الكمية المطلوبة:</label>
                                <input type="number" name="items[0][quantity]" class="form-control num" value="50" min="1" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>سعر التكلفة المتفق عليه:</label>
                                <input type="number" step="0.01" name="items[0][unit_price]" class="form-control num" value="5.00" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>شروط التوريد وملاحظات:</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="شروط التسليم والدفع..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('newPurchaseOrderModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #0284c7, #0369a1);">حفظ واعتماد المشتريات</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- MODAL 5: Add Role (إضافة دور وظيفي جديد لـ Super Admin) -->
    <div id="newRoleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #f59e0b;">+ إنشاء دور وظيفي جديد في المنظومة</h3>
                <button class="close-btn" onclick="closeModal('newRoleModal')">&times;</button>
            </div>

            <form action="{{ route('admin.roles.create') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>كود الدور الإنجليزي (Role Key):</label>
                    <input type="text" name="name" class="form-control" placeholder="مثال: quality_inspector أو branch_manager" required>
                </div>

                <div class="form-group">
                    <label>المسمى العربي للدور:</label>
                    <input type="text" name="display_name_ar" class="form-control" placeholder="مثال: مراقب جودة المحطات أو مدير فرع" required>
                </div>

                <div class="form-group">
                    <label>الوصف والمهام:</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="وصف موجز للمسؤوليات..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('newRoleModal')">إلغاء</button>
                    <button type="submit" class="btn btn-warning">حفظ وإنشاء الدور</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 6: Add User (إضافة موظف) -->
    <div id="newUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #10b981;">+ إضافة موظف جديد وتعيين دوره الوظيفي</h3>
                <button class="close-btn" onclick="closeModal('newUserModal')">&times;</button>
            </div>

            <form action="{{ route('admin.users.create') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>الاسم الكامل للموظف:</label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: عبد الله ناصر الدوسري" required>
                    </div>

                    <div class="form-group">
                        <label>رقم الجوال (اسم الدخول):</label>
                        <input type="text" name="phone" class="form-control" placeholder="05XXXXXXXX" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>البريد الإلكتروني:</label>
                        <input type="email" name="email" class="form-control" placeholder="employee@filtertech.local" required>
                    </div>

                    <div class="form-group">
                        <label>كلمة المرور الابتدائية:</label>
                        <input type="password" name="password" class="form-control" value="Staff@123456" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>نوع الحساب:</label>
                        <select name="user_type" class="form-control" required>
                            <option value="staff">موظف إداري (Staff)</option>
                            <option value="technician">فني صيانة ميداني (Technician)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>الدور الوظيفي والصلاحيات:</label>
                        <select name="role_id" class="form-control" required>
                            @foreach($allRoles as $r)
                                @if($r->name !== 'super_admin')
                                <option value="{{ $r->id }}">{{ $r->display_name_ar }} ({{ $r->name }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('newUserModal')">إلغاء</button>
                    <button type="submit" class="btn btn-success">حفظ وإنشاء الموظف</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 7: Add Permission (إضافة صلاحية جديدة لـ Super Admin) -->
    <div id="newPermissionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #38bdf8;">+ إضافة صلاحية نظام جديدة إلى المنظومة</h3>
                <button class="close-btn" onclick="closeModal('newPermissionModal')">&times;</button>
            </div>

            <form action="{{ route('admin.permissions.create') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>كود الصلاحية البرمجي (Permission Key - باللغة الإنجليزية):</label>
                    <input type="text" name="name" class="form-control" placeholder="مثال: sales.override_discounts أو pos.refund" required>
                </div>

                <div class="form-group">
                    <label>المسمى العربي للصلاحية:</label>
                    <input type="text" name="display_name_ar" class="form-control" placeholder="مثال: تجاوز سقف الخصم أو استرجاع نقدي" required>
                </div>

                <div class="form-group">
                    <label>الوحدة التابعة (Module):</label>
                    <select name="module" class="form-control" required>
                        <option value="sales">المبيعات ونقاط البيع (sales)</option>
                        <option value="procurement">المشتريات والموردين (procurement)</option>
                        <option value="inventory">المخزون والمستودعات (inventory)</option>
                        <option value="maintenance">الصيانة ومحطات التحلية (maintenance)</option>
                        <option value="reports">التقارير والتحليلات (reports)</option>
                        <option value="users">المستخدمون وفريق العمل (users)</option>
                        <option value="settings">إعدادات النظام العامة (settings)</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('newPermissionModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ وتعيين لمدير النظام</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 8: New Ticket (تذكرة صيانة سريعة) -->
    <!-- MODAL 8: New Ticket / Appointment Booking (حجز موعد صيانة وزيارة فني للعميل) -->
    <div id="newTicketModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #38bdf8;">📅 حجز موعد وزيارة فني ميداني للعميل</h3>
                <button class="close-btn" onclick="closeModal('newTicketModal')">&times;</button>
            </div>

            <form action="{{ route('tickets.quick-create') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label style="font-weight: 700; color: #38bdf8;">العميل المستفيد:</label>
                        <select name="customer_id" id="modalCustomerId" class="form-control" required onchange="filterCustomerDevicesInModal(this.value)">
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->primary_phone }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>محطة التحلية المستهدفة:</label>
                        <select name="customer_device_id" id="modalDeviceId" class="form-control">
                            <option value="">-- بدون تحديد محطة (زيارة تركيب جديد / معاينة / فحص عام) --</option>
                            @foreach($customers as $c)
                                @foreach($c->devices as $d)
                                <option value="{{ $d->id }}" data-customer-id="{{ $c->id }}">#{{ $d->device_code }} - {{ $d->model?->name }} ({{ $c->name }})</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>نوع الخدمة أو الزيارة المطلوبة:</label>
                        <select name="service_type" class="form-control" required>
                            <option value="استبدال شمعات دورية">استبدال شمعات دورية</option>
                            <option value="صيانة وإصلاح عطل">صيانة وإصلاح عطل</option>
                            <option value="تركيب محطة جديدة">تركيب محطة تحلية جديدة</option>
                            <option value="فحص جودة وأملاح TDS">فحص جودة وأملاح TDS</option>
                            <option value="زيارة كشف ومعاينة">زيارة كشف ومعاينة</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>درجة الأولوية:</label>
                        <select name="priority" class="form-control" required>
                            <option value="normal">عادي</option>
                            <option value="high">مرتفع</option>
                            <option value="urgent">عاجل وطارئ</option>
                        </select>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group" style="grid-column: span 1;">
                        <label style="font-weight: 700; color: #10b981;">الفني المكلف بالموعد:</label>
                        <select name="technician_id" class="form-control" required>
                            <option value="">-- اختر الفني الميداني --</option>
                            @foreach($technicians as $t)
                            <option value="{{ $t->id }}">🚙 {{ $t->name }} ({{ $t->phone }})</option>
                            @endforeach
                        </select>
                        <small style="font-size: 0.7rem; color: #10b981;">سيتم تحويل الموعد فوراً لجدول وتطبيق الفني المختار.</small>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 700;">تاريخ الموعد:</label>
                        <input type="date" name="scheduled_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group">
                        <label>الفترة المحددة:</label>
                        <select name="time_slot" class="form-control">
                            <option value="صباحاً (09:00 - 12:00)">صباحاً (09:00 - 12:00)</option>
                            <option value="ظهراً (12:00 - 04:00)">ظهراً (12:00 - 04:00)</option>
                            <option value="مساءً (04:00 - 08:00)">مساءً (04:00 - 08:00)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>ملاحظات العمل والقطع المطلوبة:</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="شمعات مستحقة، وصف شكوى العميل، أو تعليمات الموقع للفني..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('newTicketModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #0284c7, #0369a1);">حجز الموعد وإسناده للفني فوراً</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 9: Assign Ticket (إسناد تذكرة) -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #38bdf8;">إسناد التذكرة (<span id="assignTicketNumber"></span>) لفني ميداني</h3>
                <button class="close-btn" onclick="closeModal('assignModal')">&times;</button>
            </div>

            <form id="assignForm" method="POST" action="">
                @csrf
                <div class="form-group">
                    <label>اختر الفني الميداني:</label>
                    <select name="technician_id" class="form-control" required>
                        @foreach($technicians as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>تاريخ الزيارة:</label>
                        <input type="date" name="scheduled_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group">
                        <label>الفترة المحددة:</label>
                        <select name="time_slot" class="form-control" required>
                            <option value="صباحاً (09:00 - 12:00)">صباحاً (09:00 - 12:00)</option>
                            <option value="ظهراً (12:00 - 03:00)">ظهراً (12:00 - 03:00)</option>
                            <option value="مساءً (04:00 - 07:00)">مساءً (04:00 - 07:00)</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('assignModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تأكيد الإسناد وإشعار الفني</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 10: New Customer (إضافة عميل جديد) -->
    <div id="newCustomerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #38bdf8;">+ إضافة عميل جديد إلى المنظومة</h3>
                <button class="close-btn" onclick="closeModal('newCustomerModal')">&times;</button>
            </div>

            <form action="{{ route('customers.create') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>الاسم الكامل للعميل:</label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: فهد عبد الله الدوسري" required>
                    </div>

                    <div class="form-group">
                        <label>رقم الجوال الأساسي (للتواصل والإشعارات):</label>
                        <input type="text" name="primary_phone" class="form-control num" placeholder="مثال: 0551234567" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>رقم جوال بديل (اختياري):</label>
                        <input type="text" name="secondary_phone" class="form-control num" placeholder="05xxxxxxxx">
                    </div>

                    <div class="form-group">
                        <label>البريد الإلكتروني (اختياري):</label>
                        <input type="email" name="email" class="form-control num" placeholder="customer@example.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>المدينة:</label>
                        <input type="text" name="city" class="form-control" value="الرياض" required>
                    </div>

                    <div class="form-group">
                        <label>الحي السكني:</label>
                        <input type="text" name="district" class="form-control" placeholder="مثال: حي الياسمين / حي النرجس">
                    </div>
                </div>

                <div class="form-group">
                    <label>الشارع والعنوان التفصيلي:</label>
                    <input type="text" name="street" class="form-control" placeholder="الشارع، رقم المبنى أو معلم بارز...">
                </div>

                <div class="form-group">
                    <label>ملاحظات إضافية عن العميل:</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="أي ملاحظات تخص أوقات الزيارة أو الموقع..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('newCustomerModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ وتسجيل العميل</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 11: Edit Customer (تعديل بيانات العميل) -->
    <div id="editCustomerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #38bdf8;">✏️ تعديل بيانات العميل (<span id="editCustomerNameTitle"></span>)</h3>
                <button class="close-btn" onclick="closeModal('editCustomerModal')">&times;</button>
            </div>

            <form id="editCustomerForm" method="POST" action="">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>اسم العميل:</label>
                        <input type="text" name="name" id="editCustName" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>رقم الجوال الأساسي:</label>
                        <input type="text" name="primary_phone" id="editCustPhone" class="form-control num" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>رقم جوال بديل:</label>
                        <input type="text" name="secondary_phone" id="editCustSecPhone" class="form-control num">
                    </div>

                    <div class="form-group">
                        <label>المدينة:</label>
                        <input type="text" name="city" id="editCustCity" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>الحي السكني:</label>
                        <input type="text" name="district" id="editCustDistrict" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>العنوان والشارع:</label>
                        <input type="text" name="street" id="editCustStreet" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label>ملاحظات:</label>
                    <textarea name="notes" id="editCustNotes" class="form-control" rows="2"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editCustomerModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 12: New Device (إضافة محطة تحلية وربطها بالعميل) -->
    <div id="newDeviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #10b981;">💧 + إضافة محطة تحلية جديدة وربطها بالعميل</h3>
                <button class="close-btn" onclick="closeModal('newDeviceModal')">&times;</button>
            </div>

            <form action="{{ route('devices.create') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label style="font-weight: 700; color: #38bdf8;">تحديد العميل المالك للمحطة:</label>
                        <select name="customer_selection_type" id="devCustomerType" class="form-control" onchange="toggleDeviceCustomerType(this.value)" required>
                            <option value="existing">🔍 اختيار من قائمة العملاء المسجلين</option>
                            <option value="new">✍️ كتابة وتعيين عميل جديد فوراً</option>
                        </select>
                    </div>

                    <div class="form-group" id="devExistingCustomerBox">
                        <label>اختر العميل المسجل:</label>
                        <select name="customer_id" id="newDeviceCustomerId" class="form-control">
                            @forelse($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->primary_phone }})</option>
                            @empty
                            <option value="">لا يوجد عملاء بعد - يمكنك كتابة عميل جديد بالأسفل</option>
                            @endforelse
                        </select>
                    </div>
                </div>

                <!-- حقول كتابة عميل جديد للمحطة (مخفية افتراضياً وتظهر عند اختيار كتابة عميل) -->
                <div id="devNewCustomerFields" style="display: none; background: rgba(56, 189, 248, 0.06); padding: 1rem; border-radius: 10px; margin-bottom: 1rem; border: 1px dashed rgba(56, 189, 248, 0.35);">
                    <div style="font-size: 0.85rem; font-weight: 700; color: #38bdf8; margin-bottom: 0.75rem;">
                        👤 بيانات العميل المالك للمحطة (سيتم حفظ العميل في النظام وربط محطة التحلية به وتوليد الفلاتر فوراً):
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>اسم العميل المالك:</label>
                            <input type="text" name="new_customer_name" id="devNewCustName" class="form-control" placeholder="مثال: عبد العزيز محمد">
                        </div>
                        <div class="form-group">
                            <label>رقم جوال العميل:</label>
                            <input type="text" name="new_customer_phone" id="devNewCustPhone" class="form-control num" placeholder="05XXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label>المدينة والحي:</label>
                            <input type="text" name="new_customer_city" id="devNewCustCity" class="form-control" placeholder="الرياض - حي الياسمين">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>موديل محطة التحلية:</label>
                        <select name="device_model_id" class="form-control">
                            @forelse($deviceModels as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->stages_count }} مراحل)</option>
                            @empty
                            <option value="">محطة تحلية منزلية قياسية 6 مراحل</option>
                            @endforelse
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>الرقم التسلسلي للمحطة (Serial Number):</label>
                        <input type="text" name="serial_number" class="form-control num" placeholder="مثال: SN-RO-2026-9901">
                    </div>

                    <div class="form-group">
                        <label>تاريخ التركيب والتشغيل:</label>
                        <input type="date" name="installation_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>مدة الضمان المعتمد (بالأشهر):</label>
                        <input type="number" name="warranty_months" class="form-control num" value="24" min="0">
                    </div>

                    <div class="form-group">
                        <label>توليد جدول استحقاقات الفلاتر تلقائياً؟</label>
                        <div style="padding-top: 0.5rem; font-size: 0.85rem; color: #10b981; font-weight: 700;">
                            ✅ نعم، سيقوم النظام تلقائياً بإنشاء مراحل الفلتر وحساب مواعيد الصيانة الدورية.
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>ملاحظات مكان التركيب (المطبخ، السطح، ضغط الماء):</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="مركب تحت الحوض مع خزان فيبر..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('newDeviceModal')">إلغاء</button>
                    <button type="submit" class="btn btn-success" style="background: #10b981;">حفظ وربط المحطة وتوليد الفلاتر</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 13: Edit Device (تعديل بيانات المحطة) -->
    <div id="editDeviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #38bdf8;">✏️ تعديل بيانات محطة التحلية (<span id="editDeviceCodeTitle"></span>)</h3>
                <button class="close-btn" onclick="closeModal('editDeviceModal')">&times;</button>
            </div>

            <form id="editDeviceForm" method="POST" action="">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>الرقم التسلسلي (SN):</label>
                        <input type="text" name="serial_number" id="editDevSerial" class="form-control num">
                    </div>

                    <div class="form-group">
                        <label>تاريخ التركيب:</label>
                        <input type="date" name="installation_date" id="editDevInstallDate" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>تاريخ نهاية الضمان:</label>
                        <input type="date" name="warranty_expiry_date" id="editDevWarrantyDate" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>حالة المحطة التشغيلية:</label>
                        <select name="status" id="editDevStatus" class="form-control" required>
                            <option value="active">نشطة وتعمل بشكل ممتاز (Active)</option>
                            <option value="maintenance_required">تحتاج لصيانة عاجلة</option>
                            <option value="inactive">متوقفة مؤقتاً</option>
                            <option value="retired">مستبعدة / تالفة</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>ملاحظات:</label>
                    <textarea name="notes" id="editDevNotes" class="form-control" rows="2"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editDeviceModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ تعديلات المحطة</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 14: New Filter Stage (إضافة مرحلة شمعة جديدة) -->
    <div id="newStageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #38bdf8;">💧 + إضافة وجدولة مرحلة فلتر لمحطة تحلية</h3>
                <button class="close-btn" onclick="closeModal('newStageModal')">&times;</button>
            </div>

            <form action="{{ route('lifecycle.stages.create') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>اختر المحطة المستهدفة:</label>
                    <select name="customer_device_id" id="newStageDeviceId" class="form-control" required>
                        @foreach($customers as $c)
                            @foreach($c->devices as $d)
                            <option value="{{ $d->id }}">#{{ $d->device_code }} - {{ $d->model?->name }} (العميل: {{ $c->name }})</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>رقم المرحلة:</label>
                        <input type="number" name="stage_number" class="form-control num" value="1" min="1" max="12" required>
                    </div>

                    <div class="form-group">
                        <label>اسم المرحلة والشمعة:</label>
                        <input type="text" name="stage_name_ar" class="form-control" placeholder="مثال: شمعة قطن بولي بروبلين 10 بوصة" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>دورة الاستبدال الدورية (بالأيام):</label>
                        <select name="service_interval_days" class="form-control" required>
                            <option value="90">90 يوم (3 أشهر - شمعة قطن)</option>
                            <option value="180">180 يوم (6 أشهر - كربون حبيبي أو صلب)</option>
                            <option value="360">360 يوم (سنة - بوست كربون أو معادن)</option>
                            <option value="720">720 يوم (سنتان - غشاء الممبرين RO)</option>
                            <option value="1095">1095 يوم (3 سنوات)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>تاريخ آخر استبدال / تركيب:</label>
                        <input type="date" name="last_actual_replacement_at" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('newStageModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ وجدولة الاستحقاق</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 15: Edit Stage Due Date (تعديل موعد استحقاق الشمعة) -->
    <div id="editStageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #38bdf8;">📅 تعديل موعد استحقاق (<span id="editStageNameTitle"></span>)</h3>
                <button class="close-btn" onclick="closeModal('editStageModal')">&times;</button>
            </div>

            <form id="editStageForm" method="POST" action="">
                @csrf
                <div class="form-group">
                    <label>اسم المرحلة والشمعة:</label>
                    <input type="text" name="stage_name_ar" id="editStageName" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>دورة الاستبدال (بالأيام):</label>
                        <input type="number" name="service_interval_days" id="editStageInterval" class="form-control num" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>تاريخ الاستحقاق القادم:</label>
                        <input type="date" name="next_due_at" id="editStageNextDue" class="form-control" required>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editStageModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الموعد الجديد</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 16: Edit Ticket (تعديل بيانات التذكرة) -->
    <div id="editTicketModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #38bdf8;">✏️ تعديل التذكرة رقم (<span id="editTicketNumTitle"></span>)</h3>
                <button class="close-btn" onclick="closeModal('editTicketModal')">&times;</button>
            </div>

            <form id="editTicketForm" method="POST" action="">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>نوع الخدمة:</label>
                        <select name="service_type" id="editTicketServiceType" class="form-control" required>
                            <option value="استبدال شمعات دورية">استبدال شمعات دورية</option>
                            <option value="صيانة وإصلاح عطل">صيانة وإصلاح عطل</option>
                            <option value="تركيب محطة جديدة">تركيب محطة جديدة</option>
                            <option value="فحص جودة وأملاح TDS">فحص جودة وأملاح TDS</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>الأولوية:</label>
                        <select name="priority" id="editTicketPriority" class="form-control" required>
                            <option value="normal">عادي</option>
                            <option value="high">مرتفع</option>
                            <option value="urgent">عاجل وطارئ</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>حالة التذكرة:</label>
                        <select name="status" id="editTicketStatus" class="form-control" required>
                            <option value="new">جديدة (بلاغ)</option>
                            <option value="assigned">مسندة لفني</option>
                            <option value="in_progress">قيد التنفيذ بالموقع</option>
                            <option value="completed_pending_review">منجزة وبانتظار الاعتماد</option>
                            <option value="approved_closed">مغلقة ومعتمدة</option>
                            <option value="cancelled">ملغية</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>الفني المكلف:</label>
                        <select name="technician_id" id="editTicketTech" class="form-control">
                            <option value="">-- بدون إسناد --</option>
                            @foreach($technicians as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>تاريخ الجدولة:</label>
                        <input type="date" name="scheduled_date" id="editTicketDate" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>الفترة المحددة:</label>
                        <input type="text" name="time_slot" id="editTicketTimeSlot" class="form-control" placeholder="صباحاً / ظهراً / مساءً">
                    </div>
                </div>

                <div class="form-group">
                    <label>ملاحظات العمل والقطع:</label>
                    <textarea name="notes" id="editTicketNotes" class="form-control" rows="2"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editTicketModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ تعديلات التذكرة</button>
                </div>
            </form>
        </div>
    </div>

    @if(!$isCallCenter)
    <!-- MODAL 17: Stock Audit & Physical Inventory Count (تسجيل جرد المخزون الفعلي والتسوية) -->
    <div id="stockAuditModal" class="modal">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header">
                <h3 style="font-weight: 700; color: #38bdf8;">📋 تسجيل جرد مخزني وتسوية دفتريّة</h3>
                <button class="close-btn" onclick="closeModal('stockAuditModal')">&times;</button>
            </div>

            <form action="{{ route('inventory.adjust') }}" method="POST" id="stockAuditForm">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label style="font-weight: 700; color: #38bdf8;">المستودع / موقع الجرد:</label>
                        <select name="warehouse_id" id="auditWarehouseId" class="form-control" required onchange="updateAuditBalances()">
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->type === 'vehicle' ? 'سيارة فني متنقلة' : 'مستودع رئيسي' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 700; color: #38bdf8;">الصنف / المنتج المراد جرده:</label>
                        <select name="product_id" id="auditProductId" class="form-control" required onchange="updateAuditBalances()">
                            @foreach($products as $p)
                            <option value="{{ $p->id }}" data-sku="{{ $p->sku }}" data-unit="{{ $p->unit ?? 'قطعة' }}">
                                {{ $p->name_ar }} ({{ $p->sku }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- شريط مقارنة الرصيد الدفتري والفعلي واحتساب الفارق -->
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem; margin-bottom: 1.25rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; text-align: center;">
                        <div style="padding: 0.5rem; background: rgba(56, 189, 248, 0.08); border-radius: 8px;">
                            <div style="font-size: 0.75rem; color: var(--text-dim);">الرصيد الدفتري بالنظام</div>
                            <div id="auditBookBalanceDisplay" style="font-size: 1.35rem; font-weight: 800; color: #38bdf8; margin-top: 0.25rem;">--</div>
                        </div>

                        <div style="padding: 0.5rem; background: rgba(16, 185, 129, 0.08); border-radius: 8px;">
                            <div style="font-size: 0.75rem; color: var(--text-dim);">الرصيد الفعلي بعد العد</div>
                            <div id="auditPhysicalCountDisplay" style="font-size: 1.35rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">--</div>
                        </div>

                        <div style="padding: 0.5rem; background: rgba(168, 85, 247, 0.08); border-radius: 8px;">
                            <div style="font-size: 0.75rem; color: var(--text-dim);">الفارق (عجز / فائض)</div>
                            <div id="auditDiffDisplay" style="font-size: 1.35rem; font-weight: 800; color: #a855f7; margin-top: 0.25rem;">0</div>
                        </div>
                    </div>

                    <div id="auditStatusBanner" style="margin-top: 0.75rem; padding: 0.5rem 0.8rem; border-radius: 6px; font-size: 0.825rem; text-align: center; font-weight: 700; display: none;"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label style="font-weight: 700;">الكمية الفعلية المحصورة بالجرد (Physical Count):</label>
                        <input type="number" name="physical_count" id="auditPhysicalInput" class="form-control num" min="0" required placeholder="أدخل الكمية الفعلية الموجودة على الرف" oninput="calculateAuditDifference()">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 700;">سبب الجرد والتسوية:</label>
                        <input type="text" name="reason" id="auditReasonInput" class="form-control" required placeholder="مثال: جرد ربع سنوي دوري">
                    </div>
                </div>

                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 1rem; align-items: center;">
                    <span style="font-size: 0.75rem; color: var(--text-dim);">أسباب شائعة:</span>
                    <button type="button" class="btn btn-outline" style="padding: 0.15rem 0.5rem; font-size: 0.725rem;" onclick="setAuditReason('جرد دوري ربع سنوي')">جرد دوري ربع سنوي</button>
                    <button type="button" class="btn btn-outline" style="padding: 0.15rem 0.5rem; font-size: 0.725rem;" onclick="setAuditReason('تسوية عجز - تالف أثناء النقل')">عجز تالف أثناء النقل</button>
                    <button type="button" class="btn btn-outline" style="padding: 0.15rem 0.5rem; font-size: 0.725rem;" onclick="setAuditReason('تسوية فائض - إرجاع مرتجع صيانة')">فائض مرتجع صيانة</button>
                    <button type="button" class="btn btn-outline" style="padding: 0.15rem 0.5rem; font-size: 0.725rem;" onclick="setAuditReason('تعديل رصيد افتتاحي')">تعديل رصيد افتتاحي</button>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('stockAuditModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #0284c7, #0369a1);">اعتماد التسوية الجردية وتحديث الرصيد</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- ================= JAVASCRIPT LOGIC ================= -->
    <script>
        const tabTitles = {
            'overview': 'لوحة المؤشرات والتشغيل العام',
            'appointments': 'إضافة وجدولة مواعيد العملاء والفنيين',
            'lifecycle': 'محرك استحقاقات الفلاتر والمواعيد',
            'tickets': 'إدارة تذاكر الصيانة والعمليات',
            'customers': 'ملفات العملاء ومحطات التحلية',
            'sales': 'شاشة المبيعات ونقاط البيع والفواتير الضريبية',
            'suppliers': 'شاشة الموردين وإدارة المشتريات والتوريد',
            'inventory': 'المخزون المركزي وسيارات الفنيين',
            'reports': 'تقارير الـ ERP والتحليلات وطباعة PDF',
            'invoices': 'دفتر الفواتير والتحصيل المالي',
            'users': 'إدارة الموظفين وفريق العمل',
            'roles': 'شاشة الصلاحيات المطلقة والتحكم بالأدوار (RBAC)',
            'audit': 'سجل التدقيق الأمني (Audit Trail)',
            'settings': 'إعدادات النظام وسياسات العمل'
        };

        // Cache Role Permissions from Backend
        const rolePermissionsData = {
            @if($isSuperAdmin)
                @foreach($allRoles as $r)
                    "{{ $r->id }}": [{{ $r->permissions->pluck('id')->implode(',') }}],
                @endforeach
            @endif
        };

        // Mobile Sidebar Controls
        function toggleMobileSidebar() {
            const sidebar = document.querySelector('aside.sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!sidebar) return;
            const isOpen = sidebar.classList.toggle('mobile-open');
            if (backdrop) backdrop.classList.toggle('active', isOpen);
            document.body.classList.toggle('sidebar-open-lock', isOpen);
        }

        function closeMobileSidebar() {
            document.querySelector('aside.sidebar')?.classList.remove('mobile-open');
            document.getElementById('sidebarBackdrop')?.classList.remove('active');
            document.body.classList.remove('sidebar-open-lock');
        }

        // Tab Switcher — saves active tab to localStorage so after form submit it restores
        function switchNavTab(tabId, btnEl) {
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(btn => btn.classList.remove('active'));
            if (btnEl) btnEl.classList.add('active');

            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            const targetPane = document.getElementById('tab-' + tabId);
            if (targetPane) targetPane.classList.add('active');

            const breadcrumbEl = document.getElementById('activeBreadcrumb');
            if (breadcrumbEl && tabTitles[tabId]) {
                breadcrumbEl.innerText = tabTitles[tabId];
            }

            if (tabId === 'roles') {
                const selector = document.getElementById('roleSelector');
                if (selector) loadRolePermissions(selector.value);
            }

            if (window.innerWidth <= 1024) {
                closeMobileSidebar();
            }

            // Persist active tab across page reloads (after form submit)
            try { localStorage.setItem('erp_active_tab', tabId); } catch(e){}

            // Stamp all forms on page with the current active tab
            document.querySelectorAll('form[method]').forEach(f => {
                let inp = f.querySelector('input[name="_active_tab"]');
                if (!inp) {
                    inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = '_active_tab';
                    f.appendChild(inp);
                }
                inp.value = tabId;
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Sub-tabs for Sales
        function switchSalesSubTab(subId, btnEl) {
            document.querySelectorAll('#tab-sales .sub-pill').forEach(b => b.classList.remove('active'));
            if (btnEl) btnEl.classList.add('active');

            document.getElementById('sales-sub-invoices').style.display = (subId === 'invoices') ? 'block' : 'none';
            document.getElementById('sales-sub-orders').style.display = (subId === 'orders') ? 'block' : 'none';
        }

        // Sub-tabs for Suppliers
        function switchSuppliersSubTab(subId, btnEl) {
            document.querySelectorAll('#tab-suppliers .sub-pill').forEach(b => b.classList.remove('active'));
            if (btnEl) btnEl.classList.add('active');

            document.getElementById('suppliers-sub-directory').style.display = (subId === 'directory') ? 'block' : 'none';
            document.getElementById('suppliers-sub-orders').style.display = (subId === 'orders') ? 'block' : 'none';
        }

        // Role Permissions Load & Form Update
        function loadRolePermissions(roleId) {
            const form = document.getElementById('rolePermissionsForm');
            if (!form) return;

            form.action = '/admin/roles/' + roleId + '/permissions';

            const activePermIds = rolePermissionsData[roleId] || [];
            document.querySelectorAll('.perm-chk').forEach(chk => {
                const pId = parseInt(chk.dataset.permId);
                chk.checked = activePermIds.includes(pId);
            });
        }

        function submitRolePermissionsForm() {
            const form = document.getElementById('rolePermissionsForm');
            if (form) form.submit();
        }

        function toggleAllRolePermissions(select) {
            document.querySelectorAll('.perm-chk').forEach(chk => chk.checked = select);
        }

        function toggleModulePerms(module) {
            const perms = document.querySelectorAll('.perm-mod-' + module);
            if (!perms.length) return;
            const anyUnchecked = Array.from(perms).some(p => !p.checked);
            perms.forEach(p => p.checked = anyUnchecked);
        }

        // Modal Controls
        function openNewSaleModal() { document.getElementById('newSaleModal').classList.add('active'); }
        function openNewSupplierModal() { document.getElementById('newSupplierModal').classList.add('active'); }
        function openNewPurchaseOrderModal() { document.getElementById('newPurchaseOrderModal').classList.add('active'); }
        function openNewRoleModal() { document.getElementById('newRoleModal').classList.add('active'); }
        function openNewUserModal() { document.getElementById('newUserModal').classList.add('active'); }
        function openNewPermissionModal() { document.getElementById('newPermissionModal').classList.add('active'); }
        function openNewTicketModal() { document.getElementById('newTicketModal').classList.add('active'); }

        function openNewPurchaseOrderForSupplier(supplierId) {
            openNewPurchaseOrderModal();
            const sel = document.getElementById('poSupplierSelector');
            if (sel) sel.value = supplierId;
        }

        function openPaymentModal(invoiceId, invoiceNumber, balanceDue) {
            document.getElementById('payInvoiceNum').innerText = invoiceNumber;
            document.getElementById('payBalanceDue').innerText = parseFloat(balanceDue).toFixed(2);
            document.getElementById('payAmountInput').value = parseFloat(balanceDue).toFixed(2);
            document.getElementById('payAmountInput').max = parseFloat(balanceDue).toFixed(2);
            document.getElementById('recordPaymentForm').action = '/sales/invoices/' + invoiceId + '/payment';
            document.getElementById('recordPaymentModal').classList.add('active');
        }

        function openAssignModal(ticketId, ticketNumber) {
            document.getElementById('assignTicketNumber').innerText = ticketNumber;
            document.getElementById('assignForm').action = '/tickets/' + ticketId + '/assign';
            document.getElementById('assignModal').classList.add('active');
        }

        function openCreateTicketForStage(customerId, deviceId, stageName) {
            openNewTicketModal();
            if (customerId) {
                document.getElementById('modalCustomerId').value = customerId;
                filterCustomerDevicesInModal(customerId);
            }
            if (deviceId) document.getElementById('modalDeviceId').value = deviceId;
            const notesEl = document.querySelector('textarea[name="notes"]');
            if (notesEl) notesEl.value = 'استبدال شمعة مستحقة: ' + stageName;
        }

        function openNewTicketModalForCustomer(customerId, customerName) {
            openNewTicketModal();
            const custSel = document.getElementById('modalCustomerId');
            if (custSel) {
                custSel.value = customerId;
                filterCustomerDevicesInModal(customerId);
            }
        }

        function filterCustomerDevicesInModal(customerId) {
            const devSel = document.getElementById('modalDeviceId');
            if (!devSel) return;
            let found = false;
            Array.from(devSel.options).forEach(opt => {
                if (!opt.value) {
                    opt.style.display = 'block';
                } else {
                    const match = (opt.dataset.customerId == customerId);
                    opt.style.display = match ? 'block' : 'none';
                    if (match && !found) {
                        devSel.value = opt.value;
                        found = true;
                    }
                }
            });
            if (!found) {
                devSel.value = '';
            }
        }

        function filterCustomerDevicesInEmbedded(customerId) {
            const devSel = document.getElementById('embeddedDeviceId');
            if (!devSel) return;
            let found = false;
            Array.from(devSel.options).forEach(opt => {
                if (!opt.value) {
                    opt.style.display = 'block';
                } else {
                    const match = (opt.dataset.customerId == customerId);
                    opt.style.display = match ? 'block' : 'none';
                    if (match && !found) {
                        devSel.value = opt.value;
                        found = true;
                    }
                }
            });
            if (!found) {
                devSel.value = '';
            }
        }

        function closeModal(modalId) {
            const m = document.getElementById(modalId);
            if (m) m.classList.remove('active');
        }

        // Customers & Stations Helpers
        function openNewCustomerModal() {
            document.getElementById('newCustomerModal').classList.add('active');
        }

        function openEditCustomerModal(id, name, phone, secPhone, city, district, street, notes) {
            document.getElementById('editCustomerNameTitle').innerText = name;
            document.getElementById('editCustName').value = name;
            document.getElementById('editCustPhone').value = phone;
            document.getElementById('editCustSecPhone').value = secPhone || '';
            document.getElementById('editCustCity').value = city || 'الرياض';
            document.getElementById('editCustDistrict').value = district || '';
            document.getElementById('editCustStreet').value = street || '';
            document.getElementById('editCustNotes').value = notes || '';
            document.getElementById('editCustomerForm').action = '/customers/' + id + '/update';
            document.getElementById('editCustomerModal').classList.add('active');
        }

        function toggleDeviceCustomerType(type) {
            const existingBox = document.getElementById('devExistingCustomerBox');
            const newBox = document.getElementById('devNewCustomerFields');
            if (type === 'new') {
                if (existingBox) existingBox.style.display = 'none';
                if (newBox) newBox.style.display = 'block';
            } else {
                if (existingBox) existingBox.style.display = 'block';
                if (newBox) newBox.style.display = 'none';
            }
        }

        function openNewDeviceModal() {
            const typeSel = document.getElementById('devCustomerType');
            if (typeSel) {
                typeSel.value = 'existing';
                toggleDeviceCustomerType('existing');
            }
            document.getElementById('newDeviceModal').classList.add('active');
        }

        function openNewDeviceModalForCustomer(customerId, customerName) {
            openNewDeviceModal();
            const typeSel = document.getElementById('devCustomerType');
            if (typeSel) {
                typeSel.value = 'existing';
                toggleDeviceCustomerType('existing');
            }
            const sel = document.getElementById('newDeviceCustomerId');
            if (sel) sel.value = customerId;
        }

        // ================= STOCK AUDIT LOGIC =================
        const productBalancesMap = {
            @foreach($products as $p)
            {{ $p->id }}: {
                sku: '{{ $p->sku }}',
                name: '{{ addslashes($p->name_ar) }}',
                unit: '{{ $p->unit ?? "قطعة" }}',
                warehouses: {
                    @foreach($p->stockBalances as $sb)
                    {{ $sb->warehouse_id }}: {{ $sb->quantity }},
                    @endforeach
                }
            },
            @endforeach
        };

        function openStockAuditModal() {
            document.getElementById('stockAuditModal').classList.add('active');
            updateAuditBalances();
        }

        function openStockAuditModalForProduct(productId, productName) {
            openStockAuditModal();
            const pSel = document.getElementById('auditProductId');
            if (pSel) pSel.value = productId;
            updateAuditBalances();
        }

        function updateAuditBalances() {
            const wId = document.getElementById('auditWarehouseId')?.value;
            const pId = document.getElementById('auditProductId')?.value;
            const bookEl = document.getElementById('auditBookBalanceDisplay');

            if (!pId || !productBalancesMap[pId]) {
                if (bookEl) {
                    bookEl.innerText = '0 قطعة';
                    bookEl.dataset.currentQty = 0;
                }
                calculateAuditDifference();
                return;
            }

            const pData = productBalancesMap[pId];
            const currentQty = (pData.warehouses && pData.warehouses[wId] !== undefined) ? parseInt(pData.warehouses[wId], 10) : 0;

            if (bookEl) {
                bookEl.innerText = currentQty + ' ' + pData.unit;
                bookEl.dataset.currentQty = currentQty;
            }

            calculateAuditDifference();
        }

        function calculateAuditDifference() {
            const bookEl = document.getElementById('auditBookBalanceDisplay');
            const physInput = document.getElementById('auditPhysicalInput');
            const physDisplay = document.getElementById('auditPhysicalCountDisplay');
            const diffDisplay = document.getElementById('auditDiffDisplay');
            const banner = document.getElementById('auditStatusBanner');

            const currentQty = parseInt(bookEl?.dataset.currentQty || '0', 10);
            const val = physInput?.value;

            if (val === '' || isNaN(val)) {
                if (physDisplay) physDisplay.innerText = '--';
                if (diffDisplay) {
                    diffDisplay.innerText = '0';
                    diffDisplay.style.color = '#a855f7';
                }
                if (banner) banner.style.display = 'none';
                return;
            }

            const physical = parseInt(val, 10);
            if (physDisplay) physDisplay.innerText = physical;

            const diff = physical - currentQty;
            if (diffDisplay) {
                if (diff > 0) {
                    diffDisplay.innerText = '+' + diff;
                    diffDisplay.style.color = '#10b981';
                } else if (diff < 0) {
                    diffDisplay.innerText = diff;
                    diffDisplay.style.color = '#ef4444';
                } else {
                    diffDisplay.innerText = '0';
                    diffDisplay.style.color = '#38bdf8';
                }
            }

            if (banner) {
                banner.style.display = 'block';
                if (diff > 0) {
                    banner.style.background = 'rgba(16, 185, 129, 0.15)';
                    banner.style.color = '#10b981';
                    banner.innerText = '🟢 يوجد فائض مخزني قدره ' + diff + ' قطعة سيتم إضافته إلى الرصيد الدفتري.';
                } else if (diff < 0) {
                    banner.style.background = 'rgba(239, 68, 68, 0.15)';
                    banner.style.color = '#ef4444';
                    banner.innerText = '🔴 يوجد عجز مخزني قدره ' + Math.abs(diff) + ' قطعة سيتم خصمه وإثباته كعجز.';
                } else {
                    banner.style.background = 'rgba(56, 189, 248, 0.15)';
                    banner.style.color = '#38bdf8';
                    banner.innerText = '🔵 الرصيد الفعلي مطابق تماماً للرصيد الدفتري (لا يوجد فروقات).';
                }
            }
        }

        function setAuditReason(text) {
            const input = document.getElementById('auditReasonInput');
            if (input) input.value = text;
        }

        function openEditDeviceModal(id, code, serial, installDate, warrantyDate, status, notes) {
            document.getElementById('editDeviceCodeTitle').innerText = code;
            document.getElementById('editDevSerial').value = serial || '';
            document.getElementById('editDevInstallDate').value = installDate || '';
            document.getElementById('editDevWarrantyDate').value = warrantyDate || '';
            document.getElementById('editDevStatus').value = status || 'active';
            document.getElementById('editDevNotes').value = notes || '';
            document.getElementById('editDeviceForm').action = '/devices/' + id + '/update';
            document.getElementById('editDeviceModal').classList.add('active');
        }

        function toggleCustomerDevicesSection(customerId) {
            const row = document.getElementById('custDevicesRow_' + customerId);
            if (!row) return;
            row.style.display = (row.style.display === 'none' || !row.style.display) ? 'table-row' : 'none';
        }

        // Lifecycle & Stages Helpers
        function openNewStageModal() {
            document.getElementById('newStageModal').classList.add('active');
        }

        function openNewStageModalForDevice(deviceId, deviceCode, modelName) {
            openNewStageModal();
            const sel = document.getElementById('newStageDeviceId');
            if (sel) sel.value = deviceId;
        }

        function openEditStageModal(id, name, interval, nextDue) {
            document.getElementById('editStageNameTitle').innerText = name;
            document.getElementById('editStageName').value = name;
            document.getElementById('editStageInterval').value = interval;
            document.getElementById('editStageNextDue').value = nextDue;
            document.getElementById('editStageForm').action = '/lifecycle/stages/' + id + '/update';
            document.getElementById('editStageModal').classList.add('active');
        }

        // Ticket Helpers
        function openEditTicketModal(id, num, serviceType, priority, status, techId, schedDate, notes) {
            document.getElementById('editTicketNumTitle').innerText = num;
            document.getElementById('editTicketServiceType').value = serviceType;
            document.getElementById('editTicketPriority').value = priority;
            document.getElementById('editTicketStatus').value = status;
            document.getElementById('editTicketTech').value = techId || '';
            document.getElementById('editTicketDate').value = schedDate || '';
            document.getElementById('editTicketNotes').value = notes || '';
            document.getElementById('editTicketForm').action = '/tickets/' + id + '/update';
            document.getElementById('editTicketModal').classList.add('active');
        }

        function toggleCustomerType(val) {
            document.getElementById('existingCustomerBox').style.display = (val === 'existing') ? 'block' : 'none';
            document.getElementById('newCustomerFields').style.display = (val === 'new') ? 'block' : 'none';
        }

        // POS Dynamic Rows
        let posRowIndex = 1;
        const availableProducts = [
            @foreach($products as $p)
            { id: {{ $p->id }}, sku: "{{ $p->sku }}", name: "{{ $p->name_ar }}", price: {{ (float)$p->selling_price }} },
            @endforeach
        ];

        function addPosItemRow() {
            const idx = posRowIndex++;
            const container = document.getElementById('posItemsContainer');
            
            let optionsHtml = '<option value="">اختر الصنف...</option>';
            availableProducts.forEach(p => {
                optionsHtml += `<option value="${p.id}" data-price="${p.price}">${p.name} (${p.sku}) — ${p.price.toFixed(2)} ر.س</option>`;
            });

            const rowHtml = `
                <div class="pos-item-box" id="posRow_${idx}">
                    <div class="form-row" style="grid-template-columns: 3fr 1fr 1fr 1fr auto; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>الصنف:</label>
                            <select name="items[${idx}][product_id]" class="form-control pos-prod-select" onchange="updatePosItemPrice(${idx}, this)" required>
                                ${optionsHtml}
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>سعر الوحدة:</label>
                            <input type="number" step="0.01" name="items[${idx}][unit_price]" id="posUnitPrice_${idx}" class="form-control num" placeholder="0.00" oninput="calculatePosTotals()" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>الكمية:</label>
                            <input type="number" name="items[${idx}][quantity]" id="posQty_${idx}" class="form-control num" value="1" min="1" oninput="calculatePosTotals()" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>الخصم:</label>
                            <input type="number" step="0.01" name="items[${idx}][discount]" id="posDisc_${idx}" class="form-control num" value="0.00" oninput="calculatePosTotals()">
                        </div>
                        <button type="button" class="close-btn" style="color: #fb7185; padding-bottom: 8px;" onclick="removePosItemRow(${idx})">&times;</button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
        }

        function removePosItemRow(idx) {
            const row = document.getElementById('posRow_' + idx);
            if (row) row.remove();
            calculatePosTotals();
        }

        function updatePosItemPrice(idx, selectEl) {
            const opt = selectEl.options[selectEl.selectedIndex];
            const price = parseFloat(opt.getAttribute('data-price')) || 0;
            const priceInput = document.getElementById('posUnitPrice_' + idx);
            if (priceInput) priceInput.value = price.toFixed(2);
            calculatePosTotals();
        }

        function calculatePosTotals() {
            let subtotal = 0;
            let totalDiscount = 0;

            document.querySelectorAll('#posItemsContainer .pos-item-box').forEach(box => {
                const priceInput = box.querySelector('input[name*="[unit_price]"]');
                const qtyInput = box.querySelector('input[name*="[quantity]"]');
                const discInput = box.querySelector('input[name*="[discount]"]');

                const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
                const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
                const disc = parseFloat(discInput ? discInput.value : 0) || 0;

                subtotal += (price * qty);
                totalDiscount += disc;
            });

            const netSubtotal = Math.max(0, subtotal - totalDiscount);
            const vat = netSubtotal * 0.15;
            const grandTotal = netSubtotal + vat;

            document.getElementById('posSubtotalDisp').innerText = netSubtotal.toFixed(2);
            document.getElementById('posTaxDisp').innerText = vat.toFixed(2);
            document.getElementById('posGrandTotalDisp').innerText = grandTotal.toFixed(2) + ' ر.س';

            const method = document.getElementById('posPaymentMethod').value;
            const paidInput = document.getElementById('posPaidAmount');
            if (method !== 'credit' && paidInput) {
                paidInput.value = grandTotal.toFixed(2);
            }
        }

        function handlePaymentMethodChange(val) {
            const paidInput = document.getElementById('posPaidAmount');
            if (val === 'credit') {
                if (paidInput) paidInput.value = "0.00";
            } else {
                calculatePosTotals();
            }
        }

        // Reports Selector & Live Print URL
        let currentReportType = 'sales';

        function selectReportModule(type, cardEl) {
            currentReportType = type;
            document.querySelectorAll('#tab-reports .kpi-card').forEach(c => c.style.border = 'none');
            if (cardEl) cardEl.style.border = '2px solid #38bdf8';

            const period = document.getElementById('reportPeriodSelector').value;
            updateReportPrintUrl(type, period);

            const titleMap = {
                'sales': 'معاينة حية: تقرير المبيعات والتحصيل المالي الشامل',
                'procurement': 'معاينة حية: تقرير المشتريات والموردين وحركة التوريد',
                'inventory': 'معاينة حية: تقرير تقييم المخزون وأرصدة المستودعات',
                'maintenance': 'معاينة حية: تقرير عمليات الصيانة ومحرك استحقاقات الفلاتر'
            };
            document.getElementById('reportPreviewTitle').innerText = titleMap[type] || 'معاينة التقرير';
        }

        function updateReportPrintUrl(type, period) {
            const url = `/reports/print?type=${type}&period=${period}`;
            const btn = document.getElementById('btnDirectReportPrint');
            if (btn) btn.href = url;
        }

        function printCurrentSelectedReport() {
            const period = document.getElementById('reportPeriodSelector').value;
            window.open(`/reports/print?type=${currentReportType}&period=${period}`, '_blank');
        }

        document.getElementById('reportPeriodSelector')?.addEventListener('change', (e) => {
            updateReportPrintUrl(currentReportType, e.target.value);
        });

        // Keyboard & Backdrop dismissal
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
            }
        });

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('active');
            });
        });

        // Initialize on load — restore last active tab from localStorage or from session
        document.addEventListener('DOMContentLoaded', () => {
            const selector = document.getElementById('roleSelector');
            if (selector) loadRolePermissions(selector.value);

            // Determine which tab to open:
            // 1) Session hint from backend (after form submit)
            // 2) localStorage (last tab the user was on)
            // 3) Default to 'overview'
            @if(session('_active_tab'))
                const savedTab = '{{ session("_active_tab") }}';
            @else
                let savedTab = null;
                try { savedTab = localStorage.getItem('erp_active_tab'); } catch(e){}
            @endif

            const validTabs = Object.keys(tabTitles);
            const tabToOpen = (savedTab && validTabs.includes(savedTab)) ? savedTab : 'overview';

            // Find the corresponding nav button and activate
            const navBtn = document.querySelector(`.nav-link[onclick*="'${tabToOpen}'"]`);
            switchNavTab(tabToOpen, navBtn);

            // Stamp all forms now so first interaction is already stamped
            document.querySelectorAll('form[method]').forEach(f => {
                if (!f.querySelector('input[name="_active_tab"]')) {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = '_active_tab';
                    inp.value = tabToOpen;
                    f.appendChild(inp);
                }
            });
        });
    </script>
</body>
</html>
