<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail_config.php';

class MailHandler {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer() {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = SMTP_AUTH;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = SMTP_SECURE;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        } catch (Exception $e) {
            throw new Exception("Error al configurar el mailer: {$this->mailer->ErrorInfo}");
        }
    }

    public function sendContactEmail($name, $email, $subject, $message) {
        try {
            $this->mailer->addAddress('marianagonzalezochoa8@gmail.com');
            $this->mailer->addReplyTo($email, $name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Nuevo mensaje de contacto: $subject";
            
            $body = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333333;
                        margin: 0;
                        padding: 0;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                    }
                    .header {
                        background-color: #fcd5e4;
                        color: white;
                        padding: 20px;
                        text-align: center;
                        border-radius: 5px 5px 0 0;
                    }
                    .content {
                        background-color: #ffffff;
                        padding: 20px;
                        border: 1px solid #dddddd;
                        border-radius: 0 0 5px 5px;
                    }
                    .info-item {
                        margin-bottom: 15px;
                        padding: 10px;
                        background-color: #f9f9f9;
                        border-radius: 4px;
                    }
                    .info-label {
                        font-weight: bold;
                        color: #fcd5e4;
                    }
                    .message-box {
                        background-color: #f5f5f5;
                        padding: 15px;
                        border-left: 4px solid #fcd5e4;
                        margin-top: 20px;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 20px;
                        padding: 20px;
                        color: #666666;
                        font-size: 12px;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>Nuevo Mensaje de Contacto</h2>
                    </div>
                    <div class="content">
                        <div class="info-item">
                            <span class="info-label">Nombre:</span> ' . htmlspecialchars($name) . '
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span> ' . htmlspecialchars($email) . '
                        </div>
                        <div class="info-item">
                            <span class="info-label">Asunto:</span> ' . htmlspecialchars($subject) . '
                        </div>
                        <div class="message-box">
                            <span class="info-label">Mensaje:</span><br>
                            ' . nl2br(htmlspecialchars($message)) . '
                        </div>
                    </div>
                    <div class="footer">
                        Este mensaje fue enviado desde el formulario de contacto de tu sitio web.
                    </div>
                </div>
            </body>
            </html>';

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            return $this->mailer->send();
        } catch (Exception $e) {
            throw new Exception("Error al enviar el correo: {$this->mailer->ErrorInfo}");
        }
    }
}
?> 