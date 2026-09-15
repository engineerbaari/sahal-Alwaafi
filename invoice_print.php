<?php
require __DIR__.'/bootstrap.php'; require_perm('invoices.view');
$invoiceId=(int)($_GET['id']??0);
if(!$invoiceId && isset($_GET['shipment_id'])){
    $s=$pdo->prepare('SELECT id FROM shipment_invoices WHERE shipment_id=? ORDER BY id DESC LIMIT 1');$s->execute([(int)$_GET['shipment_id']]);$invoiceId=(int)$s->fetchColumn();
}
$q=$pdo->prepare('SELECT i.*,s.awb,s.shipment_date,s.sender,s.tell,s.receiver,s.description,s.pcs,s.kg,s.rate,s.extra,s.paid_dxb,a.name airline,a.code airline_code,ob.name origin_branch,tb.name dest_branch,i.status invoice_status FROM shipment_invoices i JOIN shipments s ON s.id=i.shipment_id LEFT JOIN airlines a ON a.id=s.airline_id JOIN branches ob ON ob.id=s.branch_id LEFT JOIN branches tb ON tb.id=s.to_branch_id WHERE i.id=?');$q->execute([$invoiceId]);$r=$q->fetch();
if(!$r){http_response_code(404);exit('Invoice not found.');}
$company=setting('company_name','CargoSom');$tagline=setting('company_tagline','Cargo Agency');$logo=setting('logo_path');$phone=setting('company_phone','');$address=setting('company_address','');
$discount=(float)($r['discount_amount']??0);$extra=(float)($r['extra']??0);
$invoiceTotal=round((float)$r['total']+$extra,2);$paid=min((float)($r['paid_dxb']??0),$invoiceTotal);$balance=max(0,$invoiceTotal-$paid);
$statusMap=['Paid'=>'success','Partially Paid'=>'warning','Unpaid'=>'secondary','Issued'=>'primary','Draft'=>'secondary','Overdue'=>'danger','Cancelled'=>'secondary'];
$statusColor=$statusMap[$r['invoice_status']]??'secondary';
$issuedAt=date('d M Y, H:i',strtotime($r['created_at']?:$r['issue_date']));
$route=trim(($r['origin_branch']??'').' → '.($r['dest_branch']?:$r['origin_branch']));
$banks=$pdo->query('SELECT id,name,bank_name,account_number FROM bank_accounts WHERE active=1 ORDER BY name')->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Invoice <?=e($r['invoice_no'])?> — <?=e($company)?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"><style>
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
.status-success{background:#e3f6e9;color:#16794a}.status-danger{background:#fde8e8;color:#b42318}.status-warning{background:#fff3cd;color:#925e04}.status-secondary{background:#eef1f4;color:#5d6b77}.status-primary{background:#e8f2ff;color:#0b58a2}
.party{background:var(--soft);border:1px solid var(--line);border-radius:12px;padding:14px 16px}
.party .label,.field-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.6px;color:var(--muted)}
.table{border:1px solid var(--line);border-radius:12px;overflow:hidden}
.table thead th{background:linear-gradient(90deg,var(--brand-dark),var(--brand));color:#fff;border:0;padding:12px 14px;font-size:.8rem;text-transform:uppercase;letter-spacing:.3px}
.table th,.table td{padding:12px 14px;font-size:.92rem}
.table tbody tr:nth-child(even){background:var(--soft)}
.amount{font-variant-numeric:tabular-nums;white-space:nowrap}
.section-title{display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:var(--brand-dark);border-bottom:2px solid var(--line);padding-bottom:8px}
.summary{border:1px solid var(--line);border-radius:12px;background:var(--soft)}
.summary>div{padding:14px 20px}
.summary .amt{font-size:1.05rem;font-weight:700}
.summary .paid{color:#16794a}
.notes{background:#fbfcff;border:1px solid var(--line);border-left:4px solid var(--brand);border-radius:10px;padding:14px 18px}
.bank-line{font-size:.9rem;color:#33475f;line-height:1.7}
.bank-line strong{color:var(--brand);letter-spacing:.3px;font-weight:700}
@page{size:A4;margin:12mm}
@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}body{background:#fff}.tools{display:none}.sheet{margin:0;max-width:none;box-shadow:none;border-radius:0;border-top:none;padding:14px}}
</style></head><body>
<div class="tools d-print-none">
  <a class="btn btn-outline-secondary" href="shipment_invoices.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
  <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print / Save PDF</button>
</div>
<div class="sheet">
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div class="flex-grow-1">
      <?php if($logo):?><img class="logo mb-3" src="<?=url($logo)?>" alt="<?=e($company)?> logo"><?php else:?><div class="mark mb-3"><?=e(strtoupper(substr($company,0,2)))?></div><?php endif?>
      <h1 class="h3 fw-bold text-primary mb-1"><?=e($company)?></h1>
      <div class="text-secondary"><?=e($tagline)?></div>
    </div>
    <div class="inv-head text-end" style="min-width:300px">
      <small class="d-block">Shipment invoice</small>
      <div class="fs-5 fw-bold mb-2">#<?=e($r['invoice_no'])?></div>
      <div class="small d-flex flex-column align-items-end gap-1">
        <span><i class="bi bi-calendar3 me-1"></i>Issued: <?=e($issuedAt)?></span>
      </div>
      <span class="status status-<?=e($statusColor)?>"><?=e($r['invoice_status'])?></span>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-md-6">
      <small class="field-label d-block">Billed to</small>
      <div class="fw-bold fs-5 mb-1"><?=e($r['sender'])?></div>
      <div class="text-secondary small"><i class="bi bi-telephone me-1"></i><?=e($r['tell']?:'—')?></div>
      <div class="text-secondary small"><i class="bi bi-person me-1"></i>Receiver: <?=e($r['receiver'])?></div>
    </div>
    <div class="col-md-6 text-md-end">
      <?php if($banks):?>
      <small class="field-label d-block">Bank accounts</small>
      <?php foreach($banks as $b):?>
      <div class="bank-line"><i class="bi bi-bank me-1"></i><?=e($b['name'])?> · <strong><?=e($b['account_number'])?></strong></div>
      <?php endforeach?>
      <?php endif?>
      <small class="field-label d-block mt-2">Payment status</small>
      <div class="mb-2"><span class="status status-<?=e($statusColor)?>"><?=e($r['invoice_status'])?></span></div>
      <div class="text-secondary small mt-1"><i class="bi bi-box-seam me-1"></i><?=e($route)?></div>
    </div>
  </div>

  <div class="section-title mb-3"><i class="bi bi-list-check"></i>Service details</div>
  <table class="table">
    <thead><tr><th>Description</th><th class="text-end">Weight (kg)</th><th class="text-end">Unit price</th><th>Extra</th><th class="text-end">Subtotal</th></tr></thead>
    <tbody>
      <tr>
        <td class="fw-medium"><?=e($r['description'])?></td>
        <td class="text-end amount"><?=number_format((float)$r['kg'],2)?></td>
        <td class="text-end amount"><?=money($r['rate'])?></td>
        <td><?=$extra>0?money($extra):'—'?></td>
        <td class="text-end amount fw-semibold"><?=money((float)$r['subtotal']+(float)$extra)?></td>
      </tr>
      <?php if($discount>0):?>
      <tr>
        <td>Discount</td><td>—</td><td class="text-end">—</td><td class="text-end">—</td>
        <td class="text-end amount text-danger fw-semibold">− <?=money($discount)?></td>
      </tr>
      <?php endif?>
    </tbody>
  </table>

  <div class="summary d-flex justify-content-between align-items-stretch mt-4">
    <div><small class="text-muted d-block">Total amount</small><div class="amt"><?=money($invoiceTotal)?></div></div>
    <div class="border-start"><small class="text-muted d-block">Amount paid</small><div class="amt paid"><i class="bi bi-check-circle me-1"></i><?=money($paid)?></div></div>
    <div class="border-start rounded-3 <?=$balance>0?'bg-danger-subtle':'bg-success-subtle'?>"><small class="text-muted d-block">Balance due</small><div class="amt <?=$balance>0?'text-danger':'text-success'?>"><?=money($balance)?></div></div>
  </div>

  <div class="notes mt-4"><div class="section-title mb-2"><i class="bi bi-journal-text"></i>Notes</div><p class="mb-0"><?=nl2br(e($r['notes']?:'No additional notes.'))?></p></div>

  <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top text-secondary small flex-wrap gap-2">
    <span>
      <?php if($phone):?><span class="me-3"><i class="bi bi-telephone me-1"></i><?=e($phone)?></span><?php endif?>
      <?php if($address):?><span class="me-3"><i class="bi bi-geo-alt me-1"></i><?=e($address)?></span><?php endif?>
    </span>
    <span class="text-nowrap"><i class="bi bi-check-circle me-1"></i>Thank you for choosing <?=e($company)?>. · <?=e($r['invoice_no'])?></span>
  </div>
</div>
</body></html>
