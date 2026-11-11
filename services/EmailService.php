<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

// Serviço de email: prepara as mensagens e envia-as através do PHPMailer.
class EmailService {
    private const LOGO_PATH = ROOT . '/public/assets/img/logo.png';
    private const LOGO_CID  = 'logo_prm_sigop';
    private static ?PHPMailer $ligacaoSMTP = null;
    private static string $ultimoErroSMTP = '';

    // ─── Métodos públicos ────────────────────────────────────────────────────

    // Valida a ligação e a autenticação sem enviar mensagens. O próximo envio
    // reutiliza esta ligação, evitando uma segunda autenticação no mesmo pedido.
    public static function verificarLigacaoSMTP(): bool {
        self::$ultimoErroSMTP = '';
        if (empty(MAIL_USER) || empty(MAIL_PASS)) {
            self::$ultimoErroSMTP = 'MAIL_USER e MAIL_PASS devem estar preenchidos no ficheiro .env.';
            return false;
        }

        try {
            if (self::$ligacaoSMTP === null) {
                self::$ligacaoSMTP = new PHPMailer(true);
                self::configurarSMTP(self::$ligacaoSMTP);
            }
            if (!self::$ligacaoSMTP->smtpConnect()) {
                throw new MailerException('Não foi possível ligar ao servidor SMTP.');
            }
            return true;
        } catch (MailerException $e) {
            self::$ultimoErroSMTP = $e->getMessage();
            error_log('[SIGOP] Falha na ligação SMTP: ' . self::$ultimoErroSMTP);
            self::fecharLigacaoSMTP();
            return false;
        }
    }

    public static function fecharLigacaoSMTP(): void {
        if (self::$ligacaoSMTP !== null) {
            self::$ligacaoSMTP->smtpClose();
            self::$ligacaoSMTP = null;
        }
    }

    // Apenas para diagnóstico no servidor; não apresentar os detalhes na página pública.
    public static function ultimoErroSMTP(): string {
        return self::$ultimoErroSMTP;
    }

    public static function enviarRecuperacaoSenha(string $toEmail, string $toNome, string $link): bool {
        $assunto = 'Recuperação de Senha — ' . APP_NAME;
        $corpo = '
            <p>Olá, <strong>' . e($toNome) . '</strong>.</p>
            <p>Recebemos um pedido de recuperação de senha para a sua conta.</p>
            <p>Clique no botão abaixo para definir uma nova senha. O link é válido por <strong>1 hora</strong>.</p>
            <p style="margin:24px 0;">
                <a href="' . e($link) . '"
                   style="background:#CC1230;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;font-weight:bold;">
                   Redefinir Senha
                </a>
            </p>
            <p>Se não pediu a recuperação de senha, ignore este email.</p>
            <hr>
            <small style="color:#999;">' . e(APP_NAME) . '</small>
        ';
        $altBody = 'Aceda ao link para redefinir a sua senha: ' . $link;

        return self::enviar($toEmail, $toNome, $assunto, $corpo, $altBody);
    }

