<?php
/**
 * email.php — PHPMailer/native mail + HTML email templates v2
 */

// ── Send email (PHPMailer → native fallback) ──────────────────
function mailAddresses(string|array $value): array {
    $list = is_array($value) ? $value : explode(',', (string)$value);
    $out = [];
    foreach ($list as $addr) {
        $addr = trim((string)$addr);
        if ($addr !== '' && filter_var($addr, FILTER_VALIDATE_EMAIL)) {
            $out[strtolower($addr)] = $addr;
        }
    }
    return array_values($out);
}

function mailReplyTo(string|array|null $value, string $fallbackName = ''): ?array {
    if (is_array($value)) {
        $email = trim((string)($value['email'] ?? $value[0] ?? ''));
        $name = trim((string)($value['name'] ?? $value[1] ?? $fallbackName));
    } else {
        $email = trim((string)$value);
        $name = $fallbackName;
    }
    $email = str_replace(["\r", "\n"], '', $email);
    $name = trim(str_replace(["\r", "\n"], ' ', $name));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    if ($name === '') {
        $name = $email;
    }
    return ['email' => $email, 'name' => $name];
}

function mailSetting(string $key, string $default = ''): string {
    try {
        if (function_exists('getSetting')) {
            $value = trim((string)getSetting($key, $default));
            if ($value !== '') {
                return $value;
            }
        }
    } catch (Exception $e) {
        // Fall back to constants when settings are unavailable.
    }
    return $default;
}

function supportMailbox(): array {
    $adminList = mailAddresses(mailSetting('admin_to', defined('ADMIN_TO') ? ADMIN_TO : ''));
    $email = $adminList[0] ?? mailSetting('mail_from', defined('MAIL_FROM') ? MAIL_FROM : '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = 'sales@aerogreetindia.com';
    }
    $name = mailSetting('mail_name', defined('MAIL_NAME') ? MAIL_NAME : 'Aero Greet India');
    if ($name === '') {
        $name = 'Aero Greet India';
    }
    return ['email' => $email, 'name' => $name];
}

function clientConfirmationCc(): array {
    return mailAddresses(['admin@travelblooper.com']);
}

function sendMail(string $to, string $toName, string $subject, string $html, array $options = []): array {
    $pmPath1 = __DIR__ . '/../vendor/autoload.php';
    $pmPath2 = __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    $pmOk = false;
    $toList = mailAddresses($to);
    $ccList = mailAddresses($options['cc'] ?? []);
    $replyTo = mailReplyTo($options['reply_to'] ?? null, $options['reply_to_name'] ?? '');

    if (file_exists($pmPath1)) { require_once $pmPath1; $pmOk = class_exists('PHPMailer\\PHPMailer\\PHPMailer'); }
    elseif (file_exists($pmPath2)) {
        require_once $pmPath2;
        require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/../PHPMailer/src/Exception.php';
        $pmOk = true;
    }

    // Start with config.php constants (always available)
    $smtpHost = SMTP_HOST; $smtpPort = SMTP_PORT;
    $smtpUser = SMTP_USER; $smtpPass = SMTP_PASS;
    $mailFrom = MAIL_FROM; $mailName = MAIL_NAME;
    // Override with DB settings if available
    try {
        if (function_exists('getSetting') && function_exists('dbOrNull') && dbOrNull() !== null) {
            $dbHost = trim(getSetting('smtp_host',''));
            $dbPort = (int)getSetting('smtp_port','0');
            $dbUser = trim(getSetting('smtp_user',''));
            $dbPass = getSetting('smtp_pass','');
            $dbFrom = trim(getSetting('mail_from',''));
            $dbName = trim(getSetting('mail_name',''));
            if ($dbHost !== '') $smtpHost = $dbHost;
            if ($dbPort > 0) $smtpPort = $dbPort;
            if ($dbUser !== '') $smtpUser = $dbUser;
            if ($dbPass !== '') $smtpPass = $dbPass;
            if ($dbFrom !== '') $mailFrom = $dbFrom;
            if ($dbName !== '') $mailName = $dbName;
        }
    } catch (Exception $e) { /* use config.php defaults */ }

    if ($pmOk) {
        try {
            $m = new PHPMailer\PHPMailer\PHPMailer(true);
            $m->isSMTP();
            $m->Host       = $smtpHost;
            $m->Port       = $smtpPort;
            $m->SMTPAuth   = true;
            $m->Username   = $smtpUser;
            $m->Password   = $smtpPass;
            $m->SMTPSecure = $smtpPort == 465
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $m->CharSet    = 'UTF-8';
            $m->isHTML(true);
            $m->setFrom($mailFrom, $mailName);
            foreach ($toList as $idx => $addr) $m->addAddress($addr, $idx === 0 ? $toName : '');
            foreach ($ccList as $addr) $m->addCC($addr);
            if ($replyTo) $m->addReplyTo($replyTo['email'], $replyTo['name']);
            $m->Subject = $subject;
            $m->Body    = $html;
            $m->AltBody = strip_tags($html);
            $m->send();
            return ['ok' => true, 'method' => 'PHPMailer/SMTP'];
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
        }
    }

    // Native mail fallback
    $nl  = "\r\n";
    $hdr = "MIME-Version: 1.0{$nl}Content-Type: text/html; charset=UTF-8{$nl}From: " . $mailName . " <" . $mailFrom . ">{$nl}";
    if ($ccList) $hdr .= "Cc: " . implode(',', $ccList) . $nl;
    if ($replyTo) $hdr .= "Reply-To: " . $replyTo['name'] . " <" . $replyTo['email'] . ">{$nl}";
    $hdr .= "X-Mailer: PHP/" . PHP_VERSION . $nl;
    $sent = false;
    $primary = $toList[0] ?? '';
    if ($primary !== '') $sent = @mail($primary, $subject, $html, $hdr);
    return ['ok' => $sent, 'method' => 'native mail()'];
}

