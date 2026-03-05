<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$name      = htmlspecialchars(trim($_POST['name']      ?? ''));
$email     = htmlspecialchars(trim($_POST['email']     ?? ''));
$menge     = htmlspecialchars(trim($_POST['menge']     ?? ''));
$einheit   = htmlspecialchars(trim($_POST['einheit']   ?? ''));
$nachricht = htmlspecialchars(trim($_POST['nachricht'] ?? ''));

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.resend.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'resend';
    $configFile = __DIR__ . '/config.php';
    if (file_exists($configFile)) require $configFile;
    $mail->Password   = defined('RESEND_API_KEY') ? RESEND_API_KEY : getenv('RESEND_API_KEY');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // FROM muss eine verifizierte Domain bei Resend sein
    $mail->setFrom('hanni@premium-erde.de', 'Premium Erde');
    $mail->addAddress('hanni@premium-erde.de', 'Hanni');
    if ($email) {
        $mail->addReplyTo($email, $name ?: 'Anfrage');
    }

    $subject = $name ? "Neue Anfrage von $name" : 'Neue Kontaktanfrage';
    $mail->Subject = $subject;

    $body = "Name:    $name\n";
    $body .= "E-Mail:  $email\n";
    if ($menge) {
        $body .= "Menge:   $menge $einheit\n";
    }
    $body .= "\nNachricht:\n$nachricht";

    $mail->Body = $body;

    $mail->send();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $mail->ErrorInfo]);
}
