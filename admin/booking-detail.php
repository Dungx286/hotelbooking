<?php
session_start();
include('../db_config.php');

// Kiểm tra đăng nhập và quyền
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header("Location: ../login.php");
    exit();
}

// Kiểm tra id
if (!isset($_GET['id'])) {
    header("Location: bookings.php");
    exit();
}

$booking_id = mysqli_real_escape_string($conn, $_GET['id']);

// Lấy thông tin chi tiết đơn đặt phòng
$sql = "SELECT b.*, u.user_name, u.user_email, u.user_phone, u.user_address, 
               r.room_number, r.room_price, r.room_description, r.room_image, 
               rt.room_type_name, rt.room_type_description 
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN rooms r ON b.room_id = r.room_id
        JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE b.booking_id = '$booking_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: bookings.php");
    exit();
}

$booking = mysqli_fetch_assoc($result);

// Tính số đêm và tổng tiền
$check_in = new DateTime($booking['booking_check_in']);
$check_out = new DateTime($booking['booking_check_out']);
$interval = $check_in->diff($check_out);
$nights = $interval->days;
$total = $nights * $booking['room_price'];

// Hình ảnh phòng
$image = !empty($booking['room_image']) ? '../assets/images/rooms/' . $booking['room_image'] : 'https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đặt phòng - Panda Hotel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .detail-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .detail-card h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            flex: 0 0 150px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .detail-value {
            flex: 1;
        }
        
        .room-image {
            width: 100%;
            height: 250px;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .status-large {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 500;
            margin-top: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-buttons .btn {
            padding: 10px 20px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }
        
        .action-buttons .btn i {
            margin-right: 10px;
        }
        
        .btn-confirm {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-cancel {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-back {
            background-color: #6c757d;
            color: white;
        }
        
        @media (max-width: 768px) {
            .booking-detail {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <h1>Chi tiết đặt phòng #<?php echo $booking['booking_id']; ?></h1>
                <div class="admin-user">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=71C55D&color=fff" alt="Avatar">
                    <span><?php echo $_SESSION['user_name']; ?></span>
                </div>
            </div>
            
            <div class="booking-detail">
                <div class="detail-card">
                    <h3>Thông tin đặt phòng</h3>
                    
                    <div class="detail-row">
                        <div class="detail-label">Mã đặt phòng</div>
                        <div class="detail-value">#<?php echo $booking['booking_id']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Ngày nhận phòng</div>
                        <div class="detail-value"><?php echo date('d/m/Y', strtotime($booking['booking_check_in'])); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Ngày trả phòng</div>
                        <div class="detail-value"><?php echo date('d/m/Y', strtotime($booking['booking_check_out'])); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Số đêm</div>
                        <div class="detail-value"><?php echo $nights; ?> đêm</div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Ngày đặt</div>
                        <div class="detail-value"><?php echo date('d/m/Y H:i:s', strtotime($booking['booking_created_at'])); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Trạng thái</div>
                        <div class="detail-value">
                            <span class="status-large status-<?php echo $booking['booking_status']; ?>">
                                <?php 
                                if ($booking['booking_status'] == 'pending') echo 'Chờ xác nhận';
                                elseif ($booking['booking_status'] == 'confirmed') echo 'Đã xác nhận';
                                else echo 'Đã hủy';
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Tổng tiền</div>
                        <div class="detail-value"><strong><?php echo number_format($total, 0, ',', '.'); ?> VND</strong></div>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if ($booking['booking_status'] == 'pending'): ?>
                            <a href="bookings.php?action=confirm&id=<?php echo $booking['booking_id']; ?>" class="btn btn-confirm confirm-action" data-confirm="Bạn có chắc muốn xác nhận đặt phòng này?">
                                <i class="fas fa-check"></i> Xác nhận
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($booking['booking_status'] != 'cancelled'): ?>
                            <a href="bookings.php?action=cancel&id=<?php echo $booking['booking_id']; ?>" class="btn btn-cancel confirm-action" data-confirm="Bạn có chắc muốn hủy đặt phòng này?">
                                <i class="fas fa-times"></i> Hủy đơn
                            </a>
                        <?php endif; ?>
                        
                        <a href="bookings.php" class="btn btn-back">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
                
                <div>
                    <div class="detail-card">
                        <h3>Thông tin khách hàng</h3>
                        
                        <div class="detail-row">
                            <div class="detail-label">Họ và tên</div>
                            <div class="detail-value"><?php echo $booking['user_name']; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo $booking['user_email']; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Số điện thoại</div>
                            <div class="detail-value"><?php echo $booking['user_phone']; ?></div>
                        </div>
                        
                        <?php if (!empty($booking['user_address'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Địa chỉ</div>
                                <div class="detail-value"><?php echo $booking['user_address']; ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="detail-card">
                        <h3>Thông tin phòng</h3>
                        
                        <div class="room-image">
                            <img src="<?php echo $image; ?>" alt="<?php echo $booking['room_number']; ?>">
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Số phòng</div>
                            <div class="detail-value"><?php echo $booking['room_number']; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Loại phòng</div>
                            <div class="detail-value"><?php echo $booking['room_type_name']; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Giá/đêm</div>
                            <div class="detail-value"><?php echo number_format($booking['room_price'], 0, ',', '.'); ?> VND</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Mô tả</div>
                            <div class="detail-value"><?php echo $booking['room_description']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>