// ════════════════════════════════════════════════════════════════
// SHARED EMAIL CHROME
// ════════════════════════════════════════════════════════════════
function emailHeader(string $badge = '', string $badgeLabel = ''): string {
    $f = "Calibri,'Segoe UI',Arial,sans-serif";
    // Try to get logo from site_media
    $logoUrl = '';
    try { $logoUrl = getSetting('logo_url',''); } catch(Exception $e){}
    $logoHtml = $logoUrl
        ? "<img src='{$logoUrl}' alt='Aero Greet India' style='height:38px;max-width:180px;object-fit:contain'>"
        : "<div style='font-family:{$f};font-size:20px;font-weight:700;color:#D4AF37'>&#9992; Aero Greet India</div><div style='font-family:{$f};font-size:10pt;color:#999;margin-top:2px'>A brand of Travel Blooper</div>";
    $b = $badge ? "<div style='background:rgba(212,175,55,.15);border:1px solid rgba(212,175,55,.4);border-radius:6px;padding:8px 18px;text-align:center'><div style='font-family:{$f};font-size:10pt;color:#D4AF37;letter-spacing:1.5px;text-transform:uppercase;font-weight:700'>{$badgeLabel}</div><div style='font-family:{$f};font-size:15pt;font-weight:700;color:#D4AF37;letter-spacing:2px;margin-top:2px'>{$badge}</div></div>" : '';
    return "
<table width='100%' cellpadding='0' cellspacing='0' style='background:#1A1A1A;padding:0'>
<tr><td style='padding:22px 28px'>{$logoHtml}</td>
<td style='padding:22px 28px;text-align:right;vertical-align:top'>{$b}</td></tr></table>";
}

function emailFooter(): string {
    $f = "Calibri,'Segoe UI',Arial,sans-serif";
    return "
<table width='100%' cellpadding='0' cellspacing='0' style='background:#111;padding:16px 28px'>
<tr><td style='text-align:center'>
<div style='font-family:{$f};font-size:12pt;font-weight:700;color:#D4AF37'>Aero Greet India</div>
<div style='font-family:{$f};font-size:10pt;color:#666;margin-top:3px'>A brand of Travel Blooper &middot; www.aerogreetindia.com</div>
<div style='font-family:{$f};font-size:10pt;color:#555;margin-top:5px'>
<a href='mailto:sales@aerogreetindia.com' style='color:#D4AF37;text-decoration:none'>sales@aerogreetindia.com</a>
&nbsp;&middot;&nbsp;
<a href='tel:+919536896071' style='color:#D4AF37;text-decoration:none'>+91 95368 96071</a>
&nbsp;&middot;&nbsp;
<a href='https://wa.me/919536896071' style='color:#D4AF37;text-decoration:none'>WhatsApp</a>
</div>
</td></tr></table>";
}

function row2(string $label, string $val, string $bg = '#fff'): string {
    $f = "Calibri,'Segoe UI',Arial,sans-serif";
    return "<tr><td style='padding:9px 16px;font-family:{$f};font-size:10.5pt;color:#777;background:{$bg};border-bottom:1px solid #EEE;width:38%;font-weight:600'>{$label}</td><td style='padding:9px 16px;font-family:{$f};font-size:10.5pt;color:#1A1A1A;background:{$bg};border-bottom:1px solid #EEE'>{$val}</td></tr>";
}

function sectionHead(string $title): string {
    $f = "Calibri,'Segoe UI',Arial,sans-serif";
    return "<div style='font-family:{$f};font-size:12pt;font-weight:700;color:#1A1A1A;margin:18px 0 8px;padding-left:10px;border-left:4px solid #D4AF37'>{$title}</div>";
}

