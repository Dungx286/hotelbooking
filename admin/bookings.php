<?php
session_start();
include('../db_config.php');

// Kiểm tra đăng nhập và quyền admin/lễ tân
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

// Xử lý các hành động với đơn đặt phòng
if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Lấy thông tin đặt phòng
    $booking_sql = "SELECT * FROM bookings WHERE booking_id = '$booking_id'";
    $booking_result = mysqli_query($conn, $booking_sql);
    
    if (mysqli_num_rows($booking_result) > 0) {
        $booking = mysqli_fetch_assoc($booking_result);
        $room_id = $booking['room_id'];
        
        // Xác nhận đặt phòng
        if ($_GET['action'] == 'confirm' && $booking['booking_status'] == 'pending') {
            $confirm_sql = "UPDATE bookings SET booking_status = 'confirmed' WHERE booking_id = '$booking_id'";
            
            if (mysqli_query($conn, $confirm_sql)) {
                // Cập nhật trạng thái phòng thành booked
                $update_room_sql = "UPDATE rooms SET room_status = 'booked' WHERE room_id = '$room_id'";
                mysqli_query($conn, $update_room_sql);
                
                $success = "Xác nhận đặt phòng thành công!";
            } else {
                $error = "Lỗi khi xác nhận đặt phòng: " . mysqli_error($conn);
            }
        } 
        // Hủy đặt phòng
        else if ($_GET['action'] == 'cancel' && $booking['booking_status'] != 'cancelled') {
            $cancel_sql = "UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = '$booking_id'";
            
            if (mysqli_query($conn, $cancel_sql)) {
                // Cập nhật trạng thái phòng thành available
                $update_room_sql = "UPDATE rooms SET room_status = 'available' WHERE room_id = '$room_id'";
                mysqli_query($conn, $update_room_sql);
                
                $success = "Hủy đặt phòng thành công!";
            } else {
                $error = "Lỗi khi hủy đặt phòng: " . mysqli_error($conn);
            }
        }
    }
}

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Tìm kiếm
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " AND (u.user_name LIKE '%$search%' OR u.user_email LIKE '%$search%' OR r.room_number LIKE '%$search%')";
}

// Filter trạng thái
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$status_condition = '';
if (!empty($status_filter)) {
    $status_condition = " AND b.booking_status = '$status_filter'";
}

// Lấy tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total 
              FROM bookings b 
              JOIN users u ON b.user_id = u.user_id 
              JOIN rooms r ON b.room_id = r.room_id
              WHERE 1=1 $search_condition $status_condition";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Lấy danh sách đặt phòng
$sql = "SELECT b.*, u.user_name, u.user_email, u.user_phone, r.room_number, r.room_price, rt.room_type_name 
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN rooms r ON b.room_id = r.room_id
        JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE 1=1 $search_condition $status_condition
        ORDER BY b.booking_created_at DESC
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đặt phòng - Panda Hotel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar Toggle Button for Mobile -->
    <div class="sidebar-toggle">
        <i class="fas fa-bars"></i>
    </div>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include('templates/sidebar.php'); ?>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Quản lý đặt phòng</h1>
                <div class="admin-user">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=71C55D&color=fff" alt="Avatar">
                    <span><?php echo $_SESSION['user_name']; ?></span>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="filter-bar">
                <form method="get" action="" class="search-form">
                    <input type="text" name="search" placeholder="Tìm kiếm theo tên, email, số phòng..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div class="filter-options">
                    <select name="status" onchange="location = this.value;">
                        <option value="bookings.php" <?php echo empty($status_filter) ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                        <option value="bookings.php?status=pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                        <option value="bookings.php?status=confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                        <option value="bookings.php?status=cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                    </select>
                </div>
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
                            <th>Ngày đặt</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($booking = mysqli_fetch_assoc($result)): ?>
                                <?php
                                // Tính số đêm
                                $check_in = new DateTime($booking['booking_check_in']);
                                $check_out = new DateTime($booking['booking_check_out']);
                                $interval = $check_in->diff($check_out);
                                $nights = $interval->days;
                                
                                // Tính tổng tiền
                                $total = $nights * $booking['room_price'];
                                ?>
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
                                    <td><?php echo date('d/m/Y H:i', strtotime($booking['booking_created_at'])); ?></td>
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
                                            <a href="booking-detail.php?id=<?php echo $booking['booking_id']; ?>" class="action-btn view-btn" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($booking['booking_status'] == 'pending'): ?>
                                                <a href="bookings.php?action=confirm&id=<?php echo $booking['booking_id']; ?>" class="action-btn confirm-btn confirm-action" data-confirm="Bạn có chắc muốn xác nhận đặt phòng này?" title="Xác nhận">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($booking['booking_status'] != 'cancelled'): ?>
                                                <a href="bookings.php?action=cancel&id=<?php echo $booking['booking_id']; ?>" class="action-btn cancel-btn confirm-action" data-confirm="Bạn có chắc muốn hủy đặt phòng này?" title="Hủy">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-data">Không tìm thấy đơn đặt phòng nào</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <div class="page-item">
                            <a class="page-link <?php echo $i == $page ? 'active' : ''; ?>" 
                               href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>