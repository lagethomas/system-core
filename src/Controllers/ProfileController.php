<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use Auth;
use PDO;

class ProfileController extends Controller {
    public function index(): void {
        $user_id = $_SESSION['user_id'];
        $user = \App\Core\Database::fetch("SELECT * FROM cp_users WHERE id = ?", [$user_id]);

        if (!$user) {
            die("Usuário não encontrado.");
        }

        $this->render('app/profile', [
            'user' => $user
        ]);
    }

    public function save(): void {
        $user_id = $_SESSION['user_id'];
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? null;

        if (!$name || !$email) {
            $this->jsonResponse(['success' => false, 'message' => 'Nome e e-mail são obrigatórios.'], 400);
        }

        try {
            // --- Secure Email Change Logic (Rule 39 / User Request) ---
            $currentData = \App\Core\Database::fetch("SELECT name, email FROM cp_users WHERE id = ?", [$user_id]);
            $emailChanged = ($email !== $currentData['email']);
            $emailConfirmationMsg = "";

            if ($emailChanged) {
                // Check if new email is already taken
                $existing = \App\Core\Database::fetch("SELECT id FROM cp_users WHERE email = ? AND id != ?", [$email, $user_id]);
                if ($existing) {
                    $this->jsonResponse(['success' => false, 'message' => 'O novo e-mail já está sendo utilizado por outra conta.'], 400);
                    return;
                }

                // Generate Token and Expiration
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+4 hours'));

                // Store pending change
                \App\Core\Database::delete('cp_email_confirmations', "user_id = ?", [$user_id]);
                \App\Core\Database::insert('cp_email_confirmations', [
                    'user_id' => $user_id,
                    'new_email' => $email,
                    'token' => $token,
                    'expires_at' => $expiresAt
                ]);

                // Send Confirmation Email
                require_once __DIR__ . '/../../includes/mailer.php';
                $confirmUrl = SITE_URL . "/confirm-email?token=" . $token;
                $subject = "Confirmação de Alteração de E-mail 📧";
                $body = "
                    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #e1e1e1; border-radius: 12px;'>
                        <h2 style='color: #2563eb;'>Confirmar Alteração de E-mail</h2>
                        <p>Olá, <strong>{$name}</strong>.</p>
                        <p>Recebemos uma solicitação para alterar seu e-mail para: <strong>{$email}</strong>.</p>
                        <p>Para concluir a troca, clique no botão abaixo em até 4 horas:</p>
                        <a href='{$confirmUrl}' style='display: inline-block; padding: 12px 25px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 700; margin: 20px 0;'>Confirmar E-mail</a>
                        <p style='font-size: 12px; color: #666;'>Se você não solicitou esta alteração, ignore este e-mail.</p>
                    </div>
                ";

                if (\Mailer::send($email, $subject, $body)) {
                    $emailConfirmationMsg = " Verifique o novo e-mail para confirmar a alteração.";
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Falha ao enviar e-mail de confirmação. Contate o suporte.'], 500);
                    return;
                }

                // Don't update email field in $data yet
                $email = $currentData['email'];
            }

            $data = [
                'name' => $name,
                'email' => $email, // Remains old email if changed
                'phone' => trim($_POST['phone'] ?? ''),
                'zip_code' => trim($_POST['zip_code'] ?? ''),
                'street' => trim($_POST['street'] ?? ''),
                'neighborhood' => trim($_POST['neighborhood'] ?? ''),
                'address_number' => trim($_POST['address_number'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'state' => trim($_POST['state'] ?? '')
            ];

            if (!empty($password)) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            // Image Handler (Rule 23 Reorg)
            require_once __DIR__ . '/../../includes/helpers/ImageHelper.php';
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/fotos';
                $oldAvatar = \App\Core\Database::fetch("SELECT avatar FROM cp_users WHERE id = ?", [$user_id])['avatar'] ?? null;
                $newFilename = \ImageHelper::uploadAndConvert($_FILES['profile_picture'], $uploadDir, 'avatar_' . $user_id);
                if ($newFilename) {
                    if ($oldAvatar && $oldAvatar !== $newFilename) {
                        \ImageHelper::safeDelete($oldAvatar, $uploadDir);
                    }
                    $data['avatar'] = $newFilename;
                }
            }

            \App\Core\Database::update('cp_users', $data, 'id = :where_id', ['where_id' => $user_id]);
            $_SESSION['user_name'] = $name;
            
            \Logger::log('edit_profile', "Usuário solicitou atualização cadastral." . ($emailChanged ? " (Troca de e-mail pendente)" : ""));

            $this->jsonResponse(['success' => true, 'message' => 'Perfil atualizado com sucesso!' . $emailConfirmationMsg]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }
}
