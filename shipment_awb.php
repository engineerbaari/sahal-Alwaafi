<?php
require __DIR__.'/bootstrap.php'; require_login();
$me=user();
require_perm('shipment_awb.view');
$canManage=can('shipment_awb.manage');
$b=(int)require_branch();

function next_awb_no(): string {
    global $pdo;
    $year=date('Y');
    $n=1+(int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(awb_no,-5) AS UNSIGNED)),0) FROM shipment_awbs WHERE awb_no LIKE 'AWB-$year-%'")->fetchColumn();
    return 'AWB-'.$year.'-'.str_pad((string)$n,5,'0',STR_PAD_LEFT);
}

/* Shipments forwarded from the register via ?add=1,2,3 (bulk "Group selected"). */
$preselect=[];
if(($_GET['add']??'')!==''){
    foreach(explode(',',(string)$_GET['add']) as $x){$n=(int)$x;if($n>0)$preselect[$n]=$n;}
    if($preselect){
        $ph=implode(',',array_map(fn($x)=>'?',$preselect));
        $q='SELECT id FROM shipments WHERE id IN ('.$ph.')'.(is_admin()?'':' AND (branch_id='.$b.' OR to_branch_id='.$b.')');
        $s=$pdo->prepare($q);$s->execute(array_keys($preselect));
        $ok=[];foreach($s->fetchAll() as $r)$ok[(int)$r['id']]=(int)$r['id'];
        $preselect=$ok;
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf(); require_perm('shipment_awb.manage');

    /* Save a group's metadata (create or update). */
    if(isset($_POST['save_group'])){
        $id=(int)($_POST['id']??0);
        $name=trim($_POST['name']??'');
        $awbNoRaw=strtoupper(trim($_POST['awb_no']??''));
        $airline=(int)($_POST['airline_id']??0)?:null;
        $notes=trim($_POST['notes']??'');
        $branch=is_admin()?((int)($_POST['branch_id']??0)?:require_branch()):require_branch();
        if(!$id&&$name===''&&$awbNoRaw===''){flash('danger','Enter an AWB number or a group name.');redirect('shipment_awb.php');}
        try{
            if($id){
                $st=$pdo->prepare('SELECT awb_no FROM shipment_awbs WHERE id=?'.(is_admin()?'':' AND branch_id='.require_branch()));$st->execute([$id]);$cur=(string)$st->fetchColumn();
                if($cur===''){flash('danger','AWB group not found.');redirect('shipment_awb.php');}
                $awbNo=$awbNoRaw!==''?$awbNoRaw:$cur;
                $pdo->prepare('UPDATE shipment_awbs SET awb_no=?,name=?,airline_id=?,notes=?,branch_id=? WHERE id=?')->execute([$awbNo,$name,$airline,$notes,$branch,$id]);
            }else{
                $awbNo=$awbNoRaw!==''?$awbNoRaw:next_awb_no();
                $pdo->prepare('INSERT INTO shipment_awbs(awb_no,name,airline_id,notes,branch_id,created_by) VALUES(?,?,?,?,?,?)')->execute([$awbNo,$name,$airline,$notes,$branch,$me['id']]);
                $id=(int)$pdo->lastInsertId();
            }
            flash('success','AWB group '.$awbNo.' saved.');
        }catch(Throwable $e){flash('danger','Could not save the group — the AWB number may already exist.');redirect('shipment_awb.php');}
        redirect('shipment_awb.php?g='.$id);
    }
/* Assign shipment ids to an existing group or to a brand-new one. */
    if(isset($_POST['assign'])){
        $aid=(int)($_POST['awb_id']??0);
        $idsRaw=$_POST['ids']??[];if(!is_array($idsRaw))$idsRaw=is_string($idsRaw)&&$idsRaw!==''?[$idsRaw]:[];
        $ids=[];foreach($idsRaw as $x){$n=(int)$x;if($n>0)$ids[$n]=$n;}
        $redirectBack='shipment_awb.php';
        if($aid>0){
            $chk=$pdo->prepare('SELECT id,awb_no FROM shipment_awbs WHERE id=?'.(is_admin()?'':' AND branch_id='.require_branch()));$chk->execute([$aid]);$grow=$chk->fetch();
            if(!$grow){flash('danger','AWB group not found.');redirect('shipment_awb.php');}
        }else{
            $name=trim($_POST['name']??'');$awbNoRaw=strtoupper(trim($_POST['awb_no']??''));
            if($name===''&&$awbNoRaw===''){flash('danger','Choose an existing group or enter an AWB number/name for a new one.');if($ids)$redirectBack='shipment_awb.php?add='.implode(',',array_keys($ids));redirect($redirectBack);}
            $awbNo=$awbNoRaw!==''?$awbNoRaw:next_awb_no();
            try{
                $airline=(int)($_POST['airline_id']??0)?:null;
                $pdo->prepare('INSERT INTO shipment_awbs(awb_no,name,airline_id,notes,branch_id,created_by) VALUES(?,?,?,?,?,?)')->execute([$awbNo,$name,$airline,trim($_POST['notes']??''),is_admin()?((int)($_POST['branch_id']??0)?:require_branch()):require_branch(),$me['id']]);
                $aid=(int)$pdo->lastInsertId();$grow=['id'=>$aid,'awb_no'=>$awbNo];
            }catch(Throwable $e){flash('danger','Could not create the group — the AWB number may already exist.');if($ids)$redirectBack='shipment_awb.php?add='.implode(',',array_keys($ids));redirect($redirectBack);}
        }
        if($ids){
            $pdo->beginTransaction();
            try{
                $ph=implode(',',array_map(fn($x)=>'?',$ids));
                $pdo->prepare("DELETE FROM shipment_awb_members WHERE shipment_id IN ($ph)")->execute(array_values($ids));
                $ins=$pdo->prepare('INSERT INTO shipment_awb_members(awb_id,shipment_id,added_by) VALUES(?,?,?)');
                foreach($ids as $sid)$ins->execute([$aid,$sid,$me['id']]);
                $pdo->commit();
                flash('success',count($ids).' shipment(s) added to '.$grow['awb_no'].'.');
            }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('danger','Some shipments could not be grouped.');}
        }
        redirect('shipment_awb.php?g='.$aid);
    }

    /* Remove one shipment from a group. */
    if(isset($_POST['remove_shipment'])){
        $aid=(int)($_POST['awb_id']??0);$sid=(int)($_POST['shipment_id']??0);
        $pdo->prepare('DELETE gm FROM shipment_awb_members gm JOIN shipment_awbs ga ON ga.id=gm.awb_id WHERE gm.awb_id=? AND gm.shipment_id=?'.(is_admin()?'':' AND ga.branch_id='.require_branch()))->execute([$aid,$sid]);
        flash('success','Shipment removed from the group.');
        redirect('shipment_awb.php?g='.$aid);
    }

    /* Delete a group (memberships cascade). */
    if(isset($_POST['delete_group'])){
        $id=(int)($_POST['id']??0);
        $pdo->prepare('DELETE FROM shipment_awbs WHERE id=?'.(is_admin()?'':' AND branch_id='.require_branch()))->execute([$id]);
        flash('success','AWB group deleted. Shipments stay in the register.');
        redirect('shipment_awb.php');
    }
    redirect('shipment_awb.php');
}

