<?php
require __DIR__.'/bootstrap.php';
require_roles(['admin','accountant']);
$branch=is_admin()?null:require_branch();
$stmt=$pdo->prepare('SELECT g.*,b.name branch_name,u.name created_by_name FROM general_invoices g JOIN branches b ON b.id=g.branch_id JOIN users u ON u.id=g.created_by WHERE g.id=?'.($branch===null?'':' AND g.branch_id='.(int)$branch));
$stmt->execute([(int)($_GET['id']??0)]);$r=$stmt->fetch();
if(!$r){http_response_code(404);exit('Invoice not found.');}
$items=$pdo->prepare('SELECT * FROM general_invoice_items WHERE general_invoice_id=? ORDER BY id');$items->execute([$r['id']]);$items=$items->fetchAll();if(!$items)$items=[['description'=>$r['description'],'kg'=>$r['kg'],'rate'=>$r['rate'],'amount'=>$r['amount']]];
$paid=$pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE general_invoice_id=?');$paid->execute([$r['id']]);$paidAmount=(float)$paid->fetchColumn();$balance=max(0,(float)$r['amount']-$paidAmount);
$payments=$pdo->prepare('SELECT p.*,u.name received_by_name,COALESCE(bank.name,\'\') bank_name FROM payments p LEFT JOIN users u ON u.id=p.received_by LEFT JOIN bank_accounts bank ON bank.id=p.bank_account_id WHERE p.general_invoice_id=? ORDER BY p.paid_at ASC');$payments->execute([$r['id']]);$payments=$payments->fetchAll();
$company=setting('company_name','CargoSom');$tagline=setting('company_tagline','Cargo Agency');$logo=setting('logo_path');
$statusClass=$r['status']==='Paid'?'success':($r['status']==='Overdue'?'danger':($r['status']==='Cancelled'?'secondary':'primary'));
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($r['invoice_no'])?> · <?=e($company)?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"><style>
:root{--ink:#22364f;--brand:#1259c7;--brand-dark:#0b3d7a;--line:#dde8f5;--soft:#f4f8fd;--muted:#6b7c93}
*{font-family:Segoe UI,Arial,sans-serif}
body{background:linear-gradient(135deg,#e8f2fb 0%,#f7fbfe 55%,#eef6ff 100%);color:var(--ink)}
.tools,.sheet{max-width:880px;margin:auto}
.tools{padding:26px 0 0;text-align:right}
.sheet{margin-top:18px;margin-bottom:36px;background:#fff;padding:46px;border-radius:18px;border-top:8px solid var(--brand);box-shadow:0 20px 55px rgb(16 42 67 / 18%)}
.logo{max-width:190px;max-height:84px;object-fit:contain}
.mark{display:grid;place-items:center;width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#1768ac,#0b3d7a);color:#fff;font-size:1.3rem;font-weight:800}
.inv-head{background:linear-gradient(90deg,#0b3d7a,#1768ac);color:#fff;padding:14px 22px;border-radius:14px}
.inv-head small{opacity:.75}
.status{display:inline-block;padding:5px 14px;border-radius:99px;font-size:.78rem;font-weight:700}
.status-success{background:#e3f6e9;color:#16794a}.status-danger{background:#fde8e8;color:#b42318}.status-secondary{background:#eef1f4;color:#5d6b77}.status-primary{background:#e8f2ff;color:#0b58a2}
.party{background:var(--soft);border:1px solid var(--line);border-radius:12px;padding:14px 16px}
.party .label{font-size:.7rem;text-transform:uppercase;letter-spacing:.6px;color:var(--muted)}
.table{border:1px solid var(--line);border-radius:12px;overflow:hidden}
.table thead th{background:linear-gradient(90deg,var(--brand-dark),var(--brand));color:#fff;border:0;padding:12px 14px;font-size:.8rem;text-transform:uppercase;letter-spacing:.3px}
.table th,.table td{padding:12px 14px;font-size:.92rem}
.table tbody tr:nth-child(even){background:var(--soft)}
.amount{font-variant-numeric:tabular-nums;white-space:nowrap}
.section-title{display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:var(--brand-dark);border-bottom:2px solid var(--line);padding-bottom:8px}
.pymt-badge{display:inline-block;padding:2px 10px;border-radius:99px;font-size:.75rem}
.pay-method{background:#e8f2ff;color:#0b58a2}
.loc{background:#eef1f4;color:#5d6b77}
.summary{border:1px solid var(--line);border-radius:12px;background:var(--soft)}
.summary>div{padding:14px 20px}
.summary .amt{font-size:1.05rem;font-weight:700}
.summary .paid{color:#16794a}
.notes{background:#fbfcff;border:1px solid var(--line);border-left:4px solid var(--brand);border-radius:10px;padding:14px 18px}
@page{size:A4;margin:12mm}
@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}body{background:#fff}.tools{display:none}.sheet{margin:0;max-width:none;box-shadow:none;border-radius:0;border-top:none;padding:14px}}
</style></head><body>
<div class="tools d-print-none"><a class="btn btn-outline-secondary" href="general_invoices.php"><i class="bi bi-arrow-left me-1"></i>Back</a> <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print / Save PDF</button></div>
<div class="sheet">
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div class="flex-grow-1">
      <?php if($logo):?><img class="logo mb-3" src="<?=url($logo)?>" alt="<?=e($company)?> logo"><?php else:?><div class="mark mb-3"><?=e(strtoupper(substr($company,0,2)))?></div><?php endif?>
      <h1 class="h3 fw-bold text-primary mb-1"><?=e($company)?></h1>
      <div class="text-secondary"><?=e($tagline)?></div>
    </div>
    <div class="inv-head text-end" style="min-width:300px">
      <small class="d-block">General invoice</small>
      <div class="fs-5 fw-bold mb-2"><?=e($r['invoice_no'])?></div>
      <div class="small d-flex flex-column align-items-end gap-1">
        <span><i class="bi bi-calendar3 me-1"></i>Issued: <?=app_date($r['issue_date'])?></span>
        <span><i class="bi bi-hourglass me-1"></i>Due: <?=app_date($r['due_date'])?></span>
      </div>
      <span class="status status-<?=e($statusClass)?>"><?=e($r['status'])?></span>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="party"><small class="label d-block">Bill to</small><h5 class="mb-1"><?=e($r['receiver'])?></h5><div class="text-secondary small"><i class="bi bi-telephone me-1"></i><?=e($r['phone']?:'—')?></div></div></div>
    <div class="col-md-4"><div class="party"><small class="label d-block">Branch</small><h5 class="mb-1"><?=e($r['branch_name'])?></h5><div class="text-secondary small"><i class="bi bi-buildings me-1"></i>Professional cargo &amp; services invoice</div></div></div>
    <div class="col-md-4"><div class="party"><small class="label d-block">Prepared by</small><h5 class="mb-1"><?=e($r['created_by_name'])?></h5><div class="text-secondary small"><i class="bi bi-person me-1"></i><?=app_date($r['issue_date'])?></div></div></div>
  </div>

  <div class="section-title mb-3"><i class="bi bi-list-check"></i>Service details</div>
  <table class="table">
    <thead><tr><th>Description</th><th class="text-center">KG</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
      <?php foreach($items as $item):?>
      <tr><td class="fw-medium"><?=e($item['description'])?></td><td class="text-center"><?=e($item['kg'])?> KG</td><td class="text-end amount"><?=money($item['rate'])?></td><td class="text-end amount fw-semibold"><?=money($item['amount'])?></td></tr>
      <?php endforeach?>
    </tbody>
  </table>

  <div class="section-title mb-3 mt-4"><i class="bi bi-credit-card"></i>Payment details</div>
  <?php if($payments):?>
  <table class="table">
    <thead><tr><th>Date</th><th>Method</th><th>Bank account</th><th>Location</th><th>Reference</th><th>Received by</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
      <?php foreach($payments as $p):?>
      <tr>
        <td><?=app_date($p['paid_at'])?><?php if($p['notes']):?><small class="d-block text-secondary"><?=e($p['notes'])?></small><?php endif?></td>
        <td><span class="pymt-badge pay-method"><?=e($p['payment_method'])?></span></td>
        <td><?=e($p['bank_name']?:'—')?></td>
        <td><span class="pymt-badge loc"><?=e($p['location'])?></span></td>
        <td><?=e($p['reference_no']?:'—')?></td>
        <td><?=e($p['received_by_name']?:'—')?></td>
        <td class="text-end amount fw-semibold"><?=money($p['amount'])?></td>
      </tr>
      <?php endforeach?>
    </tbody>
    <tfoot><tr><th colspan="6" class="text-end">Total paid</th><th class="text-end text-success amount"><?=money($paidAmount)?></th></tr></tfoot>
  </table>
  <?php else:?>
  <div class="py-4 text-center text-secondary"><i class="bi bi-info-circle me-2"></i>No payments recorded yet for this invoice.</div>
  <?php endif?>

  <div class="summary d-flex justify-content-between align-items-stretch mt-4">
    <div><small class="text-muted d-block">Total amount</small><div class="amt"><?=money($r['amount'])?></div></div>
    <div class="border-start"><small class="text-muted d-block">Paid</small><div class="amt paid"><i class="bi bi-check-circle me-1"></i><?=money($paidAmount)?></div></div>
    <div class="border-start rounded-3 <?=$balance>0?'bg-danger-subtle':'bg-success-subtle'?>"><small class="text-muted d-block">Balance due</small><div class="amt <?=$balance>0?'text-danger':'text-success'?>"><?=money($balance)?></div></div>
  </div>

  <?php if($r['notes']?:''):?>
  <div class="notes mt-4"><div class="section-title mb-2"><i class="bi bi-journal-text"></i>Notes</div><p class="mb-0"><?=nl2br(e($r['notes']))?></p></div>
  <?php endif?>

  <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top text-secondary small">
    <span><i class="bi bi-check-circle me-1"></i>Thank you for choosing <?=e($company)?>.</span>
    <span><?=e($r['invoice_no'])?> · <?=app_date($r['issue_date'])?></span>
  </div>
</div>
</body></html>
