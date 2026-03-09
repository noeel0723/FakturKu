<?php
/**
 * Layout Header - modern clean UI
 */
$pageTitle = $pageTitle ?? APP_NAME;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@1,600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --blue-0: #F0F0F0;
            --blue-1: #D1D9DF;
            --blue-2: #A0B2C2;
            --blue-3: #2A384C;
            --ink: #2A384C;
            --muted: #6f7f8f;
            --panel: #ffffff;
            --ring: rgba(160, 178, 194, 0.35);
            --success: #26b896;
            --warning: #efb341;
            --danger: #d26b6b;
        }
        body {
            background: radial-gradient(circle at top right, #d9e1e7 0%, #f0f0f0 35%, #f0f0f0 100%);
            font-family: 'DM Sans', sans-serif;
            color: var(--ink);
        }
        .brand-title { font-family: 'Playfair Display', serif; font-style: italic; letter-spacing: 0.5px; }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, #243246 0%, #2A384C 100%);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 12px 0 30px rgba(42, 56, 76, 0.2);
        }
        .sidebar .brand {
            padding: 24px 20px;
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,.09);
        }
        .sidebar .brand span { color: #c5d3de; }
        .sidebar .nav-link {
            color: #d7e0e8;
            padding: 12px 20px;
            font-size: 14px;
            border-left: 3px solid transparent;
            transition: all .2s;
            border-radius: 0 10px 10px 0;
            margin-right: 10px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.1);
            border-left-color: #dbe5ec;
        }
        .sidebar .nav-link i { width: 20px; margin-right: 10px; }
        .main-content { margin-left: 250px; padding: 22px 28px; }
        .shell {
            background: rgba(255,255,255,0.72);
            border: 1px solid rgba(160, 178, 194, 0.35);
            border-radius: 18px;
            backdrop-filter: blur(6px);
            box-shadow: 0 12px 24px rgba(42, 56, 76, 0.08);
            overflow: hidden;
        }
        .shell-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid #e6ebef;
            background: rgba(255,255,255,0.8);
        }
        .shell-title { font-weight: 700; font-size: 18px; }
        .shell-search { max-width: 260px; }
        .shell-search input {
            border-radius: 10px;
            border: 1px solid #dfe6ec;
            background: #f7f9fb;
        }
        .shell-content { padding: 22px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-header h1 { font-size: 28px; font-weight: 700; color: var(--ink); margin: 0; }
        .card {
            border: 1px solid #e5eaef;
            box-shadow: 0 10px 18px rgba(42,56,76,.06);
            border-radius: 14px;
            background: var(--panel);
        }
        .card-header { background: #fff; border-bottom: 1px solid #eef2f5; font-weight: 700; }
        .table { margin-bottom: 0; }
        .table th {
            background: #f8fafb;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: .55px;
            border-bottom: 1px solid #e5ebef;
        }
        .table td { vertical-align: middle; color: #3b4a5b; }
        .badge-status { padding: 6px 12px; border-radius: 24px; font-size: 12px; font-weight: 600; }
        .stat-pill {
            border-radius: 12px;
            border: 1px solid #dce4ea;
            background: linear-gradient(140deg, #ffffff, #f2f5f7);
            padding: 12px 14px;
        }
        .btn-primary {
            background: var(--blue-3);
            border-color: var(--blue-3);
            border-radius: 10px;
        }
        .btn-primary:hover { background: #202c3e; border-color: #202c3e; }
        .btn-outline-primary {
            border-color: var(--blue-2);
            color: var(--blue-3);
        }
        .btn-outline-primary:hover {
            background: var(--blue-3);
            border-color: var(--blue-3);
        }
        .text-muted { color: var(--muted) !important; }
        @media (max-width: 992px) {
            .sidebar { position: static; width: 100%; min-height: auto; }
            .main-content { margin-left: 0; padding: 14px; }
            .shell-topbar { flex-direction: column; align-items: start; gap: 10px; }
            .shell-search { width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="brand brand-title"><span>Faktur</span>Ku</div>
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link <?= ($_GET['url'] ?? '') === '' || ($_GET['url'] ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/">
                    <i class="bi bi-grid-1x2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_GET['url'] ?? '', 'clients') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/clients">
                    <i class="bi bi-people"></i> Klien
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_GET['url'] ?? '', 'products') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/products">
                    <i class="bi bi-box-seam"></i> Produk/Jasa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_GET['url'] ?? '', 'invoices') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/invoices">
                    <i class="bi bi-receipt"></i> Invoice
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="shell">
            <div class="shell-topbar">
                <div class="shell-title"><?= e($pageTitle) ?></div>
                <div class="d-flex align-items-center gap-2 w-100 justify-content-end">
                    <div class="shell-search">
                        <input type="text" class="form-control form-control-sm" placeholder="Search..." readonly>
                    </div>
                    <button class="btn btn-light btn-sm" type="button"><i class="bi bi-envelope"></i></button>
                    <button class="btn btn-light btn-sm" type="button"><i class="bi bi-bell"></i></button>
                </div>
            </div>
            <div class="shell-content">
                <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
