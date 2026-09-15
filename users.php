<?php
require __DIR__.'/bootstrap.php';
require_perm('users.manage');
$me = user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);

    if (isset($_POST['create'])) {
        if (strlen($_POST['password'] ?? '') < 8) {
            flash('danger', 'Password must be at least 8 characters.');
            redirect('users.php');
        }

        $permRaw = $_POST['perm'] ?? [];
        if (!is_array($permRaw)) {
            $permRaw = [$permRaw];
        }
        $permData = array_values($permRaw);
        $permJson = (($_POST['role'] ?? '') === 'admin' || !$permData) ? null : json_encode($permData);

        try {
            $pdo->prepare('INSERT INTO users(name,email,phone,password,role,permissions,branch_id) VALUES(?,?,?,?,?,?,?)')
                ->execute([
                    trim($_POST['name']),
                    strtolower(trim($_POST['email'])),
                    trim($_POST['phone']),
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['role'],
                    $permJson,
                    (int) $_POST['branch_id'],
                ]);
            flash('success', 'User account created.');
        } catch (PDOException $e) {
            flash('danger', 'That username is already in use.');
        }
    } elseif (isset($_POST['update'])) {
        if ($id === $me['id']) {
            flash('warning', 'Use another administrator to change your own access.');
            redirect('users.php');
        }

        $sql = 'UPDATE users SET name=?,phone=?,role=?,branch_id=?,active=?';
        $args = [
            trim($_POST['name']),
            trim($_POST['phone']),
            $_POST['role'],
            (int) $_POST['branch_id'],
            isset($_POST['active']) ? 1 : 0,
        ];

        if (isset($_POST['perm'])) {
            $pR = $_POST['perm'];
            $pL = is_array($pR) ? $pR : [$pR];
            if ($pL && ($_POST['role'] ?? '') !== 'admin') {
                $sql .= ',permissions=?';
                $args[] = json_encode($pL);
            } else {
                $sql .= ',permissions=NULL';
            }
        }

        $sql .= ' WHERE id=?';
        $args[] = $id;

        $pdo->prepare($sql)->execute($args);
        flash('success', 'User updated.');
    } elseif (isset($_POST['delete'])) {
        if ($id === $me['id']) {
            flash('warning', 'You cannot delete your own account.');
            redirect('users.php');
        }

        try {
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            flash('success', 'User deleted.');
        } catch (PDOException $e) {
            flash('danger', 'This user has linked records and cannot be deleted. Disable the account instead.');
        }
    }

    redirect('users.php');
}

$branches = $pdo->query('SELECT id,name,code FROM branches WHERE active=1 ORDER BY name')->fetchAll();
$rows = $pdo->query('SELECT u.*,b.name branch_name,b.code branch_code FROM users u JOIN branches b ON b.id=u.branch_id ORDER BY u.created_at DESC')->fetchAll();

$groups = [
    ['Shipments', ['shipments.view','shipments.create','shipments.edit','shipments.delete','import.upload']],
    ['Shipment AWB', ['shipment_awb.view','shipment_awb.manage']],
    ['Finance', ['payments.view','payments.record','income.manage','expenses.manage','invoices.view','invoices.create','reports.view','bank.manage']],
    ['Administration', ['users.manage','branches.manage','settings.manage']],
];

function perm_label(string $key): string
{
    return (string) (ALL_PERMISSIONS[$key] ?? $key);
}

$title = 'Users';
require __DIR__.'/partials/header.php';
?>
<script>
const ROLE_DEFAULTS = <?= json_encode(ROLE_PERMISSIONS) ?>;

function applyRoleDefaults(box, role) {
    [].forEach.call(box, el => {
        el.checked = !ROLE_DEFAULTS[role] || ROLE_DEFAULTS[role].includes(el.value);
    });
    setPermDisabled(box, role);
}

function setPermDisabled(box, role) {
    [].forEach.call(box, el => el.disabled = role === 'admin' || role === 'super_admin');
}
</script>

<div class="page-head d-flex justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Users &amp; access</h1>
        <p class="text-secondary mb-0">Manage each user’s branch, access level, and fine-grained permissions.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#user"><i class="bi bi-person-plus me-1"></i> Add user</button>
</div>

<!-- Add user modal -->
<div class="modal fade" id="user">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h2 class="h5 mb-0">Add user</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full name</label>
                        <input class="form-control" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input class="form-control" name="username" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <select class="form-select" name="branch_id" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['name'].' ('.$b['code'].')') ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" onchange="applyRoleDefaults(document.querySelectorAll('#user input[name^=perm]'),this.value)">
                            <option value="staff">Staff</option>
                            <option value="customer">Customer</option>
                            <option value="accountant">Accountant</option>
                            <option value="admin">Admin (all permissions)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Temporary password</label>
                        <input class="form-control" type="password" minlength="8" name="password" required>
                    </div>
                </div>

                <hr class="my-4">
                <h2 class="h6 mb-1">Permissions</h2>
                <p class="small text-secondary mb-3">Admin users automatically have every permission. For other roles, check what this user may do.</p>
                <?php foreach ($groups as [$group, $perms]): ?>
                    <div class="mb-3">
                        <strong class="d-block small mb-1"><?= e($group) ?></strong>
                        <?php foreach ($perms as $perm): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="perm[]" value="<?= e($perm) ?>" checked>
                                <label class="form-check-label"><?= e(perm_label($perm)) ?></label>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endforeach ?>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" name="create"><i class="bi bi-check2 me-1"></i> Create user</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit user modals -->
