<?php
require_login();
$me=user(); $current=basename($_SERVER['PHP_SELF']);
$active=fn(string $file)=>$current===$file?'active':'';
$company=setting('company_name','CargoSom'); $logo=setting('logo_path');
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($title??'Dashboard')?> · <?=e($company)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?=url('assets/app.css')?>" rel="stylesheet"><link href="<?=url('assets/sidebar.css')?>" rel="stylesheet">
<link href="<?=url('assets/sidebar-scrollbar.css')?>" rel="stylesheet"><link href="<?=url('assets/settings.css')?>" rel="stylesheet">
<link href="<?=url('assets/responsive.css')?>" rel="stylesheet">
</head><body>
<header class="mobile-header"><a class="mobile-brand" href="<?=url('dashboard.php')?>"><strong><?=e($company)?></strong></a><button id="sidebarToggle" class="btn btn-outline-light" aria-label="Open navigation"><i class="bi bi-list"></i></button></header>
<div id="sidebarOverlay" class="sidebar-overlay"></div>
<aside id="appSidebar" class="app-sidebar">
  <div class="sidebar-brand"><div><strong><?=e($company)?></strong><small><?=e(setting('company_tagline','Cargo Agency'))?></small></div><button id="sidebarClose" class="btn sidebar-close" aria-label="Close navigation"><i class="bi bi-x-lg"></i></button></div>
  <nav class="sidebar-nav">
    <div class="nav-section">Workspace</div>
    <a class="sidebar-link <?=$active('dashboard.php')?>" href="<?=url('dashboard.php')?>"><i class="bi bi-grid"></i><span>Dashboard</span></a>
    <a class="sidebar-link <?=$active('cargo.php')?>" href="<?=url('cargo.php')?>"><i class="bi bi-box-seam"></i><span>Shipments</span></a>
    <a class="sidebar-link <?=$active('shipment_awb.php')?>" href="<?=url('shipment_awb.php')?>"><i class="bi bi-collection"></i><span>Shipment AWB</span></a>
    <?php if(can('payments.view')):?>
      <div class="nav-section">Finance</div>
      <a class="sidebar-link <?=$active('invoices.php')?>" href="<?=url('invoices.php')?>"><i class="bi bi-cash-stack"></i><span>Payments</span></a>
      <a class="sidebar-link <?=$active('invoice_payment_history.php')?>" href="<?=url('invoice_payment_history.php')?>"><i class="bi bi-search"></i><span>Payment Track</span></a>
      <a class="sidebar-link <?=$active('payment_plans.php')?>" href="<?=url('payment_plans.php')?>"><i class="bi bi-calendar-check"></i><span>Payment Plans</span></a>
      <?php if(can('income.manage')):?><a class="sidebar-link <?=$active('income.php')?>" href="<?=url('income.php')?>"><i class="bi bi-graph-up-arrow"></i><span>Income</span></a><?php endif?>
      <?php if(can('expenses.manage')):?><a class="sidebar-link <?=$active('expenses.php')?>" href="<?=url('expenses.php')?>"><i class="bi bi-graph-down-arrow"></i><span>Expenses</span></a><?php endif?>
      <?php if(can('bank.manage')):?><a class="sidebar-link <?=$active('bank_accounts.php')?>" href="<?=url('bank_accounts.php')?>"><i class="bi bi-bank"></i><span>Bank Accounts</span></a><a class="sidebar-link <?=$active('bank_transactions.php')?>" href="<?=url('bank_transactions.php')?>"><i class="bi bi-arrow-left-right"></i><span>Bank Transactions</span></a><?php endif?>
      <?php if(can('invoices.view')):?><a class="sidebar-link <?=$active('shipment_invoices.php')?>" href="<?=url('shipment_invoices.php')?>"><i class="bi bi-receipt"></i><span>Shipment invoices</span></a><a class="sidebar-link <?=$active('general_invoices.php')?>" href="<?=url('general_invoices.php')?>"><i class="bi bi-file-earmark-text"></i><span>General invoices</span></a><a class="sidebar-link <?=$active('quotations.php')?>" href="<?=url('quotations.php')?>"><i class="bi bi-file-earmark-richtext"></i><span>Quotations</span></a><?php endif?>
      <?php if(can('reports.view')):?><a class="sidebar-link <?=$active('reports.php')?>" href="<?=url('reports.php')?>"><i class="bi bi-bar-chart"></i><span>Reports</span></a><a class="sidebar-link <?=$active('profit_loss.php')?>" href="<?=url('profit_loss.php')?>"><i class="bi bi-graph-up"></i><span>Profit &amp; Loss</span></a><?php endif?>
    <?php endif?>
    <?php if(can('branches.manage')||can('users.manage')||can('settings.manage')):?><div class="nav-section">Administration</div><?php if(can('branches.manage')):?><a class="sidebar-link <?=$active('branches.php')?>" href="<?=url('branches.php')?>"><i class="bi bi-buildings"></i><span>Branches</span></a><a class="sidebar-link <?=$active('airlines.php')?>" href="<?=url('airlines.php')?>"><i class="bi bi-airplane-engines"></i><span>Airlines</span></a><?php endif?><?php if(can('users.manage')):?><a class="sidebar-link <?=$active('users.php')?>" href="<?=url('users.php')?>"><i class="bi bi-people"></i><span>Users</span></a><?php endif?><?php if(can('settings.manage')):?><a class="sidebar-link <?=$active('settings.php')?>" href="<?=url('settings.php')?>"><i class="bi bi-gear"></i><span>Settings</span></a><?php endif?><?php endif?>
  </nav>
  <div class="sidebar-user"><div class="user-avatar"><?=e(strtoupper(substr($me['name'],0,1)))?></div><div class="user-details"><strong><?=e($me['name'])?></strong><small><?=e(ucfirst($me['role']).' · '.($me['branch_name']??'Branch '.$me['branch_id']))?></small></div><a class="logout-link" href="<?=url('logout.php')?>" title="Sign out"><i class="bi bi-box-arrow-right"></i></a></div>
</aside>
<main class="container-fluid page"><div class="container-xl">
<?php foreach($_SESSION['flash']??[] as [$type,$message]):?><div class="alert alert-<?=e($type)?> alert-dismissible fade show"><?=e($message)?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endforeach;unset($_SESSION['flash']);?>
