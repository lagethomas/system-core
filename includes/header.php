<?php
declare(strict_types=1);
global $pdo;
require_once __DIR__ . '/helpers/Auth.php';
require_once __DIR__ . '/helpers/CSRF.php';
require_once __DIR__ . '/repositories/NotificationRepository.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['user_name'] ?? 'Usuário';
$user_role = $_SESSION['user_role'] ?? 'usuario';

// Page title detector (MVC Aware)
global $current_page;
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Remove o base path do SITE_URL se houver (ex: /folder/dashboard -> dashboard)
$site_path = parse_url(SITE_URL, PHP_URL_PATH) ?: '';
$route = str_replace($site_path, '', $uri);
$route = trim($route, '/');

// Se for vazio ou index.php (legado), padrão é dashboard
if (empty($route) || $route === 'index.php') {
    $route = 'dashboard';
}

// Para CSS de módulos, pegamos a última parte da rota
$route_parts = explode('/', $route);
$current_page = end($route_parts);
$page_titles = [
    'dashboard.php' => 'Painel de Controle',
    'dashboard' => 'Painel de Controle',
    'users.php' => 'Usuários',
    'users' => 'Usuários',
    'logs.php' => 'Logs Globais',
    'logs' => 'Logs Globais',
    'settings.php' => 'Configurações',
    'settings' => 'Configurações',
    'profile.php' => 'Meu Perfil',
    'profile' => 'Meu Perfil',
    'integrations.php' => 'Integrações',
    'integrations' => 'Integrações'
];

// Fetch Notifications
$notifRepo = new NotificationRepository($pdo);
$unread_notifications = $user_id ? $notifRepo->getUnreadByUser($user_id) : [];
$unread_count = count($unread_notifications);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo CSRF::generateToken(); ?>">
    <?php 
    // Use Pre-loaded Platform Settings from db.php
    global $platform_settings;
    $theme_slug = $platform_settings['system_theme'] ?? 'gold-black';
    $system_name = $platform_settings['system_name'] ?? 'SaaSFlow Core';
    ?>
    <title><?php echo htmlspecialchars(($page_titles[$current_page] ?? 'Início') . ' | ' . ($system_name ?? 'SaaSFlow')); ?></title>
    
    <link rel="stylesheet" href="<?php echo \App\Core\Controller::asset('/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo \App\Core\Controller::asset('/assets/css/theme/' . $theme_slug . '.css'); ?>">
    <link rel="stylesheet" href="<?php echo \App\Core\Controller::asset('/assets/css/components/notifications.css'); ?>">
    <link rel="stylesheet" href="<?php echo \App\Core\Controller::asset('/assets/css/components/page-content.css'); ?>">
    <link rel="stylesheet" href="<?php echo \App\Core\Controller::asset('/assets/css/components/main-footer.css'); ?>">
    <link rel="stylesheet" href="<?php echo \App\Core\Controller::asset('/assets/css/components/popups.css'); ?>">
    <link rel="stylesheet" href="<?php echo \App\Core\Controller::asset('/assets/css/components/switches.css'); ?>">
    <link rel="stylesheet" href="<?php echo \App\Core\Controller::asset('/assets/css/components/badges.css'); ?>">
    
    <?php 
    // Auto-load page specific CSS from modules
    $page_name = str_replace('.php', '', $current_page);
    $css_path = dirname(__FILE__) . "/../public/assets/css/modules/{$page_name}.css";
    if (file_exists($css_path)) {
        echo '<link rel="stylesheet" href="' . \App\Core\Controller::asset('/assets/css/modules/' . $page_name . '.css') . '">';
    }
    ?>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/img/icon-192.png">
