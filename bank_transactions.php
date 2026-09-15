<?php
require __DIR__.'/bootstrap.php';require_perm('bank.manage');$branch=is_admin()?null:require_branch();
$from=preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['from']??'')?$_GET['from']:'';$to=preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['to']??'')?$_GET['to']:'';
$accountFilter=(int)($_GET['account_id']??0);
$conditions=[];$params=[];
if($branch!==null)$conditions[]='b.branch_id='.(int)$branch;
if($accountFilter>0)$conditions[]='t.bank_account_id='.$accountFilter;
if($to!==''){$conditions[]='t.transaction_date <= ?';$params[]=$to.' 23:59:59';}
$where=$conditions?' WHERE '.implode(' AND ',$conditions):'';
// Load entries before the selected period too, so each displayed balance includes prior activity.
$sql="SELECT t.*,b.name account_name,b.bank_name,b.account_number,b.opening_balance,\n       p.reference_no payment_ref,p.id payment_id,\n       g.invoice_no general_inv_no,\n       s.awb ship_awb,(SELECT si.invoice_no FROM shipment_invoices si WHERE si.shipment_id=p.shipment_id ORDER BY si.id DESC LIMIT 1) ship_inv_no,\n       ie.payer income_payer,ie.description income_desc,\n       ex.description expense_desc\nFROM bank_transactions t\nJOIN bank_accounts b ON b.id=t.bank_account_id\nLEFT JOIN payments p ON p.id=IF(t.source_table='payments',t.source_id,NULL)\nLEFT JOIN general_invoices g ON g.id=p.general_invoice_id\nLEFT JOIN shipments s ON s.id=p.shipment_id\nLEFT JOIN income_entries ie ON ie.id=IF(t.source_table='income_entries',t.source_id,NULL)\nLEFT JOIN expenses ex ON ex.id=IF(t.source_table='expenses',t.source_id,NULL)".$where.' ORDER BY t.bank_account_id,t.transaction_date,t.id';
$query=$pdo->prepare($sql);$query->execute($params);$ledger=$query->fetchAll();
$balances=[];$rows=[];foreach($ledger as $row){
    $accountId=(int)$row['bank_account_id'];
    if(!isset($balances[$accountId]))$balances[$accountId]=(float)$row['opening_balance'];
    $balances[$accountId]+=(float)$row['amount_in']-(float)$row['amount_out'];
    if($from===''||$row['transaction_date']>=$from.' 00:00:00'){
        $type=(string)$row['transaction_type'];
        if($type==='Payment'){$ref=$row['ship_inv_no']?:$row['general_inv_no']?:($row['ship_awb']?'AWB '.$row['ship_awb']:('Payment #'.$row['source_id']));}
        elseif($type==='Income'){$ref=$row['income_payer']?:$row['income_desc']?:('Income #'.$row['source_id']);}
        else{$ref=$row['expense_desc']?:('Expense #'.$row['source_id']);}
        $row['reference']=$ref;$row['running_balance']=$balances[$accountId];$rows[]=$row;
    }
}
usort($rows,fn($a,$b)=>[$b['transaction_date'],$b['id']]<=>[$a['transaction_date'],$a['id']]);
$in=array_sum(array_column($rows,'amount_in'));$out=array_sum(array_column($rows,'amount_out'));
// Include accounts without transactions and respect the selected \"to\" date and account filter.
$accountSql='SELECT id,opening_balance FROM bank_accounts'.($branch===null?'':' WHERE branch_id='.(int)$branch);
$accountRows=$pdo->query($accountSql)->fetchAll();$balance=0.0;
foreach($accountRows as $account){$accountId=(int)$account['id'];if($accountFilter>0&&$accountId!==$accountFilter)continue;$balance+=isset($balances[$accountId])?$balances[$accountId]:(float)$account['opening_balance'];}
$bankAccounts=$pdo->query('SELECT id,name,bank_name,account_number FROM bank_accounts'.($branch===null?'':' WHERE branch_id='.(int)$branch).' ORDER BY name')->fetchAll();
$title='Bank transactions';require __DIR__.'/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h1 class="h3 fw-bold mb-1">Bank Statement</h1><p class="text-secondary mb-0">Money deposited into or paid from your bank accounts, with a running balance in USD.</p></div><div class="d-flex gap-2 no-print"><a class="btn btn-outline-danger" href="bank_transactions_pdf.php?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>&account_id=<?=$accountFilter?>"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a><a class="btn btn-outline-primary" href="bank_accounts.php"><i class="bi bi-bank"></i> Bank Accounts</a></div></div>
<div class="row g-3 mb-4"><div class="col-6 col-md-3"><div class="card p-3"><small class="text-secondary">Money in</small><strong class="h5 text-success mb-0"><?=money($in)?></strong></div></div><div class="col-6 col-md-3"><div class="card p-3"><small class="text-secondary">Money out</small><strong class="h5 text-danger mb-0"><?=money($out)?></strong></div></div><div class="col-6 col-md-3"><div class="card p-3 border-start border-primary border-4"><small class="text-secondary"><?= $to!==''?'Balance up to '.$to:'Current balance' ?></small><strong class="h5 <?=($balance<0?'text-danger':'text-primary')?> mb-0"><?=money($balance)?></strong></div></div></div>
<div class="card p-3 p-lg-4"><form method="get" class="row g-2 align-items-end mb-3 no-print"><div class="col-sm-4 col-lg-3"><label class="form-label">From date</label><input class="form-control" type="date" name="from" value="<?=e($from)?>"></div><div class="col-sm-4 col-lg-3"><label class="form-label">To date</label><input class="form-control" type="date" name="to" value="<?=e($to)?>"></div><div class="col-sm-4 col-lg-3"><label class="form-label">Bank account</label><select class="form-select" name="account_id" onchange="this.form.submit()"><option value="0">All accounts</option><?php foreach($bankAccounts as $b):?><option value="<?=$b['id']?>" <?=$accountFilter==$b['id']?'selected':''?>><?=e($b['name'].' · '.$b['bank_name'])?></option><?php endforeach?></select></div><div class="col-sm-6 col-lg-3 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button><a class="btn btn-outline-secondary" href="bank_transactions.php">Clear</a></div></form><input class="form-control mb-3 no-print" style="max-width:430px" data-table-search placeholder="Search account or transaction"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Date</th><th>Account</th><th>Transaction</th><th>Reference</th><th class="text-end">Money In</th><th class="text-end">Money Out</th><th class="text-end">Balance</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=app_date($r['transaction_date'])?></td><td class="fw-semibold"><?=e($r['account_name'])?><small class="d-block text-secondary"><?=e($r['bank_name'].' · '.$r['account_number'])?></small></td><td><?=e($r['transaction_type'])?></td><td><?=e($r['reference'])?></td><td class="text-end text-success fw-bold"><?=money($r['amount_in'])?></td><td class="text-end text-danger fw-bold"><?=money($r['amount_out'])?></td><td class="text-end fw-bold <?=($r['running_balance']<0?'text-danger':'text-primary')?>"><?=money($r['running_balance'])?></td></tr><?php endforeach?><?php if(!$rows):?><tr><td colspan="7" class="text-center text-secondary py-5">No bank transactions found for this date range.</td></tr><?php endif?></tbody></table></div></div>
<style>@media print{.no-print,#appSidebar,#sidebarOverlay,footer{display:none!important}main{margin:0!important;padding:0!important}.card{border:0!important;box-shadow:none!important}}</style>
<?php require __DIR__.'/partials/footer.php';?>
