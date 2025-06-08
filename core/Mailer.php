<?php


require_once __DIR__ . '/../libs/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/phpmailer/src/Exception.php';
require_once __DIR__ . '/../libs/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
class Mailer
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
    }

    private function loadConfig()
    {
        $config = parse_ini_file(__DIR__ . '/../configuration/config.ini', true);
        return $config['smtp'];
    }

    public function configureMailer()
    {
        $config = $this->loadConfig();

        $this->mail->isSMTP();
        $this->mail->Host = $config['host'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $config['username'];
        $this->mail->Password = $config['password'];
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = $config['port'];
    }

    public function enviarCorreoActivacion($email, $usuario, $token)
    {
        try {
            $this->configureMailer();

            $this->mail->setFrom($this->mail->Username, 'Quiz Game');
            $this->mail->addAddress($email, $usuario);

            $config = parse_ini_file(__DIR__ . '/../configuration/config.ini', true);
            $baseUrl = $config['app']['base_path'];
            $link = $baseUrl . "register/activate?token=" . $token;
            $body = "
                <div style='font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 40px;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
            
                        <h2 style='color: #333333; text-align: center;'>¡Hola $usuario!</h2>
            
                        <p style='font-size: 16px; color: #555555; text-align: center;'>
                            Gracias por registrarte en <strong>Quiz Game</strong>.
                            Para activar tu cuenta, hacé clic en el botón de abajo:
                        </p>
            
                        <p style='text-align: center; margin: 30px 0;'>
                            <a href='$link'
                               style='display: inline-block;
                                      background-color: #007bff;
                                      color: #ffffff;
                                      padding: 12px 24px;
                                      text-decoration: none;
                                      font-size: 16px;
                                      border-radius: 6px;
                                      font-weight: bold;'>
                                Activar cuenta
                            </a>
                        </p>
            
                        <p style='font-size: 14px; color: #888888; text-align: center;'>
                            Si vos no realizaste este registro, podés ignorar este correo.
                        </p>
            
                    </div>
                </div>
            ";

            $this->mail->isHTML(true);
            $this->mail->Subject = 'Activa tu cuenta';
            $this->mail->Body    = $body;

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            return 'Error al enviar el correo: ' . $this->mail->ErrorInfo;
        }
    }
}