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
            $this->mailer->addAddress('rojasgaleanosamuel@gmail.com');
            $this->mailer->addReplyTo($email, $name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Nuevo mensaje de contacto: $subject";
            $body = "
                <h2>Nuevo mensaje de contacto</h2>
                <p><strong>Nombre:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Asunto:</strong> {$subject}</p>
                <p><strong>Mensaje:</strong></p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            ";
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            return $this->mailer->send();
        } catch (Exception $e) {
            throw new Exception("Error al enviar el correo: {$this->mailer->ErrorInfo}");
        }
    }
}
?> 