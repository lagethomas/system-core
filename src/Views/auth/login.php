<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo $system_name; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/modules/auth.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/theme/<?php echo $theme_slug; ?>.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/img/icon-192.png">

    <style>
        .alert-session {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.12), rgba(245, 158, 11, 0.06));
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-left: 4px solid #f59e0b;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 20px;
            color: #b45309;
            font-size: 0.9rem;
            animation: slideIn 0.3s ease;
        }
        .alert-session .alert-icon {
            font-size: 1.3rem;
            margin-bottom: 8px;
            display: block;
        }
        .alert-session strong {
            display: block;
            font-size: 0.95rem;
            margin-bottom: 4px;
            color: #92400e;
        }
        .alert-session p {
            margin: 0 0 14px 0;
            opacity: 0.85;
        }
        .btn-force {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            width: 100%;
            justify-content: center;
        }
        .btn-force:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(245,158,11,0.3);
        }
        .divider-or {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0 6px;
            color: #9ca3af;
            font-size: 0.75rem;
        }
        .divider-or::before,
        .divider-or::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(156,163,175,0.3);
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="auth-wrapper" <?php if (!empty($settings['login_background'])): ?> style="background: url('<?php echo SITE_URL; ?>/assets/img/custom/<?php echo $settings['login_background']; ?>') no-repeat center center fixed; background-size: cover;" <?php endif; ?>>
    
    <?php if (!empty($settings['login_background'])): ?>
        <div class="auth-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 0;"></div>
    <?php endif; ?>

    <div class="auth-card" style="position: relative; z-index: 1; <?php echo !empty($settings['login_background']) ? 'backdrop-filter: blur(12px); background: rgba(15, 17, 21, 0.85); border: 1px solid rgba(255,255,255,0.1); shadow: 0 25px 50px rgba(0,0,0,0.5);' : ''; ?>">
        <div class="auth-header">
            <div class="auth-logo-box">
                <?php if (!empty($settings['system_logo'])): ?>
                    <img src="<?php echo SITE_URL; ?>/assets/img/custom/<?php echo $settings['system_logo']; ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <?php else: ?>
                    <i class="fas fa-layer-group"></i>
                <?php endif; ?>
            </div>
            <h2 class="auth-title"><?php echo $system_name; ?></h2>
            <p class="auth-subtitle">Acesse sua conta para continuar</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($warn_session): ?>
            <!-- ── SESSÃO ATIVA: Bloquear e informar o usuário ── -->
            <div class="alert-session">
                <span class="alert-icon"><i class="fas fa-shield-alt"></i></span>
                <strong>Sessão Ativa Detectada</strong>
                <p>
                    Este usuário já está conectado em outro dispositivo ou navegador.
                    O sistema permite apenas <strong>uma sessão ativa por vez</strong>.
                </p>
                <!-- Formulário de força: reenvia as credenciais com flag force_login -->
                <form method="POST" action="<?php echo SITE_URL; ?>/login" id="forceForm">
                    <input type="hidden" name="csrf_token"  value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="username"    value="<?php echo htmlspecialchars($pre_username); ?>">
                    <input type="hidden" name="password"    value="<?php echo htmlspecialchars($pre_password); ?>">
                    <input type="hidden" name="force_login" value="1">
                    <button type="submit" class="btn-force" id="btnForce">
                        <i class="fas fa-sign-in-alt"></i>
                        Encerrar sessão anterior e entrar
                    </button>
                </form>

                <div class="divider-or">ou</div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo SITE_URL; ?>/login" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label class="auth-label">Usuário</label>
                <input type="text" name="username" class="form-control"
                       value="<?php echo htmlspecialchars($pre_username); ?>"
                       placeholder="Seu usuário" required autofocus>
            </div>

            <div class="form-group mt-3">
                <label class="auth-label">Senha</label>
                <input type="password" name="password" class="form-control" placeholder="Sua senha" required>
            </div>

            <button type="submit" class="btn-primary btn-block mt-4" id="btnLogin">
                <span class="btn-text">Entrar no Sistema <i class="fas fa-arrow-right ml-2"></i></span>
                <span class="btn-loader" style="display: none;">
                    <i class="fas fa-circle-notch fa-spin mr-2"></i> Processando...
                </span>
            </button>
        </form>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/components/ui-core.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof UI !== 'undefined') UI.initPasswordToggles();
        });

        function lockBtn(formId, btnId) {
            const form = document.getElementById(formId);
            if (!form) return;
            form.addEventListener('submit', function() {
                const btn = document.getElementById(btnId);
                if (!btn) return;
                btn.disabled = true;
                const text   = btn.querySelector('.btn-text');
                const loader = btn.querySelector('.btn-loader');
                if (text)   text.style.display  = 'none';
                if (loader) { loader.style.display = 'flex'; loader.style.alignItems = 'center'; }
            });
        }

        lockBtn('loginForm', 'btnLogin');
        lockBtn('forceForm',  'btnForce');
    </script>
</body>
</html>
