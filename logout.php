<?php
session_start();

// Xóa tất cả các biến session
$_SESSION = array();

// Hủy phiên session
session_destroy();

// Chuyển hướng đến trang chủ
header("Location: index.php");
exit();
?>