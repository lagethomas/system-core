<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Database;
use Auth;
use Logger;

class EmailConfirmationController extends Controller {

    /**
     * Confirm Email Change (GET /confirm-email?token=...)
     */
    public function confirm(): void {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $this->renderConfirmation('Token inválido ou ausente.', false);
            return;
        }

        try {
            // Find valid confirmation
            $request = Database::fetch(
                "SELECT * FROM cp_email_confirmations WHERE token = ? AND expires_at > NOW()", 
                [$token]
            );

            if (!$request) {
                // Check if it's expired
                $expired = Database::fetch("SELECT id FROM cp_email_confirmations WHERE token = ?", [$token]);
                if ($expired) {
                    $this->renderConfirmation('Este link de confirmação expirou (validade de 4 horas). Por favor, solicite a troca novamente.', false);
                } else {
                    $this->renderConfirmation('Token de confirmação inválido.', false);
                }
                return;
            }

            $userId = (int)$request['user_id'];
            $newEmail = $request['new_email'];

            // Update user email
            Database::update('cp_users', ['email' => $newEmail], 'id = :where_id', ['where_id' => $userId]);

            // Clear pending request
            Database::delete('cp_email_confirmations', 'user_id = ?', [$userId]);

            // Log
            require_once __DIR__ . '/../../../includes/logs.php';
            Logger::log('email_changed', "E-mail do usuário ID ". (string)$userId ." alterado para {$newEmail} após confirmação.");

            // Success message
            $this->renderConfirmation('E-mail alterado com sucesso! Agora você pode utilizar seu novo e-mail para acessar o sistema.', true);

        } catch (\Exception $e) {
            $this->renderConfirmation('Erro ao processar confirmação: ' . $e->getMessage(), false);
        }
    }

    private function renderConfirmation(string $message, bool $success): void {
        // Simple standalone view for confirmation
        global $platform_settings;
        $theme = $platform_settings['system_theme'] ?? 'gold-black';
        $systemName = $platform_settings['system_name'] ?? 'SaaSFlow Core';
        
        echo "<!DOCTYPE html>
        <html lang='pt-br'>
        <head>
            <meta charset='UTF-8'>
            <title>Confirmação de E-mail | {$systemName}</title>
            <link rel='stylesheet' href='" . SITE_URL . "/assets/css/themes/{$theme}.css'>
            <style>
                body { background: #0f172a; color: white; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .card { background: #1e293b; padding: 40px; border-radius: 16px; text-align: center; max-width: 450px; border: 1px solid #334155; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
                .icon { font-size: 48px; margin-bottom: 20px; }
                .success { color: #22c55e; }
                .error { color: #ef4444; }
                h2 { margin-top: 0; }
                p { color: #94a3b8; line-height: 1.6; }
                .btn { display: inline-block; margin-top: 30px; padding: 12px 25px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='card'>
                <div class='icon " . ($success ? 'success' : 'error') . "'>" . ($success ? '✓' : '✕') . "</div>
                <h2>" . ($success ? 'Sucesso!' : 'Opa...') . "</h2>
                <p>{$message}</p>
                <a href='" . SITE_URL . "/login' class='btn'>Ir para o Login</a>
            </div>
        </body>
        </html>";
        exit;
    }
}