    public static function enviarCasoAtribuido(
        string $toEmail, string $toNome, string $numero, string $tipoNome,
        string $esquadraNome, string $link, bool $reatribuicao = false
    ): bool {
        $titulo = $reatribuicao ? 'Caso Reatribuído' : 'Novo Caso Atribuído';
        $intro  = $reatribuicao
            ? 'Foi-lhe <strong>reatribuída</strong> a ocorrência abaixo — passa a ser o Investigador responsável por dar andamento ao caso.'
            : 'Foi-lhe <strong>atribuída</strong> a ocorrência abaixo para dar andamento ao caso.';

        $corpo = self::layout(
            $toNome,
            $titulo,
            '
            <div style="text-align:center;margin:0 0 22px;">
                <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;
                            border-radius:50%;background:rgba(232,119,34,0.1);font-size:28px;">
                    &#128193;
                </div>
            </div>
            <p style="margin:0 0 24px;font-size:15px;color:#334155;line-height:1.7;text-align:center;">' . $intro . '</p>

            <div style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:16px 22px;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;font-weight:700;width:40%;border-bottom:1px solid #E2E8F0;">Número</td>
                        <td style="padding:16px 22px;font-size:16px;font-weight:800;color:#1e293b;font-family:\'Courier New\',monospace;letter-spacing:.5px;border-bottom:1px solid #E2E8F0;">' . e($numero) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 22px;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;font-weight:700;border-bottom:1px solid #E2E8F0;">Tipo</td>
                        <td style="padding:12px 22px;font-size:14px;font-weight:600;color:#1e293b;border-bottom:1px solid #E2E8F0;">' . e($tipoNome) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 22px;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;font-weight:700;">Esquadra</td>
                        <td style="padding:12px 22px;font-size:14px;font-weight:600;color:#1e293b;">' . e($esquadraNome) . '</td>
                    </tr>
                </table>
            </div>

            <div style="text-align:center;margin:0 0 8px;">
                <a href="' . e($link) . '"
                   style="display:inline-block;background:linear-gradient(135deg,#E87722,#C8601A);color:#fff;
                          text-decoration:none;padding:14px 40px;border-radius:10px;font-size:15px;
                          font-weight:700;letter-spacing:.3px;box-shadow:0 4px 16px rgba(232,119,34,0.35);">
                    Ver Ocorrência
                </a>
            </div>
            ',
            ($reatribuicao ? 'Foi-lhe reatribuída a' : 'Foi-lhe atribuída a') . ' ocorrência ' . $numero . '.'
        );

        return self::enviar($toEmail, $toNome, APP_NAME . ' — ' . $titulo . ' (' . $numero . ')', $corpo);
    }

    public static function enviarAtualizacao(string $toEmail, string $toNome, string $numero, string $evento, string $esquadra): bool {
        $assuntos = [
            'aberta'          => '[' . $numero . '] Ocorrência registada — ' . APP_NAME,
            'aprovada'        => '[' . $numero . '] Ocorrência aceite pela esquadra',
            'em_investigacao' => '[' . $numero . '] Ocorrência em investigação',
            'resolvida'       => '[' . $numero . '] Ocorrência resolvida',
            'arquivada'       => '[' . $numero . '] Ocorrência arquivada',
        ];

        $mensagens = [
            'aberta'          => 'A sua ocorrência foi <strong>registada com sucesso</strong> no sistema SIGOP-PRM. Atribuímos-lhe o número de referência indicado abaixo.',
            'aprovada'        => 'A sua ocorrência foi registada e <strong>aceite pela esquadra</strong>. Está a ser processada pelas autoridades policiais responsáveis.',
            'em_investigacao' => 'A sua ocorrência encontra-se em <strong>fase de investigação activa</strong>. As autoridades estão a trabalhar para apurar os factos.',
            'resolvida'       => 'Temos o prazer de informar que a sua ocorrência foi <strong>resolvida</strong>. Obrigado pela colaboração com as autoridades.',
            'arquivada'       => 'A sua ocorrência foi <strong>arquivada</strong>. Para mais informações, dirija-se pessoalmente à esquadra responsável.',
        ];

        $icones = [
            'aberta'          => '&#128196;',
            'aprovada'        => '&#9989;',
            'em_investigacao' => '&#128269;',
            'resolvida'       => '&#127881;',
            'arquivada'       => '&#128193;',
        ];

        $cores = [
            'aberta'          => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1E40AF', 'label' => 'Registada'],
            'aprovada'        => ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'text' => '#92400E', 'label' => 'Aceite pela Esquadra'],
            'em_investigacao' => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1E40AF', 'label' => 'Em Investigação'],
            'resolvida'       => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#166534', 'label' => 'Resolvida'],
            'arquivada'       => ['bg' => '#F9FAFB', 'border' => '#E5E7EB', 'text' => '#6B7280', 'label' => 'Arquivada'],
        ];

        $cfg    = $cores[$evento]    ?? $cores['aberta'];
        $icone  = $icones[$evento]   ?? '&#128196;';
        $msg    = $mensagens[$evento] ?? 'O estado da sua ocorrência foi actualizado.';

