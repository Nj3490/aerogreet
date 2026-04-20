<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Aero Greet India — Setup</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#080C10;color:#EEF2F7;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
.wrap{background:#131E2D;border:1px solid rgba(212,175,55,.25);border-radius:20px;padding:2.5rem;max-width:680px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.5)}
h1{color:#D4AF37;font-size:1.6rem;margin-bottom:.25rem}
.sub{color:#8FA0B8;font-size:.85rem;margin-bottom:2rem}
.step{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:1.25rem 1.5rem;margin-bottom:1rem}
.step-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#D4AF37;margin-bottom:.85rem}
label{display:block;font-size:.7rem;font-weight:700;color:#8FA0B8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}
input,select{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(212,175,55,.18);border-radius:8px;padding:10px 13px;color:#EEF2F7;font-size:.88rem;outline:none;margin-bottom:.85rem;font-family:inherit;transition:border-color .2s}
input:focus{border-color:#D4AF37}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.note{font-size:.7rem;color:#6A7E96;margin-top:-0.6rem;margin-bottom:.75rem}
.btn{width:100%;background:linear-gradient(135deg,#D4AF37,#9a7a30);color:#080C10;padding:13px;border-radius:10px;font-weight:700;font-size:.95rem;border:none;cursor:pointer;font-family:inherit;transition:.2s;margin-top:.5rem}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(212,175,55,.4)}
.result{margin-top:1.5rem;padding:1.25rem 1.5rem;border-radius:10px;font-size:.84rem;line-height:2;white-space:pre-wrap}
.ok{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);color:#4ade80}
.err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#f87171}
.warn{background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.25);color:#fbbf24}
a{color:#D4AF37}
</style>
</head>
<body>
<div class="wrap">
  <h1>✈ Aero Greet India — Setup</h1>
  <p class="sub">One-time database installation. Run once after uploading files to your cPanel server. Delete this file afterwards.</p>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    $host   = trim($_POST['db_host']    ?? 'localhost');
    $name   = trim($_POST['db_name']    ?? '');
    $user   = trim($_POST['db_user']    ?? '');
    $pass   = $_POST['db_pass']          ?? '';
    $auser  = trim($_POST['admin_user'] ?? 'admin');
    $aemail = trim($_POST['admin_email']?? '');
    $apass  = $_POST['admin_pass']       ?? '';

    if (!$name || !$user || !$aemail || !$apass) { echo '<div class="result err">❌ All fields are required.</div>'; goto show_form; }

    $log = []; $ok = true;

    // 1. Connect
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $log[] = '✅ Database connection successful';
    } catch (Exception $e) {
        echo '<div class="result err">❌ Cannot connect: '.htmlspecialchars($e->getMessage()).'</div>';
        goto show_form;
    }

    // 2. Run schema
    $schema = file_get_contents(__DIR__.'/schema.sql');
    if ($schema) {
        try {
            foreach (array_filter(array_map('trim', explode(';', $schema))) as $sql) {
                if ($sql) $pdo->exec($sql);
            }
            $log[] = '✅ Database tables created';
        } catch (Exception $e) { $log[] = '⚠️ Schema warning: '.$e->getMessage(); }
    }

    // 3. Seed airports
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM airports")->fetchColumn();
    if ($cnt === 0) {
        $seed = file_get_contents(__DIR__.'/seed_airports.sql');
        if ($seed) {
            try { $pdo->exec($seed); $log[] = '✅ 91 airports seeded'; }
            catch (Exception $e) { $log[] = '⚠️ Airport seed: '.$e->getMessage(); }
        } else $log[] = '⚠️ seed_airports.sql not found — run manually';
    } else {
        $log[] = "✅ Airports already present ({$cnt} records)";
    }

    // 4. Admin account
    $hash = password_hash($apass, PASSWORD_BCRYPT, ['cost'=>12]);
    try {
        $pdo->prepare("INSERT INTO admins(username,email,password_hash,role)VALUES(?,?,?,'super') ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)")->execute([$auser,$aemail,$hash]);
        $log[] = "✅ Admin account: {$auser}";
    } catch (Exception $e) { $log[] = '⚠️ Admin: '.$e->getMessage(); }

    // 5. Default settings
    $defaults = ['smtp_host'=>'mail.aerogreetindia.com','smtp_port'=>'587','smtp_user'=>'hello@aerogreetindia.com','smtp_pass'=>'Hello@3490','mail_from'=>'hello@aerogreetindia.com','admin_to'=>'sales@aerogreetindia.com,admin@travelblooper.com','whatsapp'=>'919536896071','site_name'=>'Aero Greet India','razorpay_enabled'=>'0','razorpay_key_id'=>'','razorpay_key_secret'=>'','razorpay_currency'=>'INR'];
    $st = $pdo->prepare("INSERT IGNORE INTO settings(k,v)VALUES(?,?)");
    foreach ($defaults as $k=>$v) $st->execute([$k,$v]);
    $log[] = '✅ Default settings saved';

    // 5b. v2 schema additions (safe to run on existing installs)
    $v2sql = [
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS supplier_name VARCHAR(150) DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS supplier_cost DECIMAL(10,2) DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS selling_price DECIMAL(10,2) DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(80) DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS service_datetime DATETIME DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS voucher_file VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS voucher_generated_at DATETIME DEFAULT NULL",
        "ALTER TABLE bookings MODIFY COLUMN status ENUM('Pending','In Queue','Confirmed','Paid','Completed','Cancelled') DEFAULT 'Pending'",
        "CREATE TABLE IF NOT EXISTS customers (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190), email VARCHAR(255) UNIQUE NOT NULL, phone VARCHAR(50), total_bookings INT DEFAULT 0, last_booking DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_customer_last_booking (last_booking)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS email_templates (id INT AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(80) UNIQUE NOT NULL, label VARCHAR(120), subject VARCHAR(255), header_note TEXT, footer_note TEXT, custom_note TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "INSERT IGNORE INTO email_templates(slug,label,subject,header_note,footer_note,custom_note) VALUES('client_confirmation','Client Booking Confirmation','Booking Received — {ref} | Aero Greet India','Thank you for booking with Aero Greet India. We have received your request and are checking the availability of meet & greet slots. We will contact you shortly via Email and WhatsApp to confirm your booking.','For urgent queries call +91 95368 96071 or WhatsApp us.','')",
        "CREATE TABLE IF NOT EXISTS site_media (id INT AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(100) UNIQUE NOT NULL, label VARCHAR(150), url TEXT, section VARCHAR(80) DEFAULT 'general', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "INSERT IGNORE INTO site_media(slug,label,section,url) VALUES ('logo_url','Site Logo URL','branding',''),('hero_1','Hero Slide 1','hero','https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1920&q=85'),('hero_2','Hero Slide 2','hero','https://images.unsplash.com/photo-1530521954074-e64f6810b32d?w=1920&q=85'),('hero_3','Hero Slide 3','hero','https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=1920&q=85'),('hero_4','Hero Slide 4','hero','https://images.unsplash.com/photo-1483450388369-9ed95738483c?w=1920&q=85'),('hero_5','Hero Slide 5','hero','https://images.unsplash.com/photo-1569154941061-e231b4725ef1?w=1920&q=85'),('about_banner','About Page Banner','pages',''),('contact_banner','Contact Page Banner','pages','')"
    ];
    foreach ($v2sql as $sql) {
        try { $pdo->exec($sql); } catch (Exception $e) { /* column may already exist */ }
    }
    $log[] = '✅ v2 tables and columns applied';

    // 6. Write config.php
    $secret = bin2hex(random_bytes(32));
    $cfg = "<?php\ndefine('DB_HOST','".addslashes($host)."');\ndefine('DB_NAME','".addslashes($name)."');\ndefine('DB_USER','".addslashes($user)."');\ndefine('DB_PASS','".addslashes($pass)."');\ndefine('DB_CHARSET','utf8mb4');\ndefine('SITE_URL','https://aerogreetindia.com');\ndefine('SITE_NAME','Aero Greet India');\ndefine('SMTP_HOST','mail.aerogreetindia.com');\ndefine('SMTP_PORT',587);\ndefine('SMTP_USER','hello@aerogreetindia.com');\ndefine('SMTP_PASS','Hello@3490');\ndefine('MAIL_FROM','hello@aerogreetindia.com');\ndefine('MAIL_NAME','Aero Greet India');\ndefine('ADMIN_TO','sales@aerogreetindia.com,admin@travelblooper.com');\ndefine('APP_SECRET','{$secret}');\ndefine('SESSION_TTL',7200);\ndefine('RATE_LIMIT',20);\nfunction db():PDO{static \$p;if(!\$p){\$p=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}return \$p;}\n";
    if (@file_put_contents(__DIR__.'/config.php', $cfg)) $log[] = '✅ config.php written';
    else $log[] = '⚠️ Could not write config.php — update DB credentials manually';

    $cls = $ok ? 'ok' : 'err';
    echo '<div class="result '.$cls.'">'.implode("\n",$log).'</div>';
    if ($ok) echo '<div class="result warn" style="margin-top:.75rem">⚠️ Delete <strong>install.php</strong> after setup!<br><br>🔗 <a href="admin/">Open Admin Dashboard →</a><br>🔗 <a href="dashboard/">User Dashboard →</a></div>';
    goto done;
    show_form:
endif;
?>
  <form method="POST">
    <div class="step">
      <div class="step-label">① Database (create in cPanel → MySQL Databases first)</div>
      <label>Host</label><input name="db_host" value="localhost">
      <div class="fr">
        <div><label>Database Name</label><input name="db_name" placeholder="user_aerogreet" required></div>
        <div><label>Username</label><input name="db_user" placeholder="user_dbuser" required></div>
      </div>
      <label>Password</label><input name="db_pass" type="password" required>
    </div>
    <div class="step">
      <div class="step-label">② Admin Account</div>
      <div class="fr">
        <div><label>Username</label><input name="admin_user" value="admin"></div>
        <div><label>Email</label><input name="admin_email" type="email" placeholder="you@domain.com" required></div>
      </div>
      <label>Password</label><input name="admin_pass" type="password" placeholder="Strong password" required>
    </div>
    <button type="submit" class="btn">✈ Run Installation</button>
  </form>
<?php done: ?>
</div>
</body>
</html>
