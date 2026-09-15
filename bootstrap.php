<?php
declare(strict_types=1);
if (basename($_SERVER['PHP_SELF'] ?? '') === 'login.php') {
    if (isset($_POST['username']) && !isset($_POST['email'])) $_POST['email'] = trim($_POST['username']) . '@cargosom.test';
    ob_start(function($html){$company=e(setting('company_name','CargoSom'));return str_replace(['type="email" name="email"','>Email address<','admin@cargosom.test / password','<div class="mt-4 p-3 bg-light rounded-3 small"><strong>Demo:</strong> admin / password</div>','CargoSom</h1>'], ['name="username"','>Username<','admin / password','',$company.'</h1>'], $html);});
}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'cargo.php') {
    if (isset($_POST['fly_id']) && trim((string)$_POST['fly_id']) !== '') $_POST['fly_awb_no'] = trim((string)$_POST['fly_id']);
    ob_start(function($html){
        $upload = can('import.upload') ? '<a class="btn btn-outline-primary me-1" href="shipment_upload.php"><i class="bi bi-upload"></i> Upload Excel</a>' : '';
        $canDelete = can('shipments.delete');
        $html = str_replace(['<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#shipment">','new bootstrap.Modal(document.querySelector(\'#shipment\')).show();',' name="extra"'], [$upload.'<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#shipment">','window.addEventListener(\'DOMContentLoaded\',()=>new bootstrap.Modal(document.querySelector(\'#shipment\')).show());',' id="extra" name="extra"'], $html);
        $html = preg_replace('#(<a class="btn btn-sm btn-outline-secondary" href="\?edit=(\d+)">Edit</a>)#', '<a class="btn btn-sm btn-outline-primary me-1" href="shipment_view.php?id=$2"><i class="bi bi-eye"></i> View</a>$1'.($canDelete?' <form method="post" class="d-inline"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="id" value="$2"><button class="btn btn-sm btn-outline-danger" name="delete" data-confirm="Delete this shipment?">Delete</button></form>':''), $html);
        return $html.'<script>window.addEventListener("DOMContentLoaded",()=>{const x=document.querySelector("#extra"),k=document.querySelector("#kg"),r=document.querySelector("#rate"),p=document.querySelector("#paid"),b=document.querySelector("#balancePreview");const f=()=>{if(b)b.value=((+k.value||0)*(+r.value||0)-(+p.value||0)+(+x.value||0)).toFixed(2)};x?.addEventListener("input",f);k?.addEventListener("input",f);r?.addEventListener("input",f);p?.addEventListener("input",f);f()})</script>';
    });
}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'dashboard.php') {
    ob_start(fn($html) => str_replace('<section class="hero mb-4">', '<div class="d-flex justify-content-end mb-2"><button id="themeToggle" class="btn btn-sm btn-outline-secondary"><i class="bi bi-moon-stars"></i> Night light</button></div><section class="hero mb-4">', $html) . '<script>const t=document.getElementById("themeToggle"),k="cargosom-theme";function a(){document.body.classList.toggle("night",localStorage.getItem(k)==="night");t.innerHTML=document.body.classList.contains("night")?"<i class=\"bi bi-sun\"></i> Day light":"<i class=\"bi bi-moon-stars\"></i> Night light"}t.onclick=()=>{localStorage.setItem(k,document.body.classList.contains("night")?"day":"night");a()};a()</script>');
}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'invoices.php') {
    ob_start(fn($html) => preg_replace('~(<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#pay(\d+)">Record payment</button>)~','<a class="btn btn-sm btn-outline-secondary me-1" target="_blank" href="invoice_print.php?shipment_id=$2">Print</a>$1',$html));
}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'expenses.php') {
    ob_start(fn($html) => str_replace('<div class="card p-3 p-lg-4">', '<div class="card p-3 mb-3"><div class="row g-2"><div class="col-md-4"><label class="form-label small">From date</label><input id="expenseFrom" class="form-control" type="date"></div><div class="col-md-4"><label class="form-label small">To date</label><input id="expenseTo" class="form-control" type="date"></div><div class="col-md-4 d-flex align-items-end"><button id="expenseClear" type="button" class="btn btn-outline-secondary w-100">Clear dates</button></div></div></div><div class="card p-3 p-lg-4">', $html) . '<script>window.addEventListener("DOMContentLoaded",()=>{const f=document.querySelector("#expenseFrom"),t=document.querySelector("#expenseTo"),rows=document.querySelectorAll("table tbody tr");function q(){rows.forEach(r=>{const d=new Date(r.cells[0].textContent.trim());r.style.display=(!f.value||d>=new Date(f.value))&&(!t.value||d<=new Date(t.value+"T23:59:59"))?"":"none"})}f.onchange=t.onchange=q;document.querySelector("#expenseClear").onclick=()=>{f.value=t.value="";q()}})</script>');
}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'users.php') {
    register_shutdown_function(function(){global $pdo;$branches=[];try{$branches=$pdo->query('SELECT id,name FROM branches WHERE active=1 ORDER BY name')->fetchAll();}catch(Throwable $e){}$data=json_encode($branches);print '<script>const b=document.querySelector("select[name=branch_id]"),x='.$data.';if(b){b.innerHTML="";x.forEach(v=>b.add(new Option(v.name,v.id)));if(!b.options.length)b.add(new Option("Head Office","1"));}</script>';});
    if (isset($_POST['username']) && !isset($_POST['email'])) $_POST['email'] = strtolower(trim($_POST['username'])) . '@cargosom.test';
    if (isset($_POST['create'], $_POST['branch_id'], $_POST['username'])) { $branch=(int)$_POST['branch_id'];$email=strtolower(trim($_POST['username'])).'@cargosom.test';register_shutdown_function(function()use($branch,$email){global $pdo;$pdo->prepare('UPDATE users SET branch_id=? WHERE email=?')->execute([$branch,$email]);}); }
    ob_start(function($html){global $pdo;$opts='';try{foreach($pdo->query('SELECT id,name FROM branches WHERE active=1 ORDER BY name') as $b)$opts.='<option value="'.$b['id'].'">'.e($b['name']).'</option>';}catch(Throwable $e){}return str_replace(['<label class="form-label">Email</label><input class="form-control mb-3" type="email" name="email" required>', 'Email</th>', '<label class="form-label">Temporary password</label>'], ['<label class="form-label">Username</label><input class="form-control mb-3" name="username" required>', 'Username</th>', '<label class="form-label">Branch</label><select class="form-select mb-3" name="branch_id">'.$opts.'</select><label class="form-label">Temporary password</label>'], $html);});
}
$sessionPath = sys_get_temp_dir() . '/cargosom-sessions';
if (!is_dir($sessionPath)) mkdir($sessionPath, 0700, true);
if (is_writable($sessionPath)) session_save_path($sessionPath);
session_start();

