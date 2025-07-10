<?php
session_start();
include('db_config.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Lấy thông tin người dùng
$sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// Xử lý cập nhật thông tin
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_info'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        
        if (empty($name) || empty($phone)) {
            $error = "Vui lòng nhập đầy đủ thông tin bắt buộc";
        } else {
            $update_sql = "UPDATE users 
                          SET user_name = '$name', user_phone = '$phone', user_address = '$address' 
                          WHERE user_id = '$user_id'";
            
            if (mysqli_query($conn, $update_sql)) {
                $success = "Cập nhật thông tin thành công!";
                $_SESSION['user_name'] = $name;
                
                // Cập nhật lại thông tin người dùng
                $result = mysqli_query($conn, $sql);
                $user = mysqli_fetch_assoc($result);
            } else {
                $error = "Lỗi: " . mysqli_error($conn);
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = mysqli_real_escape_string($conn, $_POST['current_password']);
        $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
        $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
        
        // Kiểm tra mật khẩu hiện tại
        if ($current_password != $user['user_password']) {
            $error = "Mật khẩu hiện tại không chính xác";
        } elseif (empty($new_password) || strlen($new_password) < 6) {
            $error = "Mật khẩu mới phải có ít nhất 6 ký tự";
        } elseif ($new_password != $confirm_password) {
            $error = "Xác nhận mật khẩu không khớp";
        } else {
            // Cập nhật mật khẩu mới
            $update_sql = "UPDATE users SET user_password = '$new_password' WHERE user_id = '$user_id'";
            
            if (mysqli_query($conn, $update_sql)) {
                $success = "Đổi mật khẩu thành công!";
            } else {
                $error = "Lỗi: " . mysqli_error($conn);
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
    <title>Thông tin cá nhân - Panda Hotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include('templates/header.php'); ?>
    
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['user_name']); ?>&background=71C55D&color=fff&size=128" alt="Avatar">
            </div>
            <div>
                <h2><?php echo $user['user_name']; ?></h2>
                <p><?php echo $user['user_email']; ?></p>
                <p>
                    <span class="badge">
                        <?php 
                        if ($user['role_id'] == 1) echo 'Khách hàng';
                        elseif ($user['role_id'] == 2) echo 'Quản lý';
                        elseif ($user['role_id'] == 3) echo 'Lễ tân';
                        ?>
                    </span>
                </p>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-tabs">
            <div class="profile-tab active" data-tab="profile-info">Thông tin cá nhân</div>
            <div class="profile-tab" data-tab="change-password">Đổi mật khẩu</div>
        </div>
        
        <!-- Tab thông tin cá nhân -->
        <div id="profile-info" class="tab-content active">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="name">Họ và tên</label>
                    <input type="text" id="name" name="name" value="<?php echo $user['user_name']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?php echo $user['user_email']; ?>" disabled>
                    <small>Email không thể thay đổi</small>
                </div>
                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo $user['user_phone']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="address">Địa chỉ</label>
                    <input type="text" id="address" name="address" value="<?php echo $user['user_address']; ?>">
                </div>
                <button type="submit" name="update_info" class="btn btn-primary">Cập nhật thông tin</button>
            </form>
        </div>
        
        <!-- Tab đổi mật khẩu -->
        <div id="change-password" class="tab-content">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="current_password">Mật khẩu hiện tại</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Mật khẩu mới</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <small>Mật khẩu phải có ít nhất 6 ký tự</small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu mới</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">Đổi mật khẩu</button>
            </form>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include('templates/footer.php'); ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>