function clientSummaryRows(array $rows): string {
    $f = "Calibri,'Segoe UI',Arial,sans-serif";
    $html = '';
    $rowIndex = 0;
    foreach ($rows as $row) {
        $label = trim((string)($row['label'] ?? ''));
        $value = (string)($row['value'] ?? '');
        if ($label === '' || $value === '') {
            continue;
        }
        $bg = ($rowIndex % 2 === 0) ? '#FBF8F1' : '#FFFFFF';
        $html .= "<tr>"
            . "<td style='padding:11px 12px;border:1px solid #E2DBD0;background:{$bg};font-family:{$f};font-size:11px;line-height:1.5;color:#5B544A;font-weight:700;width:36%;vertical-align:top'>{$label}</td>"
            . "<td style='padding:11px 12px;border:1px solid #E2DBD0;background:{$bg};font-family:{$f};font-size:11px;line-height:1.5;color:#1F1A14;vertical-align:top'>{$value}</td>"
            . "</tr>";
        $rowIndex++;
    }
    return $html;
}

function clientSummaryTable(string $title, array $rows): string {
    $f = "Calibri,'Segoe UI',Arial,sans-serif";
    $rowsHtml = clientSummaryRows($rows);
    if ($rowsHtml === '') {
        return '';
    }
    return "<table width='100%' cellpadding='0' cellspacing='0' style='width:100%;border-collapse:collapse;border:1px solid #E2DBD0;border-radius:8px;overflow:hidden;table-layout:fixed'>"
        . "<tr><td colspan='2' style='background:#D4AF37;padding:11px 14px;font-family:{$f};font-size:13px;line-height:1.4;color:#FFFFFF;font-weight:700'>{$title}</td></tr>"
        . $rowsHtml
        . "</table>";
}

function clientInfoSection(string $title, string $bodyHtml, string $background = '#F7F3EA'): string {
    $f = "Calibri,'Segoe UI',Arial,sans-serif";
    return "<table width='100%' cellpadding='0' cellspacing='0' style='width:100%;border-collapse:separate;border:1px solid #E2DBD0;border-radius:8px;background:{$background}'>"
        . "<tr><td style='padding:16px 18px'>"
        . "<div style='font-family:{$f};font-size:13px;line-height:1.4;color:#1F1A14;font-weight:700;margin:0 0 8px'>{$title}</div>"
        . "<div style='font-family:{$f};font-size:11px;line-height:1.7;color:#4E4840'>{$bodyHtml}</div>"
        . "</td></tr></table>";
}

function clientStepsHtml(array $steps): string {
    $f = "Calibri,'Segoe UI',Arial,sans-serif";
    $html = '';
    foreach ($steps as $step) {
        $step = trim((string)$step);
        if ($step === '') {
            continue;
        }
        $html .= "<div style='font-family:{$f};font-size:11px;line-height:1.7;color:#4E4840;margin:0 0 6px'>&#8226; {$step}</div>";
    }
    return $html;
}

function buildClientConfirmationEmail(string $badge, string $badgeLabel, string $statusText, string $greetingName, string $introHtml, array $rows, array $options = []): string {
    $f = "Calibri,'Segoe UI',Arial,sans-serif";
    $summaryTitle = trim((string)($options['summary_title'] ?? 'Submitted Details'));
    $footerNote = trim((string)($options['footer_note'] ?? 'If you need immediate assistance, reply to this email or contact our team.'));
    $afterTableHtml = (string)($options['after_table_html'] ?? '');
    $customHtml = trim((string)($options['custom_html'] ?? ''));
    $nextSteps = $options['next_steps'] ?? [];
    $statusTone = ($options['status_tone'] ?? 'gold') === 'green' ? 'green' : 'gold';
    $statusStyles = $statusTone === 'green'
        ? ['bg' => '#E9F8EE', 'border' => '#9AD9AE', 'text' => '#1E6A39']
        : ['bg' => '#FFF7E5', 'border' => '#E8CAA0', 'text' => '#8A5C14'];
    $summaryTable = clientSummaryTable($summaryTitle, $rows);
    $nextStepsHtml = clientStepsHtml(is_array($nextSteps) ? $nextSteps : []);
    $customSection = $customHtml !== '' ? clientInfoSection('Additional Information', $customHtml, '#FDF9F0') : '';
    $stepsSection = $nextStepsHtml !== '' ? clientInfoSection('What Happens Next', $nextStepsHtml) : '';

    return "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'></head>"
        . "<body style='margin:0;padding:0;background:#F2F2F2;font-family:{$f}'>"
        . "<table width='100%' cellpadding='0' cellspacing='0' style='background:#F2F2F2;padding:24px 12px'>"
        . "<tr><td align='center'>"
        . "<table width='620' cellpadding='0' cellspacing='0' style='width:100%;max-width:620px;border-radius:10px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.14)'>"
        . "<tr><td>" . emailHeader($badge, $badgeLabel) . "</td></tr>"
        . "<tr><td style='background:#FFFFFF;padding:24px 28px 18px'>"
        . "<div style='background:{$statusStyles['bg']};border:1px solid {$statusStyles['border']};border-radius:8px;padding:12px 14px;font-family:{$f};font-size:11px;line-height:1.6;color:{$statusStyles['text']};font-weight:700;margin-bottom:16px'>{$statusText}</div>"
        . "<div style='font-family:{$f};font-size:13px;line-height:1.4;color:#1F1A14;font-weight:700;margin:0 0 10px'>Dear {$greetingName},</div>"
        . "<div style='font-family:{$f};font-size:11px;line-height:1.7;color:#4E4840;margin:0 0 18px'>{$introHtml}</div>"
        . $summaryTable
        . ($afterTableHtml !== '' ? "<div style='height:16px;line-height:16px;font-size:16px'>&nbsp;</div>{$afterTableHtml}" : '')
        . ($customSection !== '' ? "<div style='height:16px;line-height:16px;font-size:16px'>&nbsp;</div>{$customSection}" : '')
        . ($stepsSection !== '' ? "<div style='height:16px;line-height:16px;font-size:16px'>&nbsp;</div>{$stepsSection}" : '')
        . "<div style='font-family:{$f};font-size:11px;line-height:1.7;color:#4E4840;margin:18px 0 0'>{$footerNote}</div>"
        . "</td></tr>"
        . "<tr><td>" . emailFooter() . "</td></tr>"
        . "</table>"
        . "</td></tr></table>"
        . "</body></html>";
}