$airlines=$pdo->query('SELECT id,name,code FROM airlines WHERE active=1 ORDER BY name')->fetchAll();
$branches=is_admin()?$pdo->query('SELECT id,name,code FROM branches WHERE active=1 ORDER BY name')->fetchAll():[];
$groups=$pdo->query("SELECT ga.*,a.name airline,a.code airline_code,b.name branch_name,u.name created_by_name,COUNT(gm.id) shipment_count,COALESCE(SUM(s.pcs),0) total_pcs,COALESCE(SUM(s.kg),0) total_kg,COALESCE(SUM(s.total),0) total_amount,COALESCE(SUM(s.balance_mgq),0) total_balance FROM shipment_awbs ga LEFT JOIN shipment_awb_members gm ON gm.awb_id=ga.id LEFT JOIN shipments s ON s.id=gm.shipment_id LEFT JOIN airlines a ON a.id=ga.airline_id JOIN branches b ON b.id=ga.branch_id LEFT JOIN users u ON u.id=ga.created_by WHERE 1=1".(is_admin()?'':' AND ga.branch_id='.$b)." GROUP BY ga.id ORDER BY ga.created_at DESC,ga.id DESC")->fetchAll();
$group=null;
if(isset($_GET['g'])&&(int)$_GET['g']>0){
    $st=$pdo->prepare('SELECT ga.*,a.name airline,a.code airline_code,b.name branch_name,u.name created_by_name FROM shipment_awbs ga LEFT JOIN airlines a ON a.id=ga.airline_id JOIN branches b ON b.id=ga.branch_id LEFT JOIN users u ON u.id=ga.created_by WHERE ga.id=?'.(is_admin()?'':' AND ga.branch_id='.$b));$st->execute([(int)$_GET['g']]);$group=$st->fetch();
}
$members=[];$candidates=[];
if($group){
    $m=$pdo->prepare('SELECT s.*,a.code airline_code,a.name airline FROM shipment_awb_members gm JOIN shipments s ON s.id=gm.shipment_id JOIN airlines a ON a.id=s.airline_id WHERE gm.awb_id=? ORDER BY gm.created_at DESC,s.id DESC');$m->execute([$group['id']]);$members=$m->fetchAll();
    $candidates=$pdo->query('SELECT s.id,s.awb,s.shipment_date,s.mark,s.pcs,s.kg,s.total,s.balance_mgq,s.sender,s.receiver,s.status,ga.awb_no existing_group FROM shipments s JOIN airlines a ON a.id=s.airline_id LEFT JOIN shipment_awb_members gm ON gm.shipment_id=s.id LEFT JOIN shipment_awbs ga ON ga.id=gm.awb_id WHERE (gm.id IS NULL OR gm.awb_id<>'.(int)$group['id'].')'.(is_admin()?'':' AND (s.branch_id='.$b.' OR s.to_branch_id='.$b.')').' ORDER BY s.shipment_date DESC,s.id DESC LIMIT 100')->fetchAll();
}
$title='Shipment AWB';require __DIR__.'/partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><p class="text-primary text-uppercase small fw-bold mb-1">Shipment grouping</p><h1 class="h3 fw-bold mb-1">Shipment AWB</h1><p class="text-secondary mb-0">Group multiple shipments under one master AWB number.</p></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary text-nowrap" href="cargo.php"><i class="bi bi-box-seam"></i> Shipments</a><?php if($canManage):?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#groupModal"><i class="bi bi-plus-lg"></i> New AWB group</button><?php endif?></div></div>
<?php if($preselect):?>
<div class="card border-primary mb-4"><div class="card-body"><div class="d-flex flex-wrap align-items-center gap-2 mb-3"><span class="badge text-bg-primary rounded-pill"><i class="bi bi-collection me-1"></i><?=count($preselect)?></span><strong>shipment(s) selected for AWB grouping</strong><a class="btn btn-sm btn-outline-secondary ms-auto" href="shipment_awb.php">Clear selection</a></div><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><?php foreach($preselect as $sid):?><input type="hidden" name="ids[]" value="<?=$sid?>"><?php endforeach?><div class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label">Add to existing group</label><select class="form-select" name="awb_id"><option value="">— choose a group —</option><?php foreach($groups as $g):?><option value="<?=$g['id']?>"><?=e($g['awb_no'].($g['name']?' · '.$g['name']:''))?></option><?php endforeach?></select></div><div class="col-md-4"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" id="newGroupToggle"><label class="form-check-label" for="newGroupToggle">Or create a new group below</label></div></div><div class="col-md-4 text-md-end"><button class="btn btn-primary w-100" name="assign"><i class="bi bi-collection me-1"></i> Add to AWB group</button></div></div><div id="newGroupRow" class="row g-2 mt-2 d-none"><div class="col-md-3"><label class="form-label">AWB no (auto-suggested)</label><input class="form-control" name="awb_no" value="<?=e(next_awb_no())?>"></div><div class="col-md-3"><label class="form-label">Group name</label><input class="form-control" name="name"></div><div class="col-md-3"><label class="form-label">Airline</label><select class="form-select" name="airline_id"><option value="">— none —</option><?php foreach($airlines as $a):?><option value="<?=$a['id']?>"><?=e($a['name'].' ('.$a['code'].')')?></option><?php endforeach?></select></div><div class="col-md-3"><label class="form-label">Notes</label><input class="form-control" name="notes"></div></div></form></div></div>
<?php endif?>
<?php if($group): $pcs=0;$kg=0;$amt=0;$bal=0;foreach($members as $mm){$pcs+=(int)$mm['pcs'];$kg+=(float)$mm['kg'];$amt+=(float)$mm['total'];$bal+=(float)$mm['balance_mgq'];}?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><a class="btn btn-sm btn-outline-secondary mb-2" href="shipment_awb.php"><i class="bi bi-arrow-left"></i> All AWB groups</a><h1 class="h3 fw-bold mb-1"><?=e($group['awb_no'])?> <span class="badge text-bg-info border"><?=count($members)?> shipment<?=count($members)===1?'':'s'?></span></h1><p class="text-secondary mb-0"><?=e($group['name']?:'Master AWB group')?></p></div><div class="d-flex gap-2"><?php if($canManage):?><button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#groupModal"><i class="bi bi-pencil"></i> Edit</button><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$group['id']?>"><button class="btn btn-outline-danger" name="delete_group" data-confirm="Delete this AWB group and its grouping? Shipments stay in the register."><i class="bi bi-trash"></i> Delete</button></form><?php endif?></div></div>
<div class="row g-4"><div class="col-lg-8"><div class="card p-3 p-lg-4 mb-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 mb-0">Grouped shipments</h2><span class="text-secondary small"><?=count($members)?> of <?=count($candidates)+count($members)?> shipments grouped</span></div><div class="table-responsive"><table class="table table-hover text-nowrap align-middle"><thead><tr><th>Date / AWB</th><th>Mark</th><th>PCS / KG</th><th>Rate / Total</th><th>Balance MGQ</th><th>Sender / Receiver</th><th>Status</th><?php if($canManage):?><th></th><?php endif?></tr></thead><tbody><?php foreach($members as $r):?><tr><td><?=app_date($r['shipment_date'])?><strong class="d-block"><?=e($r['awb'])?></strong></td><td><?=e($r['mark']?:'—')?></td><td><?=e($r['pcs'])?> pcs<strong class="d-block"><?=e($r['kg'])?> kg</strong></td><td><?=money($r['rate'])?><strong class="d-block"><?=money($r['total'])?></strong></td><td class="fw-bold <?=$r['balance_mgq']>0?'text-danger':'text-success'?>"><?=money($r['balance_mgq'])?></td><td><?=e($r['sender'])?><small class="d-block text-secondary">To: <?=e($r['receiver'])?></small></td><td><?=e($r['status'])?></td><?php if($canManage):?><td><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="awb_id" value="<?=$group['id']?>"><input type="hidden" name="shipment_id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-danger" name="remove_shipment" data-confirm="Remove this shipment from the group?" title="Remove from group"><i class="bi bi-x-lg"></i></button></form></td><?php endif?></tr><?php endforeach?><?php if(!$members):?><tr><td colspan="<?=$canManage?8:7?>" class="text-center text-secondary py-4">No shipments in this group yet.</td></tr><?php endif?></tbody></table></div></div>
<?php if($canManage):?>
<div class="card p-3 p-lg-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 mb-0">Add shipments</h2><input id="candSearch" class="form-control" style="max-width:260px" placeholder="Search AWB / sender…"></div><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="awb_id" value="<?=$group['id']?>"><div class="table-responsive"><table class="table table-hover text-nowrap align-middle" id="candTable"><thead><tr><th style="width:40px"></th><th>Date / AWB</th><th>PCS / KG</th><th>Total</th><th>Sender / Receiver</th><th>Status</th></tr></thead><tbody><?php foreach($candidates as $r):?><tr><td><input class="form-check-input cand-check" type="checkbox" name="ids[]" value="<?=$r['id']?>" <?=isset($preselect[$r['id']])?'checked':''?>></td><td><?=app_date($r['shipment_date'])?><strong class="d-block"><?=e($r['awb'])?></strong><?php if($r['existing_group']!==null&&$r['existing_group']!==''):?><span class="badge text-bg-warning">in <?=e($r['existing_group'])?> · moving</span><?php endif?></td><td><?=e($r['pcs'])?> pcs<strong class="d-block"><?=e($r['kg'])?> kg</strong></td><td><?=money($r['total'])?></td><td><?=e($r['sender'])?><small class="d-block text-secondary">To: <?=e($r['receiver'])?></small></td><td><?=e($r['status'])?></td></tr><?php endforeach?><?php if(!$candidates):?><tr><td colspan="6" class="text-center text-secondary py-4">No more shipments available to group.</td></tr><?php endif?></tbody></table></div><div class="d-flex align-items-center gap-2 mt-3"><button class="btn btn-primary" name="assign"><i class="bi bi-collection me-1"></i> Add selected to <?=e($group['awb_no'])?></button><span class="small text-secondary">Shipments already in another group will be moved here.</span></div></form></div>
<?php endif?>
</div><div class="col-lg-4"><div class="card p-3 p-lg-4 mb-4"><h2 class="h5 mb-3">Summary</h2><div class="row g-3"><div class="col-6"><small class="text-secondary">Shipments</small><div class="fw-bold fs-5"><?=count($members)?></div></div><div class="col-6"><small class="text-secondary">Pieces</small><div class="fw-bold fs-5"><?=$pcs?></div></div><div class="col-6"><small class="text-secondary">Weight</small><div class="fw-bold fs-5"><?=number_format($kg,2)?> kg</div></div><div class="col-6"><small class="text-secondary">Airline</small><div class="fw-semibold"><?=e($group['airline']?:'—')?></div></div><div class="col-6"><small class="text-secondary">Branch</small><div class="fw-semibold"><?=e($group['branch_name'])?></div></div><div class="col-6"><small class="text-secondary">Created</small><div class="fw-semibold"><?=app_date($group['created_at'])?></div></div></div><hr class="my-3"><div class="d-flex justify-content-between fw-bold"><span>Total value</span><span><?=money($amt)?></span></div><div class="d-flex justify-content-between fw-bold mt-1"><span>Balance MGQ</span><span class="<?=$bal>0?'text-danger':'text-success'?>"><?=money($bal)?></span></div><?php if($group['notes']!==''):?><hr class="my-3"><small class="text-secondary">Notes</small><p class="mb-0 mt-1"><?=e($group['notes'])?></p><?php endif?></div></div>
<?php else:?>
<div class="card p-3 p-lg-4"><div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><input class="form-control" style="max-width:320px" data-table-search placeholder="Search AWB group…"><span class="text-secondary small"><?=count($groups)?> group<?=count($groups)===1?'':'s'?></span></div><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>AWB no / Name</th><th>Airline</th><th>Branch</th><th>Shipments</th><th>PCS / KG</th><th>Total value</th><th>Balance MGQ</th><th>Created</th><th></th></tr></thead><tbody><?php foreach($groups as $g):?><tr><td><a class="fw-bold text-primary" href="shipment_awb.php?g=<?=$g['id']?>"><?=e($g['awb_no'])?></a><small class="d-block text-secondary"><?=e($g['name']?:'—')?></small></td><td><?=e($g['airline']?:'—')?></td><td><?=e($g['branch_name'])?></td><td><span class="badge text-bg-light border"><?=$g['shipment_count']?></span></td><td><?=$g['total_pcs']?> pcs<strong class="d-block"><?=number_format((float)$g['total_kg'],2)?> kg</strong></td><td class="fw-bold"><?=money($g['total_amount'])?></td><td class="fw-bold <?=$g['total_balance']>0?'text-danger':'text-success'?>"><?=money($g['total_balance'])?></td><td><?=app_date($g['created_at'])?></td><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="shipment_awb.php?g=<?=$g['id']?>"><i class="bi bi-eye"></i> View</a><?php if($canManage):?><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$g['id']?>"><button class="btn btn-sm btn-outline-danger" name="delete_group" data-confirm="Delete this AWB group and its grouping? Shipments stay in the register."><i class="bi bi-trash"></i></button></form><?php endif?></td></tr><?php endforeach?></tbody></table></div><?php if(!$groups):?><div class="text-center text-secondary py-5"><i class="bi bi-collection display-6 d-block mb-2"></i>No AWB groups yet — create one to start grouping shipments.</div><?php endif?></div>
<?php endif?>
<?php if($canManage):$v=$group?:['id'=>'','awb_no'=>next_awb_no(),'name'=>'','airline_id'=>'','notes'=>'','branch_id'=>$b];?>
<div class="modal fade" id="groupModal"><div class="modal-dialog modal-lg"><form method="post" class="modal-content"><div class="modal-header"><h2 class="h5 mb-0"><?=$group?'Edit':'New'?> AWB group</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$v['id']?>"><div class="row g-3"><div class="col-md-6"><label class="form-label">AWB no</label><input class="form-control" name="awb_no" value="<?=e($v['awb_no'])?>" placeholder="AWB-<?=date('Y')?>-…"></div><div class="col-md-6"><label class="form-label">Group name</label><input class="form-control" name="name" value="<?=e($v['name'])?>"></div><div class="col-md-6"><label class="form-label">Airline</label><select class="form-select" name="airline_id"><option value="">— none —</option><?php foreach($airlines as $a):?><option value="<?=$a['id']?>" <?=((string)$v['airline_id']===(string)$a['id'])?'selected':''?>><?=e($a['name'].' ('.$a['code'].')')?></option><?php endforeach?></select></div><div class="col-md-6"><label class="form-label">Notes</label><input class="form-control" name="notes" value="<?=e($v['notes'])?>"></div><?php if(is_admin()):?><div class="col-md-6"><label class="form-label">Branch</label><select class="form-select" name="branch_id"><?php foreach($branches as $b2):?><option value="<?=$b2['id']?>" <?=(int)$v['branch_id']===(int)$b2['id']?'selected':''?>><?=e($b2['name'].' ('.$b2['code'].')')?></option><?php endforeach?></select></div><?php endif?></div></div><div class="modal-footer"><button class="btn btn-primary" name="save_group"><i class="bi bi-check2 me-1"></i> Save group</button></div></form></div></div>
<?php endif?>
<script>window.addEventListener("DOMContentLoaded",()=>{const t=document.querySelector("#newGroupToggle"),row=document.querySelector("#newGroupRow");if(t&&row)t.addEventListener("change",()=>row.classList.toggle("d-none",!t.checked));const cs=document.querySelector("#candSearch");if(cs)cs.addEventListener("input",()=>{const q=cs.value.toLowerCase();document.querySelectorAll("#candTable tbody tr").forEach(r=>r.hidden=!r.innerText.toLowerCase().includes(q))})})</script>
<?php require __DIR__.'/partials/footer.php';?>