$configFile = __DIR__ . '/config.php';
$config = file_exists($configFile) ? require $configFile : require __DIR__ . '/config.example.php';
try {
    $d = $config['db'];
    $pdo = new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset=utf8mb4", $d['user'], $d['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
} catch (PDOException $e) {
    http_response_code(503); exit('<h2>Database unavailable</h2><p>Copy config.example.php to config.php and import database/cargosom.sql.</p>');
}
/* Apply small, backwards-compatible feature upgrades on first request. */
try {
    $hasDiscount = (bool)$pdo->query("SHOW COLUMNS FROM shipment_invoices LIKE 'discount_amount'")->fetch();
    if (!$hasDiscount) $pdo->exec('ALTER TABLE shipment_invoices ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER subtotal');
    $hasType = (bool)$pdo->query("SHOW COLUMNS FROM general_invoices LIKE 'document_type'")->fetch();
    if (!$hasType) $pdo->exec("ALTER TABLE general_invoices ADD COLUMN document_type ENUM('Invoice','Quotation') NOT NULL DEFAULT 'Invoice' AFTER invoice_no");
    $hasAccount = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'account_no'")->fetch();
    if (!$hasAccount) $pdo->exec('ALTER TABLE users ADD COLUMN account_no VARCHAR(30) NULL UNIQUE AFTER email');
    $pdo->exec("UPDATE users SET account_no=CONCAT('CS-',LPAD(id,6,'0')) WHERE account_no IS NULL OR account_no=''");
    $pdo->exec("CREATE TABLE IF NOT EXISTS account_transactions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT UNSIGNED NOT NULL,transaction_type ENUM('Invoice','Payment','Adjustment') NOT NULL,reference_no VARCHAR(80) NOT NULL,description VARCHAR(255) NOT NULL,debit DECIMAL(12,2) NOT NULL DEFAULT 0.00,credit DECIMAL(12,2) NOT NULL DEFAULT 0.00,transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,INDEX idx_account_transaction_user_date (user_id,transaction_date)) ENGINE=InnoDB");
    if (!(bool)$pdo->query("SHOW TRIGGERS LIKE 'account_ledger_invoice'")->fetch()) $pdo->exec("CREATE TRIGGER account_ledger_invoice AFTER INSERT ON shipment_invoices FOR EACH ROW INSERT INTO account_transactions(user_id,transaction_type,reference_no,description,debit) SELECT s.customer_id,'Invoice',NEW.invoice_no,CONCAT('Shipment invoice · ',s.awb),NEW.total+s.extra FROM shipments s WHERE s.id=NEW.shipment_id AND s.customer_id IS NOT NULL");
    if (!(bool)$pdo->query("SHOW TRIGGERS LIKE 'account_ledger_payment'")->fetch()) $pdo->exec("CREATE TRIGGER account_ledger_payment AFTER INSERT ON payments FOR EACH ROW INSERT INTO account_transactions(user_id,transaction_type,reference_no,description,credit) SELECT s.customer_id,'Payment',COALESCE(NEW.reference_no,CONCAT('PAY-',NEW.id)),CONCAT('Payment for shipment · ',s.awb),NEW.amount FROM shipments s WHERE s.id=NEW.shipment_id AND s.customer_id IS NOT NULL");
    $pdo->exec("CREATE TABLE IF NOT EXISTS bank_accounts (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,bank_name VARCHAR(120) NOT NULL,account_number VARCHAR(80) NOT NULL,opening_balance DECIMAL(12,2) NOT NULL DEFAULT 0,branch_id INT UNSIGNED NOT NULL,created_by INT UNSIGNED NOT NULL,active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(branch_id) REFERENCES branches(id),FOREIGN KEY(created_by) REFERENCES users(id)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS bank_transactions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,bank_account_id INT UNSIGNED NOT NULL,transaction_type ENUM('Payment','Income','Expense') NOT NULL,source_table VARCHAR(30) NOT NULL,source_id INT UNSIGNED NOT NULL,amount_in DECIMAL(12,2) NOT NULL DEFAULT 0,amount_out DECIMAL(12,2) NOT NULL DEFAULT 0,transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(bank_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT,INDEX idx_bank_transaction_account_date(bank_account_id,transaction_date)) ENGINE=InnoDB");
} catch (Throwable $e) {
    // Existing pages still load if a restricted database user cannot alter schema.
}
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bank_accounts (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,bank_name VARCHAR(120) NOT NULL,account_number VARCHAR(80) NOT NULL,opening_balance DECIMAL(12,2) NOT NULL DEFAULT 0,branch_id INT UNSIGNED NOT NULL,created_by INT UNSIGNED NOT NULL,active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(branch_id) REFERENCES branches(id),FOREIGN KEY(created_by) REFERENCES users(id)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS bank_transactions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,bank_account_id INT UNSIGNED NOT NULL,transaction_type ENUM('Payment','Income','Expense') NOT NULL,source_table VARCHAR(30) NOT NULL,source_id INT UNSIGNED NOT NULL,amount_in DECIMAL(12,2) NOT NULL DEFAULT 0,amount_out DECIMAL(12,2) NOT NULL DEFAULT 0,transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(bank_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT,INDEX idx_bank_transaction_account_date(bank_account_id,transaction_date)) ENGINE=InnoDB");
    $hasPaymentBank = (bool)$pdo->query("SHOW COLUMNS FROM payments LIKE 'bank_account_id'")->fetch();
    if (!$hasPaymentBank) $pdo->exec('ALTER TABLE payments ADD COLUMN bank_account_id INT UNSIGNED NULL AFTER general_invoice_id');
    $hasExpenseBank = (bool)$pdo->query("SHOW COLUMNS FROM expenses LIKE 'bank_account_id'")->fetch();
    if (!$hasExpenseBank) $pdo->exec('ALTER TABLE expenses ADD COLUMN bank_account_id INT UNSIGNED NULL AFTER branch_id');
    $hasToBranch = (bool)$pdo->query("SHOW COLUMNS FROM shipments LIKE 'to_branch_id'")->fetch();
    if (!$hasToBranch) $pdo->exec("ALTER TABLE shipments ADD COLUMN to_branch_id INT UNSIGNED NULL AFTER branch_id");
    $hasReceiverPhone = (bool)$pdo->query("SHOW COLUMNS FROM shipments LIKE 'receiver_phone'")->fetch();
    if (!$hasReceiverPhone) $pdo->exec('ALTER TABLE shipments ADD COLUMN receiver_phone VARCHAR(50) NULL AFTER receiver');
    $balExpr=(string)$pdo->query("SELECT GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='shipments' AND COLUMN_NAME='balance_mgq'")->fetchColumn();
    if (strpos($balExpr,'extra')===false) $pdo->exec('ALTER TABLE shipments MODIFY COLUMN balance_mgq DECIMAL(12,2) GENERATED ALWAYS AS (kg*rate+extra-paid_dxb) STORED');
    $hasPlanPaidAt=(bool)$pdo->query("SHOW COLUMNS FROM payment_plans LIKE 'paid_at'")->fetch();
    if(!$hasPlanPaidAt)$pdo->exec('ALTER TABLE payment_plans ADD COLUMN paid_at DATETIME NULL AFTER status');
    $pdo->exec("UPDATE shipments SET to_branch_id=branch_id WHERE to_branch_id IS NULL OR to_branch_id=0");
    $pdo->exec("CREATE TABLE IF NOT EXISTS shipment_awbs (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,awb_no VARCHAR(80) NOT NULL UNIQUE,name VARCHAR(150) NOT NULL DEFAULT '',airline_id INT UNSIGNED NULL,notes VARCHAR(255) NOT NULL DEFAULT '',branch_id INT UNSIGNED NOT NULL,created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (airline_id) REFERENCES airlines(id) ON DELETE SET NULL,FOREIGN KEY (branch_id) REFERENCES branches(id),FOREIGN KEY (created_by) REFERENCES users(id),INDEX idx_awb_branch (branch_id),INDEX idx_awb_created (created_at)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS shipment_awb_members (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,awb_id INT UNSIGNED NOT NULL,shipment_id INT UNSIGNED NOT NULL UNIQUE,added_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (awb_id) REFERENCES shipment_awbs(id) ON DELETE CASCADE,FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,FOREIGN KEY (added_by) REFERENCES users(id),INDEX idx_member_awb (awb_id),INDEX idx_member_shipment (shipment_id)) ENGINE=InnoDB");
} catch (Throwable $e) {}
// Banking is written by the payment workflow so account links are explicit and
// transaction rows cannot be attached to another user's most recent payment.
try { $pdo->exec('DROP TRIGGER IF EXISTS bank_set_payment_account'); $pdo->exec('DROP TRIGGER IF EXISTS bank_tx_payment'); $pdo->exec('DROP TRIGGER IF EXISTS bank_tx_income'); $pdo->exec('DROP TRIGGER IF EXISTS bank_tx_expense'); } catch (Throwable $e) {}
$appSettings=[];
try { foreach($pdo->query('SELECT setting_key,setting_value FROM company_settings') as $row) $appSettings[$row['setting_key']]=$row['setting_value']; } catch(PDOException $e) {}
date_default_timezone_set($appSettings['timezone']??'Africa/Nairobi');
function setting(string $key,string $default=''): string { return (string)($GLOBALS['appSettings'][$key]??$default); }
function app_date(string|int|null $value=null): string { $time=is_int($value)?$value:strtotime($value??'now'); return date(setting('date_format','d M Y'),$time); }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function url(string $path=''): string { return rtrim($GLOBALS['config']['app']['base_url'],'/').'/'.ltrim($path,'/'); }
function redirect(string $path): never { header('Location: '.url($path)); exit; }
function user(): ?array { return $_SESSION['user']??null; }
function is_admin(): bool { return in_array(user()['role']??'',['super_admin','admin'],true); }
function branch_id(): int { return (int)(user()['branch_id']??0); }
function require_branch(): int { $id=branch_id(); if($id<1){http_response_code(403);exit('Your account is not assigned to a branch.');} return $id; }
function require_login(): void { if(!user()) redirect('login.php'); }
function require_roles(array $roles): void { require_login(); if((user()['role']??'')==='super_admin'||in_array(user()['role'],$roles,true))return;http_response_code(403);exit('Access denied.'); }

