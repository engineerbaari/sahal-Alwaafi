<?php
require __DIR__.'/bootstrap.php';
require_perm('branches.manage');
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $id=(int)($_POST['id']??0);
    if(isset($_POST['create'])){
        try{$pdo->prepare('INSERT INTO airlines(name,code) VALUES(?,?)')->execute([trim($_POST['name']),strtoupper(trim($_POST['code']))]);flash('success','Airline added.');}
        catch(PDOException $e){flash('danger','The airline code must be unique.');}
    }
    if(isset($_POST['update'])){
        try{$pdo->prepare('UPDATE airlines SET name=?,code=?,active=? WHERE id=?')->execute([trim($_POST['name']),strtoupper(trim($_POST['code'])),isset($_POST['active'])?1:0,$id]);flash('success','Airline updated.');}
        catch(PDOException $e){flash('danger','The airline code must be unique.');}
    }
    if(isset($_POST['toggle'])){$pdo->prepare('UPDATE airlines SET active=1-active WHERE id=?')->execute([$id]);flash('success','Airline status updated.');}
    if(isset($_POST['delete'])){
        try{$pdo->prepare('DELETE FROM airlines WHERE id=?')->execute([$id]);flash('success','Airline deleted.');}
        catch(PDOException $e){flash('danger','This airline is used by shipments and cannot be deleted. Disable it instead.');}
    }
    redirect('airlines.php');
}
$rows=$pdo->query('SELECT a.*,COUNT(s.id) shipments_count FROM airlines a LEFT JOIN shipments s ON s.airline_id=a.id GROUP BY a.id ORDER BY a.name')->fetchAll();
$title='Airlines';require __DIR__.'/partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 fw-bold mb-1">Airlines</h1><p class="text-secondary mb-0">Manage the flight carriers used on shipments.</p></div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#airline">Add airline</button></div>
<div class="card p-3 p-lg-4"><div class="table-responsive"><table class="table"><thead><tr><th>Airline</th><th>Code</th><th>Shipments</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td class="fw-semibold"><?=e($r['name'])?></td><td><span class="badge text-bg-light border"><?=e($r['code'])?></span></td><td><?=e($r['shipments_count'])?></td><td><span class="badge text-bg-<?=$r['active']?'success':'secondary'?>"><?=$r['active']?'Active':'Disabled'?></span></td><td class="text-nowrap"><button class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#edit<?=$r['id']?>"><i class="bi bi-pencil me-1"></i>Edit</button><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-secondary me-1" name="toggle"><?=$r['active']?'Disable':'Enable'?></button></form><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-danger" name="delete" data-confirm="Delete this airline? It cannot be deleted while shipments use it — disable it instead."><i class="bi bi-trash me-1"></i>Delete</button></form></td></tr><?php endforeach?></tbody></table></div></div>
<div class="modal fade" id="airline"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h2 class="h5">Add airline</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><input type="hidden" name="csrf" value="<?=csrf()?>"><label class="form-label">Airline name</label><input class="form-control mb-3" name="name" required><label class="form-label">Code</label><input class="form-control" name="code" maxlength="10" required></div><div class="modal-footer"><button class="btn btn-primary" name="create">Add airline</button></div></form></div></div>
<?php foreach($rows as $r):?>
<div class="modal fade" id="edit<?=$r['id']?>">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h2 class="h5 mb-0">Edit <?=e($r['name'])?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf" value="<?=csrf()?>">
                <input type="hidden" name="id" value="<?=$r['id']?>">
                <label class="form-label">Airline name</label>
                <input class="form-control mb-3" name="name" value="<?=e($r['name'])?>" required>
                <label class="form-label">Code</label>
                <input class="form-control" name="code" maxlength="10" value="<?=e($r['code'])?>" required>
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" name="active" id="active<?=$r['id']?>" <?=$r['active']?'checked':''?>>
                    <label class="form-check-label" for="active<?=$r['id']?>">Airline active</label>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary" name="update"><i class="bi bi-check2 me-1"></i> Save changes</button></div>
        </form>
    </div>
</div>
<?php endforeach?>
<?php require __DIR__.'/partials/footer.php'; ?>