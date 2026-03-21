<?php
$pdo = new PDO('mysql:host=localhost;dbname=erp', 'root', '');
$res = $pdo->query("SELECT id, nome, token_whatsapp, instancia_whatsapp FROM config")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
