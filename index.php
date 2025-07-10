<?php
session_start();
include('db_config.php');

// Lấy danh sách phòng có sẵn
$sql = "SELECT r.room_id, r.room_number, r.room_price, r.room_description, r.room_image, r.room_status, 
              rt.room_type_name 
        FROM rooms r 
        JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE r.room_status = 'available'";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panda Hotel - Trang chủ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include('templates/header.php'); ?>
    
    <!-- Banner -->
    <div class="banner">
        <div class="banner-content">
            <h1>Chào mừng đến với Panda Hotel</h1>
            <p>Trải nghiệm nghỉ dưỡng tuyệt vời với những tiện nghi hiện đại và dịch vụ chất lượng</p>
            <a href="#rooms" class="btn btn-primary">Đặt phòng ngay</a>
        </div>
    </div>
    
    <!-- Giới thiệu -->
    <section class="intro section">
        <div class="container">
            <h2 class="section-title">Về chúng tôi</h2>
            <div class="intro-content">
                <div class="intro-image">
                    <img src="https://images.pexels.com/photos/261102/pexels-photo-261102.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Panda Hotel">
                </div>
                <div class="intro-text">
                    <p>Panda Hotel tự hào là điểm đến lý tưởng cho những ai đang tìm kiếm một không gian nghỉ dưỡng sang trọng, tiện nghi và thân thiện với môi trường. Với thiết kế độc đáo lấy cảm hứng từ loài gấu trúc đáng yêu, khách sạn chúng tôi mang đến không gian sống tinh tế và gần gũi với thiên nhiên.</p>
                    <p>Đội ngũ nhân viên chuyên nghiệp và thân thiện của chúng tôi luôn sẵn sàng phục vụ quý khách 24/7, đảm bảo mọi nhu cầu của quý khách đều được đáp ứng một cách nhanh chóng và chu đáo nhất.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Dịch vụ -->
    <section class="services section">
        <div class="container">
            <h2 class="section-title">Dịch vụ của chúng tôi</h2>
            <div class="services-container">
                <div class="service-item">
                    <i class="fas fa-wifi"></i>
                    <h3>Wi-Fi miễn phí</h3>
                    <p>Kết nối internet tốc độ cao miễn phí trong toàn bộ khách sạn</p>
                </div>
                <div class="service-item">
                    <i class="fas fa-utensils"></i>
                    <h3>Nhà hàng</h3>
                    <p>Thực đơn phong phú với các món ăn Á - Âu do đầu bếp 5 sao chế biến</p>
                </div>
                <div class="service-item">
                    <i class="fas fa-swimming-pool"></i>
                    <h3>Hồ bơi</h3>
                    <p>Hồ bơi ngoài trời với tầm nhìn tuyệt đẹp</p>
                </div>
                <div class="service-item">
                    <i class="fas fa-spa"></i>
                    <h3>Spa & Massage</h3>
                    <p>Thư giãn với các liệu pháp spa và massage chuyên nghiệp</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Danh sách phòng -->
    <section id="rooms" class="rooms section">
        <div class="container">
            <h2 class="section-title">Phòng của chúng tôi</h2>
            <div class="room-filter">
                <select id="roomTypeFilter">
                    <option value="all">Tất cả loại phòng</option>
                    <?php
                    $typeSql = "SELECT * FROM room_types";
                    $typeResult = mysqli_query($conn, $typeSql);
                    while($typeRow = mysqli_fetch_assoc($typeResult)) {
                        echo "<option value='".$typeRow['room_type_id']."'>".$typeRow['room_type_name']."</option>";
                    }
                    ?>
                </select>
                <select id="priceFilter">
                    <option value="all">Tất cả mức giá</option>
                    <option value="0-500000">Dưới 500.000 VND</option>
                    <option value="500000-1000000">500.000 - 1.000.000 VND</option>
                    <option value="1000000+">Trên 1.000.000 VND</option>
                </select>
                <button id="filterButton" class="btn btn-secondary">Lọc</button>
            </div>
            
            <div class="rooms-container">
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $image = !empty($row['room_image']) ? 'assets/images/rooms/' . $row['room_image'] : 'https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1';
                ?>
                <div class="room-card" data-type="<?php echo $row['room_type_name']; ?>" data-price="<?php echo $row['room_price']; ?>">
                    <div class="room-image">
                        <img src="<?php echo $image; ?>" alt="<?php echo $row['room_number']; ?>">
                    </div>
                    <div class="room-info">
                        <h3><?php echo $row['room_type_name'] . ' - ' . $row['room_number']; ?></h3>
                        <p class="room-price"><?php echo number_format($row['room_price'], 0, ',', '.'); ?> VND / đêm</p>
                        <p class="room-desc"><?php echo $row['room_description']; ?></p>
                        <a href="booking.php?room_id=<?php echo $row['room_id']; ?>" class="btn btn-primary">Đặt ngay</a>
                    </div>
                </div>
                <?php
                    }
                } else {
                    echo "<p class='no-rooms'>Không có phòng khả dụng.</p>";
                }
                ?>
            </div>
        </div>
    </section>
    
    <!-- Đánh giá khách hàng -->
    <section class="testimonials section">
        <div class="container">
            <h2 class="section-title">Khách hàng nói gì về chúng tôi</h2>
            <div class="testimonial-container">
                <div class="testimonial-item">
                    <div class="testimonial-content">
                        <p>"Dịch vụ tuyệt vời, phòng sạch sẽ và nhân viên rất thân thiện. Tôi sẽ quay lại vào kỳ nghỉ tiếp theo!"</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="https://images.pexels.com/photos/1222271/pexels-photo-1222271.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Khách hàng">
                        <h4>Nguyễn Văn A</h4>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <div class="testimonial-item">
                    <div class="testimonial-content">
                        <p>"Không gian yên tĩnh, thoáng mát. Đồ ăn ngon, đặc biệt là bữa sáng buffet rất phong phú!"</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="https://images.pexels.com/photos/774909/pexels-photo-774909.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Khách hàng">
                        <h4>Trần Thị B</h4>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Liên hệ -->
    <section class="contact section">
        <div class="container">
            <h2 class="section-title">Liên hệ với chúng tôi</h2>
            <div class="contact-container">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>123 Đường ABC, Quận XYZ, Thành phố HCM</p>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <p>+84 123 456 789</p>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <p>info@pandahotel.com</p>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <p>Check-in: 14:00 | Check-out: 12:00</p>
                    </div>
                </div>
                <div class="contact-form">
                    <form id="contactForm" action="process_contact.php" method="post">
                        <div class="form-group">
                            <input type="text" id="name" name="name" placeholder="Họ và tên" required>
                        </div>
                        <div class="form-group">
                            <input type="email" id="email" name="email" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <input type="text" id="subject" name="subject" placeholder="Tiêu đề" required>
                        </div>
                        <div class="form-group">
                            <textarea id="message" name="message" placeholder="Nội dung" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Gửi tin nhắn</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include('templates/footer.php'); ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>