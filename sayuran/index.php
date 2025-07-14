<?php
// Mulai sesi PHP
session_start();

// Sertakan file koneksi database Anda
require_once __DIR__ . '/config/db.php';

// Cek apakah user sudah login
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? htmlspecialchars($_SESSION['username']) : 'Tamu';
$user_role = $is_logged_in ? htmlspecialchars($_SESSION['role']) : '';

// Judul halaman
$page_title = "Beranda - KangSayur";
include_once __DIR__ . '/include/header.php';
?>

<body class="index-page">
<main class="main">

  <section id="hero" class="hero section mb-5">
    <div class="container text-center">
      <div class="d-flex flex-column justify-content-center align-items-center">
        <h1 data-aos="fade-up">Selamat Datang di <span>KangSayur.com</span>, <?php echo $username; ?>!</h1>
        <p data-aos="fade-up" data-aos-delay="100">Panen Segar Dari Petani, Langsung ke Rumah Anda<br></p>
        <img src="assets/img/kangsayur.png" class="img-fluid hero-img" alt="" data-aos="zoom-out" data-aos-delay="300">
        <div class="d-flex" data-aos="fade-up" data-aos-delay="200">
          <a href="#about" class="btn-get-started">Get Started</a>
          <?php if (!$is_logged_in): ?>
              <a href="login/login.php" class="btn-get-started ms-2">Login</a>
              <a href="register.php" class="btn-get-started ms-2">Daftar</a>
          <?php else: ?>
              <?php if ($user_role === 'admin'): ?>
                  <a class="btn-get-started ms-2" href="admin/admin_dashboard.php">Dashboard Admin</a>
              <?php elseif ($user_role === 'penjual'): ?>
                  <a class="btn-get-started ms-2" href="seller/seller_dashboard.php">Dashboard Penjual</a>
              <?php elseif ($user_role === 'pembeli'): ?>
                  <a class="btn-get-started ms-2" href="pembeli/dashboard_pembeli.php">Dashboard Pembeli</a>
              <?php endif; ?>
              <a class="btn-get-started ms-2" href="login/logout.php">Logout</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <section id="about" class="about section mb-5">
    <div class="container">
      <div class="row gy-4">
        <div class="col-lg-6 content" data-aos="fade-up" data-aos-delay="100">
          <p class="who-we-are" style="font-weight:900; color:#000;">About Us</p>
          <h3 style="font-weight:900; color:#000;">Panen Segar dari Petani, Langsung ke Rumah Anda</h3>
          <p style="font-weight:900; color:#000;">
            <strong>KangSayur.com</strong> adalah platform modern yang menghubungkan Anda langsung dengan petani lokal di seluruh Indonesia. Kami percaya bahwa hasil panen terbaik berasal dari tangan-tangan petani yang berdedikasi, dan misi kami adalah memastikan Anda mendapatkan produk segar dengan kualitas terbaik.
          </p>
          <ul>
            <li><i class="bi bi-check-circle"></i> <span style="font-weight:900; color:#000;">Memperkuat kesejahteraan petani lokal dengan akses pasar yang lebih luas.</span></li>
            <li><i class="bi bi-check-circle"></i> <span style="font-weight:900; color:#000;">Menyediakan produk segar dari lahan pertanian ke konsumen tanpa perantara.</span></li>
            <li><i class="bi bi-check-circle"></i> <span style="font-weight:900; color:#000;">Mendorong konsumsi pangan lokal yang sehat dan berkualitas tinggi.</span></li>
          </ul>
          <a href="about.php" class="read-more"><span style="font-weight:900; color:#000;">Read More</span><i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="col-lg-6 about-images" data-aos="fade-up" data-aos-delay="200">
          <div class="row gy-4">
            <img src="assets/img/sayurabout.png" class="img-fluid" alt="">
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="contact" class="contact section mt-5 mb-5">
    <div class="container section-title" data-aos="fade-up">
      <h2>Contact</h2>
    </div>
    <div class="container" data-aos="fade-up" data-aos-delay="100">
      <div class="row gy-4">
        <div class="col-lg-6">
          <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="200">
            <i class="bi bi-geo-alt"></i>
            <h3>Address</h3>
            <p>Indonesia</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="300">
            <i class="bi bi-telephone"></i>
            <h3>Call Us</h3>
            <p>+62 812345678</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="400">
            <i class="bi bi-envelope"></i>
            <h3>Email Us</h3>
            <p>KangSayur@gmail.com</p>
          </div>
        </div>
      </div>

      <div class="row gy-4 mt-1">
        <div class="col-lg" data-aos="fade-up" data-aos-delay="300">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3588.5583!2d107.29177579999999!3d-6.3262691999999995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69818818813a45%3A0x6b301777d19c017d!2sUniversitas%20Pelita%20Bangsa%20Kampus%20Karawang!5e0!3m2!1sid!2sid!4v1701388458913!5m2!1sid!2sid" width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
      </div>
    </div>
  </section>

</main>

<?php include_once __DIR__ . '/include/footer.php'; ?>
</body>
</html>
