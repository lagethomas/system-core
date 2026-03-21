<?php
/** @var array $settings */
/** @var string $active_tab */

include_once __DIR__ . '/../../../includes/header.php';
?>

<div class="settings-tab-nav">
    <a href="?tab=general" class="nav-link-tab <?php echo $active_tab === 'general' ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i> Geral
    </a>
    <a href="?tab=themes" class="nav-link-tab <?php echo $active_tab === 'themes' ? 'active' : ''; ?>">
        <i class="fas fa-palette"></i> Temas
    </a>
    <a href="?tab=security" class="nav-link-tab <?php echo $active_tab === 'security' ? 'active' : ''; ?>">
        <i class="fas fa-shield-alt"></i> Segurança
    </a>
</div>

<div class="card settings-main-card">
    <?php if ($active_tab === 'general'): ?>
        <form method="POST">
            <div class="settings-header-box">
                <h5><i class="fas fa-cog text-primary"></i> Configurações Gerais</h5>
            </div>
            
            <div class="form-group mb-4">
                <label class="form-label">Nome do Sistema</label>
                <input type="text" name="system_name" value="<?php echo htmlspecialchars($settings['system_name'] ?? ''); ?>" class="form-control w-100">
            </div>

            <div class="form-group mb-4">
                <label class="switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                    <div>
                        <h6 class="mb-0">Ativar Logs do Sistema</h6>
                        <small class="text-muted">Registrar erros e atividades no diretório /logs</small>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="enable_system_logs" value="1" <?php echo ($settings['enable_system_logs'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </label>
            </div>

            <button type="submit" name="save_general" class="btn-primary">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </form>

    <?php elseif ($active_tab === 'themes'): ?>
        <form method="POST">
            <div class="settings-header-box">
                <h5><i class="fas fa-palette text-primary"></i> Personalização de Tema</h5>
                <p>Selecione a identidade visual que será aplicada a todos os usuários do sistema.</p>
            </div>

            <div class="theme-grid">
                <?php 
                $themes = ThemeHelper::getAvailableThemes();
                $current_theme = $settings['system_theme'] ?? 'gold-black';
                
                foreach ($themes as $slug => $theme): 
                    $isSelected = ($slug === $current_theme);
                ?>
                    <label class="theme-card-label">
                        <input type="radio" name="system_theme" value="<?php echo $slug; ?>" <?php echo $isSelected ? 'checked' : ''; ?> style="display: none;">
                        <div class="theme-card-ui">
                            <div class="theme-card-preview" style="background: <?php echo $theme['bg']; ?>;">
                                <div class="theme-card-accent" style="background: <?php echo $theme['color']; ?>; box-shadow: 0 0 15px <?php echo $theme['color']; ?>88;"></div>
                                <div class="theme-card-subaccent" style="background: <?php echo ($theme['bg'] == '#ffffff' || $theme['bg'] == 'white') ? '#eee' : 'rgba(255,255,255,0.1)'; ?>;"></div>
                            </div>
                            <div class="text-center">
                                <span class="theme-card-name"><?php echo $theme['name']; ?></span>
                            </div>
                            <div class="theme-check-icon" style="display: <?php echo $isSelected ? 'flex' : 'none'; ?>;">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="save_theme" class="btn-primary">
                <i class="fas fa-save"></i> Aplicar Tema Selecionado
            </button>
        </form>

    <?php elseif ($active_tab === 'security'): ?>
        <form method="POST">
            <div class="settings-header-box">
                <h5><i class="fas fa-shield-alt text-primary"></i> Política de Segurança</h5>
                <p>Configure regras de acesso e proteção para os dados do sistema.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <!-- Login Protection Card -->
                <div class="card" style="background: rgba(var(--primary-rgb), 0.03); border: 1px solid rgba(var(--primary-rgb), 0.1);">
                    <h6 style="color: var(--primary); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-lock"></i> Proteção de Login
                    </h6>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Máximo de Tentativas</label>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <input type="number" name="security_max_attempts" value="<?php echo htmlspecialchars($settings['security_max_attempts'] ?? '5'); ?>" class="form-control" style="width: 100px;">
                            <small class="text-muted">Tentativas antes do bloqueio</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tempo de Bloqueio (minutos)</label>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <input type="number" name="security_lockout_time" value="<?php echo htmlspecialchars($settings['security_lockout_time'] ?? '30'); ?>" class="form-control" style="width: 100px;">
                            <small class="text-muted">Duração do bloqueio preventivo</small>
                        </div>
                    </div>
                </div>

                <!-- Session & Requirements Card -->
                <div class="card" style="background: rgba(255,255,255,0.01); border: 1px solid var(--border);">
                    <h6 style="color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-key"></i> Requisitos & Sessão
                    </h6>

                    <div class="form-group mb-4">
                        <label class="switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                            <div>
                                <span style="font-size: 14px;">Exigir Senhas Fortes</span>
                                <small class="text-muted" style="display: block; font-size: 11px;">Mínimo 8 caracteres, números e símbolos</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="security_strong_password" value="1" <?php echo ($settings['security_strong_password'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Timeout de Sessão (horas)</label>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <select name="security_session_timeout" class="form-control" style="width: 120px;">
                                <option value="2" <?php echo ($settings['security_session_timeout'] ?? '') === '2' ? 'selected' : ''; ?>>2 Horas</option>
                                <option value="8" <?php echo ($settings['security_session_timeout'] ?? '') === '8' ? 'selected' : ''; ?>>8 Horas</option>
                                <option value="24" <?php echo ($settings['security_session_timeout'] ?? '') === '24' ? 'selected' : ''; ?>>24 Horas</option>
                                <option value="168" <?php echo ($settings['security_session_timeout'] ?? '') === '168' ? 'selected' : ''; ?>>7 Dias</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Global Policies Card -->
                <div class="card" style="background: rgba(var(--accent-rgb), 0.02); border: 1px solid rgba(var(--accent-rgb), 0.1);">
                    <h6 style="color: var(--accent); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-shield"></i> Políticas Globais
                    </h6>

                    <div class="form-group mb-4">
                        <label class="switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                            <div>
                                <span style="font-size: 14px;">Bloqueio por IP</span>
                                <small class="text-muted" style="display: block; font-size: 11px;">Bloquear IPs após x falhas críticas</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="security_ip_lockout" value="1" <?php echo ($settings['security_ip_lockout'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                            <div>
                                <span style="font-size: 14px;">Logins Simultâneos</span>
                                <small class="text-muted" style="display: block; font-size: 11px;">Permitir apenas uma sessão ativa por usuário</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="security_single_session" value="1" <?php echo ($settings['security_single_session'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </label>
                    </div>
                </div>

                <!-- Log Retention Card -->
                <div class="card" style="background: rgba(255,255,255,0.01); border: 1px solid var(--border);">
                    <h6 style="color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-history"></i> Retenção de Logs
                    </h6>

                    <div class="form-group mb-3">
                        <label class="form-label">Manter logs por (dias)</label>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <input type="number" name="security_log_days" value="<?php echo htmlspecialchars($settings['security_log_days'] ?? '30'); ?>" class="form-control" style="width: 100px;">
                            <small class="text-muted">Logs antigos serão apagados</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Limite de registros</label>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <input type="number" name="security_log_limit" value="<?php echo htmlspecialchars($settings['security_log_limit'] ?? '5000'); ?>" class="form-control" style="width: 100px;">
                            <small class="text-muted">Máximo de entradas no banco</small>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" name="save_security" class="btn-primary">
                <i class="fas fa-save"></i> Salvar Diretrizes de Segurança
            </button>
        </form>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../../../includes/footer.php'; ?>
