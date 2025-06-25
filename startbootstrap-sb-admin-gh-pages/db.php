<?php
    $host = 'localhost';
    $user = 'root';
    $pass = '12345678';
    $db = 'student_management';

    $conn = new mysqli($host, $user, $pass, $db);
    // Check connection
    if ($conn->connect_error) {
        die('Kết nối thất bại: ' . $conn->connect_error);
    }

?>