/* ---------- Permissions system ----------
 * Admin / super_admin    → dhammaan permissions (can() = true).
 * Users-ka kale          → waxay helaan role-deefaults, ama permissions-ga
 *                          laga xiiray (users.php) marka la doorto.
 * `permissions` column   → JSON array; NULL/empty = role default.
 */
const ALL_PERMISSIONS = [
    'shipments.view'    => 'View shipments',
    'shipments.create'  => 'Create shipments',
    'shipments.edit'    => 'Edit shipments',
    'shipments.delete'  => 'Delete shipments',
    'import.upload'     => 'Import shipments (Excel/CSV)',
    'shipment_awb.view' => 'View shipment AWB groups',
    'shipment_awb.manage' => 'Create / manage shipment AWB groups',
    'payments.view'     => 'View payments',
    'payments.record'   => 'Record payments',
    'income.manage'     => 'Manage income',
    'expenses.manage'   => 'Manage expenses',
    'invoices.view'     => 'View invoices',
    'invoices.create'   => 'Create / cancel invoices',
    'reports.view'      => 'View reports',
    'bank.manage'       => 'Manage bank accounts',
    'users.manage'      => 'Manage users & permissions',
    'branches.manage'   => 'Manage branches',
    'settings.manage'   => 'Manage settings',
];
const ROLE_PERMISSIONS = [
    'staff'      => ['shipments.view','shipments.create','shipments.edit','import.upload','payments.view','payments.record','shipment_awb.view','shipment_awb.manage'],
    'accountant' => ['shipments.view','payments.view','payments.record','income.manage','expenses.manage','invoices.view','invoices.create','reports.view','bank.manage','shipment_awb.view'],
    'customer'   => ['shipments.view','invoices.view','shipment_awb.view'],
    'admin'      => [],
];
function current_permissions(): array {
    $me=user(); if(!$me) return [];
    $role=(string)($me['role']??''); if($role==='admin'||$role==='super_admin') return array_keys(ALL_PERMISSIONS);
    $p=$me['permissions']??null;
    if(is_string($p)){ $dec=@json_decode($p); $p=is_array($dec)?$dec:null; }
    if(is_array($p)&&$p) return array_map(fn($x)=>(string)$x,$p);
    return ROLE_PERMISSIONS[$role]??[];
}
function can(string $perm): bool { return in_array($perm,current_permissions(),true); }
function require_perm(string $perm): void { require_login(); if(!can($perm)){flash('danger','You do not have permission to do that.');http_response_code(403);exit('Access denied.');} }
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(): void {
    if (hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) return;
    unset($_SESSION['csrf']);
    flash('warning', 'Your form expired. Please try again.');
    $path = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'login.php');
    header('Location: ' . url($path), true, 303);
    exit;
}
function flash(string $type,string $message): void { $_SESSION['flash'][]=[$type,$message]; }
function money(float|string|null $amount): string { return e(setting('currency',(string)($GLOBALS['config']['app']['currency']??'USD'))).' '.number_format((float)$amount,2); }
function notify(int $userId,string $subject,string $message): void { global $pdo; $pdo->prepare('INSERT INTO notifications (user_id,subject,message) VALUES (?,?,?)')->execute([$userId,$subject,$message]); }
function bank_account_id_for_branch(int $accountId, int $branchId): int { global $pdo; if(is_admin()){ $q=$pdo->prepare('SELECT id FROM bank_accounts WHERE id=? AND active=1'); $q->execute([$accountId]); } else { $q=$pdo->prepare('SELECT id FROM bank_accounts WHERE id=? AND branch_id=? AND active=1'); $q->execute([$accountId,$branchId]); } return (int)($q->fetchColumn() ?: 0); }
function add_bank_transaction(int $accountId, string $type, string $sourceTable, int $sourceId, float $amount, string $date): void { global $pdo; $q=$pdo->prepare('INSERT INTO bank_transactions(bank_account_id,transaction_type,source_table,source_id,amount_in,amount_out,transaction_date) VALUES(?,?,?,?,?,?,?)'); $q->execute([$accountId,$type,$sourceTable,$sourceId,$type==='Expense'?0:$amount,$type==='Expense'?$amount:0,$date]); }
function sync_income_bank_transaction(int $entryId, int $bankId, float $amount, string $date): void {
    global $pdo;
    $found=(int)$pdo->query("SELECT id FROM bank_transactions WHERE source_table='income_entries' AND source_id=".(int)$entryId)->fetchColumn();
    if(!$bankId){ if($found)$pdo->prepare('DELETE FROM bank_transactions WHERE id=?')->execute([$found]); return; }
    if($found){ $pdo->prepare('UPDATE bank_transactions SET bank_account_id=?,amount_in=?,amount_out=0,transaction_date=? WHERE id=?')->execute([$bankId,$amount,$date.' '.date('H:i:s'),$found]); }
    else { add_bank_transaction($bankId,'Income','income_entries',$entryId,$amount,$date.' '.date('H:i:s')); }
}
/* Keep the bank statement in sync when a payment is edited:
   - bank changed  → move the transaction to the new account (new amount/date)
   - bank unlinked → delete the transaction
   - bank added    → create the transaction                              */
