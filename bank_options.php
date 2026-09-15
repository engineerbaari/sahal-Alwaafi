<?php
require __DIR__.'/bootstrap.php'; require_roles(['admin','accountant']);
$scope=is_admin()?' WHERE active=1':' WHERE branch_id='.(int)require_branch().' AND active=1';
header('Content-Type: application/json; charset=utf-8');
echo json_encode($pdo->query('SELECT id,name,bank_name,account_number FROM bank_accounts'.$scope.' ORDER BY name')->fetchAll());
