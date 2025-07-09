<?php
session_start();
include('../db_config.php');

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../login.php");
    exit();
}

// Xử lý filter theo thời gian
$period = isset($_GET['period']) ? mysqli_real_escape_string($conn, $_GET['period']) : 'month';
$start_date = '';
$end_date = '';

// Xác định thời gian bắt đầu và kết thúc cho báo cáo
switch($period) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    case 'custom':
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        break;
    default:
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
}

// Thống kê đơn đặt phòng theo trạng thái
$status_sql = "SELECT 
                booking_status, 
                COUNT(*) as count 
               FROM bookings 
               WHERE booking_created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
               GROUP BY booking_status";
$status_result = mysqli_query($conn, $status_sql);

$status_stats = array(
    'pending' => 0,
    'confirmed' => 0,
    'cancelled' => 0
);

while($row = mysqli_fetch_assoc($status_result)) {
    $status_stats[$row['booking_status']] = $row['count'];
}

// Tính doanh thu
$revenue_sql = "SELECT 
                 SUM(r.room_price * DATEDIFF(b.booking_check_out, b.booking_check_in)) as total_revenue,
                 COUNT(DISTINCT b.user_id) as unique_customers,
                 AVG(DATEDIFF(b.booking_check_out, b.booking_check_in)) as avg_stay
                FROM bookings b
                JOIN rooms r ON b.room_id = r.room_id
                WHERE b.booking_status = 'confirmed'
                AND b.booking_created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
$revenue_result = mysqli_query($conn, $revenue_sql);
$revenue_data = mysqli_fetch_assoc($revenue_result);

// Thống kê loại phòng được đặt nhiều nhất
$room_type_sql = "SELECT 
                   rt.room_type_name,
                   COUNT(*) as booking_count
                  FROM bookings b
                  JOIN rooms r ON b.room_id = r.room_id
                  JOIN room_types rt ON r.room_type_id = rt.room_type_id
                  WHERE b.booking_status != 'cancelled'
                  AND b.booking_created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
                  GROUP BY rt.room_type_name
                  ORDER BY booking_count DESC";
$room_type_result = mysqli_query($conn, $room_type_sql);

// Lấy các đơn đặt phòng trong khoảng thời gian
$bookings_sql = "SELECT 
                  b.*,
                  u.user_name,
                  r.room_number,
                  r.room_price,
                  rt.room_type_name,
                  DATEDIFF(b.booking_check_out, b.booking_check_in) as nights
                 FROM bookings b
                 JOIN users u ON b.user_id = u.user_id
                 JOIN rooms r ON b.room_id = r.room_id
                 JOIN room_types rt ON r.room_type_id = rt.room_type_id
                 WHERE b.booking_created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
                 ORDER BY b.booking_created_at DESC
                 LIMIT 10";
