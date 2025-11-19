<?php
session_start();
require_once realpath(__DIR__ . '/../loginphp/db.php');

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  die("Bạn không có quyền truy cập trang này.");
}

// Lấy danh sách tất cả người dùng
$result = $conn->query("SELECT id, name, email, role, avatar FROM users ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý người dùng - Admin Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="icon" href="../logo.png" type="image/png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #eaf9ff;
      /* very pale blue */
      --primary-dark: #7fc9ef;
      /* mid blue */
      --primary-strong: #58b0e6;
      /* stronger blue */
      --bg: #ffffff;
      --text: #1f3f5a;
      --muted: #6c757d;
      --success: #27ae60;
      --danger: #e74c3c;
      --radius: 12px;
      --shadow: 0 8px 32px rgba(31, 38, 135, 0.06);
      --transition: all 0.25s ease;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(180deg, var(--primary), #f7fdff 60%);
      color: var(--text);
      min-height: 100vh;
      line-height: 1.6;
    }

    /* keep main layout consistent with other admin pages */
    .main-container {
      margin-left: 240px;
      padding: 2rem;
      min-height: 100vh;
      transition: var(--transition)
    }

    body.sidebar-collapsed .main-container {
      margin-left: 0
    }

    .header {
      background: var(--bg);
      backdrop-filter: blur(10px);
      border-radius: 18px;
      padding: 28px;
      margin-bottom: 24px;
      box-shadow: var(--shadow);
      border: 1px solid rgba(0, 0, 0, 0.03)
    }

    .header h1 {
      font-size: 2.25rem;
      font-weight: 700;
      background: linear-gradient(90deg, var(--primary-strong), var(--primary-dark));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      display: flex;
      align-items: center;
      gap: 12px
    }

    .header p {
      color: var(--muted);
      margin-top: 6px
    }

    .search-container {
      background: var(--bg);
      padding: 18px;
      border-radius: 14px;
      box-shadow: var(--shadow);
      border: 1px solid rgba(0, 0, 0, 0.03);
      margin-bottom: 20px
    }

    .search-box {
      position: relative;
      max-width: 480px
    }

    .search-box input {
      width: 100%;
      padding: 12px 16px 12px 44px;
      border: 1px solid #e6eef6;
      border-radius: 10px;
      font-size: 1rem;
      background: #fff
    }

    .search-box i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted)
    }

    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 16px;
      margin-bottom: 20px
    }

    .stat-card {
      background: var(--bg);
      padding: 18px;
      border-radius: 12px;
      box-shadow: var(--shadow);
      text-align: center;
      border: 1px solid rgba(0, 0, 0, 0.03)
    }

    .stat-card i {
      font-size: 1.8rem;
      margin-bottom: 8px;
      background: linear-gradient(90deg, var(--primary-strong), var(--primary-dark));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent
    }

    .stat-card h3 {
      font-size: 1.6rem;
      margin: 6px 0;
      color: var(--text)
    }

    .stat-card p {
      color: var(--muted)
    }

    .table-container {
      background: var(--bg);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: var(--shadow);
      border: 1px solid rgba(0, 0, 0, 0.03)
    }

    table {
      width: 100%;
      border-collapse: collapse
    }

    th {
      background: linear-gradient(90deg, var(--primary-dark), var(--primary));
      color: var(--text);
      padding: 14px 12px;
      text-align: left;
      font-weight: 700;
      font-size: 0.9rem;
      text-transform: uppercase
    }

    th:first-child {
      border-top-left-radius: 12px
    }

    th:last-child {
      border-top-right-radius: 12px
    }

    td {
      padding: 14px 12px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.04);
      vertical-align: middle
    }

    tr {
      transition: var(--transition)
    }

    tr:hover {
      background: linear-gradient(90deg, rgba(127, 201, 239, 0.05), rgba(233, 249, 255, 0.04));
      transform: scale(1.005)
    }

    .avatar-container {
      display: flex;
      align-items: center;
      gap: 12px
    }

    img.avatar {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--bg);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06)
    }

    .no-avatar {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: linear-gradient(90deg, var(--primary-strong), var(--primary-dark));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700
    }

    .user-info {
      display: flex;
      flex-direction: column
    }

    .user-name {
      font-weight: 700;
      color: var(--text);
      margin-bottom: 4px
    }

    .user-email {
      color: var(--muted);
      font-size: 0.9rem
    }

    .role-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 14px;
      border-radius: 999px;
      font-weight: 700;
      font-size: 0.85rem;
      text-transform: uppercase
    }

    .role-admin {
      background: linear-gradient(90deg, #dff7e6, #baf0c9);
      color: var(--text)
    }

    .role-user {
      background: linear-gradient(90deg, #fff1f1, #ffdede);
      color: var(--text)
    }

    .action-buttons {
      display: flex;
      gap: 8px;
      align-items: center
    }

    .btn {
      padding: 8px 14px;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 8px
    }

    .btn-edit {
      background: linear-gradient(90deg, var(--primary-strong), var(--primary-dark));
      color: var(--bg)
    }

    .btn-delete {
      background: linear-gradient(90deg, #ffdede, #ffcfcf);
      color: var(--text);
      border: 1px solid rgba(0, 0, 0, 0.04)
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 18px rgba(0, 0, 0, 0.06)
    }

    .current-user {
      background: linear-gradient(90deg, rgba(127, 201, 239, 0.06), rgba(233, 249, 255, 0.04));
      border-left: 4px solid var(--primary-dark)
    }

    .current-user-badge {
      background: linear-gradient(90deg, #fff3db, #ffe6b3);
      padding: 6px 10px;
      border-radius: 12px;
      font-weight: 700;
      color: var(--text)
    }

    .back-btn {
      position: fixed;
      top: 22px;
      left: 22px;
      background: var(--bg);
      padding: 10px 14px;
      border-radius: 12px;
      border: 1px solid rgba(0, 0, 0, 0.04);
      box-shadow: var(--shadow);
      z-index: 1200
    }

    .back-btn i {
      margin-right: 8px
    }

    @media(max-width:768px) {
      .main-container {
        margin-left: 0;
        padding: 16px
      }

      .header h1 {
        font-size: 1.6rem
      }

      .stats-container {
        grid-template-columns: 1fr
      }

      .table-container {
        overflow-x: auto
      }

      table {
        min-width: 640px
      }

      .back-btn {
        top: 12px;
        left: 12px;
        padding: 8px 10px
      }
    }

    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 12px 16px;
      border-radius: 10px;
      color: white;
      z-index: 2000;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      transform: translateX(400px);
      transition: all .3s ease
    }

    .notification.show {
      transform: translateX(0)
    }

    .notification.success {
      background: linear-gradient(90deg, #2ecc71, #27ae60)
    }

    .notification.error {
      background: linear-gradient(90deg, #e74c3c, #c0392b)
    }

    .loading {
      display: none;
      text-align: center;
      padding: 20px;
      color: var(--muted)
    }
  </style>
</head>

<body>
  <?php include '../includes/admin_navbar.php'; ?>
  <?php include '../includes/admin_sidebar.php'; ?>

  <a href="javascript:history.back()" class="back-btn">
    <i class="fas fa-arrow-left"></i>
  </a>

  <div class="main-container fade-in">
    <!-- Header Section -->
    <div class="header">
      <h1>
        <i class="fas fa-users-cog"></i>
        Quản lý người dùng
      </h1>
      <p>Quản lý và theo dõi tất cả tài khoản người dùng trong hệ thống</p>
    </div>

    <!-- Search Section -->
    <div class="search-container">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Tìm kiếm theo tên, email hoặc ID...">
      </div>
    </div>

    <!-- Statistics Section -->
    <div class="stats-container">
      <?php
      // Đếm tổng số người dùng
      $totalUsers = $result->num_rows;

      // Đếm admin và user
      $adminCount = 0;
      $userCount = 0;
      $result->data_seek(0); // Reset pointer
      while ($row = $result->fetch_assoc()) {
        if ($row['role'] === 'admin') {
          $adminCount++;
        } else {
          $userCount++;
        }
      }
      $result->data_seek(0); // Reset pointer for table display
      ?>

      <div class="stat-card">
        <i class="fas fa-users"></i>
        <h3 id="totalUsers"><?= $totalUsers ?></h3>
        <p>Tổng người dùng</p>
      </div>

      <div class="stat-card">
        <i class="fas fa-user-shield"></i>
        <h3 id="adminCount"><?= $adminCount ?></h3>
        <p>Quản trị viên</p>
      </div>

      <div class="stat-card">
        <i class="fas fa-user"></i>
        <h3 id="userCount"><?= $userCount ?></h3>
        <p>Người dùng thường</p>
      </div>
    </div>

    <!-- Table Section -->
    <div class="table-container">
      <table id="usersTable">
        <thead>
          <tr>
            <th><i class="fas fa-hashtag"></i> ID</th>
            <th><i class="fas fa-user-circle"></i> Thông tin</th>
            <th><i class="fas fa-envelope"></i> Email</th>
            <th><i class="fas fa-shield-alt"></i> Quyền</th>
            <th><i class="fas fa-cogs"></i> Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr data-id-uid="<?= $row['id'] ?>" class="<?= $row['id'] == $_SESSION['user']['id'] ? 'current-user' : '' ?>">
              <td>
                <strong>#<?= $row['id'] ?></strong>
                <?php if ($row['id'] == $_SESSION['user']['id']): ?>
                  <br><span class="current-user-badge">Bạn</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="avatar-container">
                  <?php if (!empty($row['avatar'])): ?>
                    <img src="<?= htmlspecialchars($row['avatar']) ?>" class="avatar" alt="Avatar">
                  <?php else: ?>
                    <div class="no-avatar">
                      <?= strtoupper(substr($row['name'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                  <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($row['name']) ?></div>
                    <div class="user-email">ID: <?= $row['id'] ?></div>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-email"><?= htmlspecialchars($row['email']) ?></div>
              </td>
              <td>
                <span class="role-badge <?= $row['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                  <i class="fas <?= $row['role'] === 'admin' ? 'fa-user-shield' : 'fa-user' ?>"></i>
                  <?= ucfirst($row['role']) ?>
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <?php if ($row['id'] != $_SESSION['user']['id']): ?>
                    <button class="btn btn-edit" onclick="editUser(<?= $row['id'] ?>)">
                      <i class="fas fa-edit"></i>
                      <span>Sửa</span>
                    </button>
                    <button class="btn btn-delete btn-delete-user" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['name']) ?>">
                      <i class="fas fa-trash"></i>
                      <span>Xóa</span>
                    </button>
                  <?php else: ?>
                    <span class="current-user-badge">
                      <i class="fas fa-lock"></i> Tài khoản của bạn
                    </span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="loading" id="loading">
      <i class="fas fa-spinner fa-spin"></i> Đang tải...
    </div>
  </div>

  <script>
    // Notification helper
    function showNotification(message, type = 'success') {
      const n = document.createElement('div');
      n.className = 'notification ' + type;
      n.innerHTML = `<span>${message}</span>`;
      document.body.appendChild(n);
      setTimeout(() => n.classList.add('show'), 50);
      setTimeout(() => {
        n.classList.remove('show');
        setTimeout(() => n.remove(), 300);
      }, 3000);
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const table = document.getElementById('usersTable');
      const rows = table.getElementsByTagName('tr');

      let visibleCount = 0;

      for (let i = 1; i < rows.length; i++) { // Skip header row
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;

        for (let j = 0; j < cells.length; j++) {
          if (cells[j].textContent.toLowerCase().includes(searchTerm)) {
            found = true;
            break;
          }
        }

        if (found) {
          row.style.display = '';
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      }

      // Update stats
      document.getElementById('totalUsers').textContent = visibleCount;
    });

    // Edit user function
    function editUser(userId) {
      // You can implement edit functionality here
      alert('Chức năng chỉnh sửa người dùng ID: ' + userId + ' sẽ được triển khai');
    }

    // Enhanced delete confirmation
    function confirmDelete(userName) {
      return confirm(`Bạn có chắc chắn muốn xóa tài khoản "${userName}"?\n\nHành động này không thể hoàn tác!`);
    }

    // Add loading animation
    window.addEventListener('load', function() {
      document.body.classList.add('loaded');
    });

    // AJAX delete user
    document.querySelectorAll('.btn-delete-user').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        if (!confirm(`Bạn có chắc chắn muốn xóa tài khoản "${name}"?`)) return;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xóa...';

        fetch('delete_user.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'user_id=' + encodeURIComponent(id) + '&ajax=1'
        }).then(r => r.text()).then(res => {
          if (res.trim() === 'success') {
            const row = document.querySelector('#usersTable tr[data-id-uid="' + id + '"]');
            if (row) row.remove();
            showNotification('Đã xóa tài khoản: ' + name, 'success');
            // update counters
            const totalEl = document.getElementById('totalUsers');
            if (totalEl) totalEl.textContent = parseInt(totalEl.textContent) - 1;
          } else {
            showNotification('Xóa thất bại, thử lại.', 'error');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-trash"></i> Xóa';
          }
        }).catch(() => {
          showNotification('Lỗi kết nối.', 'error');
          this.disabled = false;
          this.innerHTML = '<i class="fas fa-trash"></i> Xóa';
        });
      });
    });

    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        });
      });
    });
  </script>
</body>

</html>