        $corpo = self::layout(
            $toNome,
            'Actualização de Ocorrência',
            '
            <p style="margin:0 0 20px;font-size:15px;color:#334155;line-height:1.7;">' . $msg . '</p>

            <div style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:12px;overflow:hidden;margin-bottom:24px;">
                <div style="padding:16px 22px;border-bottom:1px solid #E2E8F0;background:#F1F5F9;">
                    <p style="margin:0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;font-weight:700;">Detalhes da Ocorrência</p>
                </div>
                <div style="padding:20px 22px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding:6px 0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;font-weight:700;width:40%;">Número de Referência</td>
                            <td style="padding:6px 0;font-size:18px;font-weight:800;color:#1E3A8A;font-family:\'Courier New\',monospace;letter-spacing:1px;">' . e($numero) . '</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;font-weight:700;">Esquadra Responsável</td>
                            <td style="padding:6px 0;font-size:14px;font-weight:600;color:#1e293b;">' . e($esquadra) . '</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;font-weight:700;">Estado Actual</td>
                            <td style="padding:6px 0;">
                                <span style="display:inline-block;background:' . $cfg['bg'] . ';color:' . $cfg['text'] . ';
                                             border:1px solid ' . $cfg['border'] . ';border-radius:20px;
                                             padding:4px 12px;font-size:12px;font-weight:700;">
                                    ' . $icone . ' &nbsp;' . $cfg['label'] . '
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            ',
            'Ocorrência ' . $numero . ' — ' . $cfg['label'] . '.'
        );

