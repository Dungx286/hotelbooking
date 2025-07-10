<?php
session_start();
include('db_config.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách đặt phòng của người dùng
$sql = "SELECT b.*, r.room_number, r.room_price, r.room_image, rt.room_type_name 
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE b.user_id = '$user_id'
        ORDER BY b.booking_created_at DESC";
$result = mysqli_query($conn, $sql);

// Xử lý hủy đặt phòng
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $booking_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Kiểm tra quyền hủy (chỉ được hủy đơn của mình)
    $check_sql = "SELECT * FROM bookings WHERE booking_id = '$booking_id' AND user_id = '$user_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $booking = mysqli_fetch_assoc($check_result);
        
        // Chỉ được hủy nếu chưa đến ngày nhận phòng và trạng thái là pending hoặc confirmed
        if (strtotime($booking['booking_check_in']) > strtotime(date('Y-m-d')) && 
            ($booking['booking_status'] == 'pending' || $booking['booking_status'] == 'confirmed')) {
            
            // Cập nhật trạng thái đặt phòng
            $update_sql = "UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = '$booking_id'";
            
            if (mysqli_query($conn, $update_sql)) {
                // Cập nhật trạng thái phòng thành available
                $update_room_sql = "UPDATE rooms SET room_status = 'available' WHERE room_id = '".$booking['room_id']."'";
                mysqli_query($conn, $update_room_sql);
                
                // Refresh trang
                header("Location: my-bookings.php?cancelled=success");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt phòng của tôi - Panda Hotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .bookings-list {
            margin-top: 40px;
        }
        
        .booking-item {
            display: flex;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
        }
        
        .booking-image {
            flex: 0 0 200px;
            height: 200px;
        }
        
        .booking-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .booking-details {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .booking-dates {
            display: flex;
            margin-bottom: 10px;
        }
        
        .booking-date {
            padding: 5px 15px;
            background: #f5f5f5;
            border-radius: 20px;
            margin-right: 15px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .booking-date i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .booking-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-confirmed {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        .booking-price {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .price-info {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .booking-actions a {
            padding: 8px 15px;
            border-radius: var(--border-radius);
            margin-left: 10px;
        }
        
        .cancel-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .no-bookings {
            background: white;
            padding: 30px;
            text-align: center;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        @media (max-width: 768px) {
            .booking-item {
                flex-direction: column;
            }
            
            .booking-image {
                flex: 0 0 150px;
                width: 100%;
            }
            
            .booking-header, .booking-dates {
                flex-direction: column;
            }
            
            .booking-status, .booking-date {
                margin-bottom: 10px;
            }
            
            .booking-price {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .booking-actions {
                margin-top: 15px;
                display: flex;
                width: 100%;
            }
            
            .booking-actions a {
                flex: 1;
                text-align: center;
                margin: 0 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include('templates/header.php'); ?>
    
    <div class="container" style="margin-top: 120px; margin-bottom: 60px;">
        <h2>Đặt phòng của tôi</h2>
        
        <?php if (isset($_GET['cancelled']) && $_GET['cancelled'] == 'success'): ?>
            <div class="alert alert-success">Hủy đặt phòng thành công!</div>
        <?php endif; ?>
        
        <div class="bookings-list">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($booking = mysqli_fetch_assoc($result)): ?>
                    <?php 
                    $image = !empty($booking['room_image']) ? 'assets/images/rooms/' . $booking['room_image'] : 'https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1';
                    
                    // Tính số ngày lưu trú
                    $check_in = new DateTime($booking['booking_check_in']);
                    $check_out = new DateTime($booking['booking_check_out']);
                    $interval = $check_in->diff($check_out);
                    $nights = $interval->days;
                    
                    // Tính tổng tiền
                    $total = $nights * $booking['room_price'];
                    
                    // Định dạng ngày
                    $formatted_check_in = date('d/m/Y', strtotime($booking['booking_check_in']));
                    $formatted_check_out = date('d/m/Y', strtotime($booking['booking_check_out']));
                    ?>
                    <div class="booking-item">
                        <div class="booking-image">
                            <img src="<?php echo $image; ?>" alt="Room">
                        </div>
                        <div class="booking-details">
                            <div class="booking-header">
                                <h3><?php echo $booking['room_type_name'] . ' - ' . $booking['room_number']; ?></h3>
                                <div class="booking-status status-<?php echo $booking['booking_status']; ?>">
                                    <?php 
                                    if ($booking['booking_status'] == 'pending') echo 'Chờ xác nhận';
                                    else if ($booking['booking_status'] == 'confirmed') echo 'Đã xác nhận';
                                    else echo 'Đã hủy';
                                    ?>
                                </div>
                            </div>
                            <div class="booking-dates">
                                <div class="booking-date">
                                    <i class="fas fa-calendar-check"></i>
                                    Nhận phòng: <?php echo $formatted_check_in; ?>
                                </div>
                                <div class="booking-date">
                                    <i class="fas fa-calendar-times"></i>
                                    Trả phòng: <?php echo $formatted_check_out; ?>
                                </div>
                                <div class="booking-date">
                                    <i class="fas fa-moon"></i>
                                    <?php echo $nights; ?> đêm
                                </div>
                            </div>
                            <div class="booking-price">
                                <div class="price-info">
                                    Tổng tiền: <?php echo number_format($total, 0, ',', '.'); ?> VND
                                </div>
                                <div class="booking-actions">
                                    <?php if ($booking['booking_status'] != 'cancelled' && strtotime($booking['booking_check_in']) > strtotime(date('Y-m-d'))): ?>
                                        <a href="my-bookings.php?action=cancel&id=<?php echo $booking['booking_id']; ?>" class="btn cancel-btn" onclick="return confirm('Bạn có chắc muốn hủy đặt phòng này?')">Hủy đặt phòng</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <h3>Bạn chưa có đơn đặt phòng nào</h3>
                    <p>Hãy khám phá các phòng tuyệt vời của chúng tôi và đặt ngay!</p>
                    <a href="index.php#rooms" class="btn btn-primary">Xem phòng</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include('templates/footer.php'); ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>