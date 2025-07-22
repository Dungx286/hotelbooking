<?php
session_start();
include('db_config.php');

$error = '';
$success = '';

// Kiểm tra nếu đã đăng nhập thì chuyển hướng đến trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Xử lý đăng ký
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $error = "Vui lòng nhập đầy đủ thông tin";
    } elseif ($password != $confirm_password) {
        $error = "Mật khẩu và xác nhận mật khẩu không khớp";
    } else {
        // Kiểm tra email đã tồn tại chưa
        $check_email = "SELECT * FROM users WHERE user_email = '$email'";
        $result = mysqli_query($conn, $check_email);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Email đã được sử dụng. Vui lòng sử dụng email khác.";
        } else {
            // Mặc định role_id = 1 (khách hàng)
            $role_id = 1;
            
            // Thực hiện đăng ký (trong thực tế nên dùng password_hash)
            $sql = "INSERT INTO users (user_name, user_email, user_password, user_phone, user_address, role_id) 
                    VALUES ('$name', '$email', '$password', '$phone', '$address', $role_id)";
            
            if (mysqli_query($conn, $sql)) {
                $success = "Đăng ký thành công! Vui lòng đăng nhập.";
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
    <title>Đăng ký - Panda Hotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include('templates/header.php'); ?>
    
    <!-- Register Form -->
    <div class="auth-container">
        <div class="auth-header">
            <img src="image/logo.jpg" alt="Panda Hotel Logo">
            <h2>Đăng ký tài khoản</h2>
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
        
        <form class="auth-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="name">Họ và tên</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="address">Địa chỉ</label>
                <input type="text" id="address" name="address">
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Đăng ký</button>
        </form>
        
        <div class="auth-footer">
            <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include('templates/footer.php'); ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>