        $assunto = $assuntos[$evento] ?? '[' . $numero . '] Actualização — ' . APP_NAME;
        return self::enviar($toEmail, $toNome, $assunto, $corpo);
    }

    // ─── Métodos privados ────────────────────────────────────────────────────

    private static function layout(string $toNome, string $titulo, string $conteudo, string $preheader = ''): string {
        $dados = date('d/m/Y \à\s H:i');
        $ano  = date('Y');
        $hasLogo = file_exists(self::LOGO_PATH);

        $logoHtml = $hasLogo
            ? '<img src="cid:' . self::LOGO_CID . '" alt="PRM" width="56" height="56" style="display:block;border:0;border-radius:50%;background:#fff;">'
            : '<div style="width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:900;color:#E87722;">P</div>';

        $preheaderHtml = $preheader !== ''
            ? '<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#EEF2F7;">' . e($preheader) . '&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>'
            : '';

        return '<!DOCTYPE html>
<html lang="pt" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>' . e(APP_NAME) . '</title>
<!--[if !mso]><!-->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet" type="text/css">
<!--<![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#EEF2F7;font-family:\'Inter\',Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
' . $preheaderHtml . '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EEF2F7;padding:32px 16px;">
<tr><td align="center">

  <!-- Container -->
  <table role="presentation" width="600" cellpadding="0" cellspacing="0"
         style="max-width:600px;width:100%;border-radius:16px;overflow:hidden;
                box-shadow:0 8px 32px rgba(0,0,0,0.12),0 2px 8px rgba(0,0,0,0.08);">

    <!-- HEADER -->
    <tr>
      <td style="background:linear-gradient(135deg,#1C1C1C 0%,#262626 100%);padding:32px 40px;border-bottom:3px solid #E87722;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="vertical-align:middle;width:72px;">
              ' . $logoHtml . '
            </td>
            <td style="vertical-align:middle;padding-left:16px;">
              <p style="margin:0 0 3px;font-size:10px;color:#F59340;
                         letter-spacing:2px;text-transform:uppercase;font-weight:700;">
                Polícia da República de Moçambique
              </p>
              <h1 style="margin:0 0 2px;font-size:22px;font-weight:900;color:#FFFFFF;letter-spacing:-0.3px;">
                ' . e(APP_NAME) . '
              </h1>
              <p style="margin:0;font-size:11px;color:rgba(255,255,255,0.45);">
                Sistema Integrado de Gestão de Ocorrências Policiais
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- TÍTULO DA SECÇÃO -->
    <tr>
      <td style="background:#E87722;padding:10px 40px;">
        <p style="margin:0;font-size:12px;font-weight:700;color:#FFFFFF;letter-spacing:1px;text-transform:uppercase;">
          ' . e($titulo) . '
        </p>
      </td>
    </tr>

    <!-- CORPO -->
    <tr>
      <td style="background:#FFFFFF;padding:36px 40px;">

        <!-- Saudação -->
        <p style="margin:0 0 6px;font-size:12px;color:#94a3b8;">Exmo(a) Sr(a),</p>
        <p style="margin:0 0 24px;font-size:18px;font-weight:800;color:#1e293b;letter-spacing:-0.3px;">
          ' . e($toNome) . '
        </p>

        <!-- Conteúdo dinâmico -->
        ' . $conteudo . '

      </td>
    </tr>

    <!-- DIVIDER -->
    <tr>
      <td style="background:#FFFFFF;padding:0 40px;">
        <div style="height:1px;background:linear-gradient(90deg,transparent,#E2E8F0,transparent);"></div>
      </td>
    </tr>

    <!-- AVISO -->
    <tr>
      <td style="background:#FFFFFF;padding:20px 40px 32px;">
        <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.7;">
          Este é um email automático gerado pelo sistema SIGOP-PRM. Por favor, não responda directamente
          a este email. Para assistência, contacte a esquadra responsável ou o administrador do sistema.
        </p>
      </td>
    </tr>

    <!-- FOOTER -->
    <tr>
      <td style="background:#1C1C1C;padding:20px 40px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td>
              <p style="margin:0 0 2px;font-size:12px;font-weight:700;color:#FFFFFF;">' . e(APP_NAME) . '</p>
              <p style="margin:0;font-size:11px;color:rgba(255,255,255,0.45);">
                ' . e(COMMAND_NAME) . ' &middot; ' . $dados . '
              </p>
            </td>
            <td align="right">
              <p style="margin:0;font-size:10px;color:rgba(255,255,255,0.3);">&copy; ' . $ano . ' PRM</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

  </table>
  <!-- /Container -->

</td></tr>
</table>

</body>
</html>';
    }

    private static function configurarSMTP(PHPMailer $mail): void {
        $mail->isSMTP();
        $mail->Host     = MAIL_HOST;
        $mail->Port     = MAIL_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USER;
        $mail->Password = MAIL_PASS;
        // As senhas de aplicação Google são apresentadas em grupos de quatro letras.
        if (strtolower(MAIL_HOST) === 'smtp.gmail.com'
            && preg_match('/^[a-z]{4}( [a-z]{4}){3}$/', MAIL_PASS)) {
            $mail->Password = str_replace(' ', '', MAIL_PASS);
        }

        switch (MAIL_ENCRYPTION) {
            case 'tls':
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                break;
            case 'ssl':
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                break;
            default:
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
        }

        $mail->CharSet = 'UTF-8';
        // Evita que uma falha de rede deixe o formulário à espera indefinidamente.
        $mail->Timeout = 5;
        $mail->getSMTPInstance()->Timelimit = 5;
    }

    private static function enviar(string $toEmail, string $toNome, string $subject, string $body, ?string $altBody = null): bool {
        if (empty(MAIL_USER) || empty(MAIL_PASS)) return false;

        $mail = self::$ligacaoSMTP ?? new PHPMailer(true);
        self::$ligacaoSMTP = null;
        try {
            self::configurarSMTP($mail);

            $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toNome);

            // Anexa o logo quando o layout o referencia.
            if (str_contains($body, 'cid:' . self::LOGO_CID) && file_exists(self::LOGO_PATH)) {
                $mail->addEmbeddedImage(self::LOGO_PATH, self::LOGO_CID, 'logo.png', 'base64', 'image/png');
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody ?? strip_tags(str_replace(
                ['<br>', '<br/>', '<br />', '</p>', '</tr>', '</td>'],
                ["\n", "\n", "\n", "\n", "\n", " "],
                $body
            ));

            $mail->send();
            return true;
        } catch (MailerException $e) {
            self::$ultimoErroSMTP = $e->getMessage();
            error_log('[SIGOP] Falha ao enviar email para ' . $toEmail . ': ' . $e->getMessage());
            return false;
        } finally {
            $mail->smtpClose();
        }
    }
}
