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

function save_to_csv($name, $email, $menge, $einheit, $nachricht, $status) {
    $dir  = __DIR__ . '/data';
    $file = $dir . '/anfragen.csv';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $new = !file_exists($file);
    $fh  = fopen($file, 'a');
    if (!$fh) return;
    if ($new) fputcsv($fh, ['Datum', 'Name', 'Email', 'Menge', 'Einheit', 'Nachricht', 'Email-Status']);
    fputcsv($fh, [date('Y-m-d H:i:s'), $name, $email, $menge, $einheit, $nachricht, $status]);
    fclose($fh);
}

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

    $mail->setFrom('info@premium-erde.de', 'Premium Erde');
    $mail->addAddress('info@premium-erde.de', 'Hanni');
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
    save_to_csv($name, $email, $menge, $einheit, $nachricht, 'gesendet');
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    save_to_csv($name, $email, $menge, $einheit, $nachricht, 'email-fehler');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $mail->ErrorInfo]);
}
