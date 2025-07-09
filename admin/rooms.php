<?php
session_start();
include('../db_config.php');

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

// Xử lý thêm/sửa/xóa phòng
if (isset($_POST['add_room'])) {
    $room_number = mysqli_real_escape_string($conn, $_POST['room_number']);
    $room_type_id = mysqli_real_escape_string($conn, $_POST['room_type_id']);
    $room_price = mysqli_real_escape_string($conn, $_POST['room_price']);
    $room_description = mysqli_real_escape_string($conn, $_POST['room_description']);
    $room_status = mysqli_real_escape_string($conn, $_POST['room_status']);
    $room_image = 'room_default.jpg'; // Mặc định
    
    // Kiểm tra số phòng đã tồn tại chưa
    $check_sql = "SELECT * FROM rooms WHERE room_number = '$room_number'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Số phòng đã tồn tại. Vui lòng chọn số phòng khác.";
    } else {
        $add_sql = "INSERT INTO rooms (room_number, room_type_id, room_price, room_description, room_image, room_status) 
                   VALUES ('$room_number', '$room_type_id', '$room_price', '$room_description', '$room_image', '$room_status')";
        
        if (mysqli_query($conn, $add_sql)) {
            $success = "Thêm phòng mới thành công!";
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    }
} elseif (isset($_POST['update_room']) && isset($_POST['room_id'])) {
    $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);
    $room_type_id = mysqli_real_escape_string($conn, $_POST['room_type_id']);
    $room_price = mysqli_real_escape_string($conn, $_POST['room_price']);
    $room_description = mysqli_real_escape_string($conn, $_POST['room_description']);
    $room_status = mysqli_real_escape_string($conn, $_POST['room_status']);
    
    $update_sql = "UPDATE rooms 
                   SET room_type_id = '$room_type_id', room_price = '$room_price', 
                       room_description = '$room_description', room_status = '$room_status' 
                   WHERE room_id = '$room_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        $success = "Cập nhật phòng thành công!";
    } else {
        $error = "Lỗi: " . mysqli_error($conn);
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $room_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Kiểm tra phòng có đơn đặt không
    $check_bookings = "SELECT * FROM bookings WHERE room_id = '$room_id' AND booking_status != 'cancelled'";
    $check_result = mysqli_query($conn, $check_bookings);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Không thể xóa phòng này vì có đơn đặt phòng liên quan!";
    } else {
        $delete_sql = "DELETE FROM rooms WHERE room_id = '$room_id'";
        
        if (mysqli_query($conn, $delete_sql)) {
            $success = "Xóa phòng thành công!";
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    }
}

// Lấy thông tin phòng cần sửa
$room_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $room_id = mysqli_real_escape_string($conn, $_GET['id']);
    $edit_sql = "SELECT * FROM rooms WHERE room_id = '$room_id'";
    $edit_result = mysqli_query($conn, $edit_sql);
    
    if (mysqli_num_rows($edit_result) > 0) {
        $room_to_edit = mysqli_fetch_assoc($edit_result);
    }
}

// Lấy danh sách loại phòng
$room_types_sql = "SELECT * FROM room_types";
$room_types_result = mysqli_query($conn, $room_types_sql);

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Tìm kiếm
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " WHERE room_number LIKE '%$search%' OR room_description LIKE '%$search%'";
}

// Lấy tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total FROM rooms $search_condition";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Lấy danh sách phòng
$sql = "SELECT r.*, rt.room_type_name 
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.room_type_id
        $search_condition
        ORDER BY r.room_id DESC
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phòng - Panda Hotel</title>
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
                <h1><?php echo isset($room_to_edit) ? 'Chỉnh sửa phòng' : 'Quản lý phòng'; ?></h1>
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
            
            <!-- Form thêm/sửa phòng -->
            <div class="admin-form">
                <div class="form-header">
                    <h2><?php echo isset($room_to_edit) ? 'Chỉnh sửa phòng' : 'Thêm phòng mới'; ?></h2>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <?php if (isset($room_to_edit)): ?>
                        <input type="hidden" name="room_id" value="<?php echo $room_to_edit['room_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="room_number">Số phòng</label>
                            <input type="text" id="room_number" name="room_number" value="<?php echo isset($room_to_edit) ? $room_to_edit['room_number'] : ''; ?>" <?php echo isset($room_to_edit) ? 'readonly' : 'required'; ?>>
                        </div>
                        <div class="form-group">
                            <label for="room_type_id">Loại phòng</label>
                            <select id="room_type_id" name="room_type_id" required>
                                <option value="">Chọn loại phòng</option>
                                <?php while ($type = mysqli_fetch_assoc($room_types_result)): ?>
                                    <option value="<?php echo $type['room_type_id']; ?>" <?php echo (isset($room_to_edit) && $room_to_edit['room_type_id'] == $type['room_type_id']) ? 'selected' : ''; ?>>
                                        <?php echo $type['room_type_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="room_price">Giá phòng (VND/đêm)</label>
                            <input type="number" id="room_price" name="room_price" value="<?php echo isset($room_to_edit) ? $room_to_edit['room_price'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="room_status">Trạng thái</label>
                            <select id="room_status" name="room_status" required>
                                <option value="available" <?php echo (isset($room_to_edit) && $room_to_edit['room_status'] == 'available') ? 'selected' : ''; ?>>Còn trống</option>
                                <option value="booked" <?php echo (isset($room_to_edit) && $room_to_edit['room_status'] == 'booked') ? 'selected' : ''; ?>>Đã đặt</option>
                                <option value="maintenance" <?php echo (isset($room_to_edit) && $room_to_edit['room_status'] == 'maintenance') ? 'selected' : ''; ?>>Bảo trì</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="room_description">Mô tả phòng</label>
                        <textarea id="room_description" name="room_description" rows="4"><?php echo isset($room_to_edit) ? $room_to_edit['room_description'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-footer">
                        <?php if (isset($room_to_edit)): ?>
                            <button type="submit" name="update_room" class="btn btn-primary">Cập nhật phòng</button>
                            <a href="rooms.php" class="btn btn-secondary">Hủy</a>
                        <?php else: ?>
                            <button type="submit" name="add_room" class="btn btn-primary">Thêm phòng</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Danh sách phòng -->
            <?php if (!isset($room_to_edit)): ?>
                <div class="filter-bar">
                    <form method="get" action="" class="search-form">
                        <input type="text" name="search" placeholder="Tìm kiếm phòng..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Số phòng</th>
                                <th>Loại phòng</th>
                                <th>Giá/đêm</th>
                                <th>Trạng thái</th>
                                <th>Mô tả</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($room = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $room['room_id']; ?></td>
                                        <td><?php echo $room['room_number']; ?></td>
                                        <td><?php echo $room['room_type_name']; ?></td>
                                        <td><?php echo number_format($room['room_price'], 0, ',', '.'); ?> VND</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $room['room_status']; ?>">
                                                <?php 
                                                if ($room['room_status'] == 'available') echo 'Còn trống';
                                                elseif ($room['room_status'] == 'booked') echo 'Đã đặt';
                                                elseif ($room['room_status'] == 'maintenance') echo 'Bảo trì';
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo substr($room['room_description'], 0, 50); ?>...</td>
                                        <td>
                                            <div class="actions">
                                                <a href="rooms.php?action=edit&id=<?php echo $room['room_id']; ?>" class="action-btn edit-btn" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="rooms.php?action=delete&id=<?php echo $room['room_id']; ?>" class="action-btn delete-btn confirm-action" data-confirm="Bạn có chắc muốn xóa phòng này?" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">Không tìm thấy phòng nào</td>
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
                                href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>