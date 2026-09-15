<?php
require __DIR__.'/bootstrap.php';
header('Content-Type: text/plain');
$lines=file('/opt/lampp/htdocs/cargosom/payment_plans.php');
echo '--- RUN1: eval of file line 43 ---',"\n";
try{eval($lines[42]);echo 'OK1 rows=',count($rows),"\n";}catch(Throwable $e2){echo 'ERR1: ',$e2->getMessage(),"\n";}
echo '--- RUN2: extract sql via surgery then run it ---',"\n";
$line=$lines[42]??'';
$sql2=str_replace('$rows=$pdo->query(', '$sql=', $line);
$sql2=str_replace(')->fetchAll();', ';', $sql2);
echo 'surgery line: ',$sql2,"\n";
eval($sql2);
echo 'sql len=',strlen($sql),"\n";
try{$r=$pdo->query($sql)->fetchAll();echo 'OK2 rows=',count($r),"\n";}catch(Throwable $e3){echo 'ERR2: ',$e3->getMessage(),"\n";}