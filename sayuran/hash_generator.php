<?php
$password_plain = 'admin12345'; // Ganti dengan password yang Anda inginkan untuk admin
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);
echo $password_hashed;
?>