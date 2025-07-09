<?php
// Kiểm tra role của user
$userRole = $_SESSION['role_id'];
$isAdmin = ($userRole == 2);
?>

<aside class="admin-sidebar">
    <div class="sidebar-header">
        <img src="https://img.freepik.com/premium-vector/cute-panda-logo_147788-7.jpg" alt="Panda Hotel Logo">
        <h2>Panda Hotel</h2>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tổng quan</span>
                </a>
            </li>
            <li>
                <a href="bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Quản lý đặt phòng</span>
                </a>
            </li>
            <?php if ($isAdmin): ?>
            <li>
                <a href="rooms.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bed"></i>
                    <span>Quản lý phòng</span>
                </a>
            </li>
            <li>
                <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Quản lý người dùng</span>
                </a>
            </li>
            <li>
                <a href="room-types.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'room-types.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i>
                    <span>Loại phòng</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Thống kê báo cáo</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="../profile.php">
            <i class="fas fa-user-circle"></i>
            <span>Hồ sơ cá nhân</span>
        </a>
        <a href="../logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Đăng xuất</span>
        </a>
    </div>
</aside>