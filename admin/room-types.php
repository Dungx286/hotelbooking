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

// Xử lý thêm/sửa/xóa loại phòng
if (isset($_POST['add_room_type'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Kiểm tra tên loại phòng đã tồn tại chưa
    $check_sql = "SELECT * FROM room_types WHERE room_type_name = '$name'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Loại phòng đã tồn tại. Vui lòng chọn tên khác.";
    } else {
        $add_sql = "INSERT INTO room_types (room_type_name, room_type_description) 
                   VALUES ('$name', '$description')";
        
        if (mysqli_query($conn, $add_sql)) {
            $success = "Thêm loại phòng mới thành công!";
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    }
} elseif (isset($_POST['update_room_type']) && isset($_POST['room_type_id'])) {
    $room_type_id = mysqli_real_escape_string($conn, $_POST['room_type_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Kiểm tra tên loại phòng đã tồn tại chưa (nếu thay đổi tên)
    $check_sql = "SELECT * FROM room_types WHERE room_type_name = '$name' AND room_type_id != '$room_type_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Loại phòng đã tồn tại. Vui lòng chọn tên khác.";
    } else {
        $update_sql = "UPDATE room_types 
                       SET room_type_name = '$name', room_type_description = '$description' 
                       WHERE room_type_id = '$room_type_id'";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Cập nhật loại phòng thành công!";
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $room_type_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Kiểm tra loại phòng có phòng nào không
    $check_rooms = "SELECT * FROM rooms WHERE room_type_id = '$room_type_id'";
    $check_result = mysqli_query($conn, $check_rooms);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Không thể xóa loại phòng này vì có phòng đang sử dụng!";
    } else {
        $delete_sql = "DELETE FROM room_types WHERE room_type_id = '$room_type_id'";
        
        if (mysqli_query($conn, $delete_sql)) {
            $success = "Xóa loại phòng thành công!";
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    }
}

// Lấy thông tin loại phòng cần sửa
$room_type_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $room_type_id = mysqli_real_escape_string($conn, $_GET['id']);
    $edit_sql = "SELECT * FROM room_types WHERE room_type_id = '$room_type_id'";
    $edit_result = mysqli_query($conn, $edit_sql);
    
    if (mysqli_num_rows($edit_result) > 0) {
        $room_type_to_edit = mysqli_fetch_assoc($edit_result);
    }
}

// Lấy danh sách loại phòng
$sql = "SELECT rt.*, COUNT(r.room_id) as room_count 
       FROM room_types rt
       LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
       GROUP BY rt.room_type_id
       ORDER BY rt.room_type_id";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý loại phòng - Panda Hotel</title>
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
                <h1><?php echo isset($room_type_to_edit) ? 'Chỉnh sửa loại phòng' : 'Quản lý loại phòng'; ?></h1>
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
            
            <!-- Form thêm/sửa loại phòng -->
            <div class="admin-form">
                <div class="form-header">
                    <h2><?php echo isset($room_type_to_edit) ? 'Chỉnh sửa loại phòng' : 'Thêm loại phòng mới'; ?></h2>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <?php if (isset($room_type_to_edit)): ?>
                        <input type="hidden" name="room_type_id" value="<?php echo $room_type_to_edit['room_type_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Tên loại phòng</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($room_type_to_edit) ? $room_type_to_edit['room_type_name'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea id="description" name="description" rows="4"><?php echo isset($room_type_to_edit) ? $room_type_to_edit['room_type_description'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-footer">
                        <?php if (isset($room_type_to_edit)): ?>
                            <button type="submit" name="update_room_type" class="btn btn-primary">Cập nhật loại phòng</button>
                            <a href="room-types.php" class="btn btn-secondary">Hủy</a>
                        <?php else: ?>
                            <button type="submit" name="add_room_type" class="btn btn-primary">Thêm loại phòng</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Danh sách loại phòng -->
            <?php if (!isset($room_type_to_edit)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên loại phòng</th>
                                <th>Mô tả</th>
                                <th>Số phòng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($room_type = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $room_type['room_type_id']; ?></td>
                                        <td><?php echo $room_type['room_type_name']; ?></td>
                                        <td><?php echo $room_type['room_type_description']; ?></td>
                                        <td><?php echo $room_type['room_count']; ?></td>
                                        <td>
                                            <div class="actions">
                                                <a href="room-types.php?action=edit&id=<?php echo $room_type['room_type_id']; ?>" class="action-btn edit-btn" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="room-types.php?action=delete&id=<?php echo $room_type['room_type_id']; ?>" class="action-btn delete-btn confirm-action" data-confirm="Bạn có chắc muốn xóa loại phòng này?" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="no-data">Không tìm thấy loại phòng nào</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>