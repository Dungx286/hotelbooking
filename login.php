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

// Xử lý đăng nhập
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Kiểm tra email và password
    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin đăng nhập";
    } else {
        // Truy vấn kiểm tra tài khoản
        $sql = "SELECT * FROM users WHERE user_email = '$email'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            
            // Kiểm tra mật khẩu (trong thực tế nên dùng password_hash và password_verify)
            if ($password == $row['user_password']) {
                // Đăng nhập thành công, lưu thông tin vào session
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_name'] = $row['user_name'];
                $_SESSION['role_id'] = $row['role_id'];
                
                // Chuyển hướng đến trang chủ
                header("Location: index.php");
                exit();
            } else {
                $error = "Mật khẩu không chính xác";
            }
        } else {
            $error = "Email không tồn tại trong hệ thống";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Panda Hotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include('templates/header.php'); ?>
    
    <!-- Login Form -->
    <div class="auth-container">
        <div class="auth-header">
            <img src="image/logo.jpg" alt="Panda Hotel Logo">
            <h2>Đăng nhập</h2>
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
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Đăng nhập</button>
        </form>
        
        <div class="auth-footer">
            <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include('templates/footer.php'); ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>