<?php
include 'conexion.php';
if (isset($_SERVER['REQUEST_METHOD'])) {
    
    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        include('services/get.php');
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        include('services/post.php');
    }
};



?>