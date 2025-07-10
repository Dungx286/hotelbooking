<?php
session_start();
include('db_config.php');

$error = '';
$success = '';

// Kiểm tra xem có room_id được truyền vào không
if (!isset($_GET['room_id'])) {
    header("Location: index.php");
    exit();
}

$room_id = mysqli_real_escape_string($conn, $_GET['room_id']);

// Lấy thông tin phòng
$room_sql = "SELECT r.*, rt.room_type_name 
             FROM rooms r 
             JOIN room_types rt ON r.room_type_id = rt.room_type_id 
             WHERE r.room_id = '$room_id'";
$room_result = mysqli_query($conn, $room_sql);

if (mysqli_num_rows($room_result) == 0) {
    header("Location: index.php");
    exit();
}

$room = mysqli_fetch_assoc($room_result);
$image = !empty($room['room_image']) ? 'assets/images/rooms/' . $room['room_image'] : 'https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1';

// Xử lý form đặt phòng
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : '';
    
    // Kiểm tra ngày
    if (empty($check_in) || empty($check_out)) {
        $error = "Vui lòng chọn ngày nhận phòng và trả phòng";
    } elseif (strtotime($check_in) < strtotime(date('Y-m-d'))) {
        $error = "Ngày nhận phòng không thể là ngày trong quá khứ";
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $error = "Ngày trả phòng phải sau ngày nhận phòng";
    } else {
        // Kiểm tra phòng có sẵn không
        $availability_sql = "SELECT * FROM bookings 
                             WHERE room_id = '$room_id' 
                             AND booking_status != 'cancelled'
                             AND (
                                 (booking_check_in <= '$check_in' AND booking_check_out >= '$check_in')
                                 OR (booking_check_in <= '$check_out' AND booking_check_out >= '$check_out')
                                 OR (booking_check_in >= '$check_in' AND booking_check_out <= '$check_out')
                             )";
        $availability_result = mysqli_query($conn, $availability_sql);
        
        if (mysqli_num_rows($availability_result) > 0) {
            $error = "Phòng đã được đặt trong khoảng thời gian này. Vui lòng chọn ngày khác.";
        } else {
            // Nếu đã đăng nhập
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                
                $booking_sql = "INSERT INTO bookings (user_id, room_id, booking_check_in, booking_check_out, booking_status)
                                VALUES ('$user_id', '$room_id', '$check_in', '$check_out', 'pending')";
                
                if (mysqli_query($conn, $booking_sql)) {
                    $success = "Đặt phòng thành công! Nhân viên sẽ xác nhận đơn đặt phòng của bạn trong thời gian sớm nhất.";
                    
                    // Cập nhật trạng thái phòng
                    $update_room_sql = "UPDATE rooms SET room_status = 'booked' WHERE room_id = '$room_id'";
                    mysqli_query($conn, $update_room_sql);
                } else {
                    $error = "Đã xảy ra lỗi. Vui lòng thử lại sau.";
                }
            } else {
                // Nếu chưa đăng nhập, kiểm tra thông tin khách vãng lai
                if (empty($name) || empty($email) || empty($phone)) {
                    $error = "Vui lòng nhập đầy đủ thông tin cá nhân";
                } else {
                    // Kiểm tra email có tồn tại không
                    $check_email = "SELECT * FROM users WHERE user_email = '$email'";
                    $email_result = mysqli_query($conn, $check_email);
                    
                    if (mysqli_num_rows($email_result) > 0) {
                        $error = "Email đã tồn tại trong hệ thống. Vui lòng đăng nhập để đặt phòng.";
                    } else {
                        // Tạo tài khoản mới cho khách vãng lai
                        $password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                        $address = "";
                        
                        $user_sql = "INSERT INTO users (user_name, user_email, user_password, user_phone, user_address, role_id)
                                     VALUES ('$name', '$email', '$password', '$phone', '$address', 1)";
                        
                        if (mysqli_query($conn, $user_sql)) {
                            $user_id = mysqli_insert_id($conn);
                            
                            // Tạo đơn đặt phòng
                            $booking_sql = "INSERT INTO bookings (user_id, room_id, booking_check_in, booking_check_out, booking_status)
                                            VALUES ('$user_id', '$room_id', '$check_in', '$check_out', 'pending')";
                            
                            if (mysqli_query($conn, $booking_sql)) {
                                $success = "Đặt phòng thành công! Mật khẩu tạm thời của bạn là: $password";
                                
                                // Cập nhật trạng thái phòng
                                $update_room_sql = "UPDATE rooms SET room_status = 'booked' WHERE room_id = '$room_id'";
                                mysqli_query($conn, $update_room_sql);
                            } else {
                                $error = "Đã xảy ra lỗi khi đặt phòng. Vui lòng thử lại sau.";
                            }
                        } else {
                            $error = "Đã xảy ra lỗi khi tạo tài khoản. Vui lòng thử lại sau.";
                        }
                    }
                }
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
    <title>Đặt phòng - Panda Hotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include('templates/header.php'); ?>
    
    <!-- Booking Form -->
    <div class="booking-container">
        <h2>Đặt phòng</h2>
        
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
        
        <div class="booking-details">
            <div class="booking-image">
                <img src="<?php echo $image; ?>" alt="<?php echo $room['room_number']; ?>">
            </div>
            <div class="booking-info">
                <h3><?php echo $room['room_type_name'] . ' - ' . $room['room_number']; ?></h3>
                <p class="room-price"><?php echo number_format($room['room_price'], 0, ',', '.'); ?> VND / đêm</p>
                <p><?php echo $room['room_description']; ?></p>
                <p><strong>Trạng thái:</strong> 
                    <?php 
                    if ($room['room_status'] == 'available') echo 'Còn trống';
                    else if ($room['room_status'] == 'booked') echo 'Đã đặt';
                    else echo 'Đang bảo trì';
                    ?>
                </p>
            </div>
        </div>
        
        <form class="booking-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?room_id=" . $room_id); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="check_in">Ngày nhận phòng</label>
                    <input type="date" id="check_in" name="check_in" required>
                </div>
                <div class="form-group">
                    <label for="check_out">Ngày trả phòng</label>
                    <input type="date" id="check_out" name="check_out" required>
                </div>
            </div>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Họ và tên</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary">Đặt phòng</button>
        </form>
    </div>
    
    <!-- Footer -->
    <?php include('templates/footer.php'); ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>