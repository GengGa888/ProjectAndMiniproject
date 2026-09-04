<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบผู้ดูแลระบบ (Admin Console)</title>
    <!-- Google Fonts & FontAwesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --sidebar-bg: #1e1b4b;
            --sidebar-text: #c7d2fe;
            --sidebar-active: #312e81;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        aside {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 24px 20px;
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand i {
            color: #818cf8;
            font-size: 1.5rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 12px;
            flex-grow: 1;
        }

        .sidebar-menu li {
            margin-bottom: 8px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 400;
        }

        .sidebar-menu a:hover, .sidebar-menu li.active a {
            background-color: var(--sidebar-active);
            color: #ffffff;
        }

        .sidebar-menu a i {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: #fca5a5;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background-color: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        /* Main Content Styling */
        main {
            margin-left: 260px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: var(--card-bg);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-info .name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-info .role {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Content Body */
        .content-body {
            padding: 32px;
            flex-grow: 1;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background-color: var(--card-bg);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.blue { background-color: #e0e7ff; color: var(--primary-color); }
        .stat-icon.green { background-color: #d1fae5; color: var(--success); }
        .stat-icon.yellow { background-color: #fef3c7; color: var(--warning); }

        .stat-info h3 {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .stat-info .number {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Card Section */
        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 32px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .btn {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background-color: var(--primary-hover);
        }

        /* Table Styling */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 14px 24px;
            font-size: 0.9rem;
        }

        th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge.success { background-color: #d1fae5; color: #065f46; }
        .badge.warning { background-color: #fef3c7; color: #92400e; }
        .badge.danger { background-color: #fee2e2; color: #991b1b; }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 0.8rem;
            border-radius: 4px;
        }

        .btn-edit { background-color: #3b82f6; color: white; }
        .btn-edit:hover { background-color: #2563eb; }
        
        .btn-delete { background-color: var(--danger); color: white; }
        .btn-delete:hover { background-color: #dc2626; }

        /* Responsive */
        @media(max-width: 768px) {
            aside { width: 70px; }
            aside .sidebar-brand span, 
            aside .sidebar-menu span, 
            aside .sidebar-footer span { display: none; }
            main { margin-left: 70px; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Menu -->
    <aside>
        <div class="sidebar-brand">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Admin Panel</span>
        </div>
        <ul class="sidebar-menu">
            <li class="active"><a href="#"><i class="fa-solid fa-chart-pie"></i> <span>แดชบอร์ด</span></a></li>
            <li><a href="#"><i class="fa-solid fa-folder-open"></i> <span>จัดการโครงงาน</span></a></li>
            <li><a href="#"><i class="fa-solid fa-users"></i> <span>จัดการผู้ใช้งาน</span></a></li>
            <li><a href="#"><i class="fa-solid fa-gear"></i> <span>ตั้งค่าระบบ</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="#" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> <span>ออกจากระบบ</span></a>
        </div>
    </aside>

    <!-- Main Section -->
    <main>
        <!-- Header -->
        <header>
            <div class="header-title">
                <h1>ภาพรวมระบบ (Dashboard)</h1>
            </div>
            <div class="user-profile">
                <div class="user-avatar">A</div>
                <div class="user-info">
                    <div class="name">Administrator</div>
                    <div class="role">ผู้ดูแลระบบสูงสุด</div>
                </div>
            </div>
        </header>

        <!-- Content Body -->
        <div class="content-body">
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fa-solid fa-folder-closed"></i>
                    </div>
                    <div class="stat-info">
                        <h3>โครงงานทั้งหมด</h3>
                        <div class="number">24</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>อนุมัติแล้ว</h3>
                        <div class="number">18</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>รอตรวจสอบ</h3>
                        <div class="number">6</div>
                    </div>
                </div>
            </div>

            <!-- Project Table Section -->
            <div class="card">
                <div class="card-header">
                    <h2>รายการโครงงานล่าสุด</h2>
                    <a href="#" class="btn"><i class="fa-solid fa-plus"></i> เพิ่มโครงงาน</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ชื่อโครงงาน</th>
                                <th>ผู้จัดทำ</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>ระบบจัดการร้านค้าออนไลน์ด้วย AI</td>
                                <td>นายสมชาย เรียนดี</td>
                                <td><span class="badge success">อนุมัติแล้ว</span></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn btn-sm btn-edit"><i class="fa-solid fa-pen"></i></button>
                                        <button class="btn btn-sm btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>แอปพลิเคชันเตือนภัยน้ำท่วมฉับพลัน</td>
                                <td>นางสาวสมหญิง รักเรียน</td>
                                <td><span class="badge warning">รอตรวจสอบ</span></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn btn-sm btn-edit"><i class="fa-solid fa-pen"></i></button>
                                        <button class="btn btn-sm btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>ระบบตรวจจับใบหน้าเข้า-ออกอาคาร</td>
                                <td>นายกิตติศักดิ์ พัฒนา</td>
                                <td><span class="badge danger">ไม่อนุมัติ</span></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn btn-sm btn-edit"><i class="fa-solid fa-pen"></i></button>
                                        <button class="btn btn-sm btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</body>
</html>