<?php foreach ($rows as $r):
    if ($r['id'] === $me['id']) {
        continue;
    }
    $saved = $r['permissions'] ? (is_string($r['permissions']) ? json_decode($r['permissions']) ?? [] : []) : (ROLE_PERMISSIONS[$r['role']] ?? []);
    $isAdminRole = $r['role'] === 'admin';
?>
    <div class="modal fade" id="edit<?= $r['id'] ?>">
        <div class="modal-dialog modal-lg">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h2 class="h5 mb-0">Update <?= e($r['name']) ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full name</label>
                            <input class="form-control" name="name" value="<?= e($r['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" value="<?= e($r['phone']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id">
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= $r['branch_id'] == $b['id'] ? 'selected' : '' ?>><?= e($b['name'].' ('.$b['code'].')') ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" onchange="applyRoleDefaults(document.querySelectorAll('#edit<?= $r['id'] ?> input[name^=perm]'),this.value)">
                                <?php foreach (['staff','customer','accountant','admin'] as $role): ?>
                                    <option value="<?= $role ?>" <?= $r['role'] === $role ? 'selected' : '' ?>><?= e(ucfirst($role)) ?><?= $role === 'admin' ? ' (all permissions)' : '' ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="active" id="active<?= $r['id'] ?>" <?= $r['active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active<?= $r['id'] ?>">Account active</label>
                    </div>

                    <hr class="my-4">
                    <h2 class="h6 mb-1">Permissions</h2>
                    <p class="small text-secondary mb-3">Admin users automatically have every permission. Leave a checkbox set to grant that permission.</p>
                    <?php foreach ($groups as [$group, $perms]): ?>
                        <div class="mb-3">
                            <strong class="d-block small mb-1"><?= e($group) ?></strong>
                            <?php foreach ($perms as $perm): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="perm[]" value="<?= e($perm) ?>" <?= in_array($perm, $saved, true) || $isAdminRole ? 'checked' : '' ?> <?= $isAdminRole ? 'disabled' : '' ?>>
                                    <label class="form-check-label"><?= e(perm_label($perm)) ?></label>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endforeach ?>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" name="update"><i class="bi bi-check2 me-1"></i> Save changes</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach ?>

<!-- Users table -->
<div class="card p-3 p-lg-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <input class="form-control" style="max-width:320px" data-table-search placeholder="Search people…">
        <span class="text-secondary small"><?= count($rows) ?> user<?= count($rows) === 1 ? '' : 's' ?></span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Branch</th>
                    <th>Role</th>
                    <th>Permissions</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $userPerms = $r['permissions'] ? (is_string($r['permissions']) ? json_decode($r['permissions']) ?? [] : []) : ($r['role'] === 'admin' ? array_keys(ALL_PERMISSIONS) : (ROLE_PERMISSIONS[$r['role']] ?? []));
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="user-avatar"><?= e(strtoupper(substr($r['name'], 0, 1))) ?></span>
                                <div>
                                    <div class="fw-semibold"><?= e($r['name']) ?></div>
                                    <small class="text-secondary d-block"><?= e($r['phone'] ?: '—') ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= e(strstr($r['email'], '@', true) ?: $r['email']) ?></td>
                        <td>
                            <?= e($r['branch_name']) ?>
                            <small class="text-secondary d-block"><?= e($r['branch_code']) ?></small>
                        </td>
                        <td><span class="badge role-badge role-<?= e($r['role']) ?>"><?= e(ucfirst($r['role'])) ?></span></td>
                        <td><span class="text-secondary"><?= e($r['role'] === 'admin' ? 'All (admin)' : count($userPerms).' granted') ?></span></td>
                        <td><span class="badge text-bg-<?= $r['active'] ? 'success' : 'secondary' ?>"><?= $r['active'] ? 'Active' : 'Disabled' ?></span></td>
                        <td class="text-end">
                            <?php if ($r['id'] !== $me['id']): ?>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#edit<?= $r['id'] ?>"><i class="bi bi-pencil"></i> Update</button>
                                <form class="d-inline" method="post">
                                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" name="delete" data-confirm="Delete this user?"><i class="bi bi-trash"></i> Delete</button>
                                </form>
                            <?php else: ?>
                                <small class="text-secondary"><i class="bi bi-person-check me-1"></i>Current user</small>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__.'/partials/footer.php'; ?>