function sendClientConfirmationMail(string $to, string $toName, string $subject, string $html, array $options = []): array {
    $support = supportMailbox();
    $existingCc = mailAddresses($options['cc'] ?? []);
    $options['cc'] = mailAddresses(array_merge(clientConfirmationCc(), $existingCc));
    $options['reply_to'] = mailReplyTo($options['reply_to'] ?? $support, $support['name']) ?? $support;
    return sendMail($to, $toName, $subject, $html, $options);
}

// ════════════════════════════════════════════════════════════════
// ADMIN NOTIFICATION EMAIL
// ════════════════════════════════════════════════════════════════
function buildAdminEmail(array $b): string {
    $f   = "Calibri,'Segoe UI',Arial,sans-serif";
    $ref = $b['ref']; $fn=$b['fn']; $ln=$b['ln']; $email=$b['email']; $phone=$b['phone'];
    $apt = $b['apt']; $svc=$b['svc']; $ft=$b['ft']; $pax=$b['pax'];
    $flno= $b['flno']?:($b['arrFl']?:$b['depFl']?:'—');
    $price=$b['price']; $addons=$b['addons']!=='None'?$b['addons']:'';
    $srcUrl=$b['srcUrl'];
    $dateTime=$b['date_fmt'].' '.$b['time_fmt'];
    $wa=preg_replace('/[^0-9]/','',urlencode($phone));

    $addonsRow = $addons ? "<div style='font-family:{$f};font-size:10pt;color:#B8600B;margin-top:4px'>+ Add-ons: {$addons}</div>" : '';

    $transitSection = '';
    if ($b['isT']) {
        $transitSection = "
<br>".sectionHead('🔄 Arriving Flight')."
<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #E0D5C0;border-radius:6px;overflow:hidden'>
".row2('Flight No.',"<strong>{$b['arrFl']}</strong>")
.row2('From',$b['arrFr'],'#FDFAF4')
.row2('Date &amp; Time',$b['arr_date_fmt'].' at '.$b['arr_time_fmt'])."
</table>
<br>".sectionHead('🔄 Connecting / Departing Flight')."
<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #E0D5C0;border-radius:6px;overflow:hidden'>
".row2('Flight No.',"<strong>{$b['depFl']}</strong>")
.row2('To',$b['depTo'],'#FDFAF4')
.row2('Date &amp; Time',$b['dep_date_fmt'].' at '.$b['dep_time_fmt'])."
</table>";
    }

    return "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#F2F2F2;font-family:{$f}'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#F2F2F2;padding:24px 0'>
<tr><td align='center'>
<table width='620' cellpadding='0' cellspacing='0' style='max-width:620px;width:100%;border-radius:10px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.14)'>
<tr><td>".emailHeader($ref,'NEW BOOKING')."</td></tr>
<tr><td style='background:#FFF8E8;border-left:5px solid #D4AF37;padding:10px 22px'>
<span style='font-family:{$f};font-size:10.5pt;color:#7A5000;font-weight:700'>⚡ Action Required</span>
<span style='font-family:{$f};font-size:10.5pt;color:#7A5000'> — Confirm within 2 hours</span>
</td></tr>
<tr><td style='background:#fff;padding:24px 28px'>

<table width='100%' cellpadding='0' cellspacing='0' style='border:2px solid #D4AF37;border-radius:8px;overflow:hidden;margin-bottom:20px'>
<tr><td colspan='2' style='background:#D4AF37;padding:10px 16px'><span style='font-family:{$f};font-size:12pt;font-weight:700;color:#fff'>📋 Booking Summary</span></td></tr>
<tr>
<td style='padding:12px 16px;background:#FDFAF4;border-bottom:1px solid #E0D5C0;vertical-align:top'>
<div style='font-family:{$f};font-size:9pt;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.7px;margin-bottom:2px'>Airport</div>
<div style='font-family:{$f};font-size:12pt;font-weight:700;color:#1A1A1A'>{$apt}</div>
</td>
<td style='padding:12px 16px;background:#FDFAF4;border-bottom:1px solid #E0D5C0;border-left:1px solid #E0D5C0;vertical-align:top'>
<div style='font-family:{$f};font-size:9pt;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.7px;margin-bottom:2px'>Date &amp; Time</div>
<div style='font-family:{$f};font-size:12pt;font-weight:700;color:#1A1A1A'>{$dateTime}</div>
</td>
</tr>
<tr><td colspan='2' style='padding:12px 16px;background:#fff'>
<div style='font-family:{$f};font-size:9pt;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.7px;margin-bottom:4px'>Price</div>
<div style='font-family:{$f};font-size:18pt;font-weight:700;color:#9A6F20'>{$price}</div>
{$addonsRow}
<div style='font-family:{$f};font-size:9pt;color:#999;margin-top:3px'>Incl. 18% GST &middot; Per person</div>
</td></tr>
</table>

".sectionHead('👤 Customer Details')."
<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #E0D5C0;border-radius:6px;overflow:hidden;margin-bottom:18px'>
".row2('Name',"$fn $ln",'#FDFAF4')
.row2('Email',"<a href='mailto:{$email}' style='color:#1a6fc4'>{$email}</a>")
.row2('Phone',"<a href='tel:{$phone}' style='color:#1a6fc4'>{$phone}</a>",'#FDFAF4')
.row2('Passengers',$pax)."
</table>

".sectionHead('✈ Travel Details')."
<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #E0D5C0;border-radius:6px;overflow:hidden;margin-bottom:18px'>
".row2('Service',$svc,'#FDFAF4')
.row2('Flight Type',$ft)
.row2('Flight No.',$flno,'#FDFAF4')
.row2('Ref No.',"<strong>#{$ref}</strong>")."
</table>

{$transitSection}

<table width='100%' cellpadding='0' cellspacing='0' style='margin-top:20px'>
<tr>
<td style='padding-right:8px'><a href='mailto:{$email}?subject=Re: Booking {$ref}' style='display:block;background:#D4AF37;color:#fff;text-align:center;padding:12px;border-radius:7px;text-decoration:none;font-family:{$f};font-size:11pt;font-weight:700'>📧 Reply to Client</a></td>
<td style='padding-left:8px'><a href='https://wa.me/{$wa}' style='display:block;background:#25D366;color:#fff;text-align:center;padding:12px;border-radius:7px;text-decoration:none;font-family:{$f};font-size:11pt;font-weight:700'>💬 WhatsApp Client</a></td>
</tr></table>
<div style='font-family:{$f};font-size:9pt;color:#999;margin-top:14px'>Source: <a href='{$srcUrl}' style='color:#888'>{$srcUrl}</a></div>

</td></tr>
<tr><td>".emailFooter()."</td></tr>
</table>
</td></tr></table>
</body></html>";
}

