<?php
    session_start();
    require('connection.php');

    if($_SERVER[REQUEST_METHOD] === 'POST'){

    $login = $_POST['login'];
    $password = $_POST['password'];
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    $errorsReg=[];
    }
?>