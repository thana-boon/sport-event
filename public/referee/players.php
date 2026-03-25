<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ✅ อนุญาตทั้ง 'referee' และ 'admin'
if (empty($_SESSION['referee']) || !in_array(($_SESSION['referee']['role'] ?? ''), ['referee', 'admin'], true)) {
  header('Location: ' . BASE_URL . '/referee/login.php'); 
  exit;
}

// ✅ เช็ค session timeout (60 นาที)
$timeout = 3600; // 60 นาที
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
  log_activity('LOGOUT', 'users', $_SESSION['referee']['id'] ?? null, 
    'ออกจากระบบอัตโนมัติ (session timeout 60 นาที - referee) | Username: ' . ($_SESSION['referee']['username'] ?? 'unknown') . ' | Role: ' . ($_SESSION['referee']['role'] ?? 'unknown'));
  
  session_unset();
  session_destroy();
  header('Location: ' . BASE_URL . '/referee/login.php?timeout=1');
  exit;
}
$_SESSION['last_activity'] = time();

$pdo = db();
$yearId = active_year_id($pdo);
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>รายชื่อนักกีฬา | ผู้ตัดสิน</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
  <link rel="shortcut icon" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --brand: #0ea5e9;
      --brand-600: #0284c7;
      --brand-700: #0369a1;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --substituted: #fef3c7;
      --substituted-border: #f59e0b;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Kanit', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      min-height: 100vh;
    }
    
    /* Header */
    header.app {
      background: linear-gradient(135deg, var(--brand) 0%, var(--brand-700) 100%);
      color: #fff;
      box-shadow: 0 4px 20px rgba(14, 165, 233, 0.3);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    
    header.app .container {
      padding: 1.5rem 1rem;
    }
    
    .header-title {
      font-size: 1.5rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .header-icon {
      font-size: 2rem;
    }
    
    /* Navigation */
    .nav-tabs {
      background: white;
      padding: 1rem 1rem 0;
      border-bottom: 2px solid #e2e8f0;
      border-radius: 1rem 1rem 0 0;
      margin-bottom: 0;
    }
    
    .nav-tabs .nav-link {
      color: #64748b;
      border: none;
      border-bottom: 3px solid transparent;
      padding: 0.75rem 1.5rem;
      font-weight: 500;
      transition: all 0.2s;
      border-radius: 0.5rem 0.5rem 0 0;
    }
    
    .nav-tabs .nav-link:hover {
      color: var(--brand);
      background: #f0f9ff;
    }
    
    .nav-tabs .nav-link.active {
      color: var(--brand);
      background: white;
      border-bottom-color: var(--brand);
      font-weight: 600;
    }
    
    /* Cards */
    .card {
      border: none;
      border-radius: 1.25rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
    }
    
    .card:hover {
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .card-header {
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
      border: none;
      padding: 1.25rem 1.5rem;
      font-weight: 600;
      color: var(--brand-700);
    }
    
    /* Filter Section */
    .filter-card {
      background: white;
      margin-bottom: 1.5rem;
    }
    
    .form-label {
      font-weight: 500;
      color: #64748b;
      font-size: 0.875rem;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .form-select, .form-control {
      border: 2px solid #e2e8f0;
      border-radius: 0.75rem;
      padding: 0.625rem 1rem;
      transition: all 0.2s;
      font-size: 0.95rem;
    }
    
    .form-select:focus, .form-control:focus {
      border-color: var(--brand);
      box-shadow: 0 0 0 0.2rem rgba(14, 165, 233, 0.15);
    }
    
    /* Table */
    .table-responsive {
      border-radius: 1rem;
      overflow-x: auto;
      overflow-y: hidden;
      -webkit-overflow-scrolling: touch;
    }
    
    .table {
      margin-bottom: 0;
      min-width: 900px;
      white-space: nowrap;
    }
    
    .table thead {
      background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
      position: sticky;
      top: 0;
      z-index: 10;
    }
    
    .table thead th {
      border: none;
      padding: 1rem 0.75rem;
      font-weight: 600;
      color: var(--brand-700);
      text-transform: uppercase;
      font-size: 0.8rem;
      letter-spacing: 0.5px;
    }
    
    .table tbody tr {
      transition: all 0.2s;
      border-left: 3px solid transparent;
    }
    
    .table tbody tr:hover {
      background: #f0f9ff !important;
      transform: translateX(4px);
      border-left-color: var(--brand);
      box-shadow: 0 2px 8px rgba(14, 165, 233, 0.1);
    }
    
    .table tbody td {
      padding: 1rem 0.75rem;
      vertical-align: middle;
      border-color: #f1f5f9;
    }
    
    /* Substituted Row Highlight */
    .table tbody tr.substituted {
      background: var(--substituted) !important;
      border-left-color: var(--substituted-border);
    }
    
    .table tbody tr.substituted:hover {
      background: #fde68a !important;
      border-left-color: var(--substituted-border);
    }
    
    /* Badges */
    .badge {
      padding: 0.5rem 1rem;
      border-radius: 2rem;
      font-weight: 500;
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .badge-substituted {
      background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
      color: white;
      box-shadow: 0 2px 10px rgba(245, 158, 11, 0.3);
    }
    
    /* Color Chips */
    .color-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.375rem 0.875rem;
      border-radius: 2rem;
      font-weight: 500;
      font-size: 0.9rem;
    }
    
    .color-dot {
      width: 1rem;
      height: 1rem;
      border-radius: 50%;
      display: inline-block;
      border: 2px solid rgba(255,255,255,0.5);
      box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
    }
    
    .color-chip.orange {
      background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
      color: white;
    }
    
    .color-chip.green {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
    }
    
    .color-chip.pink {
      background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
      color: white;
    }
    
    .color-chip.blue {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
    }
    
    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: #94a3b8;
    }
    
    .empty-state i {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    
    /* Loading */
    .loading {
      text-align: center;
      padding: 2rem;
    }
    
    .spinner-border {
      width: 3rem;
      height: 3rem;
    }
    
    /* Logout Button */
    .btn-logout {
      background: rgba(255,255,255,0.2);
      border: 2px solid rgba(255,255,255,0.3);
      color: white;
      padding: 0.5rem 1.25rem;
      border-radius: 0.75rem;
      font-weight: 500;
      transition: all 0.2s;
    }
    
    .btn-logout:hover {
      background: rgba(255,255,255,0.3);
      border-color: rgba(255,255,255,0.5);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    /* Pagination */
    .pagination-wrapper {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem;
      background: white;
      border-top: 2px solid #e2e8f0;
      flex-wrap: wrap;
      gap: 1rem;
    }
    
    .pagination {
      margin: 0;
    }
    
    .pagination .page-link {
      border: 2px solid #e2e8f0;
      color: var(--brand);
      padding: 0.5rem 0.875rem;
      margin: 0 0.25rem;
      border-radius: 0.5rem;
      font-weight: 500;
      transition: all 0.2s;
    }
    
    .pagination .page-link:hover {
      background: #f0f9ff;
      border-color: var(--brand);
      transform: translateY(-2px);
    }
    
    .pagination .page-item.active .page-link {
      background: var(--brand);
      border-color: var(--brand);
      color: white;
      box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
    }
    
    .pagination .page-item.disabled .page-link {
      background: #f8fafc;
      border-color: #e2e8f0;
      color: #cbd5e1;
    }
    
    .page-size-selector {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    .page-size-selector select {
      border: 2px solid #e2e8f0;
      border-radius: 0.5rem;
      padding: 0.5rem 2rem 0.5rem 0.75rem;
      font-weight: 500;
      color: var(--brand-700);
      cursor: pointer;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .header-title {
        font-size: 1.25rem;
      }
      
      .table {
        font-size: 0.875rem;
      }
      
      .nav-tabs .nav-link {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
      }
      
      .pagination-wrapper {
        flex-direction: column;
        align-items: stretch;
      }
      
      .pagination {
        justify-content: center;
        flex-wrap: wrap;
      }
      
      .page-size-selector {
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="app">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center">
        <div class="header-title">
          <span class="header-icon">👥</span>
          <span>รายชื่อนักกีฬา</span>
        </div>
        <div class="d-flex align-items-center gap-3">
          <span class="d-none d-md-inline">👋 <?= htmlspecialchars($_SESSION['referee']['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
          <a href="<?= BASE_URL ?>/referee/logout.php" class="btn btn-logout btn-sm">ออกจากระบบ</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Navigation Tabs -->
  <div class="container mt-3">
    <ul class="nav nav-tabs">
      <li class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/referee/index.php">📝 บันทึกผล</a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="<?= BASE_URL ?>/referee/players.php">👥 รายชื่อนักกีฬา</a>
      </li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="container pb-5">
    <div class="card filter-card">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">
              <span>🔍</span>
              ค้นหาชื่อนักกีฬา
            </label>
            <input type="text" class="form-control" id="searchStudent" placeholder="พิมพ์ชื่อ-นามสกุล หรือรหัสนักเรียน">
          </div>
          <div class="col-md-3">
            <label class="form-label">
              <span>🔎</span>
              ค้นหาชื่อกีฬา
            </label>
            <input type="text" class="form-control" id="searchSport" placeholder="พิมพ์ชื่อกีฬา">
          </div>
          <div class="col-md-3">
            <label class="form-label">
              <span>🏅</span>
              ประเภทกีฬา
            </label>
            <select class="form-select" id="filterCategory">
              <option value="">-- ทุกประเภท --</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">
              <span>⚽</span>
              รายการกีฬา
            </label>
            <select class="form-select" id="filterSport">
              <option value="">-- ทุกรายการ --</option>
            </select>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-md-3">
            <label class="form-label">
              <span>🎨</span>
              สี
            </label>
            <select class="form-select" id="filterColor">
              <option value="">-- ทุกสี --</option>
              <option value="ส้ม">ส้ม</option>
              <option value="เขียว">เขียว</option>
              <option value="ชมพู">ชมพู</option>
              <option value="ฟ้า">ฟ้า</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">
              <span>🔄</span>
              สถานะ
            </label>
            <select class="form-select" id="filterSubstituted">
              <option value="">-- ทั้งหมด --</option>
              <option value="yes">มีการเปลี่ยนตัว</option>
              <option value="no">ไม่มีการเปลี่ยนตัว</option>
            </select>
          </div>
          <div class="col-md-3"></div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-secondary w-100" id="btnReset">
              🔄 รีเซ็ต
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>📋 รายชื่อนักกีฬา</span>
        <span class="badge bg-primary" id="totalCount">0 คน</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th style="width: 5%">#</th>
              <th style="width: 10%">รหัส</th>
              <th style="width: 15%">ชื่อ-นามสกุล</th>
              <th style="width: 10%">ชั้น</th>
              <th style="width: 10%">สี</th>
              <th style="width: 20%">รายการกีฬา</th>
              <th style="width: 15%">ประเภท</th>
              <th style="width: 15%">สถานะ</th>
            </tr>
          </thead>
          <tbody id="resultsBody">
            <tr>
              <td colspan="8" class="loading">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">กำลังโหลด...</span>
                </div>
                <div class="mt-2">กำลังโหลดข้อมูล...</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="pagination-wrapper">
        <div class="page-size-selector">
          <label for="pageSize" style="color: #64748b; font-weight: 500;">แสดง:</label>
          <select id="pageSize" class="form-select form-select-sm">
            <option value="25">25</option>
            <option value="50" selected>50</option>
            <option value="100">100</option>
            <option value="200">200</option>
            <option value="500">500</option>
          </select>
          <span style="color: #64748b; font-weight: 500;">รายการ/หน้า</span>
        </div>
        
        <nav aria-label="Page navigation">
          <ul class="pagination mb-0" id="pagination">
          </ul>
        </nav>
        
        <div style="color: #64748b; font-weight: 500;" id="pageInfo">
          แสดง 0-0 จาก 0 รายการ
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let allData = [];
    let categories = [];
    let sports = [];
    let currentPage = 1;
    let pageSize = 50;
    let filteredData = [];

    // โหลดข้อมูลเริ่มต้น
    async function loadData() {
      try {
        const response = await fetch('<?= BASE_URL ?>/referee/fetch_players.php', {
          method: 'GET',
          headers: { 'Content-Type': 'application/json' },
          cache: 'no-store'
        });
        
        const data = await response.json();
        
        if (data.success) {
          allData = data.players || [];
          categories = data.categories || [];
          sports = data.sports || [];
          
          populateCategories();
          populateSports();
          renderTable(allData);
        } else {
          showError(data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
      } catch (error) {
        console.error('Error:', error);
        showError('ไม่สามารถโหลดข้อมูลได้');
      }
    }

    // แสดงข้อมูลในตาราง
    function renderTable(data) {
      filteredData = data;
      const tbody = document.getElementById('resultsBody');
      const totalCount = document.getElementById('totalCount');
      
      if (!data || data.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="8" class="empty-state">
              <div style="font-size: 3rem; opacity: 0.3;">🔍</div>
              <h5>ไม่พบข้อมูล</h5>
              <p>ลองค้นหาด้วยคำค้นอื่น หรือปรับเงื่อนไขการกรอง</p>
            </td>
          </tr>
        `;
        totalCount.textContent = '0 คน';
        updatePagination();
        return;
      }
      
      // คำนวณข้อมูลในหน้าปัจจุบัน
      const startIndex = (currentPage - 1) * pageSize;
      const endIndex = Math.min(startIndex + pageSize, data.length);
      const pageData = data.slice(startIndex, endIndex);
      
      let html = '';
      pageData.forEach((player, index) => {
        const globalIndex = startIndex + index + 1;
        const rowClass = player.is_substituted ? 'substituted' : '';
        const colorClass = player.color === 'ส้ม' ? 'orange' : 
                          player.color === 'เขียว' ? 'green' : 
                          player.color === 'ชมพู' ? 'pink' : 'blue';
        
        const statusBadge = player.is_substituted 
          ? `<span class="badge badge-substituted">🔄 เปลี่ยนตัว</span>
             <div style="font-size: 0.75rem; color: #78716c; margin-top: 0.25rem;">
               เดิม: ${escapeHtml(player.old_student_name || '-')}
             </div>`
          : `<span class="badge bg-success">✓ ปกติ</span>`;
        
        html += `
          <tr class="${rowClass}">
            <td>${globalIndex}</td>
            <td>${escapeHtml(player.student_code)}</td>
            <td><strong>${escapeHtml(player.student_name)}</strong></td>
            <td>${escapeHtml(player.class_level)} / ${escapeHtml(player.class_room)}</td>
            <td>
              <span class="color-chip ${colorClass}">
                <i class="color-dot"></i>
                ${escapeHtml(player.color)}
              </span>
            </td>
            <td>${escapeHtml(player.sport_name)}</td>
            <td>${escapeHtml(player.category_name)}</td>
            <td>${statusBadge}</td>
          </tr>
        `;
      });
      
      tbody.innerHTML = html;
      totalCount.textContent = `${data.length} คน`;
      updatePagination();
    }

    // เติมข้อมูลประเภทกีฬา
    function populateCategories() {
      const select = document.getElementById('filterCategory');
      categories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name;
        select.appendChild(option);
      });
    }

    // เติมข้อมูลรายการกีฬา
    function populateSports() {
      const select = document.getElementById('filterSport');
      sports.forEach(sport => {
        const option = document.createElement('option');
        option.value = sport.id;
        option.textContent = sport.name;
        option.dataset.categoryId = sport.category_id;
        select.appendChild(option);
      });
    }

    // อัพเดท Pagination UI
    function updatePagination() {
      const totalItems = filteredData.length;
      const totalPages = Math.ceil(totalItems / pageSize);
      const paginationEl = document.getElementById('pagination');
      const pageInfoEl = document.getElementById('pageInfo');
      
      // อัพเดทข้อมูลหน้า
      const startItem = totalItems === 0 ? 0 : (currentPage - 1) * pageSize + 1;
      const endItem = Math.min(currentPage * pageSize, totalItems);
      pageInfoEl.textContent = `แสดง ${startItem}-${endItem} จาก ${totalItems} รายการ`;
      
      if (totalPages <= 1) {
        paginationEl.innerHTML = '';
        return;
      }
      
      let html = '';
      
      // ปุ่ม Previous
      html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="goToPage(${currentPage - 1}); return false;">
            ‹ ก่อนหน้า
          </a>
        </li>
      `;
      
      // เลขหน้า
      const maxButtons = 5;
      let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
      let endPage = Math.min(totalPages, startPage + maxButtons - 1);
      
      if (endPage - startPage < maxButtons - 1) {
        startPage = Math.max(1, endPage - maxButtons + 1);
      }
      
      if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(1); return false;">1</a></li>`;
        if (startPage > 2) {
          html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
      }
      
      for (let i = startPage; i <= endPage; i++) {
        html += `
          <li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${i}); return false;">
              ${i}
            </a>
          </li>
        `;
      }
      
      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${totalPages}); return false;">${totalPages}</a></li>`;
      }
      
      // ปุ่ม Next
      html += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="goToPage(${currentPage + 1}); return false;">
            ถัดไป ›
          </a>
        </li>
      `;
      
      paginationEl.innerHTML = html;
    }
    
    // ไปหน้าที่กำหนด
    function goToPage(page) {
      const totalPages = Math.ceil(filteredData.length / pageSize);
      if (page < 1 || page > totalPages) return;
      
      currentPage = page;
      renderTable(filteredData);
      
      // Scroll to top
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // กรองข้อมูล
    function filterData() {
      const searchText = document.getElementById('searchStudent').value.toLowerCase();
      const searchSport = document.getElementById('searchSport').value.toLowerCase();
      const categoryId = document.getElementById('filterCategory').value;
      const sportId = document.getElementById('filterSport').value;
      const color = document.getElementById('filterColor').value;
      const substituted = document.getElementById('filterSubstituted').value;
      
      let filtered = allData;
      
      // ค้นหาชื่อนักเรียน
      if (searchText) {
        filtered = filtered.filter(p => 
          p.student_name.toLowerCase().includes(searchText) ||
          p.student_code.toLowerCase().includes(searchText)
        );
      }
      
      // ค้นหาชื่อกีฬา
      if (searchSport) {
        filtered = filtered.filter(p => 
          p.sport_name.toLowerCase().includes(searchSport)
        );
      }
      
      // กรองตามประเภทกีฬา
      if (categoryId) {
        filtered = filtered.filter(p => p.category_id == categoryId);
      }
      
      // กรองตามรายการกีฬา
      if (sportId) {
        filtered = filtered.filter(p => p.sport_id == sportId);
      }
      
      // กรองตามสี
      if (color) {
        filtered = filtered.filter(p => p.color === color);
      }
      
      // กรองตามสถานะการเปลี่ยนตัว
      if (substituted === 'yes') {
        filtered = filtered.filter(p => p.is_substituted);
      } else if (substituted === 'no') {
        filtered = filtered.filter(p => !p.is_substituted);
      }
      
      currentPage = 1; // รีเซ็ตไปหน้า 1 เมื่อกรองข้อมูล
      renderTable(filtered);
    }

    // รีเซ็ตฟิลเตอร์
    function resetFilters() {
      document.getElementById('searchStudent').value = '';
      document.getElementById('searchSport').value = '';
      document.getElementById('filterCategory').value = '';
      document.getElementById('filterSport').value = '';
      document.getElementById('filterColor').value = '';
      document.getElementById('filterSubstituted').value = '';
      currentPage = 1;
      renderTable(allData);
    }

    // แสดงข้อผิดพลาด
    function showError(message) {
      const tbody = document.getElementById('resultsBody');
      tbody.innerHTML = `
        <tr>
          <td colspan="8" class="text-center text-danger p-4">
            <div style="font-size: 2rem;">❌</div>
            <div class="mt-2">${escapeHtml(message)}</div>
          </td>
        </tr>
      `;
    }

    // Escape HTML
    function escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // Event Listeners
    document.getElementById('searchStudent').addEventListener('input', filterData);
    document.getElementById('searchSport').addEventListener('input', filterData);
    document.getElementById('filterCategory').addEventListener('change', function() {
      // เมื่อเปลี่ยนประเภท ให้กรองรายการกีฬาด้วย
      const categoryId = this.value;
      const sportSelect = document.getElementById('filterSport');
      
      Array.from(sportSelect.options).forEach(option => {
        if (option.value === '') return;
        if (!categoryId || option.dataset.categoryId == categoryId) {
          option.style.display = '';
        } else {
          option.style.display = 'none';
        }
      });
      
      // รีเซ็ตตัวเลือกรายการกีฬาถ้าไม่ตรงกับประเภท
      if (categoryId && sportSelect.value) {
        const selectedOption = sportSelect.options[sportSelect.selectedIndex];
        if (selectedOption.dataset.categoryId != categoryId) {
          sportSelect.value = '';
        }
      }
      
      filterData();
    });
    document.getElementById('filterSport').addEventListener('change', filterData);
    document.getElementById('filterColor').addEventListener('change', filterData);
    document.getElementById('filterSubstituted').addEventListener('change', filterData);
    document.getElementById('btnReset').addEventListener('click', resetFilters);
    document.getElementById('pageSize').addEventListener('change', function() {
      pageSize = parseInt(this.value);
      currentPage = 1;
      renderTable(filteredData);
    });

    // โหลดข้อมูลเมื่อเริ่มต้น
    loadData();
  </script>
</body>
</html>