function sync_payment_bank_transaction(int $paymentId, int $bankId, float $amount, string $date): void {
    global $pdo;
    $found=(int)$pdo->query("SELECT id FROM bank_transactions WHERE source_table='payments' AND source_id=".(int)$paymentId)->fetchColumn();
    if(!$bankId){ if($found)$pdo->prepare('DELETE FROM bank_transactions WHERE id=?')->execute([$found]); return; }
    if($found){ $pdo->prepare('UPDATE bank_transactions SET bank_account_id=?,amount_in=?,amount_out=0,transaction_date=? WHERE id=?')->execute([$bankId,$amount,$date,$found]); }
    else { add_bank_transaction($bankId,'Payment','payments',$paymentId,$amount,$date); }
}
/* Keep the bank statement in sync when an expense is edited (money OUT):
   bank changed → move it; unlinked → delete it; added → create it.       */
function sync_expense_bank_transaction(int $expenseId, int $bankId, float $amount, string $date): void {
    global $pdo;
    $found=(int)$pdo->query("SELECT id FROM bank_transactions WHERE source_table='expenses' AND source_id=".(int)$expenseId)->fetchColumn();
    if(!$bankId){ if($found)$pdo->prepare('DELETE FROM bank_transactions WHERE id=?')->execute([$found]); return; }
    if($found){ $pdo->prepare('UPDATE bank_transactions SET bank_account_id=?,amount_in=0,amount_out=?,transaction_date=? WHERE id=?')->execute([$bankId,$amount,$date,$found]); }
    else { add_bank_transaction($bankId,'Expense','expenses',$expenseId,$amount,$date); }
}
function create_shipment_invoice(int $shipmentId, float $subtotal, string $createdBy): string {
    global $pdo;
    $year=date('Y');
    $invNo='INV-'.$year.'-'.str_pad((string)(1+(int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_no,-5) AS UNSIGNED)),0) FROM shipment_invoices WHERE invoice_no LIKE 'INV-".$year."-%'")->fetchColumn()),5,'0',STR_PAD_LEFT);
    $pdo->prepare('INSERT INTO shipment_invoices(invoice_no,shipment_id,subtotal,discount_amount,tax_rate,tax_amount,total,issue_date,due_date,status,notes,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$invNo,$shipmentId,$subtotal,0,0,0,$subtotal,date('Y-m-d'),date('Y-m-d',strtotime('+14 days')),'Issued','Created automatically when the shipment was registered',(int)$createdBy]);
    return $invNo;
}
/* Backfill one invoice for every existing shipment that does not have one yet,
   so the shipment-invoice list is complete even for records created before
   automatic invoice generation was introduced. */
