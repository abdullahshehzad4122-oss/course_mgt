<?php
/**
 * Dynamic Header Component - Presentation Layer
 * Implements Section 7 and Section 3.1 (dynamic branding)
 */
global $pdo; // Required for database access

// Get app branding
$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_name = 'app_name'");
$app_name = $stmt->fetchColumn() ?: 'Course Management System';

$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_name = 'app_logo'");
$app_logo = $stmt->fetchColumn() ?: 'logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?></title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Theme style (AdminLTE 3.2.0 & Bootstrap 4.6) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Premium Glassmorphism UI/UX Overrides -->
    <style>
        :root {
            --primary: #6c63ff;
            --primary-dark: #4f46e5;
            --primary-glow: rgba(108, 99, 255, 0.35);
            --secondary: #64748b;
            --success: #10b981;
            --info: #0ea5e9;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #0f172a;
            --background: #f1f5f9;
        }
        
        body {
            background-color: var(--background);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #334155;
            letter-spacing: -0.01em;
        }

        /* ── Modern Card Styling (Glassmorphism) ── */
        .card {
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05) !important;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04) !important;
        }
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            padding: 1.25rem 1.5rem;
        }
        .card-title {
            font-weight: 700;
            color: var(--dark);
            font-size: 1.15rem;
            letter-spacing: -0.02em;
        }

        /* ── Improved Info Boxes ── */
        .info-box {
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04) !important;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 1rem;
            min-height: 100px;
            overflow: hidden;
            position: relative;
        }
        .info-box-icon {
            border-radius: 0.75rem !important;
            width: 65px;
            font-size: 1.6rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .info-box-text {
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
        }
        .info-box-number {
            color: var(--dark);
            font-size: 1.75rem !important;
            font-weight: 800;
        }

        /* ── Sidebar Styling ── */
        .main-sidebar {
            background: #0f172a; /* Deep Slate */
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
        }
        .brand-link {
            border-bottom: 1px solid rgba(255,255,255,0.05) !important;
            background: rgba(0,0,0,0.15);
            padding: 1.2rem 0.5rem;
        }
        .brand-text {
            font-family: 'Inter', sans-serif;
            font-weight: 700 !important;
            color: #ffffff;
            letter-spacing: -0.03em;
        }
        .user-panel {
            border-bottom: 1px solid rgba(255,255,255,0.05) !important;
        }
        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link {
            border-radius: 0.5rem;
            margin: 0.3rem 0.8rem;
            color: #cbd5e1;
            font-weight: 500;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link:hover {
            background-color: rgba(255,255,255,0.08);
            color: #ffffff;
            transform: translateX(4px);
        }
        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px var(--primary-glow);
            font-weight: 600;
        }

        /* ── Navbar & Top Elements ── */
        .main-header {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.04) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .navbar-light .navbar-nav .nav-link {
            color: #475569;
        }
        
        /* ── Modern Buttons ── */
        .btn {
            border-radius: 0.5rem;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
            letter-spacing: -0.01em;
        }
        .btn-sm {
            padding: 0.25rem 0.6rem;
            border-radius: 0.4rem;
        }
        /* Button Gradients */
        .btn-primary { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); 
            border: none; 
            box-shadow: 0 4px 12px var(--primary-glow); 
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 15px var(--primary-glow); }
        
        .btn-success { 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
            border: none; 
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); 
        }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4); }
        
        .btn-info { 
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); 
            border: none; 
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3); 
        }
        .btn-info:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(14, 165, 233, 0.4); }
        
        .btn-warning { 
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
            border: none; 
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); 
            color: #fff !important;
        }
        .btn-warning:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(245, 158, 11, 0.4); color: #fff; }
        
        .btn-danger { 
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
            border: none; 
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); 
        }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(239, 68, 68, 0.4); }

        /* ── Tables ── */
        .table {
            color: #334155;
            margin-bottom: 0;
        }
        .table thead th {
            border-bottom: 2px solid #f1f5f9;
            border-top: none;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 1.1rem 1rem;
            background-color: transparent;
            white-space: nowrap;
        }
        .table td {
            vertical-align: middle;
            border-top: 1px solid #f8fafc;
            padding: 1rem;
            color: #475569;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(248, 250, 252, 0.5);
        }
        .table-hover tbody tr {
            transition: background-color 0.2s, transform 0.2s;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f5f9;
            transform: scale(1.002);
            box-shadow: inset 2px 0 0 var(--primary);
        }
        
        /* ── Alerts & Badges ── */
        .alert {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .badge {
            padding: 0.45em 0.85em;
            border-radius: 0.5rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        
        /* ── Layout Polish ── */
        .content-header h1 {
            font-weight: 800;
            color: var(--dark);
            font-size: 1.6rem;
            letter-spacing: -0.03em;
        }
        .breadcrumb {
            background: transparent;
            padding: 0;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .content-wrapper {
            padding-bottom: 2rem;
        }
        /* ── Dark Mode Overrides ── */
        body.dark-mode {
            --background: #0f172a;
            color: #cbd5e1;
        }
        body.dark-mode .card {
            background: rgba(30, 41, 59, 0.85); /* Slate 800 */
            border-color: rgba(255, 255, 255, 0.1);
        }
        body.dark-mode .card-header {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }
        body.dark-mode .card-title {
            color: #f8fafc;
        }
        body.dark-mode .info-box {
            background: rgba(30, 41, 59, 0.85);
            border-color: rgba(255, 255, 255, 0.1);
        }
        body.dark-mode .info-box-number {
            color: #f8fafc;
        }
        body.dark-mode .info-box-text {
            color: #94a3b8;
        }
        body.dark-mode .main-header {
            background: rgba(15, 23, 42, 0.8) !important;
            border-bottom-color: rgba(255, 255, 255, 0.05) !important;
        }
        body.dark-mode .navbar-light .navbar-nav .nav-link {
            color: #cbd5e1;
        }
        body.dark-mode .navbar-light .navbar-nav .nav-link:hover {
            color: #ffffff;
        }
        body.dark-mode .table {
            color: #cbd5e1;
        }
        body.dark-mode .table td {
            border-top-color: rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
        }
        body.dark-mode .table thead th {
            color: #94a3b8;
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }
        body.dark-mode .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.03);
            box-shadow: inset 2px 0 0 var(--primary);
        }
        body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.015);
        }
        body.dark-mode .content-header h1 {
            color: #f8fafc;
        }
        body.dark-mode .modal-content {
            background-color: #1e293b;
            color: #cbd5e1;
            border-color: rgba(255, 255, 255, 0.1);
        }
        body.dark-mode .modal-header,
        body.dark-mode .modal-footer {
            border-bottom-color: rgba(255, 255, 255, 0.05);
            border-top-color: rgba(255, 255, 255, 0.05);
        }
        body.dark-mode .form-control {
            background-color: #0f172a;
            border-color: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
        }
        body.dark-mode .form-control:focus {
            background-color: #1e293b;
            border-color: var(--primary);
            color: #f8fafc;
        }
        body.dark-mode .form-control::placeholder {
            color: rgba(148, 163, 184, 0.5);
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<!-- Inject Dark Mode Instantly -->
<script>
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.body.classList.add('dark-mode');
    }
</script>
<div class="wrapper">