$bookings_result = mysqli_query($conn, $bookings_sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê - Panda Hotel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .report-title h2 {
            margin-bottom: 5px;
        }
        
        .report-period {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .report-filters {
            display: flex;
            gap: 10px;
        }
        
        .report-filters a {
            padding: 8px 16px;
            border-radius: var(--border-radius);
            text-decoration: none;
            background-color: #f5f5f5;
            color: var(--text-dark);
        }
        
        .report-filters a.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .stats-container {
            margin-bottom: 30px;
        }
        
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .report-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
        }
        
        .card-title {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 15px;
        }
        
        .card-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .card-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .status-distribution {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .status-item {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: var(--border-radius);
        }
        
        .status-pending-bg {
            background-color: rgba(255, 193, 7, 0.2);
        }
        
        .status-confirmed-bg {
            background-color: rgba(40, 167, 69, 0.2);
        }
        
        .status-cancelled-bg {
            background-color: rgba(220, 53, 69, 0.2);
        }
        
        .status-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .status-label {
            font-size: 0.85rem;
            color: var(--text-dark);
        }
        
        .custom-date-form {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: var(--border-radius);
            display: <?php echo $period == 'custom' ? 'flex' : 'none'; ?>;
            gap: 10px;
            align-items: center;
        }
        
        .custom-date-form input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
        }
        
        .room-type-chart {
            margin-top: 20px;
        }
        
        .bar-chart {
            margin-top: 15px;
        }
        
        .bar-item {
            margin-bottom: 15px;
        }
        
        .bar-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .bar-name {
            font-weight: 500;
        }
        
        .bar-value {
            color: var(--primary-color);
        }
        
        .bar-container {
            height: 25px;
            background-color: #f5f5f5;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .bar {
            height: 100%;
            background-color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .report-filters {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
            
            .custom-date-form {
                flex-direction: column;
                align-items: stretch;
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
                <h1>Báo cáo thống kê</h1>
                <div class="admin-user">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=71C55D&color=fff" alt="Avatar">
                    <span><?php echo $_SESSION['user_name']; ?></span>
                </div>
            </div>
            
            <div class="report-header">
                <div class="report-title">
                    <h2>Báo cáo hoạt động</h2>
                    <div class="report-period">
                        Từ: <?php echo date('d/m/Y', strtotime($start_date)); ?> - 
                        Đến: <?php echo date('d/m/Y', strtotime($end_date)); ?>
                    </div>
                </div>
                
                <div class="report-filters">
                    <a href="?period=week" class="<?php echo $period == 'week' ? 'active' : ''; ?>">Tuần này</a>
                    <a href="?period=month" class="<?php echo $period == 'month' ? 'active' : ''; ?>">Tháng này</a>
                    <a href="?period=year" class="<?php echo $period == 'year' ? 'active' : ''; ?>">Năm nay</a>
                    <a href="?period=custom" class="<?php echo $period == 'custom' ? 'active' : ''; ?>">Tùy chỉnh</a>
                </div>
            </div>
            
            <!-- Form chọn ngày tùy chỉnh -->
            <form method="get" action="" class="custom-date-form">
                <input type="hidden" name="period" value="custom">
                <label for="start_date">Từ ngày:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                <label for="end_date">Đến ngày:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                <button type="submit" class="btn btn-primary">Áp dụng</button>
            </form>
            
            <div class="report-grid">
                <div class="report-card">
                    <div class="card-title">Tổng doanh thu</div>
                    <div class="card-value"><?php echo number_format($revenue_data['total_revenue'] ?? 0, 0, ',', '.'); ?> VND</div>
                    <div class="card-subtitle">Giai đoạn: <?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?></div>
                </div>
                
                <div class="report-card">
                    <div class="card-title">Tổng đơn đặt phòng</div>
                    <div class="card-value"><?php echo array_sum($status_stats); ?></div>
                    <div class="status-distribution">
                        <div class="status-item status-pending-bg">
                            <div class="status-value"><?php echo $status_stats['pending']; ?></div>
                            <div class="status-label">Chờ xác nhận</div>
                        </div>
                        <div class="status-item status-confirmed-bg">
                            <div class="status-value"><?php echo $status_stats['confirmed']; ?></div>
                            <div class="status-label">Đã xác nhận</div>
                        </div>
                        <div class="status-item status-cancelled-bg">
                            <div class="status-value"><?php echo $status_stats['cancelled']; ?></div>
                            <div class="status-label">Đã hủy</div>
                        </div>
                    </div>
                </div>
                
                <div class="report-card">
                    <div class="card-title">Khách hàng</div>
                    <div class="card-value"><?php echo $revenue_data['unique_customers'] ?? 0; ?></div>
                    <div class="card-subtitle">Số khách hàng đã đặt phòng</div>
                </div>
                
                <div class="report-card">
                    <div class="card-title">Thời gian lưu trú trung bình</div>
                    <div class="card-value"><?php echo round($revenue_data['avg_stay'] ?? 0, 1); ?> đêm</div>
                    <div class="card-subtitle">Dựa trên các đơn đã xác nhận</div>
                </div>
            </div>
            
            <div class="report-grid">
                <div class="report-card">
                    <div class="card-title">Phân bố loại phòng được đặt</div>
                    <div class="room-type-chart">
                        <div class="bar-chart">
                            <?php 
                            $max_count = 0;
                            $room_type_data = array();
                            
                            // Tìm giá trị lớn nhất để tính phần trăm
                            if (mysqli_num_rows($room_type_result) > 0) {
                                while ($type = mysqli_fetch_assoc($room_type_result)) {
                                    $room_type_data[] = $type;
                                    if ($type['booking_count'] > $max_count) {
                                        $max_count = $type['booking_count'];
                                    }
                                }
                            }
                            
                            // Hiển thị biểu đồ
                            if (!empty($room_type_data)) {
                                foreach ($room_type_data as $type) {
                                    $percentage = $max_count > 0 ? ($type['booking_count'] / $max_count) * 100 : 0;
                            ?>
                                <div class="bar-item">
                                    <div class="bar-label">
                                        <span class="bar-name"><?php echo $type['room_type_name']; ?></span>
                                        <span class="bar-value"><?php echo $type['booking_count']; ?> đơn</span>
                                    </div>
                                    <div class="bar-container">
                                        <div class="bar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                            <?php
                                }
                            } else {
                                echo "<p>Không có dữ liệu</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="report-card">
                    <div class="card-title">Đơn đặt phòng gần đây</div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Khách hàng</th>
                                    <th>Phòng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($bookings_result) > 0): ?>
                                    <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                        <?php
                                        // Tính tổng tiền
                                        $total = $booking['room_price'] * $booking['nights'];
                                        ?>
                                        <tr>
                                            <td>#<?php echo $booking['booking_id']; ?></td>
                                            <td><?php echo $booking['user_name']; ?></td>
                                            <td><?php echo $booking['room_type_name'] . ' - ' . $booking['room_number']; ?></td>
                                            <td><?php echo number_format($total, 0, ',', '.'); ?> VND</td>
                                            <td>
                                                <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                                    <?php 
                                                    if ($booking['booking_status'] == 'pending') echo 'Chờ xác nhận';
                                                    elseif ($booking['booking_status'] == 'confirmed') echo 'Đã xác nhận';
                                                    else echo 'Đã hủy';
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="no-data">Không có đơn đặt phòng nào</td>
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