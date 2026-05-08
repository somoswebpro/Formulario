<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ================== CONFIGURACIÓN ==================
    $secretKey = "TU_SECRET_KEY_V3_AQUI";   // Cámbiala por tu Secret Key v3
    $recipientEmail = "tucorreo@dominio.com"; // Correo donde recibirás los datos
    // ==================================================

    // 1. Verificar token de reCAPTCHA v3
    $recaptchaToken = $_POST['recaptcha_token'] ?? '';
    if (empty($recaptchaToken)) {
        echo "<script>alert('reCAPTCHA token missing. Please try again.'); window.history.back();</script>";
        exit;
    }

    $verifyUrl = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaToken";
    $verifyResponse = file_get_contents($verifyUrl);
    $responseData = json_decode($verifyResponse);

    if (!$responseData->success || $responseData->score < 0.5) {
        // Score bajo (posible bot) o fallo de verificación
        echo "<script>alert('reCAPTCHA verification failed. Please ensure you are human and try again.'); window.history.back();</script>";
        exit;
    }

    // 2. Sanitizar datos del formulario
    $fullname = htmlspecialchars(trim($_POST['fullname'] ?? ''));
    $email    = htmlspecialchars(trim($_POST['email'] ?? ''));
    $zipcode  = htmlspecialchars(trim($_POST['zipcode'] ?? ''));
    $phone    = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $city     = htmlspecialchars(trim($_POST['city'] ?? ''));
    $message  = htmlspecialchars(trim($_POST['message'] ?? ''));
    $services = isset($_POST['services']) ? implode(", ", array_map('htmlspecialchars', $_POST['services'])) : "None selected";

    // Validar campos requeridos
    if (empty($fullname) || empty($email) || empty($zipcode) || empty($phone) || empty($city)) {
        echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
        exit;
    }

    // 3. Subir archivo (si existe)
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $filePath = "";
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['file']['name']);
        $safeName = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        $target = $uploadDir . $safeName;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $filePath = $target;
        }
    }

    // 4. Construir correo electrónico
    $subject = "New Quote Request - Hands on Epoxy";
    $headers = "From: Hands on Epoxy Handyman <noreply@tudominio.com>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $emailBody = "<h2>✨ New Quote Request from $fullname</h2>";
    $emailBody .= "<p><strong>Full Name:</strong> $fullname</p>";
    $emailBody .= "<p><strong>Email:</strong> $email</p>";
    $emailBody .= "<p><strong>Phone:</strong> $phone</p>";
    $emailBody .= "<p><strong>Zip Code:</strong> $zipcode</p>";
    $emailBody .= "<p><strong>City:</strong> $city</p>";
    $emailBody .= "<p><strong>Services needed:</strong> $services</p>";
    $emailBody .= "<p><strong>Message:</strong><br>" . nl2br($message) . "</p>";
    if ($filePath) {
        $emailBody .= "<p><strong>Attached file:</strong> <a href='$filePath'>$filePath</a></p>";
    }
    $emailBody .= "<hr><small>Sent via reCAPTCHA v3 - Score: {$responseData->score}</small>";

    // 5. Enviar correo
    $mailSent = mail($recipientEmail, $subject, $emailBody, $headers);

    if ($mailSent) {
        echo "<script>alert('✅ Form sent successfully! We will contact you soon.'); window.location.href = 'free-estimate.html';</script>";
    } else {
        echo "<script>alert('❌ Error sending form. Please try again later or call us directly.'); window.history.back();</script>";
    }
} else {
    // Si alguien accede directamente al PHP, redirigir al formulario
    header("Location: free-estimate.html");
    exit;
}
?>
