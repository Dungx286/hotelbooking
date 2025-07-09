<?php
// Kiểm tra đăng nhập
$isLoggedIn = isset($_SESSION['user_id']) ? true : false;
$userRole = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
?>

<header class="header">
    <div class="container">
        <div class="logo">
            <a href="index.php">
                <img src="https://img.freepik.com/premium-vector/cute-panda-logo_147788-7.jpg" alt="Panda Hotel Logo">
                <span>Panda Hotel</span>
            </a>
        </div>
        <nav class="nav">
            <ul class="menu">
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="rooms.php">Phòng</a></li>
                <li><a href="#rooms">Đặt phòng</a></li>
                <li><a href="#services">Dịch vụ</a></li>
                <li><a href="#contact">Liên hệ</a></li>
            </ul>
        </nav>
        <div class="auth-buttons">
            <?php if ($isLoggedIn): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user"></i>
                        <span><?php echo $userName; ?></span>
                    </div>
                    <div class="dropdown-menu">
                        <?php if ($userRole == 2): ?>
                            <a href="admin/index.php">Quản lý</a>
                        <?php elseif ($userRole == 3): ?>
                            <a href="admin/bookings.php">Quản lý đặt phòng</a>
                        <?php endif; ?>
                        <a href="profile.php">Thông tin cá nhân</a>
                        <a href="my-bookings.php">Đặt phòng của tôi</a>
                        <a href="logout.php">Đăng xuất</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Đăng nhập</a>
                <a href="register.php">Đăng ký</a>
            <?php endif; ?>
        </div>
        <div class="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</header>