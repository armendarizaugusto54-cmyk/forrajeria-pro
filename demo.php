<?php
declare(strict_types=1);
require_once __DIR__.'/Inventario.php';
$db=new PDO('sqlite::memory:');
$app=new Inventario($db); $app->instalar();
$id=$app->agregar('Alimento balanceado 20 kg',1850000,12);
$app->agregar('Maíz partido 10 kg',650000,8);
echo "Inventario inicial:\n";
foreach($app->buscar('') as $p) echo $p['nombre'].': '.$p['stock']." unidades\n";
$total=$app->vender($id,2);
echo 'Venta: $'.number_format($total/100,2,',','.')."\n";
echo 'Stock restante: '.$app->buscar('Alimento')[0]['stock']."\n";
