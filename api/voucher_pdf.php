<?php

function agVoucherValue($value, string $fallback = '-'): string {
    $text = trim(html_entity_decode((string)($value ?? ''), ENT_QUOTES, 'UTF-8'));
    if ($text === '') {
        return $fallback;
    }
    $text = preg_replace('/\s+/', ' ', $text) ?: '';
    return $text !== '' ? $text : $fallback;
}

function agVoucherEsc($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function agVoucherSafeName(string $value): string {
    $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $value);
    $safe = trim((string)$safe, '-');
    return $safe !== '' ? $safe : 'voucher';
}

function agVoucherDateTime(array $booking): string {
    if (!empty($booking['service_datetime'])) {
        $ts = strtotime((string)$booking['service_datetime']);
        if ($ts) {
            return date('d M Y, h:i A', $ts);
        }
    }

    $date = trim((string)($booking['travel_date'] ?? ''));
    $time = trim((string)($booking['flight_time'] ?? ''));
    if ($date !== '' && $time !== '') {
        $ts = strtotime($date . ' ' . $time);
        if ($ts) {
            return date('d M Y, h:i A', $ts);
        }
    }

    if ($date !== '') {
        $ts = strtotime($date);
        if ($ts) {
            return date('d M Y', $ts);
        }
    }

    return '-';
}

function agVoucherStatusClass(string $status): string {
    $status = strtolower(trim($status));
    switch ($status) {
        case 'confirmed':
        case 'paid':
        case 'completed':
            return 'ok';
        case 'in queue':
            return 'queue';
        case 'cancelled':
            return 'cancelled';
        default:
            return 'pending';
    }
}

function agVoucherJoin(array $parts, string $fallback = '-'): string {
    $clean = [];
    foreach ($parts as $part) {
        $value = trim((string)$part);
        if ($value !== '' && $value !== '-') {
            $clean[] = $value;
        }
    }
    return $clean ? implode(' | ', $clean) : $fallback;
}

function agVoucherRows(array $rows): string {
    $html = '';
    foreach ($rows as $label => $value) {
        $html .= '<div class="row">';
        $html .= '<div class="label">' . agVoucherEsc($label) . '</div>';
        $html .= '<div class="value">' . agVoucherEsc($value) . '</div>';
        $html .= '</div>';
    }
    return $html;
}

function agVoucherWhatsAppSvg(): string {
    return '<svg viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path fill="#25D366" d="M16.02 3.2c-7.06 0-12.78 5.7-12.78 12.74 0 2.25.6 4.44 1.74 6.37L3.1 28.8l6.67-1.75a12.83 12.83 0 0 0 6.25 1.6h.01c7.06 0 12.78-5.71 12.78-12.75 0-3.41-1.33-6.61-3.74-9.01A12.7 12.7 0 0 0 16.02 3.2Z"/><path fill="#FFF" d="M25.12 16.01c0-2.42-.95-4.7-2.66-6.4a9.03 9.03 0 0 0-6.43-2.66c-5.02 0-9.1 4.07-9.1 9.08 0 1.61.42 3.18 1.23 4.57l.19.31-1.08 3.94 4.04-1.06.3.18a9.12 9.12 0 0 0 4.39 1.13h.01c5.02 0 9.11-4.08 9.11-9.09Z"/><path fill="#25D366" d="M21.47 18.53c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.95 1.15-.17.2-.35.22-.65.08-.3-.15-1.27-.47-2.42-1.5-.9-.8-1.5-1.79-1.68-2.1-.18-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.08-.15-.67-1.6-.92-2.18-.24-.58-.48-.49-.67-.5h-.57c-.2 0-.53.08-.8.38-.27.3-1.04 1.01-1.04 2.46s1.07 2.86 1.22 3.06c.15.2 2.09 3.19 5.07 4.47.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.08 1.75-.72 2-1.42.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35Z"/></svg>';
}

