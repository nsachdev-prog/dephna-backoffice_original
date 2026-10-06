<?php
    $to = 'rajive@dephna.com';
    $from = 'robot@dephna.com';
    $fromName = 'Dephna Robot';
    $subject = 'Running Daily Backup';
    $htmlContent = '<h3>Running Daily Backup</h3><p>This email is sent from the PHP script with attachment.</p>';
    $headers = "From: $fromName"." <".$from.">";
    $semi_rand = md5(time());
    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
    $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";
    $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n";
    if (!empty($rdbfile) > 0) {
        if (is_file($rdbfile)) {
            $message .= "--{$mime_boundary}\n";
            $fp   = @fopen($rdbfile,"rb");
            $data = @fread($fp,filesize($rdbfile));
            @fclose($fp);
            $data = chunk_split(base64_encode($data));
            $message .= "Content-Type: application/octet-stream; name=\"".basename($rdbfile)."\"\n" .
            "Content-Description: ".basename($rdbfile)."\n" .
            "Content-Disposition: attachment;\n" . " filename=\"".basename($rdbfile)."\"; size=".filesize($rdbfile).";\n" .
            "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
        }
    }
    $message .= "--{$mime_boundary}--";
    $mail = @mail($to, $subject, $message, $headers);
?>
