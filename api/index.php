<?php
/**
 * api/index.php — Aero Greet India REST API v2.1
 * Fully DB-resilient: email routes work even if DB is unavailable
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/voucher_pdf.php';

// ── CORS ─────────────────────────────────────────────────────────
$allowed = ['https://aerogreetindia.com','https://www.aerogreetindia.com',
            'http://aerogreetindia.com','http://www.aerogreetindia.com',
            'http://localhost','http://127.0.0.1'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$h = in_array($origin,$allowed) ? $origin : ($origin ?: '*');
header("Access-Control-Allow-Origin: $h");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type,Authorization,X-Requested-With');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(200);exit;}

// ── Helpers ──────────────────────────────────────────────────────
function ok($data=[],$code=200):never{http_response_code($code);echo json_encode(['ok'=>true]+$data);exit;}
function fail($msg,$code=400,$extra=[]):never{http_response_code($code);echo json_encode(['ok'=>false,'error'=>$msg]+$extra);exit;}
function body():array{static $d;if($d===null){$raw=file_get_contents('php://input');$d=json_decode($raw,true)??$_POST??[];}return $d;}
function g($k,$def=''):string{return htmlspecialchars(trim((string)($_GET[$k]??$def)),ENT_QUOTES,'UTF-8');}
function s($v):string{return htmlspecialchars(trim((string)($v??'')),ENT_QUOTES,'UTF-8');}

// DB with graceful fail — returns null if unavailable
function dbOrNull():?PDO {
    static $pdo, $tried=false;
    if($tried)return $pdo;
    $tried=true;
    try{
        $pdo=new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
            DB_USER,DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES=>false]
        );
    }catch(PDOException $e){
        error_log("AG DB connect fail: ".$e->getMessage());
        $pdo=null;
    }
    return $pdo;
}

// DB required — fail with proper message if unavailable
function requireDB():PDO {
    $pdo=dbOrNull();
    if(!$pdo)fail('Database not configured. Please run install.php first.',503);
    return $pdo;
}

function rateLimit(int $max=RATE_LIMIT):void{
    $ip=trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']??$_SERVER['REMOTE_ADDR']??'x')[0]);
    $f=sys_get_temp_dir().'/ag_rl_'.md5($ip);
    $d=file_exists($f)?json_decode(file_get_contents($f),true):['c'=>0,'w'=>time()];
    if(time()-$d['w']>3600)$d=['c'=>0,'w'=>time()];
    if(++$d['c']>$max){file_put_contents($f,json_encode($d),LOCK_EX);fail('Too many requests',429);}
    file_put_contents($f,json_encode($d),LOCK_EX);
}

function bearerToken():string{
    $h=$_SERVER['HTTP_AUTHORIZATION']??'';
    return str_starts_with($h,'Bearer ')?substr($h,7):'';
}

function newSession(PDO $pdo, int $id, string $type):string{
    $tok=bin2hex(random_bytes(32));
    $exp=date('Y-m-d H:i:s',time()+SESSION_TTL);
    $pdo->prepare("DELETE FROM sessions WHERE expires_at<NOW()")->execute();
    $pdo->prepare("INSERT INTO sessions(token,entity_id,entity_type,expires_at,ip)VALUES(?,?,?,?,?)")
       ->execute([$tok,$id,$type,$exp,$_SERVER['REMOTE_ADDR']??'']);
    return $tok;
}

function authAdmin():array{
    $pdo=requireDB();
    $tok=bearerToken()?:($_COOKIE['ag_adm']??'');
    if(!$tok)fail('Unauthorized',401);
    $st=$pdo->prepare("SELECT s.entity_id id,a.username,a.email,a.role FROM sessions s JOIN admins a ON s.entity_id=a.id WHERE s.token=? AND s.entity_type='admin' AND s.expires_at>NOW()");
    $st->execute([$tok]);
    $r=$st->fetch();
    if(!$r)fail('Session expired',401);
    return $r;
}

function authUser():array{
    $pdo=requireDB();
    $tok=bearerToken()?:($_COOKIE['ag_usr']??'');
    if(!$tok)fail('Unauthorized',401);
    $st=$pdo->prepare("SELECT s.entity_id id,u.email,u.first_name,u.last_name,u.phone FROM sessions s JOIN users u ON s.entity_id=u.id WHERE s.token=? AND s.entity_type='user' AND s.expires_at>NOW()");
    $st->execute([$tok]);
    $r=$st->fetch();
    if(!$r)fail('Session expired',401);
    return $r;
}

function genRef(PDO $pdo):string{
    $y=date('Y');
    $n=(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE ref LIKE 'TB-$y-%'")->fetchColumn();
    return 'TB-'.$y.'-'.str_pad($n+1,4,'0',STR_PAD_LEFT);
}

function paginate(int $total,int $page,int $pp):array{
    $pages=max(1,(int)ceil($total/$pp));
    $page=max(1,min($page,$pages));
    return['total'=>$total,'page'=>$page,'per_page'=>$pp,'total_pages'=>$pages,'offset'=>($page-1)*$pp];
}

function setCookie2(string $name,string $val):void{
    setcookie($name,$val,['expires'=>time()+SESSION_TTL,'path'=>'/','httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
}

function getSetting(string $k, string $def=''):string{
    $pdo=dbOrNull();
    if(!$pdo)return $def;
    try{
        $st=$pdo->prepare("SELECT v FROM settings WHERE k=? LIMIT 1");
        $st->execute([$k]);
        $r=$st->fetchColumn();
        return $r!==false?(string)$r:$def;
    }catch(Exception $e){return $def;}
}

// ── Router ───────────────────────────────────────────────────────
$route  = g('r');
$method = $_SERVER['REQUEST_METHOD'];

match(true){
    $route==='airports'                => routeAirports($method),
    $route==='book'                    => routeBook($method),
    $route==='contact'                 => routeContact($method),
    $route==='track'                   => routeTrack($method),
    $route==='auth/login'              => routeAuthLogin($method),
    $route==='auth/register'           => routeAuthRegister($method),
    $route==='auth/admin-login'        => routeAdminLogin($method),
    $route==='auth/logout'             => routeLogout(),
    $route==='auth/me'                 => routeMe(),
    $route==='auth/change-password'    => routeChangePassword($method),
    $route==='admin/stats'             => routeAdminStats($method),
    $route==='admin/bookings'          => routeAdminBookings($method),
    $route==='admin/bookings-export'   => routeAdminBookingsExport($method),
    $route==='admin/prices'            => routeAdminPrices($method),
    $route==='admin/customers'         => routeAdminCustomers($method),
    $route==='admin/customers-export'  => routeAdminCustomersExport($method),
    $route==='admin/emails'            => routeAdminEmails($method),
    $route==='admin/settings'          => routeAdminSettings($method),
    $route==='admin/email-templates'   => routeAdminEmailTemplates($method),
    $route==='admin/media'             => routeAdminMedia($method),
    $route==='admin/voucher'           => routeAdminVoucher($method),
    $route==='admin/razorpay'          => routeAdminRazorpay($method),
    $route==='payment/order'           => routePaymentOrder($method),
    $route==='payment/verify'          => routePaymentVerify($method),
    $route==='user/bookings'           => routeUserBookings($method),
    $route==='user/profile'            => routeUserProfile($method),
    default                            => fail("Unknown route: $route",404),
};

// ════════════════════════════════════════════════════════════════
// AIRPORTS
// ════════════════════════════════════════════════════════════════
function routeAirports(string $m):void{
    if($m!=='GET')fail('GET only',405);
    $pdo=requireDB();
    $code=strtoupper(g('code'));
    if($code){
        $st=$pdo->prepare("SELECT * FROM airports WHERE code=? AND active=1");$st->execute([$code]);
        $r=$st->fetch();if(!$r)fail('Not found',404);ok(['airport'=>$r]);
    }
    $all=$pdo->query("SELECT code,city,name,state,dom_price,intl_price,porter_price,buggy_price,img_url FROM airports WHERE active=1 ORDER BY city")->fetchAll();
    $byCode=[];foreach($all as $a)$byCode[$a['code']]=$a;
    ok(['airports'=>$all,'by_code'=>$byCode,'count'=>count($all)]);
}

// ════════════════════════════════════════════════════════════════
// BOOKING — works with or without DB
// ════════════════════════════════════════════════════════════════
function routeBook(string $m):void{
    if($m!=='POST')fail('POST only',405);
    rateLimit(15);
    $d=body();

    $fn    =s($d['firstName']   ??""); $ln=s($d['lastName']??"");
    $email =trim($d['email']    ??""); $phone=s($d['phone']??"");
    $apt   =s($d['airport']     ??""); $code=strtoupper(s($d['airportCode']??""));
    $svc   =s($d['serviceType'] ??""); $ft=s($d['flightType']??"Domestic");
    $pax   =s($d['passengers']  ??"1");
    $flno  =s($d['flightNo']    ??""); $dt=s($d['travelDate']??""); $tm=s($d['flightTime']??"");
    $term  =s($d['terminal']    ??""); $special=s($d['specialReq']??"");
    $price =s($d['price']       ??""); $addons=s($d['addons']??"None");
    $srcUrl=s($d['pageUrl']     ??$_SERVER['HTTP_REFERER']??"");
    $isT   =!empty($d['arrFlightNo'])||stripos($svc,'transit')!==false;

    $arrFl =s($d['arrFlightNo'] ??""); $arrDt=s($d['arrDate']??""); $arrTm=s($d['arrTime']??""); $arrFr=s($d['arrFrom']??"");
    $depFl =s($d['depFlightNo'] ??""); $depDt=s($d['depDate']??""); $depTm=s($d['depTime']??""); $depTo=s($d['depTo']??"");

    if(!$fn)fail('First name required');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))fail('Valid email required');
    if(!$phone)fail('Phone required');
    if(!$apt)fail('Airport required');
    if(!in_array($ft,['Domestic','International']))$ft='Domestic';

    $cleanDt =($dt&&($o=DateTime::createFromFormat('Y-m-d',$dt)))?$o->format('Y-m-d'):null;
    $cleanArr=($arrDt&&($o=DateTime::createFromFormat('Y-m-d',$arrDt)))?$o->format('Y-m-d'):null;
    $cleanDep=($depDt&&($o=DateTime::createFromFormat('Y-m-d',$depDt)))?$o->format('Y-m-d'):null;
    $serviceDt=composeServiceDateTime($cleanDt,$tm);
    if(!$code){preg_match('/\(([A-Z]{3})\)/',$apt,$mx);$code=$mx[1]??'';}

    $pdo=dbOrNull();
    $ref=$pdo?genRef($pdo):('TB-'.date('Y').'-'.str_pad(rand(1000,9999),4,'0',STR_PAD_LEFT));

    // Save to DB if available
    if($pdo){
        ensureAdminEnhancements($pdo);
        try{
            $pdo->prepare("INSERT INTO bookings(ref,airport_code,airport_name,service_type,flight_type,first_name,last_name,email,phone,passengers,flight_no,travel_date,flight_time,service_datetime,terminal,special_req,price,addons,is_transit,arr_flight_no,arr_date,arr_time,arr_from,dep_flight_no,dep_date,dep_time,dep_to,source_url,status)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
              ->execute([$ref,$code,$apt,$svc,$ft,$fn,$ln,$email,$phone,$pax,$flno,$cleanDt,$tm?:null,$serviceDt,$term,$special,$price,$addons,$isT?1:0,$arrFl,$cleanArr,$arrTm?:null,$arrFr,$depFl,$cleanDep,$depTm?:null,$depTo,$srcUrl,'Pending']);
            $pdo->prepare("INSERT INTO users(email,first_name,last_name,phone) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE first_name=COALESCE(NULLIF(first_name,''),VALUES(first_name)),last_name=COALESCE(NULLIF(last_name,''),VALUES(last_name)),phone=COALESCE(NULLIF(phone,''),VALUES(phone))")
              ->execute([$email,$fn,$ln,$phone]);
            syncCustomerRollup($pdo,$email,trim("$fn $ln"),$phone);
        }catch(Exception $e){error_log("AG book DB: ".$e->getMessage());}
    }

    // Check Razorpay
    $rzEnabled=getSetting('razorpay_enabled','0')==='1';
    if($rzEnabled){
        $rzKey=getSetting('razorpay_key_id','');
        preg_match('/[\d,]+/',$price,$pm);
        $amt=(int)(str_replace(',','',$pm[0]??0))*100;
        ok(['ref'=>$ref,'razorpay'=>true,'key_id'=>$rzKey,'amount'=>$amt,'currency'=>getSetting('razorpay_currency','INR'),'name'=>'Aero Greet India','description'=>"Booking {$ref}",'prefill'=>['name'=>"$fn $ln",'email'=>$email,'contact'=>$phone]]);
    }

    // Build email data
    $b=compact('ref','fn','ln','email','phone','apt','code','svc','ft','pax','flno','price','addons','special','srcUrl','isT','arrFl','arrFr','depFl','depTo');
    $b+=['date_fmt'=>fmtD($cleanDt??''),'time_fmt'=>fmtT($tm),'arr_date_fmt'=>fmtD($cleanArr??''),'arr_time_fmt'=>fmtT($arrTm),'dep_date_fmt'=>fmtD($cleanDep??''),'dep_time_fmt'=>fmtT($depTm)];

    // Load email template
    $tplRow=null;
    if($pdo){
        try{$st=$pdo->prepare("SELECT * FROM email_templates WHERE slug='client_confirmation' LIMIT 1");$st->execute();$tplRow=$st->fetch()?:null;}catch(Exception $e){}
    }

    $adminHtml=buildAdminEmail($b);
    $clientHtml=buildBookingClientConfirmationEmail($b,$tplRow);
    $adminSubj="[{$ref}] New Booking — ".($apt?explode('(',$apt)[0]:'India')." — $fn $ln";
    $clientSubj=$tplRow?str_replace('{ref}',$ref,$tplRow['subject']):"Booking Received — {$ref} | Aero Greet India";
    $adminRecipients=buildAdminRecipients(getSetting('admin_to',ADMIN_TO));
    $adminLogTo=implode(', ',array_merge([$adminRecipients['to']],$adminRecipients['cc']));

    $r1=sendMail($adminRecipients['to'],'Aero Greet Sales',$adminSubj,$adminHtml,[
        'cc'=>$adminRecipients['cc'],
        'reply_to'=>['email'=>$email,'name'=>trim("$fn $ln") ?: $email],
    ]);
    $r2=sendClientConfirmationMail($email,"$fn $ln",$clientSubj,$clientHtml);

    if($pdo){
        try{
            logMail($pdo,$ref,$adminLogTo,$adminSubj,$r1['ok'],$r1['method']);
            logMail($pdo,$ref,$email,$clientSubj,$r2['ok'],$r2['method']);
            if($r1['ok']||$r2['ok'])$pdo->prepare("UPDATE bookings SET email_sent=1 WHERE ref=?")->execute([$ref]);
        }catch(Exception $e){}
    }

    $bookingMessage=$r2['ok']
        ? 'Booking received! Confirmation sent to '.$email
        : 'Booking received! We will email your confirmation shortly.';
    ok(['ref'=>$ref,'message'=>$bookingMessage]);
}

// ════════════════════════════════════════════════════════════════
// CONTACT — works WITHOUT DB
// ════════════════════════════════════════════════════════════════
function routeContact(string $m):void{
    if($m!=='POST')fail('POST only',405);
    rateLimit(10);
    $d=body();
    $fn=s($d['firstName']??""); $ln=s($d['lastName']??"");
    $email=trim($d['email']??""); $phone=s($d['phone']??"");
    $subject=s($d['subject']??"General Enquiry"); $msg=s($d['message']??"");
    if(!$fn||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$msg)fail('Name, email and message required');

    // Save to DB if available (non-critical)
    $pdo=dbOrNull();
    if($pdo){
        try{
            $pdo->prepare("INSERT INTO contacts(first_name,last_name,email,phone,subject,message)VALUES(?,?,?,?,?,?)")
               ->execute([$fn,$ln,$email,$phone,$subject,$msg]);
        }catch(Exception $e){error_log("AG contact DB: ".$e->getMessage());}
    }

    // Always send email regardless of DB status
    $html=contactEmail($fn,$ln,$email,$phone,$subject,$msg);
    $clientHtml=buildContactClientEmail($fn,$ln,$email,$phone,$subject,$msg);
    $adminTo=getSetting('admin_to',ADMIN_TO);
    $adminRecipients=buildAdminRecipients($adminTo);
    $r=sendMail($adminRecipients['to'],'Aero Greet Sales',"[Contact] $subject — $fn $ln",$html,[
        'cc'=>$adminRecipients['cc'],
        'reply_to'=>['email'=>$email,'name'=>trim("$fn $ln") ?: $email],
    ]);

    if(!$r['ok']){
        // Try fallback directly with constants if getSetting returned wrong addr
        if($adminTo!==ADMIN_TO){
            $fallbackRecipients=buildAdminRecipients(ADMIN_TO);
            $r=sendMail($fallbackRecipients['to'],'Aero Greet Sales',"[Contact] $subject — $fn $ln",$html,[
                'cc'=>$fallbackRecipients['cc'],
                'reply_to'=>['email'=>$email,'name'=>trim("$fn $ln") ?: $email],
            ]);
        }
    }

    $clientSubj="We received your message | Aero Greet India";
    $clientMail=sendClientConfirmationMail($email,trim("$fn $ln"),$clientSubj,$clientHtml);
    $contactMessage=$clientMail['ok']
        ? "Message received! A confirmation email has been sent to {$email}."
        : "Message received! Our team will reply to {$email} soon.";

    ok(['message'=>$contactMessage]);
}

// ════════════════════════════════════════════════════════════════
// TRACK BOOKING
// ════════════════════════════════════════════════════════════════
function routeTrack(string $m):void{
    $pdo=dbOrNull();
    if(!$pdo)fail('Booking system not yet configured. Please contact sales@aerogreetindia.com',503);
    if($m==='GET'){
        $ref=g('ref'); $email=g('email');
        if(!$ref||!$email)fail('ref and email required');
        $st=$pdo->prepare("SELECT * FROM bookings WHERE ref=? AND LOWER(email)=LOWER(?) LIMIT 1");
        $st->execute([$ref,$email]);
        $b=$st->fetch();
        if(!$b)fail('No booking found with those details. Please check your reference number and email address.',404);
        unset($b['admin_notes'],$b['user_id'],$b['supplier_name'],$b['supplier_cost'],$b['selling_price']);
        ok(['booking'=>$b]);
    }
    if($m==='POST'){
        $d=body();$email=filter_var(s($d['email']??''),FILTER_VALIDATE_EMAIL);
        if(!$email)fail('Valid email required');
        $st=$pdo->prepare("SELECT ref,airport_name,service_type,status,travel_date,price,is_transit,created_at FROM bookings WHERE LOWER(email)=LOWER(?) ORDER BY created_at DESC LIMIT 20");
        $st->execute([$email]);
        ok(['bookings'=>$st->fetchAll()]);
    }
    fail('Method not allowed',405);
}

// ════════════════════════════════════════════════════════════════
// AUTH
// ════════════════════════════════════════════════════════════════
function routeAdminLogin(string $m):void{
    if($m!=='POST')fail('POST only',405);
    rateLimit(10);
    $pdo=requireDB();
    $d=body();$u=s($d['username']??'');$p=$d['password']??'';
    if(!$u||!$p)fail('Username and password required');
    $st=$pdo->prepare("SELECT * FROM admins WHERE username=? OR email=? LIMIT 1");
    $st->execute([$u,$u]);$a=$st->fetch();
    if(!$a||!password_verify($p,$a['password_hash']))fail('Invalid credentials',401);
    $tok=newSession($pdo,$a['id'],'admin');
    $pdo->prepare("UPDATE admins SET last_login=NOW() WHERE id=?")->execute([$a['id']]);
    setCookie2('ag_adm',$tok);
    ok(['token'=>$tok,'admin'=>['username'=>$a['username'],'email'=>$a['email'],'role'=>$a['role']]]);
}

function routeAuthLogin(string $m):void{
    if($m!=='POST')fail('POST only',405);
    rateLimit(15);
    $pdo=requireDB();
    $d=body();$email=filter_var(s($d['email']??''),FILTER_VALIDATE_EMAIL);$p=$d['password']??'';
    if(!$email||!$p)fail('Email and password required');
    $st=$pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");$st->execute([$email]);$u=$st->fetch();
    if(!$u||!$u['password_hash']||!password_verify($p,$u['password_hash']))fail('Invalid credentials',401);
    $tok=newSession($pdo,$u['id'],'user');
    $pdo->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$u['id']]);
    setCookie2('ag_usr',$tok);
    ok(['token'=>$tok,'user'=>['email'=>$u['email'],'first_name'=>$u['first_name'],'last_name'=>$u['last_name']]]);
}

function routeAuthRegister(string $m):void{
    if($m!=='POST')fail('POST only',405);
    rateLimit(10);
    $pdo=requireDB();
    $d=body();
    $email=filter_var(s($d['email']??''),FILTER_VALIDATE_EMAIL);
    $pass=$d['password']??'';$fn=s($d['first_name']??'');$ln=s($d['last_name']??'');$ph=s($d['phone']??'');
    if(!$email)fail('Valid email required');
    if(strlen($pass)<8)fail('Password must be at least 8 characters');
    if(!$fn)fail('First name required');
    $ex=$pdo->prepare("SELECT id,password_hash FROM users WHERE email=? LIMIT 1");$ex->execute([$email]);$existing=$ex->fetch();
    $hash=password_hash($pass,PASSWORD_BCRYPT,['cost'=>12]);
    if($existing){
        $pdo->prepare("UPDATE users SET password_hash=?,first_name=COALESCE(NULLIF(first_name,''),?),last_name=COALESCE(NULLIF(last_name,''),?),phone=COALESCE(NULLIF(phone,''),?) WHERE id=?")->execute([$hash,$fn,$ln,$ph,$existing['id']]);
        $uid=(int)$existing['id'];
    } else {
        $pdo->prepare("INSERT INTO users(email,password_hash,first_name,last_name,phone)VALUES(?,?,?,?,?)")->execute([$email,$hash,$fn,$ln,$ph]);
        $uid=(int)$pdo->lastInsertId();
    }
    $tok=newSession($pdo,$uid,'user');setCookie2('ag_usr',$tok);
    ok(['token'=>$tok,'user'=>['email'=>$email,'first_name'=>$fn,'last_name'=>$ln]]);
}

function routeLogout():void{
    $tok=bearerToken();
    if(!$tok)$tok=$_COOKIE['ag_adm']??$_COOKIE['ag_usr']??'';
    $pdo=dbOrNull();
    if($tok&&$pdo)$pdo->prepare("DELETE FROM sessions WHERE token=?")->execute([$tok]);
    setcookie('ag_adm','',['expires'=>1,'path'=>'/']);
    setcookie('ag_usr','',['expires'=>1,'path'=>'/']);
    ok(['message'=>'Logged out']);
}

function routeMe():void{
    $pdo=dbOrNull();
    if(!$pdo)fail('No session',401);
    $tok=bearerToken()?:($_COOKIE['ag_adm']??$_COOKIE['ag_usr']??'');
    if(!$tok)fail('No session',401);
    $st=$pdo->prepare("SELECT s.entity_id id,a.username,a.email,a.role,'admin' type FROM sessions s JOIN admins a ON s.entity_id=a.id WHERE s.token=? AND s.entity_type='admin' AND s.expires_at>NOW()");
    $st->execute([$tok]);$r=$st->fetch();
    if($r){ok(['type'=>'admin','admin'=>$r]);}
    $st=$pdo->prepare("SELECT s.entity_id id,u.email,u.first_name,u.last_name,'user' type FROM sessions s JOIN users u ON s.entity_id=u.id WHERE s.token=? AND s.entity_type='user' AND s.expires_at>NOW()");
    $st->execute([$tok]);$r=$st->fetch();
    if($r)ok(['type'=>'user','user'=>$r]);
    fail('Session expired',401);
}

function routeChangePassword(string $m):void{
    if($m!=='POST')fail('POST only',405);
    $pdo=requireDB();
    $d=body();
    $tok=bearerToken()?:($_COOKIE['ag_adm']??'');
    if($tok){
        $st=$pdo->prepare("SELECT id,password_hash FROM admins WHERE id=(SELECT entity_id FROM sessions WHERE token=? AND entity_type='admin' AND expires_at>NOW())");
        $st->execute([$tok]);$a=$st->fetch();
        if($a){
            if(!password_verify($d['current']??'',$a['password_hash']))fail('Current password incorrect');
            if(strlen($d['new']??'')<8)fail('Min 8 characters');
            $pdo->prepare("UPDATE admins SET password_hash=? WHERE id=?")->execute([password_hash($d['new'],PASSWORD_BCRYPT,['cost'=>12]),$a['id']]);
            ok(['message'=>'Password changed']);
        }
    }
    fail('Unauthorized',401);
}

// ════════════════════════════════════════════════════════════════
// ADMIN — STATS
// ════════════════════════════════════════════════════════════════
function routeAdminStats(string $m):void{
    authAdmin();
    $pdo=requireDB();
    ensureAdminEnhancements($pdo);
    $ov=[
        'total'    =>(int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'today'    =>(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
        'week'     =>(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn(),
        'month'    =>(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn(),
    ];
    $byStatus=array_fill_keys(bookingStatuses(),0);
    foreach($pdo->query("SELECT status,COUNT(*) c FROM bookings GROUP BY status")->fetchAll() as $r)$byStatus[$r['status']]=(int)$r['c'];
    $topAirports=$pdo->query("SELECT airport_code,airport_name,COUNT(*) cnt FROM bookings WHERE airport_code!='' GROUP BY airport_code ORDER BY cnt DESC LIMIT 10")->fetchAll();
    $recent=$pdo->query("SELECT ref,airport_code,first_name,last_name,email,service_type,status,price,created_at FROM bookings ORDER BY created_at DESC LIMIT 10")->fetchAll();
    $dailyMap=[];
    foreach($pdo->query("SELECT DATE(created_at) d,COUNT(*) cnt FROM bookings WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 29 DAY) GROUP BY DATE(created_at) ORDER BY d")->fetchAll() as $row)$dailyMap[$row['d']]=(int)$row['cnt'];
    $daily=[];
    for($i=29;$i>=0;$i--){
        $day=date('Y-m-d',strtotime("-{$i} day"));
        $daily[]=['d'=>$day,'cnt'=>$dailyMap[$day]??0];
    }
    ok(compact('ov','byStatus','topAirports','recent','daily'));
}

// ════════════════════════════════════════════════════════════════
// ADMIN — BOOKINGS
// ════════════════════════════════════════════════════════════════
function routeAdminBookings(string $m):void{
    authAdmin();
    $pdo=requireDB();
    ensureAdminEnhancements($pdo);
    if($m==='GET'){
        $ref=g('ref');
        if($ref){
            $b=fetchAdminBooking($pdo,$ref);
            if(!$b)fail('Not found',404);
            ok(['booking'=>presentBooking($b)]);
        }
        $page=max(1,(int)g('page','1'));$pp=min(200,max(10,(int)g('per_page','25')));
        $filters=buildBookingFiltersFromQuery($pdo);
        $sort=in_array(g('sort'),['created_at','travel_date','service_datetime','first_name','status','ref'])?g('sort'):'created_at';
        $dir=g('dir')==='asc'?'ASC':'DESC';
        $ws=$filters['where'];$p=$filters['params'];
        $cst=$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE $ws");$cst->execute($p);$total=(int)$cst->fetchColumn();
        $pg=paginate($total,$page,$pp);
        $limit=(int)$pg['per_page'];$offset=(int)$pg['offset'];
        $bst=$pdo->prepare("SELECT ".adminBookingColumns($pdo)." FROM bookings WHERE $ws ORDER BY $sort $dir LIMIT {$limit} OFFSET {$offset}");
        $bst->execute($p);
        $rows=array_map('presentBooking',$bst->fetchAll());
        $sc=array_fill_keys(bookingStatuses(),0);
        foreach($pdo->query("SELECT status,COUNT(*) c FROM bookings GROUP BY status")->fetchAll() as $r)$sc[$r['status']]=(int)$r['c'];
        ok(['bookings'=>$rows,'pagination'=>$pg,'status_counts'=>$sc]);
    }
    if($m==='PUT'||($m==='POST'&&(body()['_method']??'')==='PUT')){
        $d=body();$ref=s($d['ref']??'');if(!$ref)fail('ref required');
        $current=fetchAdminBooking($pdo,$ref);if(!$current)fail('Not found',404);
        // Build dynamic update — handle optional columns safely
        $sets=[];$vals=[];
        if(array_key_exists('status',$d)){
            $status=trim((string)$d['status']);
            if(!in_array($status,bookingStatuses(),true))fail('Invalid status');
            $sets[]="status=?";$vals[]=$status;
        }
        foreach(['admin_notes','price','passengers','flight_no','terminal','special_req'] as $f){
            if(array_key_exists($f,$d)){
                $val=trim((string)($d[$f]??''));
                $sets[]="$f=?";$vals[]=$val!==''?s($val):null;
            }
        }
        $newTravelDate=$current['travel_date']??null;
        if(array_key_exists('travel_date',$d)){
            $newTravelDate=normalizeSqlDate((string)($d['travel_date']??''));
            $sets[]="travel_date=?";$vals[]=$newTravelDate;
        }
        $newFlightTime=$current['flight_time']??null;
        if(array_key_exists('flight_time',$d)){
            $newFlightTime=normalizeSqlTime((string)($d['flight_time']??''));
            $sets[]="flight_time=?";$vals[]=$newFlightTime;
        }
        if((array_key_exists('travel_date',$d)||array_key_exists('flight_time',$d))&&bookingColumnAvailable($pdo,'service_datetime')){
            $sets[]="service_datetime=?";$vals[]=composeServiceDateTime($newTravelDate,$newFlightTime);
        }
        foreach(['supplier_name','invoice_number'] as $f){
            if(array_key_exists($f,$d)&&bookingColumnAvailable($pdo,$f)){
                $val=trim((string)($d[$f]??''));
                $sets[]="$f=?";$vals[]=$val!==''?s($val):null;
            }
        }
        foreach(['supplier_cost','selling_price'] as $f){
            if(array_key_exists($f,$d)&&bookingColumnAvailable($pdo,$f)){
                $sets[]="$f=?";$vals[]=$d[$f]===''||$d[$f]===null?null:(float)$d[$f];
            }
        }
        if(!$sets)fail('Nothing to update');
        $vals[]=$ref;
        $pdo->prepare("UPDATE bookings SET ".implode(',',$sets).",updated_at=NOW() WHERE ref=?")->execute($vals);
        $updated=fetchAdminBooking($pdo,$ref);
        if($updated)syncCustomerRollup($pdo,(string)($updated['email']??''),trim((string)($updated['first_name']??'').' '.(string)($updated['last_name']??'')),(string)($updated['phone']??''));
        $voucherReady=false;
        if($updated&&bookingAllowsVoucher((string)($updated['status']??''))){
            $voucher=agEnsureVoucherFile($pdo,$updated);
            $voucherReady=(bool)($voucher['ok']??false);
            $updated=fetchAdminBooking($pdo,$ref);
        }
        ok(['message'=>'Updated','ref'=>$ref,'booking'=>$updated?presentBooking($updated):null,'voucher_ready'=>$voucherReady]);
    }
    if($m==='DELETE'){
        $a=authAdmin();if($a['role']!=='super')fail('Super admin only',403);
        $ref=g('ref')?:s(body()['ref']??'');if(!$ref)fail('ref required');
        $st=$pdo->prepare("DELETE FROM bookings WHERE ref=?");$st->execute([$ref]);
        if(!$st->rowCount())fail('Not found',404);ok(['message'=>'Deleted']);
    }
    fail('Method not allowed',405);
}

function routeAdminBookingsExport(string $m):void{
    if($m!=='GET')fail('GET only',405);
    authAdmin();
    $pdo=requireDB();
    ensureAdminEnhancements($pdo);
    $filters=buildBookingFiltersFromQuery($pdo);
    $st=$pdo->prepare("SELECT ".adminBookingColumns($pdo)." FROM bookings WHERE {$filters['where']} ORDER BY created_at DESC");
    $st->execute($filters['params']);
    $rows=$st->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bookings-'.date('Y-m-d').'.csv"');
    $out=fopen('php://output','w');
    fprintf($out, chr(239).chr(187).chr(191));
    fputcsv($out,['Ref','Name','Email','Phone','Airport','Service','Flight Type','Date Time','Price','Status','Invoice','Supplier','Supplier Cost','Selling Price','Add-ons','Special Request','Created At']);
    foreach($rows as $row){
        fputcsv($out,[
            html_entity_decode((string)($row['ref']??''),ENT_QUOTES,'UTF-8'),
            trim(html_entity_decode((string)($row['first_name']??''),ENT_QUOTES,'UTF-8').' '.html_entity_decode((string)($row['last_name']??''),ENT_QUOTES,'UTF-8')),
            html_entity_decode((string)($row['email']??''),ENT_QUOTES,'UTF-8'),
            html_entity_decode((string)($row['phone']??''),ENT_QUOTES,'UTF-8'),
            html_entity_decode((string)(($row['airport_name'] ?? '') !== '' ? $row['airport_name'] : ($row['airport_code'] ?? '')),ENT_QUOTES,'UTF-8'),
            html_entity_decode((string)($row['service_type']??''),ENT_QUOTES,'UTF-8'),
            html_entity_decode((string)($row['flight_type']??''),ENT_QUOTES,'UTF-8'),
            agVoucherDateTime($row),
            html_entity_decode((string)($row['price']??''),ENT_QUOTES,'UTF-8'),
            html_entity_decode((string)($row['status']??''),ENT_QUOTES,'UTF-8'),
            html_entity_decode((string)($row['invoice_number']??''),ENT_QUOTES,'UTF-8'),
            html_entity_decode((string)($row['supplier_name']??''),ENT_QUOTES,'UTF-8'),
            $row['supplier_cost'],
            $row['selling_price'],
            html_entity_decode((string)($row['addons']??''),ENT_QUOTES,'UTF-8'),
            html_entity_decode((string)($row['special_req']??''),ENT_QUOTES,'UTF-8'),
            $row['created_at']??'',
        ]);
    }
    fclose($out);
    exit;
}

// ════════════════════════════════════════════════════════════════
// ADMIN — PRICES
// ════════════════════════════════════════════════════════════════
function routeAdminPrices(string $m):void{
    if($m==='GET'){
        $pdo=requireDB();
        $code=strtoupper(g('code'));
        if($code){$st=$pdo->prepare("SELECT * FROM airports WHERE code=?");$st->execute([$code]);$r=$st->fetch();if(!$r)fail('Not found',404);ok(['airport'=>$r]);}
        $all=$pdo->query("SELECT code,city,name,state,dom_price,intl_price,porter_price,buggy_price,img_url,active FROM airports ORDER BY city")->fetchAll();
        ok(['airports'=>$all]);
    }
    $admin=authAdmin();
    $pdo=requireDB();
    if($m==='PUT'||($m==='POST'&&(body()['_method']??'')==='PUT')){
        $d=body();$code=strtoupper(s($d['code']??''));if(!$code)fail('code required');
        $cur=$pdo->prepare("SELECT * FROM airports WHERE code=?");$cur->execute([$code]);$old=$cur->fetch();if(!$old)fail('Not found',404);
        $fields=['dom_price','intl_price','porter_price','buggy_price','city','name','state','img_url','active'];
        $sets=[];$vals=[];
        foreach($fields as $f){
            if(!isset($d[$f])||$d[$f]==='')continue;
            $nv=in_array($f,['dom_price','intl_price','porter_price','buggy_price'])?round((float)$d[$f],2):s($d[$f]);
            if(in_array($f,['dom_price','intl_price','porter_price','buggy_price'])&&(string)$nv!==(string)($old[$f]??'')){
                try{$pdo->prepare("INSERT INTO price_history(airport_code,field_name,old_value,new_value,changed_by)VALUES(?,?,?,?,?)")->execute([$code,$f,$old[$f],$nv,$admin['username']]);}catch(Exception $e){}
            }
            $sets[]="$f=?";$vals[]=$nv;
        }
        if(!$sets)ok(['message'=>'No changes']);
        $vals[]=$code;
        $pdo->prepare("UPDATE airports SET ".implode(',',$sets).",updated_at=NOW() WHERE code=?")->execute($vals);
        ok(['message'=>'Updated','code'=>$code]);
    }
    if($m==='POST'){
        $d=body();$act=s($d['action']??'');
        if($act==='bulk'){
            $updates=$d['updates']??[];if(!is_array($updates))fail('updates required');
            $st=$pdo->prepare("UPDATE airports SET dom_price=COALESCE(?,dom_price),intl_price=COALESCE(?,intl_price),porter_price=COALESCE(?,porter_price),buggy_price=COALESCE(?,buggy_price),updated_at=NOW() WHERE code=?");
            $lg=null;
            try{$lg=$pdo->prepare("INSERT INTO price_history(airport_code,field_name,old_value,new_value,changed_by)VALUES(?,?,?,?,?)");}catch(Exception $e){}
            $n=0;
            foreach($updates as $u){
                $c=strtoupper(s($u['code']??''));if(!$c)continue;
                $old=$pdo->prepare("SELECT dom_price,intl_price,porter_price,buggy_price FROM airports WHERE code=?");$old->execute([$c]);$ov=$old->fetch();if(!$ov)continue;
                $nDom=isset($u['dom_price'])&&$u['dom_price']!==''&&$u['dom_price']!==null?(float)$u['dom_price']:null;
                $nInt=isset($u['intl_price'])&&$u['intl_price']!==''&&$u['intl_price']!==null?(float)$u['intl_price']:null;
                $nPo =isset($u['porter_price'])&&$u['porter_price']!==''&&$u['porter_price']!==null?(float)$u['porter_price']:null;
                $nBu =isset($u['buggy_price'])&&$u['buggy_price']!==''&&$u['buggy_price']!==null?(float)$u['buggy_price']:null;
                $st->execute([$nDom,$nInt,$nPo,$nBu,$c]);
                if($lg){
                    foreach(['dom_price'=>$nDom,'intl_price'=>$nInt,'porter_price'=>$nPo,'buggy_price'=>$nBu] as $f=>$nv){
                        if($nv!==null&&(string)$nv!==(string)($ov[$f]??''))try{$lg->execute([$c,$f,$ov[$f],$nv,$admin['username']]);}catch(Exception $e){}
                    }
                }
                $n++;
            }
            ok(['message'=>"Updated $n airports"]);
        }
        fail('Unknown action');
    }
    fail('Method not allowed',405);
}

// ════════════════════════════════════════════════════════════════
// ADMIN — CUSTOMERS
// ════════════════════════════════════════════════════════════════
function routeAdminCustomers(string $m):void{
    authAdmin();
    $pdo=requireDB();
    ensureAdminEnhancements($pdo);
    seedCustomersFromBookings($pdo);
    $page=max(1,(int)g('page','1'));$pp=25;$search=g('search');
    $w='1=1';$p=[];
    if($search){$lk="%$search%";$w="(email LIKE ? OR name LIKE ? OR COALESCE(phone,'') LIKE ?)";$p=[$lk,$lk,$lk];}
    $cst=$pdo->prepare("SELECT COUNT(*) FROM customers WHERE $w");$cst->execute($p);$total=(int)$cst->fetchColumn();
    $pg=paginate($total,$page,$pp);
    $limit=(int)$pg['per_page'];$offset=(int)$pg['offset'];
    $st=$pdo->prepare("SELECT id,name,email,phone,total_bookings,last_booking,created_at,updated_at FROM customers WHERE $w ORDER BY COALESCE(last_booking,created_at) DESC LIMIT {$limit} OFFSET {$offset}");
    $st->execute($p);
    ok(['customers'=>$st->fetchAll(),'pagination'=>$pg]);
}

function routeAdminCustomersExport(string $m):void{
    if($m!=='GET')fail('GET only',405);
    authAdmin();
    $pdo=requireDB();
    ensureAdminEnhancements($pdo);
    seedCustomersFromBookings($pdo);
    $search=g('search');
    $w='1=1';$p=[];
    if($search){$lk="%$search%";$w="(email LIKE ? OR name LIKE ? OR COALESCE(phone,'') LIKE ?)";$p=[$lk,$lk,$lk];}
    $st=$pdo->prepare("SELECT name,email,phone,total_bookings,last_booking,created_at,updated_at FROM customers WHERE $w ORDER BY COALESCE(last_booking,created_at) DESC");
    $st->execute($p);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="customers-'.date('Y-m-d').'.csv"');
    $out=fopen('php://output','w');
    fprintf($out, chr(239).chr(187).chr(191));
    fputcsv($out,['Name','Email','Phone','Total Bookings','Last Booking','Created At','Updated At']);
    foreach($st->fetchAll() as $row){
        fputcsv($out,[
            html_entity_decode((string)($row['name']??''),ENT_QUOTES,'UTF-8'),
            html_entity_decode((string)($row['email']??''),ENT_QUOTES,'UTF-8'),
            html_entity_decode((string)($row['phone']??''),ENT_QUOTES,'UTF-8'),
            $row['total_bookings']??0,
            $row['last_booking']??'',
            $row['created_at']??'',
            $row['updated_at']??'',
        ]);
    }
    fclose($out);
    exit;
}

// ════════════════════════════════════════════════════════════════
// ADMIN — EMAIL LOGS
// ════════════════════════════════════════════════════════════════
function routeAdminEmails(string $m):void{
    authAdmin();
    $pdo=requireDB();
    $page=max(1,(int)g('page','1'));$pp=50;
    $cst=$pdo->query("SELECT COUNT(*) FROM email_logs");$total=(int)$cst->fetchColumn();
    $pg=paginate($total,$page,$pp);
    $limit=(int)$pg['per_page'];$offset=(int)$pg['offset'];
    $st=$pdo->prepare("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT {$limit} OFFSET {$offset}");
    $st->execute();
    ok(['logs'=>$st->fetchAll(),'pagination'=>$pg]);
}

// ════════════════════════════════════════════════════════════════
// ADMIN — SETTINGS
// ════════════════════════════════════════════════════════════════
function routeAdminSettings(string $m):void{
    authAdmin();
    $pdo=requireDB();
    if($m==='GET'){
        $rows=$pdo->query("SELECT k,v FROM settings")->fetchAll();
        $s=[];foreach($rows as $r)$s[$r['k']]=$r['v'];
        if(g('sub')==='price_history'){
            $code=g('code');$w=$code?"WHERE airport_code='".addslashes($code)."'":'';
            $h=[];
            try{$h=$pdo->query("SELECT * FROM price_history $w ORDER BY changed_at DESC LIMIT 200")->fetchAll();}catch(Exception $e){}
            ok(['history'=>$h]);
        }
        ok(['settings'=>$s]);
    }
    if($m==='POST'){
        $d=body();
        $allowed=['smtp_host','smtp_port','smtp_user','smtp_pass','mail_from','admin_to','whatsapp','site_name','site_url'];
        $st=$pdo->prepare("INSERT INTO settings(k,v)VALUES(?,?) ON DUPLICATE KEY UPDATE v=VALUES(v),updated_at=NOW()");
        foreach($allowed as $k){if(isset($d[$k]))$st->execute([$k,s($d[$k])]);}
        ok(['message'=>'Settings saved']);
    }
    fail('Method not allowed',405);
}

// ════════════════════════════════════════════════════════════════
// ADMIN — EMAIL TEMPLATES
// ════════════════════════════════════════════════════════════════
function routeAdminEmailTemplates(string $m):void{
    authAdmin();
    $pdo=requireDB();
    if($m==='GET'){
        $slug=g('slug');
        if($slug){
            $st=$pdo->prepare("SELECT * FROM email_templates WHERE slug=? LIMIT 1");$st->execute([$slug]);
            $r=$st->fetch();if(!$r)fail('Not found',404);ok(['template'=>$r]);
        }
        try{$all=$pdo->query("SELECT * FROM email_templates ORDER BY label")->fetchAll();}catch(Exception $e){$all=[];}
        ok(['templates'=>$all]);
    }
    if($m==='POST'||$m==='PUT'){
        $d=body();
        $slug=s($d['slug']??'');if(!$slug)fail('slug required');
        $fields=['label','subject','header_note','footer_note','custom_note'];
        $sets=[];$vals=[];
        foreach($fields as $f){if(isset($d[$f])){$sets[]="$f=?";$vals[]=trim((string)$d[$f]);}}
        if(!$sets)fail('Nothing to update');
        $vals[]=$slug;
        try{
            $up=$pdo->prepare("UPDATE email_templates SET ".implode(',',$sets)." WHERE slug=?");
            $up->execute($vals);
            if(!$up->rowCount()){
                $vals2=[$slug];$cols=['slug'];
                foreach($fields as $f){if(isset($d[$f])){$cols[]=$f;$vals2[]=trim((string)$d[$f]);}}
                $pdo->prepare("INSERT INTO email_templates(".implode(',',$cols).")VALUES(".implode(',',array_fill(0,count($cols),'?')).")")->execute($vals2);
            }
        }catch(Exception $e){fail('Template table not found. Run install.php first.');}
        ok(['message'=>'Template saved']);
    }
    fail('Method not allowed',405);
}

// ════════════════════════════════════════════════════════════════
// ADMIN — MEDIA
// ════════════════════════════════════════════════════════════════
function routeAdminMedia(string $m):void{
    authAdmin();
    $pdo=requireDB();
    if($m==='GET'){
        $section=g('section');
        $w=$section?"WHERE section=?":"";$p=$section?[$section]:[];
        try{$st=$pdo->prepare("SELECT * FROM site_media $w ORDER BY section,label");$st->execute($p);ok(['media'=>$st->fetchAll()]);}
        catch(Exception $e){ok(['media'=>[]]);}
    }
    if($m==='POST'||$m==='PUT'){
        $d=body();
        $slug=s($d['slug']??'');$url=trim((string)($d['url']??''));
        $label=s($d['label']??'');$section=s($d['section']??'general');
        if(!$slug)fail('slug required');
        try{$pdo->prepare("INSERT INTO site_media(slug,label,section,url)VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE url=VALUES(url),label=COALESCE(NULLIF(VALUES(label),''),label),updated_at=NOW()")->execute([$slug,$label,$section,$url]);}
        catch(Exception $e){fail('Media table not found. Run install.php.');}
        ok(['message'=>'Media updated']);
    }
    fail('Method not allowed',405);
}

function routeAdminVoucher(string $m):void{
    if($m!=='GET')fail('GET only',405);
    authAdmin();
    $pdo=requireDB();
    ensureAdminEnhancements($pdo);
    $ref=s(g('ref'));
    if(!$ref)fail('ref required');
    $booking=fetchAdminBooking($pdo,$ref);
    if(!$booking)fail('Not found',404);
    if(!bookingAllowsVoucher((string)($booking['status']??'')))fail('Voucher is available after confirmation.',400);
    $voucher=agEnsureVoucherFile($pdo,$booking);
    if(!($voucher['ok']??false))fail($voucher['error']??'Could not generate voucher',500);
    $content=(string)($voucher['content']??'');
    if($content==='')fail('Voucher is empty',500);

    header('Content-Type: '.($voucher['mime']??'text/html; charset=UTF-8'));
    header('Content-Disposition: inline; filename="'.agVoucherSafeName($ref).'-voucher.html"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $content;
    exit;
}

// ════════════════════════════════════════════════════════════════
// ADMIN — RAZORPAY
// ════════════════════════════════════════════════════════════════
function routeAdminRazorpay(string $m):void{
    authAdmin();
    $pdo=requireDB();
    if($m==='GET'){
        $st=$pdo->prepare("SELECT k,v FROM settings WHERE k IN ('razorpay_enabled','razorpay_key_id','razorpay_currency')");
        $st->execute();$s=[];foreach($st->fetchAll() as $r)$s[$r['k']]=$r['v'];
        ok(['razorpay'=>$s]);
    }
    if($m==='POST'){
        $d=body();
        $st=$pdo->prepare("INSERT INTO settings(k,v)VALUES(?,?) ON DUPLICATE KEY UPDATE v=VALUES(v),updated_at=NOW()");
        $st->execute(['razorpay_enabled',isset($d['enabled'])&&$d['enabled']?'1':'0']);
        if(isset($d['key_id']))$st->execute(['razorpay_key_id',trim($d['key_id'])]);
        if(isset($d['key_secret'])&&$d['key_secret'])$st->execute(['razorpay_key_secret',trim($d['key_secret'])]);
        if(isset($d['currency']))$st->execute(['razorpay_currency',s($d['currency'])]);
        ok(['message'=>'Razorpay settings saved']);
    }
    fail('Method not allowed',405);
}

// ════════════════════════════════════════════════════════════════
// PAYMENT — RAZORPAY
// ════════════════════════════════════════════════════════════════
function routePaymentOrder(string $m):void{
    if($m!=='POST')fail('POST only',405);
    $d=body();$ref=s($d['ref']??'');if(!$ref)fail('ref required');
    $rzKey=getSetting('razorpay_key_id','');$rzSecret=getSetting('razorpay_key_secret','');
    if(!$rzKey||!$rzSecret)fail('Payment not configured');
    $pdo=requireDB();
    $bst=$pdo->prepare("SELECT price FROM bookings WHERE ref=? LIMIT 1");$bst->execute([$ref]);$bk=$bst->fetch();
    if(!$bk)fail('Booking not found',404);
    preg_match('/[\d,]+/',$bk['price'],$pm);
    $amt=(int)(str_replace(',','',$pm[0]??0))*100;
    $orderData=['amount'=>$amt,'currency'=>getSetting('razorpay_currency','INR'),'receipt'=>$ref];
    $ch=curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($orderData),CURLOPT_USERPWD=>"$rzKey:$rzSecret",CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
    $res=curl_exec($ch);curl_close($ch);
    $order=json_decode($res,true);
    if(!isset($order['id']))fail('Could not create payment order');
    ok(['order_id'=>$order['id'],'amount'=>$amt,'currency'=>getSetting('razorpay_currency','INR'),'key_id'=>$rzKey]);
}

function routePaymentVerify(string $m):void{
    if($m!=='POST')fail('POST only',405);
    $d=body();
    $orderId=s($d['razorpay_order_id']??'');$paymentId=s($d['razorpay_payment_id']??'');
    $signature=s($d['razorpay_signature']??'');$ref=s($d['ref']??'');
    $rzSecret=getSetting('razorpay_key_secret','');
    $expected=hash_hmac('sha256',$orderId.'|'.$paymentId,$rzSecret);
    if(!hash_equals($expected,$signature))fail('Payment verification failed',400);
    $pdo=requireDB();
    ensureAdminEnhancements($pdo);
    $pdo->prepare("UPDATE bookings SET status='Paid',updated_at=NOW() WHERE ref=?")->execute([$ref]);
    $booking=fetchAdminBooking($pdo,$ref);
    if($booking)agEnsureVoucherFile($pdo,$booking);
    ok(['message'=>'Payment verified','ref'=>$ref]);
}

// ════════════════════════════════════════════════════════════════
// USER — BOOKINGS
// ════════════════════════════════════════════════════════════════
function routeUserBookings(string $m):void{
    $u=authUser();
    $pdo=requireDB();
    if($m==='GET'){
        $ref=g('ref');
        if($ref){
            $st=$pdo->prepare("SELECT * FROM bookings WHERE ref=? AND email=? LIMIT 1");$st->execute([$ref,$u['email']]);
            $b=$st->fetch();if(!$b)fail('Not found',404);
            unset($b['admin_notes'],$b['supplier_name'],$b['supplier_cost'],$b['selling_price']);
            ok(['booking'=>$b]);
        }
        $st=$pdo->prepare("SELECT ref,airport_name,service_type,flight_type,status,travel_date,flight_time,price,addons,is_transit,passengers,email_sent,created_at,arr_flight_no,arr_from,arr_date,arr_time,dep_flight_no,dep_to,dep_date,dep_time,flight_no,special_req FROM bookings WHERE email=? ORDER BY created_at DESC");
        $st->execute([$u['email']]);ok(['bookings'=>$st->fetchAll(),'user'=>$u]);
    }
    fail('Method not allowed',405);
}

// ════════════════════════════════════════════════════════════════
// USER — PROFILE
// ════════════════════════════════════════════════════════════════
function routeUserProfile(string $m):void{
    $u=authUser();
    $pdo=requireDB();
    if($m==='GET')ok(['user'=>$u]);
    if($m==='POST'||$m==='PUT'){
        $d=body();$fn=s($d['first_name']??'');$ln=s($d['last_name']??'');$ph=s($d['phone']??'');
        $sets=[];$vals=[];
        if($fn){$sets[]="first_name=?";$vals[]=$fn;}
        if($ln){$sets[]="last_name=?";$vals[]=$ln;}
        if($ph){$sets[]="phone=?";$vals[]=$ph;}
        if($d['password']??false){
            if(strlen($d['password'])<8)fail('Min 8 chars');
            $sets[]="password_hash=?";$vals[]=password_hash($d['password'],PASSWORD_BCRYPT,['cost'=>12]);
        }
        if($sets){$vals[]=$u['id'];$pdo->prepare("UPDATE users SET ".implode(',',$sets)." WHERE id=?")->execute($vals);}
        ok(['message'=>'Profile updated']);
    }
    fail('Method not allowed',405);
}

// ════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════
function fmtD(string $d):string{return $d?date('d M Y',strtotime($d)):'—';}
function fmtT(string $t):string{return $t?date('h:i A',strtotime($t)):'—';}
function logMail(PDO $pdo, string $ref,string $to,string $sub,bool $ok,string $method):void{
    try{$pdo->prepare("INSERT INTO email_logs(booking_ref,recipient,subject,status,method)VALUES(?,?,?,?,?)")->execute([$ref,$to,$sub,$ok?'sent':'failed',$method]);}catch(Exception $e){}
}

function bookingStatuses():array{
    return ['Pending','In Queue','Confirmed','Paid','Completed','Cancelled'];
}

function bookingAllowsVoucher(string $status):bool{
    return in_array($status,['Confirmed','Paid','Completed'],true);
}

function normalizeSqlDate(string $value):?string{
    $value=trim($value);
    if($value==='')return null;
    $dt=DateTime::createFromFormat('Y-m-d',$value);
    return $dt?$dt->format('Y-m-d'):null;
}

function normalizeSqlTime(string $value):?string{
    $value=trim($value);
    if($value==='')return null;
    foreach(['H:i:s','H:i'] as $format){
        $dt=DateTime::createFromFormat($format,$value);
        if($dt)return $dt->format('H:i:s');
    }
    return null;
}

function composeServiceDateTime(?string $date, ?string $time):?string{
    if(!$date)return null;
    $time=$time?:'00:00:00';
    $ts=strtotime(trim($date).' '.trim($time));
    return $ts?date('Y-m-d H:i:s',$ts):null;
}

function buildAdminRecipients(string $raw=''):array{
    $list=mailAddresses($raw?:ADMIN_TO);
    if(!$list)$list=mailAddresses('sales@aerogreetindia.com,admin@travelblooper.com');
    $to=array_shift($list) ?: 'sales@aerogreetindia.com';
    return ['to'=>$to,'cc'=>$list];
}

function bookingColumnAvailable(PDO $pdo,string $column):bool{
    return columnExists($pdo,'bookings',$column);
}

function bookingSelectExpr(PDO $pdo,string $column):string{
    return bookingColumnAvailable($pdo,$column) ? $column : "NULL AS {$column}";
}

function adminBookingColumns(PDO $pdo):string{
    return implode(',',[
        'id','ref','airport_code','airport_name','service_type','flight_type',
        'first_name','last_name','email','phone','passengers','flight_no',
        'travel_date','flight_time',bookingSelectExpr($pdo,'service_datetime'),'terminal','price',
        bookingSelectExpr($pdo,'selling_price'),bookingSelectExpr($pdo,'supplier_name'),bookingSelectExpr($pdo,'supplier_cost'),bookingSelectExpr($pdo,'invoice_number'),
        'status','is_transit','email_sent','created_at',bookingSelectExpr($pdo,'updated_at'),'addons',
        'special_req','admin_notes','source_url','arr_flight_no','arr_from',
        'arr_date','arr_time','dep_flight_no','dep_to','dep_date','dep_time',
        bookingSelectExpr($pdo,'voucher_file'),bookingSelectExpr($pdo,'voucher_generated_at')
    ]);
}

function presentBooking(array $booking):array{
    $booking['customer_name']=trim(($booking['first_name']??'').' '.($booking['last_name']??''));
    $booking['voucher_available']=bookingAllowsVoucher((string)($booking['status']??''));
    return $booking;
}

function fetchAdminBooking(PDO $pdo,string $ref):?array{
    $st=$pdo->prepare("SELECT ".adminBookingColumns($pdo)." FROM bookings WHERE ref=? LIMIT 1");
    $st->execute([$ref]);
    $row=$st->fetch();
    return $row?:null;
}

function buildBookingFiltersFromQuery(PDO $pdo):array{
    $status=g('status');$search=g('search');$from=g('from');$to=g('to');
    $airport=g('airport');$service=g('service');$flightType=g('flight_type');
    $where=['1=1'];$params=[];
    if($status&&in_array($status,bookingStatuses(),true)){$where[]='status=?';$params[]=$status;}
    if($search){
        $lk="%{$search}%";
        $invoiceExpr=bookingColumnAvailable($pdo,'invoice_number') ? "COALESCE(invoice_number,'')" : "''";
        $where[]="(ref LIKE ? OR email LIKE ? OR CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR airport_code LIKE ? OR {$invoiceExpr} LIKE ?)";
        $params=array_merge($params,[$lk,$lk,$lk,$lk,$lk,$lk,$lk,$lk]);
    }
    $dateExpr=bookingColumnAvailable($pdo,'service_datetime') ? "DATE(COALESCE(service_datetime,created_at))" : "DATE(created_at)";
    if($from){$where[]="{$dateExpr}>=?";$params[]=$from;}
    if($to){$where[]="{$dateExpr}<=?";$params[]=$to;}
    if($airport){$where[]="airport_code=?";$params[]=strtoupper($airport);}
    if($service){$where[]="service_type LIKE ?";$params[]="%{$service}%";}
    if($flightType&&in_array($flightType,['Domestic','International'],true)){$where[]="flight_type=?";$params[]=$flightType;}
    return ['where'=>implode(' AND ',$where),'params'=>$params];
}

function tableExists(PDO $pdo,string $table):bool{
    $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
    $st->execute([$table]);
    return (bool)$st->fetchColumn();
}

function columnExists(PDO $pdo,string $table,string $column):bool{
    $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
    $st->execute([$table,$column]);
    return (bool)$st->fetchColumn();
}

function ensureAdminEnhancements(PDO $pdo):void{
    static $done=false;
    if($done)return;
    $done=true;

    try{
        $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) DEFAULT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(50) DEFAULT NULL,
            total_bookings INT NOT NULL DEFAULT 0,
            last_booking DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_last_booking (last_booking)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }catch(Exception $e){}

    $bookingAdds=[
        'supplier_name'=>"ALTER TABLE bookings ADD COLUMN supplier_name VARCHAR(150) DEFAULT NULL AFTER addons",
        'supplier_cost'=>"ALTER TABLE bookings ADD COLUMN supplier_cost DECIMAL(10,2) DEFAULT NULL AFTER supplier_name",
        'selling_price'=>"ALTER TABLE bookings ADD COLUMN selling_price DECIMAL(10,2) DEFAULT NULL AFTER supplier_cost",
        'invoice_number'=>"ALTER TABLE bookings ADD COLUMN invoice_number VARCHAR(80) DEFAULT NULL AFTER selling_price",
        'service_datetime'=>"ALTER TABLE bookings ADD COLUMN service_datetime DATETIME DEFAULT NULL AFTER flight_time",
        'voucher_file'=>"ALTER TABLE bookings ADD COLUMN voucher_file VARCHAR(255) DEFAULT NULL AFTER invoice_number",
        'voucher_generated_at'=>"ALTER TABLE bookings ADD COLUMN voucher_generated_at DATETIME DEFAULT NULL AFTER voucher_file",
    ];
    foreach($bookingAdds as $column=>$sql){
        try{if(!columnExists($pdo,'bookings',$column))$pdo->exec($sql);}catch(Exception $e){}
    }
    try{$pdo->exec("ALTER TABLE bookings MODIFY status ENUM('Pending','In Queue','Confirmed','Paid','Completed','Cancelled') DEFAULT 'Pending'");}catch(Exception $e){}
    try{$pdo->exec("UPDATE bookings SET service_datetime=CASE WHEN travel_date IS NOT NULL THEN STR_TO_DATE(CONCAT(travel_date,' ',COALESCE(TIME_FORMAT(flight_time,'%H:%i:%s'),'00:00:00')),'%Y-%m-%d %H:%i:%s') ELSE created_at END WHERE service_datetime IS NULL");}catch(Exception $e){}
}

function syncCustomerRollup(PDO $pdo,string $email,string $name='',string $phone=''):void{
    $email=trim($email);
    if(!$email||!filter_var($email,FILTER_VALIDATE_EMAIL))return;
    ensureAdminEnhancements($pdo);
    $name=trim($name);
    $phone=trim($phone);

    $metaSt=$pdo->prepare("SELECT COUNT(*) total, MAX(created_at) last_booking FROM bookings WHERE email=?");
    $metaSt->execute([$email]);
    $meta=$metaSt->fetch()?:['total'=>0,'last_booking'=>null];
    $total=(int)($meta['total']??0);
    $lastBooking=$meta['last_booking']??null;

    $cur=$pdo->prepare("SELECT id,name,phone FROM customers WHERE email=? LIMIT 1");
    $cur->execute([$email]);
    $existing=$cur->fetch();
    if($existing){
        $pdo->prepare("UPDATE customers SET name=?, phone=?, total_bookings=?, last_booking=?, updated_at=NOW() WHERE email=?")
            ->execute([$name!==''?$name:($existing['name']??$email),$phone!==''?$phone:($existing['phone']??null),$total,$lastBooking,$email]);
        return;
    }
    $pdo->prepare("INSERT INTO customers(name,email,phone,total_bookings,last_booking) VALUES(?,?,?,?,?)")
        ->execute([$name!==''?$name:$email,$email,$phone!==''?$phone:null,$total,$lastBooking]);
}

function seedCustomersFromBookings(PDO $pdo):void{
    static $done=false;
    if($done)return;
    $done=true;
    ensureAdminEnhancements($pdo);
    try{
        $existing=(int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        if($existing>0)return;
        $rows=$pdo->query("SELECT email,first_name,last_name,phone FROM bookings WHERE COALESCE(email,'')<>'' ORDER BY created_at DESC")->fetchAll();
        $seen=[];
        foreach($rows as $row){
            $email=trim((string)($row['email']??''));
            $key=strtolower($email);
            if($email===''||isset($seen[$key]))continue;
            $seen[$key]=true;
            syncCustomerRollup($pdo,$email,trim((string)($row['first_name']??'').' '.(string)($row['last_name']??'')),(string)($row['phone']??''));
        }
    }catch(Exception $e){}
}
