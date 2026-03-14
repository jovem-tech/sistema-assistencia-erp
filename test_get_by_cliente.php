<?php
include 'public/index.php';
$model = new \App\Models\EquipamentoModel();
$res = $model->getByCliente(1);
print_r($res);