try {
    $missing=$pdo->query("SELECT s.id shipment_id,s.total FROM shipments s WHERE s.status<>'Cancelled' AND s.total>0 AND NOT EXISTS (SELECT 1 FROM shipment_invoices i WHERE i.shipment_id=s.id)")->fetchAll();
    if ($missing) foreach($missing as $m){ try{ create_shipment_invoice((int)$m['shipment_id'],(float)$m['total'],'1'); }catch(Throwable $e){} }
} catch (Throwable $e) {}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'settings.php') {
    ob_start(function($html){
        $address=json_encode(setting('company_address',''), JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_TAG);
        $mobile=json_encode(setting('company_mobile_numbers',''), JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_TAG);
        return $html.'<script>window.addEventListener("DOMContentLoaded",()=>{const logo=document.querySelector("input[name=logo]");if(!logo)return;const wrap=document.createElement("div");wrap.className="row g-3 mt-1";wrap.innerHTML="<div class=\\"col-md-6\\"><label class=\\"form-label\\">Address</label><input class=\\"form-control\\" name=\\"company_address\\" placeholder=\\"Company address\\"></div><div class=\\"col-md-6\\"><label class=\\"form-label\\">Mobile Numbers</label><input class=\\"form-control\\" name=\\"company_mobile_numbers\\" placeholder=\\"+252... / +971...\\"></div>";logo.closest(".col-12")?.after(wrap);wrap.querySelector("[name=company_address]").value='.$address.';wrap.querySelector("[name=company_mobile_numbers]").value='.$mobile.';})</script>';
    });
}
if (!in_array(basename($_SERVER['PHP_SELF'] ?? ''), ['login.php','bank_options.php'], true)) {
    ob_start(function($html){
        if (basename($_SERVER['PHP_SELF'] ?? '') === 'quotations.php') $html=str_replace('>Rate</label>', '>Rate (Money)</label>', $html);
        $html .= '<script>window.addEventListener("DOMContentLoaded",()=>document.querySelectorAll("table.table").forEach(t=>{if(!t.closest(".table-responsive")){const w=document.createElement("div");w.className="table-responsive";t.before(w);w.append(t)}}))</script>';
        return $html;
    });
}
/* ---------- Language switching (sidebar labels) ---------- */
$__lang = setting('language','en');
if ($__lang !== 'en' && in_array($__lang,['so','ar'],true)) {
    ob_start(function($html) use($__lang){
        $dict = $__lang === 'so'
            ? ['Dashboard'=>'Dashboarda','Shipments'=>'Shixnaad','Finance'=>'Maaliyad','Payments'=>'Bixis','Payment Track'=>'Raadka bixista','Income'=>'Dakhil','Expenses'=>'Kharash','Bank Accounts'=>'Xisaabaha bangiyada','Bank Transactions'=>'Macamilada bangiyada','Shipment invoices'=>'Qaanshooyinka shixnaadka','General invoices'=>'Qaanshooyin guud','Quotations'=>'Xisaab qaansho','Reports'=>'Warbixinno','Profit & Loss'=>'Faa\'iido & Khasaaro','Administration'=>'Maamulka','Branches'=>'Laamooyinka','Airlines'=>'Diyaaradaha','Users & access'=>'Isticmaalayaasha','Settings'=>'Dejinta','Workspace'=>'Shaqo-gud','View shipments'=>'Eeg shixnaadka','Create shipments'=>'Ku dar shixnaad','Shipment AWB'=>'AWB shixnaad','Login'=>'Gelitaan','Sign out'=>'Ka bix']
            : ['Dashboard'=>'لوحة التحكم','Shipments'=>'الشحنات','Finance'=>'المالية','Payments'=>'المدفوعات','Payment Track'=>'تتبع المدفوعات','Income'=>'الإيرادات','Expenses'=>'المصروفات','Bank Accounts'=>'الحسابات البنكية','Bank Transactions'=>'التحويلات البنكية','Shipment invoices'=>'فواتير الشحن','General invoices'=>'فواتير عامة','Quotations'=>'عروض الأسعار','Reports'=>'التقارير','Profit & Loss'=>'الأرباح والخسائر','Administration'=>'الإدارة','Branches'=>'الفروع','Airlines'=>'الخطوط الجوية','Users & access'=>'المستخدمون','Settings'=>'الإعدادات','Workspace'=>'مساحة العمل','View shipments'=>'عرض الشحنات','Create shipments'=>'إضافة شحنة','Shipment AWB'=>'مجموعات AWB','Login'=>'تسجيل الدخول','Sign out'=>'خروج'];
        $dir = $__lang === 'ar' ? 'document.documentElement.dir="rtl";' : '';
        return $html.'<script>window.addEventListener("DOMContentLoaded",()=>{const d='.json_encode($dict).';document.querySelectorAll(".sidebar-link span,.nav-section,.badge").forEach(el=>{const t=el.textContent.trim();if(d[t])el.textContent=d[t]});'.$dir.'})</script>';
    });
}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'cargo.php') {
    ob_start(fn($html) => $html . '<script>window.addEventListener("load",()=>{document.querySelectorAll("table tbody tr").forEach(row=>{const cells=row.cells;const heads=[...row.closest("table").querySelectorAll("thead th")].map(h=>h.textContent.trim().toLowerCase());const fi=heads.findIndex(h=>h.startsWith("flight"));const ai=heads.findIndex(h=>h.startsWith("date"));if(fi<0||ai<0)return;const fly=cells[fi]?.querySelector("small")?.textContent.trim();const awb=cells[ai]?.querySelector("strong");if(!fly||!awb)return;awb.closest("td")?.querySelectorAll(".fly-id-badge,.text-secondary").forEach(x=>{if(x.textContent.includes("Fly ID:"))x.remove()});awb.insertAdjacentHTML("beforebegin",`<span class="badge rounded-pill text-bg-primary border border-primary-subtle me-1 fly-id-badge"><i class="bi bi-airplane-engines me-1"></i>Fly ID: ${fly}</span>`);})})</script>');
}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'cargo.php') {
    ob_start(fn($html) => $html . '<script>window.addEventListener("DOMContentLoaded",()=>{const awb=document.querySelector("input[name=fly_awb_no]");if(!awb||document.querySelector("input[name=fly_id]"))return;const box=document.createElement("div");box.className="col-md-3";box.innerHTML=`<label class="form-label">Fly ID</label><input class="form-control" name="fly_id" placeholder="Enter Fly ID">`;const field=box.querySelector("input");field.value=awb.value;field.oninput=()=>awb.value=field.value;awb.closest(".col-md-3")?.before(box)})</script>');
}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'cargo.php') {
    ob_start(fn($html) => $html . '<script>window.addEventListener("load",()=>{const fly=document.querySelector("input[name=fly_id]")?.closest(".col-md-3"),date=document.querySelector("input[name=shipment_date]")?.closest(".col-md-3");if(fly&&date)date.after(fly)})</script>');
}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'cargo.php') {
    ob_start(fn($html) => $html . '<script>window.addEventListener("load",()=>{const date=document.querySelector("input[name=shipment_date]")?.closest(".col-md-3");if(!date||document.querySelector("#shipmentSections"))return;const a=document.createElement("div");a.id="shipmentSections";a.className="col-12 mt-2";a.innerHTML=`<div class="d-flex align-items-center gap-2 border-bottom pb-2"><span class="badge text-bg-primary rounded-pill"><i class="bi bi-box-seam me-1"></i>1</span><strong>Shipment Details</strong><small class="text-secondary">Cargo, customer, and charges</small></div>`;date.before(a)})</script>');
}
if (basename($_SERVER['PHP_SELF'] ?? '') === 'cargo.php') {
    ob_start(fn($html) => $html . '<script>window.addEventListener("load",()=>setTimeout(()=>{const customer=document.querySelector("select[name=customer_id]")?.closest(".col-md-3"),flight=[...document.querySelectorAll(".col-12")].find(x=>x.innerText.includes("Flight Details"));if(customer&&flight)flight.before(customer)},0))</script>');
}