// ════════════════════════════════════════════════════════════════
// CLIENT CONFIRMATION EMAIL
// ════════════════════════════════════════════════════════════════
function buildClientEmail(array $b, ?array $tpl=null, bool $paid=false): string {
    $f   = "Calibri,'Segoe UI',Arial,sans-serif";
    $ref = $b['ref']; $fn=$b['fn']; $ln=$b['ln']; $email=$b['email']; $phone=$b['phone'];
    $apt = $b['apt']; $svc=$b['svc']; $ft=$b['ft']; $pax=$b['pax'];
    $flno= $b['flno']?:($b['arrFl']?:$b['depFl']?:'—');
    $price=$b['price'];

    // Custom notes from template
    $headerNote = $tpl['header_note'] ?? 'Thank you for booking with Aero Greet India. We have received your request and our team is checking the availability of meet & greet slots. We will contact you shortly via Email and WhatsApp to confirm your booking.';
    $footerNote = $tpl['footer_note'] ?? 'For urgent queries call +91 95368 96071 or WhatsApp us.';
    $customNote = $tpl['custom_note'] ?? '';

    if ($paid) {
        $headerNote = 'Your payment has been received and your booking is confirmed! Our team will contact you with your greeter\'s details shortly.';
    }

    $transitSection = '';
    if ($b['isT']) {
        $transitSection = "
<tr><td style='background:#fff;padding:0 28px 20px'>
".sectionHead('🔄 Your Transit Flight Details')."
<table width='100%' cellpadding='0' cellspacing='0' style='border:2px solid #D4AF37;border-radius:6px;overflow:hidden'>
<tr><td colspan='2' style='background:#D4AF37;padding:8px 14px'><span style='font-family:{$f};font-size:11pt;font-weight:700;color:#fff'>✈ Arriving Flight</span></td></tr>
".row2('Flight No.',"<strong>{$b['arrFl']}</strong>")
.row2('From',$b['arrFr'],'#FDFAF4')
.row2('Arrival Date &amp; Time',$b['arr_date_fmt'].' at '.$b['arr_time_fmt'])."
<tr><td colspan='2' style='background:#D4AF37;padding:8px 14px'><span style='font-family:{$f};font-size:11pt;font-weight:700;color:#fff'>✈ Connecting / Departing Flight</span></td></tr>
".row2('Flight No.',"<strong>{$b['depFl']}</strong>")
.row2('To',$b['depTo'],'#FDFAF4')
.row2('Departure Date &amp; Time',$b['dep_date_fmt'].' at '.$b['dep_time_fmt'])."
</table>
</td></tr>";
    }

    $statusBadge = $paid
        ? "<div style='background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:12px 18px;text-align:center;margin-bottom:18px'><div style='font-family:{$f};font-size:11pt;font-weight:700;color:#166534'>✅ Payment Confirmed — Booking #{$ref}</div></div>"
        : "<div style='background:#FFF8E8;border:1px solid rgba(212,175,55,.5);border-radius:8px;padding:12px 18px;text-align:center;margin-bottom:18px'><div style='font-family:{$f};font-size:11pt;font-weight:700;color:#9A6F20'>📋 Booking Received — #{$ref}</div></div>";

    $customSection = $customNote ? "<tr><td style='background:#FDFAF4;padding:12px 28px;border-top:1px solid #EEE;border-bottom:1px solid #EEE'><div style='font-family:{$f};font-size:10.5pt;color:#555;line-height:1.6'>{$customNote}</div></td></tr>" : '';

    return "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#F2F2F2;font-family:{$f}'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#F2F2F2;padding:24px 0'>
<tr><td align='center'>
<table width='620' cellpadding='0' cellspacing='0' style='max-width:620px;width:100%;border-radius:10px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.14)'>
<tr><td>".emailHeader()."</td></tr>
<tr><td style='background:#fff;padding:24px 28px 12px'>
{$statusBadge}
<p style='font-family:{$f};font-size:12pt;color:#1A1A1A;margin:0 0 12px'>Dear <strong>{$fn} {$ln}</strong>,</p>
<p style='font-family:{$f};font-size:11pt;color:#444;line-height:1.7;margin:0 0 12px'>{$headerNote}</p>
<p style='font-family:{$f};font-size:11pt;color:#444;margin:0 0 0'>Urgent? Call us: <a href='tel:+919536896071' style='color:#D4AF37;text-decoration:none;font-weight:700'>+91 95368 96071</a> or <a href='https://wa.me/919536896071' style='color:#25D366;text-decoration:none;font-weight:700'>WhatsApp</a></p>
</td></tr>
<tr><td style='background:#fff;padding:0 28px 20px'>
<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #E0D5C0;border-radius:6px;overflow:hidden'>
<tr><td colspan='2' style='background:#D4AF37;padding:10px 16px'><span style='font-family:{$f};font-size:12pt;font-weight:700;color:#fff'>Your Booking Details</span></td></tr>
".row2('Booking Ref',"<strong style='color:#D4AF37'>#{$ref}</strong>",'#FDFAF4')
.row2('Airport',$apt)
.row2('Service',$svc,'#FDFAF4')
.row2('Flight Type',$ft)
.row2('Name',"$fn $ln",'#FDFAF4')
.row2('Passengers',$pax)
.row2('Email',$email,'#FDFAF4')
.row2('Phone',$phone)
.row2('Date',$b['date_fmt'],'#FDFAF4')
.row2('Time',$b['time_fmt'])
.row2('Flight No.',$flno,'#FDFAF4')
.row2('Price',"<strong style='color:#9A6F20;font-size:13pt'>{$price}</strong>")."
</table>
</td></tr>
{$transitSection}
{$customSection}
<tr><td style='background:#FDFAF4;padding:18px 28px'>
<div style='font-family:{$f};font-size:12pt;font-weight:700;color:#1A1A1A;margin-bottom:10px'>What happens next?</div>
<div style='font-family:{$f};font-size:11pt;color:#444;line-height:2'>
" . ($paid
    ? "✅&nbsp; Your booking is <strong>confirmed</strong><br>
✅&nbsp; Our coordinator will share your greeter's details<br>
✅&nbsp; Invoice will be sent by <strong>Travel Blooper</strong><br>
✅&nbsp; Keep this email as your booking reference"
    : "✅&nbsp; Our coordinator will call / WhatsApp you within 2 hours<br>
✅&nbsp; We are checking meet &amp; greet slot availability<br>
✅&nbsp; You'll receive your greeter's name and meeting point<br>
✅&nbsp; Invoice will be sent by <strong>Travel Blooper</strong><br>
✅&nbsp; No payment required until after confirmation") . "
</div>
</td></tr>
<tr><td style='padding:14px 28px;background:#fff'>
<p style='font-family:{$f};font-size:10.5pt;color:#666;line-height:1.6;margin:0'>{$footerNote}</p>
</td></tr>
<tr><td>".emailFooter()."</td></tr>
</table>
</td></tr></table>
</body></html>";
}

