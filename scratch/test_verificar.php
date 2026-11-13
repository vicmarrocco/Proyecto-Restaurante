<?php
$ch = curl_init('http://localhost/PROYECTO_REST/mostaza_web/api/auth/verificar_codigo.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'ap***@gmail.com',
    'codigo' => '999999',
    'idempleado' => 13,
    'app' => 'cajero'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resp = curl_exec($ch);
curl_close($ch);
echo $resp . PHP_EOL;
