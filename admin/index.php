<?php
session_start();
include('../db_config.php');

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header("Location: ../login.php");
    exit();
}

// Thống kê tổng quan
$stats = array();

// Tổng số phòng
$rooms_sql = "SELECT COUNT(*) as total FROM rooms";
$rooms_result = mysqli_query($conn, $rooms_sql);
$stats['total_rooms'] = mysqli_fetch_assoc($rooms_result)['total'];

// Số phòng đã đặt
$booked_rooms_sql = "SELECT COUNT(*) as total FROM rooms WHERE room_status = 'booked'";
$booked_rooms_result = mysqli_query($conn, $booked_rooms_sql);
$stats['booked_rooms'] = mysqli_fetch_assoc($booked_rooms_result)['total'];

// Tổng số đơn đặt phòng
$bookings_sql = "SELECT COUNT(*) as total FROM bookings";
$bookings_result = mysqli_query($conn, $bookings_sql);
$stats['total_bookings'] = mysqli_fetch_assoc($bookings_result)['total'];

// Số đơn đặt phòng đang chờ xác nhận
$pending_sql = "SELECT COUNT(*) as total FROM bookings WHERE booking_status = 'pending'";
$pending_result = mysqli_query($conn, $pending_sql);
$stats['pending_bookings'] = mysqli_fetch_assoc($pending_result)['total'];

// Tổng số người dùng
$users_sql = "SELECT COUNT(*) as total FROM users WHERE role_id = 1";
$users_result = mysqli_query($conn, $users_sql);
$stats['total_users'] = mysqli_fetch_assoc($users_result)['total'];

// Đặt phòng gần đây
$recent_bookings_sql = "SELECT b.*, u.user_name, u.user_email, u.user_phone, r.room_number, rt.room_type_name 
                        FROM bookings b
                        JOIN users u ON b.user_id = u.user_id
                        JOIN rooms r ON b.room_id = r.room_id
                        JOIN room_types rt ON r.room_type_id = rt.room_type_id
                        ORDER BY b.booking_created_at DESC
                        LIMIT 5";
$recent_bookings_result = mysqli_query($conn, $recent_bookings_sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang quản trị - Panda Hotel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include('templates/sidebar.php'); ?>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Tổng quan</h1>
                <div class="admin-user">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=71C55D&color=fff" alt="Avatar">
                    <span><?php echo $_SESSION['user_name']; ?></span>
                </div>
            </div>
            
            <div class="dashboard">
                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stats-card">
                        <div class="stats-icon rooms-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="stats-info">
                            <h3>Phòng</h3>
                            <p><?php echo $stats['booked_rooms']; ?> / <?php echo $stats['total_rooms']; ?></p>
                            <small>Phòng đã đặt / Tổng số</small>
                        </div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-icon bookings-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stats-info">
                            <h3>Đặt phòng</h3>
                            <p><?php echo $stats['total_bookings']; ?></p>
                            <small><?php echo $stats['pending_bookings']; ?> đơn chờ xác nhận</small>
                        </div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-icon users-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-info">
                            <h3>Khách hàng</h3>
                            <p><?php echo $stats['total_users']; ?></p>
                            <small>Người dùng đã đăng ký</small>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="recent-section">
                    <div class="section-header">
                        <h2>Đặt phòng gần đây</h2>
                        <a href="bookings.php" class="view-all">Xem tất cả</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Khách hàng</th>
                                    <th>Phòng</th>
                                    <th>Ngày nhận</th>
                                    <th>Ngày trả</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($recent_bookings_result) > 0): ?>
                                    <?php while ($booking = mysqli_fetch_assoc($recent_bookings_result)): ?>
                                        <tr>
                                            <td>#<?php echo $booking['booking_id']; ?></td>
                                            <td>
                                                <div class="user-info">
                                                    <span class="user-name"><?php echo $booking['user_name']; ?></span>
                                                    <span class="user-email"><?php echo $booking['user_email']; ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo $booking['room_type_name'] . ' - ' . $booking['room_number']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($booking['booking_check_in'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($booking['booking_check_out'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                                    <?php 
                                                    if ($booking['booking_status'] == 'pending') echo 'Chờ xác nhận';
                                                    elseif ($booking['booking_status'] == 'confirmed') echo 'Đã xác nhận';
                                                    else echo 'Đã hủy';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a href="booking-detail.php?id=<?php echo $booking['booking_id']; ?>" class="action-btn view-btn">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($booking['booking_status'] == 'pending'): ?>
                                                        <a href="bookings.php?action=confirm&id=<?php echo $booking['booking_id']; ?>" class="action-btn confirm-btn">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($booking['booking_status'] != 'cancelled'): ?>
                                                        <a href="bookings.php?action=cancel&id=<?php echo $booking['booking_id']; ?>" class="action-btn cancel-btn">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="no-data">Không có đơn đặt phòng nào</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>