// ════════════════════════════════════════════════════════════════
// CONTACT EMAIL
// ════════════════════════════════════════════════════════════════
function contactEmail(string $fn, string $ln, string $email, string $phone, string $subject, string $msg): string {
    $f   = "Calibri,'Segoe UI',Arial,sans-serif";
    $now = date('d M Y H:i A');
    $wa  = preg_replace('/[^0-9]/', '', $phone);
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#F2F2F2'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#F2F2F2;padding:24px 0'>
<tr><td align='center'>
<table width='620' cellpadding='0' cellspacing='0' style='max-width:620px;width:100%;border-radius:10px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.14)'>
<tr><td>".emailHeader()."</td></tr>
<tr><td style='background:#FFF8E8;border-left:5px solid #D4AF37;padding:10px 22px'>
<span style='font-family:{$f};font-size:10.5pt;color:#7A5000;font-weight:700'>📩 New Contact Message — {$now}</span>
</td></tr>
<tr><td style='background:#fff;padding:24px 28px'>
<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #E0D5C0;border-radius:6px;overflow:hidden;margin-bottom:18px'>
".row2('Name',"$fn $ln",'#FDFAF4')
.row2('Email',"<a href='mailto:{$email}'>{$email}</a>")
.row2('Phone',$phone,'#FDFAF4')
.row2('Subject',$subject)."
<tr><td style='padding:9px 16px;font-family:{$f};font-size:10.5pt;color:#777;background:#FDFAF4;width:38%;font-weight:600;vertical-align:top'>Message</td>
<td style='padding:9px 16px;font-family:{$f};font-size:10.5pt;color:#1A1A1A;background:#FDFAF4;line-height:1.6'>{$msg}</td></tr>
</table>
<table width='100%' cellpadding='0' cellspacing='0'>
<tr>
<td style='padding-right:8px'><a href='mailto:{$email}?subject=Re: {$subject}' style='display:block;background:#D4AF37;color:#fff;text-align:center;padding:12px;border-radius:7px;text-decoration:none;font-family:{$f};font-size:11pt;font-weight:700'>📧 Reply</a></td>
<td style='padding-left:8px'><a href='https://wa.me/{$wa}' style='display:block;background:#25D366;color:#fff;text-align:center;padding:12px;border-radius:7px;text-decoration:none;font-family:{$f};font-size:11pt;font-weight:700'>💬 WhatsApp</a></td>
</tr></table>
</td></tr>
<tr><td>".emailFooter()."</td></tr>
</table></td></tr></table></body></html>";
}

