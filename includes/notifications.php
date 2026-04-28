<?php
/**
 * TechnoHacks Notification Module
 * Handles WhatsApp and Email triggers
 */

function sendAdmissionNotification($student_id, $mobile, $email, $pdf_link) {
    // WhatsApp URL
    $wa_msg = urlencode("Congratulations! Your admission at TechnoHacks is confirmed.\nStudent ID: $student_id\nDownload your receipt: $pdf_link");
    $wa_link = "https://wa.me/$mobile?text=$wa_msg";
    
    // Email logic (Placeholder for PHPMailer)
    // In a real scenario, you would include PHPMailer here.
    $to = $email;
    $subject = "Admission Confirmed - TechnoHacks";
    $message = "Dear Student,\n\nYour admission is confirmed. Your Student ID is $student_id.\nYou can download your receipt here: $pdf_link\n\nRegards,\nTechnoHacks Solutions";
    $headers = "From: no-reply@technohacks.com";
    
    @mail($to, $subject, $message, $headers);
    
    return [
        'whatsapp' => $wa_link,
        'email_sent' => true
    ];
}
?>
