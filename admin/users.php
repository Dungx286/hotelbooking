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

// Xử lý thêm/sửa/xóa người dùng
if (isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $role_id = mysqli_real_escape_string($conn, $_POST['role_id']);
    
    // Kiểm tra email
    $check_sql = "SELECT * FROM users WHERE user_email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Email đã tồn tại. Vui lòng sử dụng email khác.";
    } else {
        // Thêm người dùng mới
        $add_sql = "INSERT INTO users (user_name, user_email, user_password, user_phone, user_address, role_id) 
                   VALUES ('$name', '$email', '$password', '$phone', '$address', '$role_id')";
        
        if (mysqli_query($conn, $add_sql)) {
            $success = "Thêm người dùng mới thành công!";
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    }
} elseif (isset($_POST['update_user']) && isset($_POST['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $role_id = mysqli_real_escape_string($conn, $_POST['role_id']);
    
    // Cập nhật người dùng
    $update_sql = "UPDATE users 
                   SET user_name = '$name', user_phone = '$phone', 
                       user_address = '$address', role_id = '$role_id' 
                   WHERE user_id = '$user_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        $success = "Cập nhật người dùng thành công!";
    } else {
        $error = "Lỗi: " . mysqli_error($conn);
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Kiểm tra người dùng có đơn đặt phòng không
    $check_bookings = "SELECT * FROM bookings WHERE user_id = '$user_id'";
    $check_result = mysqli_query($conn, $check_bookings);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Không thể xóa người dùng này vì có đơn đặt phòng liên quan!";
    } else {
        // Xóa người dùng
        $delete_sql = "DELETE FROM users WHERE user_id = '$user_id'";
        
        if (mysqli_query($conn, $delete_sql)) {
            $success = "Xóa người dùng thành công!";
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    }
}

// Lấy thông tin người dùng cần sửa
$user_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['id']);
    $edit_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
    $edit_result = mysqli_query($conn, $edit_sql);
    
    if (mysqli_num_rows($edit_result) > 0) {
        $user_to_edit = mysqli_fetch_assoc($edit_result);
    }
}

// Lấy danh sách vai trò
$roles_sql = "SELECT * FROM roles";
$roles_result = mysqli_query($conn, $roles_sql);

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Tìm kiếm
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " WHERE user_name LIKE '%$search%' OR user_email LIKE '%$search%'";
}

// Lấy tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total FROM users $search_condition";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Lấy danh sách người dùng
$sql = "SELECT u.*, r.role_name
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id
        $search_condition
        ORDER BY u.user_id DESC
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Panda Hotel</title>
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
                <h1><?php echo isset($user_to_edit) ? 'Chỉnh sửa người dùng' : 'Quản lý người dùng'; ?></h1>
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
            
            <!-- Form thêm/sửa người dùng -->
            <div class="admin-form">
                <div class="form-header">
                    <h2><?php echo isset($user_to_edit) ? 'Chỉnh sửa người dùng' : 'Thêm người dùng mới'; ?></h2>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <?php if (isset($user_to_edit)): ?>
                        <input type="hidden" name="user_id" value="<?php echo $user_to_edit['user_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Họ và tên</label>
                            <input type="text" id="name" name="name" value="<?php echo isset($user_to_edit) ? $user_to_edit['user_name'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo isset($user_to_edit) ? $user_to_edit['user_email'] : ''; ?>" <?php echo isset($user_to_edit) ? 'readonly' : 'required'; ?>>
                            <?php if (isset($user_to_edit)): ?>
                                <small>Email không thể thay đổi</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!isset($user_to_edit)): ?>
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo isset($user_to_edit) ? $user_to_edit['user_phone'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="role_id">Vai trò</label>
                            <select id="role_id" name="role_id" required>
                                <option value="">Chọn vai trò</option>
                                <?php 
                                mysqli_data_seek($roles_result, 0); // Reset con trỏ
                                while ($role = mysqli_fetch_assoc($roles_result)): 
                                ?>
                                    <option value="<?php echo $role['role_id']; ?>" <?php echo (isset($user_to_edit) && $user_to_edit['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($role['role_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Địa chỉ</label>
                        <input type="text" id="address" name="address" value="<?php echo isset($user_to_edit) ? $user_to_edit['user_address'] : ''; ?>">
                    </div>
                    
                    <div class="form-footer">
                        <?php if (isset($user_to_edit)): ?>
                            <button type="submit" name="update_user" class="btn btn-primary">Cập nhật người dùng</button>
                            <a href="users.php" class="btn btn-secondary">Hủy</a>
                        <?php else: ?>
                            <button type="submit" name="add_user" class="btn btn-primary">Thêm người dùng</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Danh sách người dùng -->
            <?php if (!isset($user_to_edit)): ?>
                <div class="filter-bar">
                    <form method="get" action="" class="search-form">
                        <input type="text" name="search" placeholder="Tìm kiếm người dùng..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Họ và tên</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Vai trò</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($user = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo $user['user_name']; ?></td>
                                        <td><?php echo $user['user_email']; ?></td>
                                        <td><?php echo $user['user_phone']; ?></td>
                                        <td><?php echo ucfirst($user['role_name']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($user['user_created_at'])); ?></td>
                                        <td>
                                            <div class="actions">
                                                <a href="users.php?action=edit&id=<?php echo $user['user_id']; ?>" class="action-btn edit-btn" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="users.php?action=delete&id=<?php echo $user['user_id']; ?>" class="action-btn delete-btn confirm-action" data-confirm="Bạn có chắc muốn xóa người dùng này?" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">Không tìm thấy người dùng nào</td>
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