function buildBookingClientConfirmationEmail(array $b, ?array $tpl = null, bool $paid = false): string {
    $ref = $b['ref']; $fn = $b['fn']; $ln = $b['ln']; $email = $b['email']; $phone = $b['phone'];
    $apt = $b['apt']; $svc = $b['svc']; $ft = $b['ft']; $pax = $b['pax'];
    $flno = $b['flno'] ?: ($b['arrFl'] ?: ($b['depFl'] ?: '--'));
    $price = $b['price'];
    $headerNote = $tpl['header_note'] ?? 'Thank you for your booking request. We have received your details and our team is checking meet and greet availability for your journey.';
    $footerNote = $tpl['footer_note'] ?? 'For urgent assistance, call +91 95368 96071 or message us on WhatsApp.';
    $customNote = trim((string)($tpl['custom_note'] ?? ''));

    if ($paid) {
        $headerNote = 'Your payment has been received and your booking is confirmed. Our team will share the greeter details with you shortly.';
    }

    $rows = [
        ['label' => 'Booking Reference', 'value' => "<strong style='color:#8A5C14'>#{$ref}</strong>"],
        ['label' => 'Name', 'value' => trim("{$fn} {$ln}")],
        ['label' => 'Email', 'value' => "<a href='mailto:{$email}' style='color:#8A5C14;text-decoration:none'>{$email}</a>"],
        ['label' => 'Phone', 'value' => "<a href='tel:{$phone}' style='color:#8A5C14;text-decoration:none'>{$phone}</a>"],
        ['label' => 'Airport', 'value' => $apt],
        ['label' => 'Service', 'value' => $svc],
        ['label' => 'Flight Type', 'value' => $ft],
        ['label' => 'Passengers', 'value' => $pax],
        ['label' => 'Travel Date', 'value' => $b['date_fmt']],
        ['label' => 'Flight Time', 'value' => $b['time_fmt']],
        ['label' => 'Flight Number', 'value' => $flno],
        ['label' => 'Price', 'value' => "<strong style='color:#8A5C14'>{$price}</strong>"],
    ];
    if (($b['addons'] ?? '') !== '' && ($b['addons'] ?? '') !== 'None') {
        $rows[] = ['label' => 'Add-ons', 'value' => $b['addons']];
    }
    if (($b['special'] ?? '') !== '') {
        $rows[] = ['label' => 'Special Request', 'value' => $b['special']];
    }

    $afterTableHtml = '';
    if (!empty($b['isT'])) {
        $transitTables = [];
        $arrivingTable = clientSummaryTable('Arriving Flight', [
            ['label' => 'Flight Number', 'value' => $b['arrFl'] ?? ''],
            ['label' => 'From', 'value' => $b['arrFr'] ?? ''],
            ['label' => 'Arrival Date', 'value' => $b['arr_date_fmt'] ?? ''],
            ['label' => 'Arrival Time', 'value' => $b['arr_time_fmt'] ?? ''],
        ]);
        $departingTable = clientSummaryTable('Connecting or Departing Flight', [
            ['label' => 'Flight Number', 'value' => $b['depFl'] ?? ''],
            ['label' => 'To', 'value' => $b['depTo'] ?? ''],
            ['label' => 'Departure Date', 'value' => $b['dep_date_fmt'] ?? ''],
            ['label' => 'Departure Time', 'value' => $b['dep_time_fmt'] ?? ''],
        ]);
        if ($arrivingTable !== '') {
            $transitTables[] = $arrivingTable;
        }
        if ($departingTable !== '') {
            $transitTables[] = $departingTable;
        }
        if ($transitTables) {
            $afterTableHtml = clientInfoSection(
                'Transit Flight Details',
                implode("<div style='height:12px;line-height:12px;font-size:12px'>&nbsp;</div>", $transitTables)
            );
        }
    }

    $nextSteps = $paid
        ? [
            'Your booking is confirmed and reserved.',
            'Our coordinator will share greeter details and meeting instructions.',
            'Please keep this email for your reference.',
        ]
        : [
            'Our coordinator will review availability and contact you shortly.',
            'You may receive a follow-up by email or WhatsApp for final confirmation.',
            'Please keep this email as your submission reference.',
        ];

    return buildClientConfirmationEmail(
        "#{$ref}",
        $paid ? 'BOOKING CONFIRMED' : 'BOOKING RECEIVED',
        $paid ? "Payment received for booking #{$ref}." : "Thank you for your booking request. Reference #{$ref} has been received.",
        trim("{$fn} {$ln}") ?: 'Guest',
        $headerNote,
        $rows,
        [
            'summary_title' => 'Submitted Booking Details',
            'footer_note' => $footerNote,
            'after_table_html' => $afterTableHtml,
            'custom_html' => $customNote,
            'next_steps' => $nextSteps,
            'status_tone' => $paid ? 'green' : 'gold',
        ]
    );
}