function agVoucherTransitBlock(array $booking): string {
    if (empty($booking['is_transit'])) {
        return '';
    }

    $arrival = agVoucherJoin([
        $booking['arr_flight_no'] ?? '',
        $booking['arr_from'] ?? '',
        $booking['arr_date'] ?? '',
        $booking['arr_time'] ?? '',
    ], 'Arrival details not shared');

    $departure = agVoucherJoin([
        $booking['dep_flight_no'] ?? '',
        $booking['dep_to'] ?? '',
        $booking['dep_date'] ?? '',
        $booking['dep_time'] ?? '',
    ], 'Departure details not shared');

    return '
      <section class="section section-full keep">
        <div class="section-title">Transit Flight Details</div>
        <div class="mini-grid">
          <div class="mini-card">
            <div class="mini-title">Arrival Leg</div>
            <div class="mini-copy">' . agVoucherEsc($arrival) . '</div>
          </div>
          <div class="mini-card">
            <div class="mini-title">Departure Leg</div>
            <div class="mini-copy">' . agVoucherEsc($departure) . '</div>
          </div>
        </div>
      </section>';
}

function agVoucherHtml(array $booking): string {
    $ref = agVoucherValue($booking['ref'] ?? '', 'TBA');
    $status = agVoucherValue($booking['status'] ?? '', 'Pending');
    $issuedAt = date('d M Y, h:i A');
    $serviceDate = agVoucherDateTime($booking);
    $statusClass = agVoucherStatusClass($status);

    $customer = trim((string)($booking['first_name'] ?? '') . ' ' . (string)($booking['last_name'] ?? ''));
    $customer = trim($customer);
    if ($customer === '') {
        $customer = agVoucherValue($booking['email'] ?? '', 'Guest');
    }

    $airport = agVoucherValue($booking['airport_name'] ?? ($booking['airport_code'] ?? ''), '-');
    $service = agVoucherValue($booking['service_type'] ?? '', '-');
    $flightType = agVoucherValue($booking['flight_type'] ?? '', '-');
    $passengers = agVoucherValue($booking['passengers'] ?? '', '1');
    $flightNo = agVoucherValue($booking['flight_no'] ?? '', '-');
    $terminal = agVoucherValue($booking['terminal'] ?? '', '-');
    $repName = agVoucherValue($booking['supplier_name'] ?? '', 'To be assigned by our operations team');
    $repContact = '+91 9536896071';
    $airportWelcome = $airport !== '-' ? $airport : 'your selected airport';

    $passengerRows = agVoucherRows([
        'Passenger Name' => $customer,
        'No. of Pax' => $passengers,
        'Booking Reference' => $ref,
        'Booking Status' => $status,
    ]);

    $serviceRows = agVoucherRows([
        'Airport' => $airport,
        'Service Type' => $service,
        'Flight Type' => $flightType,
        'Flight Number' => $flightNo,
        'Service Date & Time' => $serviceDate,
        'Terminal' => $terminal,
    ]);

    $transitBlock = agVoucherTransitBlock($booking);

    $refEsc = agVoucherEsc($ref);
    $statusEsc = agVoucherEsc($status);
    $issuedAtEsc = agVoucherEsc($issuedAt);
    $airportWelcomeEsc = agVoucherEsc($airportWelcome);
    $serviceEsc = agVoucherEsc($service);
    $flightNoEsc = agVoucherEsc($flightNo);
    $serviceDateEsc = agVoucherEsc($serviceDate);
    $repNameEsc = agVoucherEsc($repName);
    $repContactEsc = agVoucherEsc($repContact);
    $waIcon = agVoucherWhatsAppSvg();
    $repContactLine = '<span class="wa-line"><span class="wa-icon">' . $waIcon . '</span><span>' . $repContactEsc . '</span></span>';

    return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Confirmation Voucher - {$refEsc}</title>
  <style>
    *{box-sizing:border-box}
    html,body{margin:0;padding:0}
    body{
      font-family:Calibri, Arial, sans-serif;
      color:#1d2a36;
      background:#e9edf2;
    }
    .screen{
      padding:12px 0;
    }
    .toolbar{
      width:210mm;
      margin:0 auto 8px;
      display:flex;
      justify-content:flex-end;
      gap:8px;
      padding:0 2mm;
    }
    .toolbar button,
    .toolbar a{
      font-family:Calibri, Arial, sans-serif;
      font-size:12px;
      font-weight:700;
      border-radius:4px;
      padding:8px 14px;
      cursor:pointer;
      text-decoration:none;
      border:1px solid #7d8b99;
      background:#fff;
      color:#1d2a36;
    }
    .toolbar button{
      background:#d8b45c;
      border-color:#b89234;
      color:#fff;
    }
    .sheet{
      width:210mm;
      min-height:297mm;
      margin:0 auto;
      background:#fff;
      border:1px solid #c9d1da;
      box-shadow:0 8px 24px rgba(19,30,45,.10);
      padding:11mm 12mm 9mm;
      display:flex;
      flex-direction:column;
    }
    .topbar{
      border-top:4px solid #c79a32;
      border-bottom:1px solid #d6dde4;
      padding:0 0 8px;
      margin-bottom:8px;
    }
    .head{
      display:flex;
      justify-content:space-between;
      gap:10mm;
      align-items:flex-start;
    }
    .brand-name{
      font-size:28px;
      font-weight:700;
      letter-spacing:.3px;
      color:#19314e;
      margin:0;
      line-height:1.1;
    }
    .brand-sub{
      margin-top:4px;
      font-size:12px;
      color:#566574;
      line-height:1.45;
    }
    .meta{
      min-width:62mm;
      border:1px solid #cfd7df;
      padding:8px 10px;
    }
    .meta-line{
      display:flex;
      justify-content:space-between;
      gap:10px;
      padding:2px 0;
      font-size:12px;
      line-height:1.35;
    }
    .meta-label{
      font-weight:700;
      color:#516170;
      text-transform:uppercase;
      letter-spacing:.3px;
      font-size:11px;
    }
    .meta-value{
      font-weight:700;
      color:#1c2936;
      text-align:right;
    }
    .status-ok{color:#1f7d47}
    .status-queue{color:#215ec4}
    .status-pending{color:#9a6b12}
    .status-cancelled{color:#b43f3f}
    .title-band{
      margin:8px 0 10px;
      border:1px solid #d8dee5;
      background:#f6f8fa;
      text-align:center;
      padding:8px 10px;
    }
    .title-band h1{
      margin:0;
      font-size:20px;
      letter-spacing:.8px;
      color:#1b3351;
      font-weight:700;
      text-transform:uppercase;
    }
    .title-band p{
      margin:3px 0 0;
      font-size:12px;
      color:#627181;
    }
    .hero-strip{
      display:grid;
      grid-template-columns:repeat(4,1fr);
      gap:8px;
      margin-bottom:10px;
    }
    .hero-card{
      border:1px solid #d8dee5;
      padding:8px 9px;
      min-height:48px;
    }
    .hero-label{
      font-size:10px;
      font-weight:700;
      color:#677686;
      text-transform:uppercase;
      letter-spacing:.5px;
      margin-bottom:4px;
    }
    .hero-value{
      font-size:14px;
      font-weight:700;
      color:#1b2c3e;
      line-height:1.3;
      word-break:break-word;
    }
    .grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:10px;
      flex:1;
    }
    .section{
      border:1px solid #d8dee5;
      padding:9px 10px 4px;
    }
    .section-full{
      grid-column:1 / -1;
    }
    .section-title{
      font-size:13px;
      font-weight:700;
      color:#1d3858;
      text-transform:uppercase;
      letter-spacing:.5px;
      padding-bottom:6px;
      margin-bottom:2px;
      border-bottom:1px solid #e2e7ec;
    }
    .row{
      display:grid;
      grid-template-columns:42mm 1fr;
      gap:8px;
      padding:6px 0;
      border-bottom:1px solid #edf1f4;
    }
    .row:last-child{
      border-bottom:none;
    }
    .label{
      font-size:12px;
      color:#627181;
      font-weight:700;
    }
    .value{
      font-size:12.5px;
      color:#1e2d3b;
      line-height:1.4;
      font-weight:600;
      word-break:break-word;
    }
    .mini-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:8px;
    }
    .mini-card{
      border:1px solid #dfe5eb;
      padding:8px 9px;
    }
    .mini-title{
      font-size:12px;
      font-weight:700;
      color:#25405f;
      text-transform:uppercase;
      margin-bottom:5px;
    }
    .mini-copy{
      font-size:12px;
      line-height:1.45;
      color:#243342;
    }
    .contact-block{
      grid-column:1 / -1;
      border:1px solid #d8dee5;
      padding:9px 10px 4px;
    }
    .contact-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:8px;
      margin-top:2px;
    }
    .contact-card{
      border:1px solid #dfe5eb;
      padding:8px 9px;
    }
    .contact-title{
      font-size:12px;
      font-weight:700;
      color:#25405f;
      text-transform:uppercase;
      margin-bottom:5px;
    }
    .contact-value{
      font-size:14px;
      font-weight:700;
      color:#1b2c3e;
      line-height:1.35;
    }
    .wa-line{
      display:inline-flex;
      align-items:center;
      gap:6px;
      white-space:nowrap;
    }
    .wa-icon{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:18px;
      height:18px;
      flex:0 0 18px;
    }
    .wa-icon svg{
      display:block;
      width:18px;
      height:18px;
    }
    .contact-note{
      font-size:11px;
      color:#6a7988;
      margin-top:4px;
      line-height:1.4;
    }
    .message{
      grid-column:1 / -1;
      border:1px solid #d8dee5;
      padding:10px;
      background:#fcfcfd;
    }
    .message p{
      margin:0;
      font-size:12.5px;
      line-height:1.6;
      color:#283644;
    }
    .message p + p{
      margin-top:6px;
    }
    .footer{
      margin-top:10px;
      padding-top:8px;
      border-top:1px solid #d8dee5;
      display:flex;
      justify-content:space-between;
      gap:10px;
      font-size:11px;
      color:#667585;
    }
    .footer strong{
      color:#2b3a49;
    }
    .keep{
      page-break-inside:avoid;
      break-inside:avoid;
    }
    @media (max-width: 900px){
      .screen{padding:8px}
      .toolbar,
      .sheet{width:100%}
      .sheet{min-height:auto}
      .hero-strip,
      .grid,
      .mini-grid,
      .contact-grid{
        grid-template-columns:1fr;
      }
      .head{
        flex-direction:column;
      }
      .meta{
        width:100%;
        min-width:0;
      }
      .row{
        grid-template-columns:1fr;
        gap:4px;
      }
    }
    @page{
      size:A4;
      margin:6mm;
    }
    @media print{
      html,body{background:#fff}
      .screen{
        padding:0;
      }
      .toolbar{
        display:none;
      }
      .sheet{
        width:auto;
        min-height:auto;
        margin:0;
        border:none;
        box-shadow:none;
        padding:7mm 8mm 6mm;
        page-break-after:avoid;
        break-after:avoid;
      }
      .topbar{
        padding:0 0 6px;
        margin-bottom:6px;
      }
      .head{
        gap:6mm;
      }
      .brand-name{
        font-size:23px;
      }
      .brand-sub{
        font-size:10.5px;
        line-height:1.35;
      }
      .meta{
        min-width:56mm;
        padding:6px 8px;
      }
      .meta-line{
        font-size:10.5px;
        padding:1px 0;
      }
      .meta-label{
        font-size:9.5px;
      }
      .title-band{
        margin:6px 0 8px;
        padding:6px 8px;
      }
      .title-band h1{
        font-size:17px;
      }
      .title-band p{
        font-size:10.5px;
        margin-top:2px;
      }
      .hero-strip{
        gap:6px;
        margin-bottom:8px;
      }
      .hero-card{
        padding:6px 7px;
        min-height:40px;
      }
      .hero-label{
        font-size:8.5px;
        margin-bottom:3px;
      }
      .hero-value{
        font-size:11.5px;
        line-height:1.22;
      }
      .grid{
        gap:8px;
      }
      .section,
      .contact-block,
      .message{
        padding:7px 8px 3px;
      }
      .section-title{
        font-size:11px;
        padding-bottom:5px;
      }
      .row{
        grid-template-columns:34mm 1fr;
        gap:6px;
        padding:4px 0;
      }
      .label{
        font-size:10.5px;
      }
      .value{
        font-size:11px;
        line-height:1.28;
      }
      .mini-grid,
      .contact-grid{
        gap:6px;
      }
      .mini-card,
      .contact-card{
        padding:6px 7px;
      }
      .mini-title,
      .contact-title{
        font-size:10.5px;
        margin-bottom:4px;
      }
      .mini-copy{
        font-size:10.5px;
        line-height:1.32;
      }
      .contact-value{
        font-size:12px;
        line-height:1.2;
      }
      .wa-line{
        gap:5px;
      }
      .wa-icon{
        width:14px;
        height:14px;
        flex-basis:14px;
      }
      .wa-icon svg{
        width:14px;
        height:14px;
      }
      .contact-note,
      .message p{
        font-size:10.5px;
        line-height:1.35;
      }
      .message p + p{
        margin-top:4px;
      }
      .footer{
        margin-top:8px;
        padding-top:6px;
        font-size:9.8px;
        gap:8px;
      }
      .footer strong{
        font-size:10px;
      }
    }
  </style>
</head>
<body>
  <div class="screen">
    <div class="toolbar">
      <button type="button" onclick="window.print()">Print Voucher</button>
      <a href="javascript:window.close()">Close</a>
    </div>

    <article class="sheet">
      <div class="topbar">
        <div class="head">
          <div>
            <h2 class="brand-name">Aero Greet India</h2>
            <div class="brand-sub">Premium airport meet and greet assistance across India<br>Aero Greet India is Brand of Travel Blooper</div>
          </div>

          <div class="meta">
            <div class="meta-line">
              <div class="meta-label">Reference</div>
              <div class="meta-value">{$refEsc}</div>
            </div>
            <div class="meta-line">
              <div class="meta-label">Status</div>
              <div class="meta-value status-{$statusClass}">{$statusEsc}</div>
            </div>
            <div class="meta-line">
              <div class="meta-label">Issued On</div>
              <div class="meta-value">{$issuedAtEsc}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="title-band keep">
        <h1>Confirmation Voucher</h1>
        <p>Please carry this voucher and present it to our service team at the airport.</p>
      </div>

      <div class="hero-strip keep">
        <div class="hero-card">
          <div class="hero-label">Airport</div>
          <div class="hero-value">{$airportWelcomeEsc}</div>
        </div>
        <div class="hero-card">
          <div class="hero-label">Service Type</div>
          <div class="hero-value">{$serviceEsc}</div>
        </div>
        <div class="hero-card">
          <div class="hero-label">Flight Number</div>
          <div class="hero-value">{$flightNoEsc}</div>
        </div>
        <div class="hero-card">
          <div class="hero-label">Service Date &amp; Time</div>
          <div class="hero-value">{$serviceDateEsc}</div>
        </div>
      </div>

      <div class="grid">
        <section class="section keep">
          <div class="section-title">Passenger Details</div>
          {$passengerRows}
        </section>

        <section class="section keep">
          <div class="section-title">Flight And Service Details</div>
          {$serviceRows}
        </section>

        {$transitBlock}

        <section class="contact-block keep">
          <div class="section-title">Airport Assistance Contact</div>
          <div class="contact-grid">
            <div class="contact-card">
              <div class="contact-title">Airport Rep Name</div>
              <div class="contact-value">{$repNameEsc}</div>
              <div class="contact-note">Your assigned airport representative for service coordination.</div>
            </div>
            <div class="contact-card">
              <div class="contact-title">Contact No.</div>
              <div class="contact-value">{$repContactLine}</div>
              <div class="contact-note">Please call this number if you need immediate help at the airport.</div>
            </div>
          </div>
        </section>

        <section class="message keep">
          <p>If you require any further assistance, please feel free to reach out to us at +91 9536896071.</p>
          <p>We look forward to welcoming you to {$airportWelcomeEsc}.</p>
        </section>
      </div>

      <div class="footer">
        <div><strong>Aero Greet India</strong><br>sales@aerogreetindia.com</div>
        <div><strong>Support</strong><br>{$repContactLine}</div>
        <div><strong>Voucher Ref</strong><br>{$refEsc}</div>
      </div>
    </article>
  </div>
</body>
</html>
HTML;
}

function agEnsureVoucherFile(PDO $pdo, array $booking): array {
    $allowed = ['Confirmed', 'Paid', 'Completed'];
    if (!in_array((string)($booking['status'] ?? ''), $allowed, true)) {
        return ['ok' => false, 'error' => 'Voucher is available after confirmation.'];
    }

    $html = agVoucherHtml($booking);

    try {
        $pdo->prepare("UPDATE bookings SET voucher_file=NULL, voucher_generated_at=NOW(), updated_at=NOW() WHERE ref=?")
            ->execute([$booking['ref']]);
    } catch (Exception $e) {
        try {
            $pdo->prepare("UPDATE bookings SET voucher_generated_at=NOW(), updated_at=NOW() WHERE ref=?")
                ->execute([$booking['ref']]);
        } catch (Exception $ignored) {
        }
    }

    return [
        'ok' => true,
        'content' => $html,
        'mime' => 'text/html; charset=UTF-8',
        'relative_path' => '',
        'abs_path' => '',
        'saved' => false,
    ];
}