</head>
<body>
    <div class="app-container">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo SITE_URL; ?>/dashboard" class="logo">
                    <div class="sidebar-logo-icon" <?php echo !empty($platform_settings['system_logo']) ? 'style="background: transparent;"' : ''; ?>>
                        <?php if (!empty($platform_settings['system_logo'])): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/logos/<?php echo $platform_settings['system_logo']; ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <i class="fas fa-layer-group"></i>
                        <?php endif; ?>
                    </div>
                    <span><?php echo htmlspecialchars($system_name); ?></span>
                </a>
                <button class="sidebar-collapse-toggle" onclick="toggleSidebarCollapse()" title="Encolher Menu">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?php echo ($current_page == 'dashboard.php' || $current_page == 'dashboard') ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/dashboard">
                            <i class="fas fa-th-large"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <?php if (Auth::isAdmin()): ?>
                    <li class="<?php echo ($current_page == 'users.php' || $current_page == 'users') ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/users">
                            <i class="fas fa-users"></i> <span>Usuários</span>
                        </a>
                    </li>
                    <li class="<?php echo ($current_page == 'integrations.php' || $current_page == 'integrations') ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/integrations">
                            <i class="fas fa-plug"></i> <span>Integrações</span>
                        </a>
                    </li>
                    <li class="<?php echo ($current_page == 'logs.php' || $current_page == 'logs') ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/logs">
                            <i class="fas fa-terminal"></i> <span>Logs Globais</span>
                        </a>
                    </li>
                    <li class="<?php echo ($current_page == 'settings.php' || $current_page == 'settings') ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/settings">
                            <i class="fas fa-cogs"></i> <span>Configurações</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile" id="user-profile-trigger">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?> <i class="fas fa-chevron-up user-chevron"></i></span>
                        <span class="user-role"><?php echo ucfirst($user_role); ?></span>
                    </div>
                </div>
                <!-- Popup de Perfil/Sair -->
                <div class="sidebar-user-dropdown" id="user-dropdown">
                    <a href="<?php echo SITE_URL; ?>/profile" class="btn-secondary" style="display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 8px; text-decoration: none; color: var(--text-main); background: rgba(255,255,255,0.03); border: 1px solid var(--border);">
                        <i class="fas fa-user-circle"></i> Meu Perfil Maroto
                    </a>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="user-dropdown-item danger">
                        <i class="fas fa-sign-out-alt"></i> Sair do Sistema
                    </a>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <div class="top-bar-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="page-title"><?php echo $page_titles[$current_page] ?? 'Início'; ?></h2>
                </div>

                <div class="top-nav-right">
                    <!-- Notificações -->
                    <div class="notif-trigger" id="notif-trigger">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notif-badge"><?php echo (string)$unread_count; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="notification-dropdown" id="notif-dropdown">
                        <div class="notif-header">
                            <span>Notificações</span>
                            <?php if ($unread_count > 0): ?>
                                <button onclick="markAllRead()" class="btn-mark-read">Marcar todas como lidas</button>
                            <?php endif; ?>
                        </div>
                        <div class="notif-list">
                            <?php if (empty($unread_notifications)): ?>
                                <div class="notif-empty">
                                    <i class="fas fa-bell-slash"></i>
                                    <span>Nenhuma nova notificação</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($unread_notifications as $notif): ?>
                                    <a href="<?php echo $notif['link'] ?: '#'; ?>" class="notif-item">
                                        <div class="notif-icon primary">
                                            <i class="<?php echo $notif['icon'] ?: 'fas fa-info-circle'; ?>"></i>
                                        </div>
                                        <div class="notif-content">
                                            <span class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></span>
                                            <span class="notif-text"><?php echo htmlspecialchars($notif['message']); ?></span>
                                            <span class="notif-time"><?php echo date('d/m H:i', strtotime($notif['created_at'])); ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="notif-footer">
                            <a href="#">Ver todas as notificações</a>
                        </div>
                    </div>
                </div>
            </header>
            <div class="page-content">
<script>

// Dropdown de perfil
document.getElementById('user-profile-trigger').addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('user-dropdown');
    dropdown.classList.toggle('active');
    this.classList.toggle('active');
});

// Dropdown de notificações
document.getElementById('notif-trigger').addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('notif-dropdown').classList.toggle('active');
});

// Fechar dropdowns ao clicar fora
document.addEventListener('click', function() {
    const dropdown = document.getElementById('user-dropdown');
    const trigger = document.getElementById('user-profile-trigger');
    if (dropdown) dropdown.classList.remove('active');
    if (trigger) trigger.classList.remove('active');

    const notif = document.getElementById('notif-dropdown');
    if (notif) notif.classList.remove('active');
});

async function markRead(id) {
    await fetch('<?php echo SITE_URL; ?>/api/notifications/read/' + id);
}

async function markAllRead() {
    const res = await fetch('<?php echo SITE_URL; ?>/api/notifications/read_all');
    if (res.ok) window.location.reload();
}
</script>