function buildContactClientEmail(string $fn, string $ln, string $email, string $phone, string $subject, string $msg): string {
    $submittedAt = date('d M Y h:i A');
    $rows = [
        ['label' => 'Name', 'value' => trim("{$fn} {$ln}")],
        ['label' => 'Email', 'value' => "<a href='mailto:{$email}' style='color:#8A5C14;text-decoration:none'>{$email}</a>"],
        ['label' => 'Phone', 'value' => $phone !== '' ? "<a href='tel:{$phone}' style='color:#8A5C14;text-decoration:none'>{$phone}</a>" : 'Not provided'],
        ['label' => 'Subject', 'value' => $subject],
        ['label' => 'Message', 'value' => $msg],
        ['label' => 'Submitted On', 'value' => $submittedAt],
    ];

    return buildClientConfirmationEmail(
        'ENQUIRY',
        'MESSAGE RECEIVED',
        'Thank you for contacting Aero Greet India. Your message has been received successfully.',
        trim("{$fn} {$ln}") ?: 'Guest',
        'Thank you for reaching out to Aero Greet India. Our team will review your message and respond as soon as possible.',
        $rows,
        [
            'summary_title' => 'Submitted Contact Details',
            'footer_note' => 'If your request is urgent, call +91 95368 96071 or reply directly to this email.',
            'next_steps' => [
                'Our team will review your enquiry carefully.',
                'You can expect a response by email or phone as soon as possible.',
                'Please keep this confirmation for your records.',
            ],
            'status_tone' => 'gold',
        ]
    );
}
