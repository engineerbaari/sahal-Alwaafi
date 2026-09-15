<?php
require __DIR__.'/bootstrap.php';
require_perm('branches.manage');
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $id=(int)($_POST['id']??0);
    if(isset($_POST['create'])){
        try{$pdo->prepare('INSERT INTO branches(name,code,phone,location) VALUES(?,?,?,?)')->execute([trim($_POST['name']),strtoupper(trim($_POST['code'])),trim($_POST['phone']),trim($_POST['address'])]);flash('success','Branch created.');}
        catch(PDOException $e){flash('danger','The branch code must be unique.');}
    }
    if(isset($_POST['update'])){
        try{$pdo->prepare('UPDATE branches SET name=?,code=?,phone=?,location=?,active=? WHERE id=?')->execute([trim($_POST['name']),strtoupper(trim($_POST['code'])),trim($_POST['phone']),trim($_POST['location']),isset($_POST['active'])?1:0,$id]);flash('success','Branch updated.');}
        catch(PDOException $e){flash('danger','The branch code must be unique.');}
    }
    if(isset($_POST['toggle'])){$pdo->prepare('UPDATE branches SET active=1-active WHERE id=?')->execute([$id]);flash('success','Branch status updated.');}
    if(isset($_POST['delete'])){
        if($id===(int)(user()['branch_id']??0)){flash('warning','You cannot delete the branch you are signed in to.');}
        else{try{$pdo->prepare('DELETE FROM branches WHERE id=?')->execute([$id]);flash('success','Branch deleted.');}
        catch(PDOException $e){flash('danger','This branch has linked users or records and cannot be deleted. Disable it instead.');}}
    }
    redirect('branches.php');
}
$rows=$pdo->query('SELECT b.*,COUNT(u.id) users_count FROM branches b LEFT JOIN users u ON u.branch_id=b.id GROUP BY b.id ORDER BY b.name')->fetchAll();
$title='Branches';require __DIR__.'/partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 fw-bold mb-1">Branches</h1><p class="text-secondary mb-0">Create and manage your company locations.</p></div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#branch">Add branch</button></div>
<div class="card p-3 p-lg-4"><div class="table-responsive"><table class="table"><thead><tr><th>Branch</th><th>Code</th><th>Contact</th><th>Users</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td class="fw-semibold"><?=e($r['name'])?><small class="d-block text-secondary"><?=e($r['location']?:'—')?></small></td><td><?=e($r['code'])?></td><td><?=e($r['phone']?:'—')?></td><td><?=e($r['users_count'])?></td><td><span class="badge text-bg-<?=$r['active']?'success':'secondary'?>"><?=$r['active']?'Active':'Disabled'?></span></td><td class="text-nowrap"><button class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#edit<?=$r['id']?>"><i class="bi bi-pencil me-1"></i>Edit</button><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-secondary me-1" name="toggle"><?=$r['active']?'Disable':'Enable'?></button></form><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-danger" name="delete" data-confirm="Delete this branch permanently? It cannot be deleted while users, shipments or other records are linked to it."><i class="bi bi-trash me-1"></i>Delete</button></form></td></tr><?php endforeach?></tbody></table></div></div>
<div class="modal fade" id="branch"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h2 class="h5">Add branch</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><input type="hidden" name="csrf" value="<?=csrf()?>"><label class="form-label">Branch name</label><input class="form-control mb-3" name="name" required><label class="form-label">Code</label><input class="form-control mb-3" name="code" maxlength="20" required><label class="form-label">Phone</label><input class="form-control mb-3" name="phone"><label class="form-label">Address</label><input class="form-control" name="address"></div><div class="modal-footer"><button class="btn btn-primary" name="create">Create branch</button></div></form></div></div>
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
                <label class="form-label">Branch name</label>
                <input class="form-control mb-3" name="name" value="<?=e($r['name'])?>" required>
                <label class="form-label">Code</label>
                <input class="form-control mb-3" name="code" maxlength="20" value="<?=e($r['code'])?>" required>
                <label class="form-label">Phone</label>
                <input class="form-control mb-3" name="phone" value="<?=e($r['phone']??'')?>">
                <label class="form-label">Address</label>
                <input class="form-control" name="location" value="<?=e($r['location']??'')?>">
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" name="active" id="active<?=$r['id']?>" <?=$r['active']?'checked':''?>>
                    <label class="form-check-label" for="active<?=$r['id']?>">Branch active</label>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary" name="update"><i class="bi bi-check2 me-1"></i> Save changes</button></div>
        </form>
    </div>
</div>
<?php endforeach?>
<?php require __DIR__.'/partials/footer.php'; ?>
