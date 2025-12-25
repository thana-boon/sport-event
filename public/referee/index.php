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

// ✅ เช็ค session timeout (30 นาที)
$timeout = 1800; // 30 นาที
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
  // 🔥 LOG: Session timeout
  log_activity('LOGOUT', 'users', $_SESSION['referee']['id'] ?? null, 
    'ออกจากระบบอัตโนมัติ (session timeout 30 นาที - referee) | Username: ' . ($_SESSION['referee']['username'] ?? 'unknown') . ' | Role: ' . ($_SESSION['referee']['role'] ?? 'unknown'));
  
  session_unset();
  session_destroy();
  header('Location: ' . BASE_URL . '/referee/login.php?timeout=1');
  exit;
}
$_SESSION['last_activity'] = time();
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ผู้ตัดสิน | บันทึกผล</title>
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
      animation: bounce 2s ease-in-out infinite;
    }
    
    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-5px); }
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
    
    /* Badges */
    .badge {
      padding: 0.5rem 1rem;
      border-radius: 2rem;
      font-weight: 500;
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
    }
    
    .badge-saved {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    .badge-nosave {
      background: #e2e8f0;
      color: #64748b;
    }
    
    .badge.bg-secondary {
      background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
      color: white;
      box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    }
    
    /* Color Chip */
    .color-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.4rem 0.9rem;
      border-radius: 2rem;
      background: #f8fafc;
      font-weight: 500;
      border: 2px solid #e2e8f0;
    }
    
    .color-dot {
      width: 0.9rem;
      height: 0.9rem;
      border-radius: 50%;
      display: inline-block;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    /* Buttons */
    .btn {
      border-radius: 0.75rem;
      padding: 0.5rem 1.25rem;
      font-weight: 500;
      transition: all 0.2s;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--brand) 0%, var(--brand-600) 100%);
    }
    
    .btn-light {
      background: white;
      color: var(--brand-700);
      font-weight: 600;
    }
    
    .btn-light:hover {
      background: #f8fafc;
      color: var(--brand);
    }
    
    .btn-outline-danger {
      color: var(--danger);
      border: 2px solid var(--danger);
    }
    
    .btn-outline-danger:hover {
      background: var(--danger);
      color: white;
    }
    
    .btn-group {
      border-radius: 0.75rem;
      overflow: hidden;
    }
    
    /* Empty State */
    .empty-state {
      padding: 4rem 2rem;
      text-align: center;
      color: #64748b;
    }
    
    .empty-icon {
      font-size: 5rem;
      margin-bottom: 1.5rem;
      opacity: 0.5;
      animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
      0%, 100% { opacity: 0.5; }
      50% { opacity: 0.8; }
    }
    
    /* Modal */
    .modal-content {
      border: none;
      border-radius: 1.5rem;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header {
      background: linear-gradient(135deg, var(--brand) 0%, var(--brand-700) 100%);
      color: white;
      border: none;
      padding: 1.5rem 2rem;
      border-radius: 1.5rem 1.5rem 0 0;
    }
    
    .modal-title {
      font-weight: 600;
      font-size: 1.25rem;
    }
    
    .modal-body {
      padding: 2rem;
    }
    
    .modal-footer {
      border: none;
      padding: 1.5rem 2rem;
      background: #f8fafc;
    }
    
    /* Form Controls in Modal */
    .time-input {
      font-family: 'Courier New', monospace;
      font-weight: 600;
      text-align: center;
    }
    
    .rank-sel {
      text-align: center;
      font-weight: 600;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .header-title {
        font-size: 1.25rem;
      }
      
      .table thead th {
        font-size: 0.7rem;
        padding: 0.75rem 0.5rem;
      }
      
      .table tbody td {
        padding: 0.75rem 0.5rem;
        font-size: 0.9rem;
      }
      
      .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
      }
    }
    
    /* Loading Animation */
    @keyframes shimmer {
      0% { background-position: -1000px 0; }
      100% { background-position: 1000px 0; }
    }
    
    .loading {
      background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%);
      background-size: 1000px 100%;
      animation: shimmer 2s infinite;
    }
    
    /* Record Broken Badge */
    @keyframes fireGlow {
      0%, 100% { 
        box-shadow: 0 0 5px rgba(255, 69, 0, 0.5), 0 0 10px rgba(255, 69, 0, 0.3);
        transform: scale(1);
      }
      50% { 
        box-shadow: 0 0 15px rgba(255, 69, 0, 0.8), 0 0 25px rgba(255, 69, 0, 0.5);
        transform: scale(1.05);
      }
    }
    
    @keyframes firePulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.8; }
    }
    
    .record-broken-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      background: linear-gradient(135deg, #ff4500 0%, #ff6347 100%);
      color: white;
      padding: 0.25rem 0.6rem;
      border-radius: 1rem;
      font-size: 0.85rem;
      font-weight: 600;
      margin-left: 0.5rem;
      animation: fireGlow 2s ease-in-out infinite, firePulse 1.5s ease-in-out infinite;
      box-shadow: 0 2px 8px rgba(255, 69, 0, 0.4);
      vertical-align: middle;
    }
    
    .record-broken-badge .fire-icon {
      font-size: 1.1em;
      animation: firePulse 1s ease-in-out infinite;
    }
    
    /* Modal Table Row Colors - Override Bootstrap */
    #resultModal .table tbody tr {
      transition: background-color 0.2s ease;
    }
    
    #resultModal .table tbody tr td {
      background-color: inherit !important;
    }
  </style>
</head>
<body>
<header class="app">
  <div class="container d-flex justify-content-between align-items-center">
    <div class="header-title">
      <span class="header-icon">⚖️</span>
      <span>โหมดผู้ตัดสิน</span>
    </div>
    <a class="btn btn-light" href="<?= BASE_URL ?>/referee/logout.php">
      🚪 ออกจากระบบ
    </a>
  </div>
</header>

<main class="container py-4">
  <!-- Filters -->
  <div class="card filter-card shadow-sm">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label">🏆 ประเภทกีฬา</label>
          <select id="filterCat" class="form-select">
            <option value="">ทั้งหมด</option>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">🎓 ระดับชั้น</label>
          <select id="filterLevel" class="form-select">
            <option value="">ทั้งหมด</option>
            <option value="P">ประถม (ป..)</option>
            <option value="S">มัธยม (ม..)</option>
          </select>
        </div>
        <div class="col-12 col-md-5">
          <label class="form-label">🔍 ค้นหา</label>
          <input id="filterQ" class="form-control" placeholder="พิมพ์ชื่อกีฬา...">
        </div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="sportsTable">
          <thead>
            <tr>
              <th style="width:60px" class="text-center">#️⃣</th>
              <th>🏅 ชื่อกีฬา</th>
              <th style="width:180px">📂 หมวด</th>
              <th style="width:200px">🎓 ระดับชั้น</th>
              <th style="width:140px" class="text-center">📊 สถานะ</th>
              <th style="width:180px" class="text-center">⚙️ จัดการ</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<!-- Modal -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">📝 บันทึกผล</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ ปิด</button>
        <button type="button" class="btn btn-primary" id="btnSave">✅ บันทึก</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let SPORTS=[], COLORS=[{name:'ส้ม',hex:'#FFA726'},{name:'เขียว',hex:'#4CAF50'},{name:'ชมพู',hex:'#EC407A'},{name:'ฟ้า',hex:'#29B6F6'}];

function toast(m, type='info'){ 
  Swal.fire({
    text: m,
    icon: type,
    confirmButtonText: 'ตกลง',
    timer: type === 'success' ? 2000 : undefined,
    timerProgressBar: type === 'success'
  });
}
function levelType(txt){ const t=(txt||'').trim(); if(t.startsWith('ป')) return 'P'; if(t.startsWith('ม')) return 'S'; return ''; }

// บันทึกค่าตัวกรองลง localStorage
function saveFilters() {
  localStorage.setItem('referee_filter_cat', document.getElementById('filterCat').value);
  localStorage.setItem('referee_filter_level', document.getElementById('filterLevel').value);
  localStorage.setItem('referee_filter_q', document.getElementById('filterQ').value);
}

// โหลดค่าตัวกรองจาก localStorage
function loadFilters() {
  const cat = localStorage.getItem('referee_filter_cat');
  const level = localStorage.getItem('referee_filter_level');
  const q = localStorage.getItem('referee_filter_q');
  
  if (cat !== null) document.getElementById('filterCat').value = cat;
  if (level !== null) document.getElementById('filterLevel').value = level;
  if (q !== null) document.getElementById('filterQ').value = q;
}

async function loadInit(){
  const [sRes, cRes] = await Promise.all([ fetch('api_sports.php'), fetch('api_colors.php') ]);
  const s = await sRes.json(); const c = await cRes.json();
  if(!s.ok) return toast(s.error||'โหลดกีฬาไม่สำเร็จ'); SPORTS = s.sports||[];
  if(c.ok && c.colors) COLORS = c.colors;

  const cats = Array.from(new Map(SPORTS.map(x => [String(x.category_id), x.category_name])));
  const selCat = document.getElementById('filterCat');
  selCat.innerHTML = '<option value="">ทั้งหมด</option>' + cats.map(([id,name])=>`<option value="${id}">${name}</option>`).join('');
  
  // โหลดค่าตัวกรองที่เก็บไว้ หรือใช้ default "กีฬาสากล"
  const savedCat = localStorage.getItem('referee_filter_cat');
  if (savedCat !== null) {
    selCat.value = savedCat;
  } else {
    // ถ้ายังไม่เคยเลือก → ใช้ default "กีฬาสากล"
    let defaultVal = '';
    const found = SPORTS.find(s => (s.category_name || '') === 'กีฬาสากล');
    if (found) defaultVal = String(found.category_id);
    if (!defaultVal) {
      const opt = Array.from(selCat.options).find(o => o.text === 'กีฬาสากล');
      if (opt) defaultVal = opt.value;
    }
    if (defaultVal) selCat.value = defaultVal;
  }
  
  // โหลดค่าตัวกรองอื่น ๆ
  loadFilters();

  renderTable();
}

function renderTable(){
  const q=(document.getElementById('filterQ').value||'').toLowerCase();
  const cat=(document.getElementById('filterCat').value||'').trim();
  const lvl=(document.getElementById('filterLevel').value||'').trim();
  const tb=document.querySelector('#sportsTable tbody'); tb.innerHTML='';

  const filtered = SPORTS.filter(s => {
    if(q && !s.name.toLowerCase().includes(q)) return false;
    if(cat && String(s.category_id)!==cat) return false;
    if(lvl && levelType(s.grade_levels)!==lvl) return false;
    return true;
  });

  if(!filtered.length){ 
    tb.innerHTML=`<tr><td colspan="6">
      <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h5 class="mb-2">ไม่พบข้อมูล</h5>
        <p class="text-muted mb-0">ไม่พบกีฬาตามเงื่อนไขที่ค้นหา</p>
      </div>
    </td></tr>`; 
    return; 
  }

  filtered.forEach((s,idx)=>{
    const saved = s.saved ? '<span class="badge badge-saved">✅ บันทึกแล้ว</span>' : '<span class="badge badge-nosave">⏳ ยังไม่บันทึก</span>';
    const isAth = /กรีฑ/.test(s.category_name);
    const eventCode = s.event_code ? `<span class="badge bg-secondary me-2">${s.event_code}</span>` : '';
    
    // แสดง badge ไฟถ้าทำลายสถิติภายใน 7 วัน
    const fireIcon = s.record_broken ? '<span class="record-broken-badge"><span class="fire-icon">🔥</span>ทำลายสถิติ!</span>' : '';
    
    const tr=document.createElement('tr');
    const rowClass = idx % 2 === 0 ? '' : 'table-light';
    tr.className = rowClass;
    tr.innerHTML=`
      <td class="text-center fw-bold" style="color: var(--brand)">${idx+1}</td>
      <td class="fw-semibold">${eventCode}${s.name}${fireIcon}</td>
      <td><span class="badge" style="background: #f1f5f9; color: #475569;">${s.category_name}</span></td>
      <td>${s.grade_levels||'-'}</td>
      <td class="text-center">${saved}</td>
      <td class="text-center">
        <div class="btn-group btn-group-sm" role="group">
          <button class="btn btn-primary" onclick="openResult(${s.id}, ${isAth?1:0}, '${s.name.replace(/'/g,"\\'")}')">📝 บันทึกผล</button>
          ${s.saved ? `<button class="btn btn-outline-danger" onclick="deleteResult(${s.id}, '${s.name.replace(/'/g,"\\'")}')">🗑️ ลบผล</button>` : ''}
        </div>
      </td>`;
    tb.appendChild(tr);
  });
}

document.getElementById('filterQ').addEventListener('input', () => { saveFilters(); renderTable(); });
document.getElementById('filterCat').addEventListener('change', () => { saveFilters(); renderTable(); });
document.getElementById('filterLevel').addEventListener('change', () => { saveFilters(); renderTable(); });

async function openResult(sportId, isAthletics, sportName){
  if(isAthletics) return openAthletics(sportId, sportName);

  const opts=['','1','2','3','4'].map(v=>`<option value="${v}">${v||'-'}</option>`).join('');
  const rows = COLORS.map((c, idx)=>{
    const bgColor = idx % 2 === 0 ? '#ffffff' : '#e9ecef';
    return `
    <tr style="background-color: ${bgColor} !important;" data-row-idx="${idx}">
      <td><span class="color-chip"><i class="color-dot" style="background:${c.hex}"></i>${c.name}</span></td>
      <td style="width:140px"><select class="form-select form-select-sm rank-sel" data-color="${c.name}" data-row-idx="${idx}">${opts}</select></td>
    </tr>`;
  }).join('');
  document.getElementById('modalTitle').textContent='📝 บันทึกผล — '+sportName;
  document.getElementById('modalBody').innerHTML = `
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>🎨 สี</th><th style="width:140px">🏆 อันดับ</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
  const modal=new bootstrap.Modal(document.getElementById('resultModal')); modal.show();

  // preload
  try{
    const r=await fetch('save.php?ajax=load_non_athletics&sport_id='+sportId,{cache:'no-store'});
    const d=await r.json(); 
    if(d.ok && d.ranks){ 
      document.querySelectorAll('.rank-sel').forEach(sel=>{ 
        if(d.ranks[sel.dataset.color]) sel.value=String(d.ranks[sel.dataset.color]); 
      }); 
    }
  }catch(e){}
  
  // เพิ่ม event listener สำหรับเปลี่ยนสีพื้นหลังตามอันดับที่เลือก
  document.querySelectorAll('.rank-sel').forEach(select => {
    select.addEventListener('change', function() {
      const row = this.closest('tr');
      const rank = parseInt(this.value, 10);
      const idx = parseInt(this.dataset.rowIdx || '0', 10);
      
      // รีเซ็ตสีก่อน (กลับไปเป็นสีเดิม)
      const defaultBg = idx % 2 === 0 ? '#ffffff' : '#e9ecef';
      
      // ตั้งสีตามอันดับ หรือใช้สีเริ่มต้น
      let bgColor = defaultBg;
      if (rank === 1) {
        bgColor = '#FFD700'; // ทอง
      } else if (rank === 2) {
        bgColor = '#C0C0C0'; // เงิน
      } else if (rank === 3) {
        bgColor = '#CD7F32'; // ทองแดง
      } else if (rank === 4) {
        bgColor = '#87CEEB'; // ฟ้า
      }
      
      // ใช้ cssText และ !important เพื่อให้แน่ใจว่าสีจะเปลี่ยน
      row.style.cssText = `background-color: ${bgColor} !important;`;
    });
    
    // Trigger เพื่อตั้งสีเริ่มต้น (ถ้ามีค่าอยู่แล้ว)
    setTimeout(() => select.dispatchEvent(new Event('change')), 50);
  });

  document.getElementById('btnSave').onclick = async ()=>{
    const ranks={}; 
    document.querySelectorAll('.rank-sel').forEach(sel=>{ 
      if(sel.value) ranks[sel.dataset.color]=parseInt(sel.value,10); 
    });
    
    // ⭐ ตรวจสอบต้องกรอกครบทั้ง 4 สี
    if (Object.keys(ranks).length !== 4) {
      toast('⚠️ กรุณากรอกอันดับให้ครบทั้ง 4 สี', 'error');
      return;
    }
    
    // ⭐ ตรวจสอบอันดับต้องเป็น 1, 2, 3, 4 และไม่ซ้ำกัน
    const rankValues = Object.values(ranks).sort((a, b) => a - b);
    if (rankValues.length !== 4 || rankValues[0] !== 1 || rankValues[1] !== 2 || rankValues[2] !== 3 || rankValues[3] !== 4) {
      toast('⚠️ อันดับต้องเป็น 1, 2, 3, 4 และไม่ซ้ำกัน', 'error');
      return;
    }
    
    const res=await fetch('save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({type:'non_athletics',sport_id:sportId,ranks})});
    const out=await res.json(); 
    if(out.ok){ 
      await Swal.fire({text:'✅ บันทึกสำเร็จ',icon:'success',timer:1500,showConfirmButton:false}); 
      modal.hide(); 
      loadInit(); 
    } else {
      toast(out.error||'❌ บันทึกไม่สำเร็จ', 'error');
    }
  };
}

async function openAthletics(sportId, sportName){
  document.getElementById('modalTitle').textContent = '📝 บันทึกผล — ' + sportName;
  document.getElementById('modalBody').innerHTML = '<div class="text-muted text-center py-4 loading">⏳ กำลังโหลด...</div>';
  const modal = new bootstrap.Modal(document.getElementById('resultModal'));
  modal.show();

  const r = await fetch('fetch_athletics.php?sport_id=' + sportId, {cache:'no-store'});
  const d = await r.json();
  if (!d.ok) { document.getElementById('modalBody').textContent = d.error || 'โหลดไม่สำเร็จ'; return; }

  // ถ้ามีการทำลายสถิติ → แสดง badge ในชื่อ modal
  const fireIcon = d.record_broken ? ' <span class="record-broken-badge"><span class="fire-icon">🔥</span>ทำลายสถิติ!</span>' : '';
  document.getElementById('modalTitle').innerHTML = '📝 บันทึกผล — ' + sportName + fireIcon;

  const isRelay = !!d.is_relay;
  const colorHex = Object.fromEntries(COLORS.map(c => [c.name, c.hex]));

  const rows = (d.lanes||[]).map((l, idx) => {
    const colorCell = l.color ? `<span class="color-chip"><i class="color-dot" style="background:${colorHex[l.color]||'#999'}"></i>${l.color}</span>` : '-';
    const bgColor = idx % 2 === 0 ? '#ffffff' : '#e9ecef';
    if (isRelay) {
      return `
      <tr style="background-color: ${bgColor} !important;" data-row-idx="${idx}">
        <td class="text-center" style="width:56px">${l.lane_no}<input type="hidden" name="lane_no[]" value="${l.lane_no}"></td>
        <td style="width:140px">${colorCell}</td>
        <td style="width:120px"><input type="text" class="form-control form-control-sm time-input" name="time[]" value="${l.time_sec ?? ''}"></td>
        <td style="width:100px"><input type="number" class="form-control form-control-sm rank-input" name="rank[]" value="${l.rank ?? ''}" min="1" data-lane="${l.lane_no}" data-row-idx="${idx}"></td>
        <td class="text-center" style="width:70px"><input type="checkbox" class="form-check-input record-check" name="is_record[]" ${l.is_record ? 'checked' : ''} data-name="${l.display_name || l.color || ''}" data-lane="${l.lane_no}"></td>
      </tr>`;
    } else {
      return `
      <tr style="background-color: ${bgColor} !important;" data-row-idx="${idx}">
        <td class="text-center" style="width:56px">${l.lane_no}<input type="hidden" name="lane_no[]" value="${l.lane_no}"></td>
        <td style="width:120px">${colorCell}</td>
        <td style="white-space:pre-line;">${l.display_name || '<span class="text-muted">ยังไม่เลือกผู้เล่น</span>'}</td>
        <td style="width:120px"><input type="text" class="form-control form-control-sm time-input" name="time[]" value="${l.time_sec ?? ''}"></td>
        <td style="width:100px"><input type="number" class="form-control form-control-sm rank-input" name="rank[]" value="${l.rank ?? ''}" min="1" data-lane="${l.lane_no}" data-row-idx="${idx}"></td>
        <td class="text-center" style="width:70px"><input type="checkbox" class="form-check-input record-check" name="is_record[]" ${l.is_record ? 'checked' : ''} data-name="${l.display_name || ''}" data-lane="${l.lane_no}"></td>
      </tr>`;
    }
  }).join('');

  const best = d.best || { holder:'', time_sec:'', year:'' };
  const headerCols = isRelay
    ? `<th class="text-center" style="width:60px">🏃 ลู่</th><th style="width:140px">🎨 สี</th><th style="width:120px">⏱️ เวลา (12.25)</th><th style="width:100px">🏆 อันดับ</th><th class="text-center" style="width:70px">🔥 สถิติ</th>`
    : `<th class="text-center" style="width:60px">🏃 ลู่</th><th style="width:120px">🎨 สี</th><th>👤 ชื่อ - นามสกุล / ทีม</th><th style="width:120px">⏱️ เวลา (12.25)</th><th style="width:100px">🏆 อันดับ</th><th class="text-center" style="width:70px">🔥 สถิติ</th>`;

  // สร้าง HTML ประวัติสถิติ (แสดงเสมอ)
  let historyRows = '';
  
  if (d.history && d.history.length > 0) {
    historyRows = d.history.map((h, idx) => {
      const date = new Date(h.broken_at).toLocaleString('th-TH', {dateStyle: 'short', timeStyle: 'short'});
      const yearBadge = h.year_name ? `<span class="badge bg-info text-dark" style="font-size:0.7rem">${h.year_name}</span>` : (h.year_id ? `<span class="badge bg-info text-dark" style="font-size:0.7rem">ปี ${h.year_id}</span>` : '');
      const isRestored = h.restored_at;
      const rowClass = isRestored ? 'table-success' : '';
      const restoreInfo = isRestored ? `<span class="badge bg-success" style="font-size:0.65rem">คืนค่าแล้ว</span>` : '';
      
      return `
        <tr class="${rowClass}">
          <td class="text-center">${idx + 1}</td>
          <td>${h.old_notes || '-'} ${yearBadge}</td>
          <td class="text-center">${h.old_best_time || '-'}</td>
          <td class="text-center">${h.old_best_year_be || '-'}</td>
          <td class="text-center">${h.broken_by_time || '-'}</td>
          <td class="small text-muted">${date} ${restoreInfo}</td>
          <td class="text-center">
            <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-outline-primary" onclick="restoreRecord(${h.id}, '${(h.old_notes||'').replace(/'/g,"\\'")}', '${h.old_best_time||''}', '${h.old_best_year_be||''}')" ${isRestored ? 'disabled' : ''}>↩️ คืนค่า</button>
              <button class="btn btn-outline-danger" onclick="deleteHistory(${h.id})" ${isRestored ? 'disabled' : ''}>🗑️ ลบ</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  } else {
    historyRows = `
      <tr>
        <td colspan="7" class="text-center text-muted py-3">
          <i class="bi bi-inbox"></i> ยังไม่มีประวัติสถิติ
        </td>
      </tr>
    `;
  }
  
  const historyHtml = `
    <div class="border-top pt-3 mt-3" id="historySection">
      <h6 class="mb-3">📜 ประวัติการบันทึกสถิติ</h6>
      <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
        <table class="table table-sm table-bordered table-hover">
          <thead class="table-light sticky-top">
            <tr>
              <th class="text-center" style="width:40px">#</th>
              <th>ชื่อผู้ครอง</th>
              <th class="text-center" style="width:80px">เวลา</th>
              <th class="text-center" style="width:60px">ปี</th>
              <th class="text-center" style="width:100px">ทำลายด้วยเวลา</th>
              <th style="width:160px">วันที่ถูกทำลาย</th>
              <th class="text-center" style="width:150px">จัดการ</th>
            </tr>
          </thead>
          <tbody id="historyTableBody">${historyRows}</tbody>
        </table>
      </div>
    </div>
  `;

  document.getElementById('modalBody').innerHTML = `
    <div class="mb-3 small text-muted">⚡ กรีฑา • ${sportName}</div>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr>${headerCols}</tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    <div class="border-top pt-3 mt-3">
      <div class="row g-3">
        <div class="col-12 col-md-4">
          <label class="form-label small">👤 ชื่อผู้ครองสถิติ</label>
          <input id="bestName" class="form-control form-control-sm" value="${best.holder||''}" placeholder="เช่น เด็กชายตัวอย่าง">
        </div>
        <div class="col-6 col-md-4">
          <label class="form-label small">⏱️ เวลาสถิติ</label>
          <input id="bestTime" class="form-control form-control-sm" value="${best.time_sec||''}" placeholder="เช่น 12.25">
        </div>
        <div class="col-6 col-md-4">
          <label class="form-label small">📅 ปีการศึกษา (พ.ศ.)</label>
          <input id="bestYear" class="form-control form-control-sm" value="${best.year || new Date().getFullYear() + 543}" placeholder="2568">
        </div>
      </div>
    </div>
    ${historyHtml}
    <input type="hidden" id="eventId" value="${d.event_id || ''}">
    <input type="hidden" id="currentSportId" value="${sportId}">`;

  // เพิ่ม event listener สำหรับ checkbox "ทำลายสถิติ"
  document.querySelectorAll('.record-check').forEach(chk => {
    chk.addEventListener('change', function() {
      if (this.checked) {
        const row = this.closest('tr');
        const timeInput = row.querySelector('.time-input');
        const name = this.dataset.name || '';
        const time = timeInput.value.trim();
        
        if (time && name) {
          // อัปเดตชื่อผู้ครองสถิติ + เวลาอัตโนมัติ
          document.getElementById('bestName').value = name;
          document.getElementById('bestTime').value = time;
          document.getElementById('bestYear').value = new Date().getFullYear() + 543;
          
          // ยกเลิกการติ๊กอื่น (เพราะมีสถิติได้แค่คนเดียว)
          document.querySelectorAll('.record-check').forEach(c => {
            if (c !== this) c.checked = false;
          });
        } else {
          Swal.fire({text:'กรุณากรอกเวลา' + (isRelay ? '' : 'และมีชื่อผู้เล่น') + 'ก่อนติ๊ก "ทำลายสถิติ"',icon:'warning'});
          this.checked = false;
        }
      }
    });
  });
  
  // เพิ่ม event listener สำหรับเปลี่ยนสีพื้นหลังตามอันดับที่กรอก
  document.querySelectorAll('.rank-input').forEach(input => {
    input.addEventListener('input', function() {
      const row = this.closest('tr');
      const rank = parseInt(this.value, 10);
      const idx = parseInt(this.dataset.rowIdx || '0', 10);
      
      // รีเซ็ตสีก่อน (กลับไปเป็นสีเดิม)
      const defaultBg = idx % 2 === 0 ? '#ffffff' : '#e9ecef';
      
      // ตั้งสีตามอันดับ หรือใช้สีเริ่มต้น
      let bgColor = defaultBg;
      if (rank === 1) {
        bgColor = '#FFD700'; // ทอง
      } else if (rank === 2) {
        bgColor = '#C0C0C0'; // เงิน
      } else if (rank === 3) {
        bgColor = '#CD7F32'; // ทองแดง
      } else if (rank === 4) {
        bgColor = '#87CEEB'; // ฟ้า
      }
      
      // ใช้ cssText และ !important เพื่อให้แน่ใจว่าสีจะเปลี่ยน
      row.style.cssText = `background-color: ${bgColor} !important;`;
    });
    
    // Trigger เพื่อตั้งสีเริ่มต้น (ถ้ามีค่าอยู่แล้ว)
    setTimeout(() => input.dispatchEvent(new Event('input')), 50);
  });

  document.getElementById('btnSave').onclick = async () => {
    try {
      const lanes = [];
      let recordLaneNo = null;
      
      // หา lane ที่ติ๊ก "ทำลายสถิติ"
      document.querySelectorAll('.record-check').forEach(chk => {
        if (chk.checked) recordLaneNo = parseInt(chk.dataset.lane, 10);
      });
      
      document.querySelectorAll('#resultModal tbody tr').forEach(tr => {
        const laneNoInput = tr.querySelector('input[name="lane_no[]"]');
        const timeInput = tr.querySelector('input[name="time[]"]');
        const rankInput = tr.querySelector('input[name="rank[]"]');
        
        if (!laneNoInput) return; // ข้าม row ที่ไม่มี input
        
        const laneNo = parseInt(laneNoInput.value, 10);
        const isRecord = (laneNo === recordLaneNo);
        
        lanes.push({
          lane_no: laneNo,
          time: (timeInput ? timeInput.value : '').trim(),
          rank: (rankInput ? rankInput.value : '').trim(),
          is_record: isRecord
        });
      });
      
      // ⭐ ตรวจสอบต้องมีอันดับครบ 4 อันดับ
      const filledRanks = lanes.filter(l => l.rank !== '');
      if (filledRanks.length !== 4) {
        toast('⚠️ กรุณากรอกอันดับให้ครบทั้ง 4 อันดับ', 'error');
        return;
      }
      
      // ⭐ ตรวจสอบอันดับต้องเป็น 1, 2, 3, 4 และไม่ซ้ำกัน
      const rankValues = filledRanks.map(l => parseInt(l.rank, 10)).sort((a, b) => a - b);
      if (rankValues.length !== 4 || rankValues[0] !== 1 || rankValues[1] !== 2 || rankValues[2] !== 3 || rankValues[3] !== 4) {
        toast('⚠️ อันดับต้องเป็น 1, 2, 3, 4 และไม่ซ้ำกัน', 'error');
        return;
      }
      
      const bestNameEl = document.getElementById('bestName');
      const bestTimeEl = document.getElementById('bestTime');
      const bestYearEl = document.getElementById('bestYear');
      
      const payload = {
        type:'athletics', sport_id:sportId, lanes,
        best_name: bestNameEl ? bestNameEl.value : '',
        best_time: bestTimeEl ? bestTimeEl.value : '',
        best_year: bestYearEl ? (parseInt(bestYearEl.value||'0',10) || null) : null
      };
      
      const res = await fetch('save.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
      const out = await res.json();
      
      if (out.ok) {
        await Swal.fire({text:'✅ บันทึกสำเร็จ',icon:'success',timer:1500,showConfirmButton:false});
        modal.hide(); 
        saveFilters();
        loadInit(); 
      } else {
        console.error('Save error:', out);
        toast(out.error || '❌ บันทึกล้มเหลว', 'error');
      }
    } catch (e) {
      console.error('Exception during save:', e);
      toast('⚠️ เกิดข้อผิดพลาด: ' + e.message, 'error');
    }
  };
}

async function deleteResult(sportId, sportName){
  const result = await Swal.fire({
    title: '🗑️ ยืนยันการลบ',
    text: 'ลบผลของ "' + sportName + '" ใช่หรือไม่?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ใช่, ลบเลย',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#d33'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    saveFilters();
    const res = await fetch('save.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ type: 'delete_result', sport_id: sportId })
    });
    const out = await res.json();
    if (out.ok) {
      await Swal.fire({text:'✅ ลบผลเรียบร้อย',icon:'success',timer:1500,showConfirmButton:false});
      await loadInit();
    } else {
      toast(out.error || '❌ ลบผลไม่สำเร็จ', 'error');
    }
  } catch (e) {
    toast('⚠️ เกิดข้อผิดพลาดระหว่างลบผล', 'error');
  }
}

async function restoreRecord(historyId, oldName, oldTime, oldYear) {
  const result = await Swal.fire({
    title: '↩️ ย้อนกลับสถิติ',
    html: `คืนค่าสถิติเป็น:<br><strong>${oldName}</strong><br>เวลา: ${oldTime} | ปี: ${oldYear}`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'ใช่, คืนค่า',
    cancelButtonText: 'ยกเลิก'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    const res = await fetch('save.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ 
        type: 'restore_record', 
        history_id: historyId
      })
    });
    const out = await res.json();
    if (out.ok) {
      await Swal.fire({text:'✅ คืนค่าสถิติเรียบร้อย',icon:'success',timer:1500,showConfirmButton:false});
      const sportId = document.getElementById('currentSportId').value;
      const modal = bootstrap.Modal.getInstance(document.getElementById('resultModal'));
      if (modal) modal.hide();
      await loadInit();
    } else {
      toast(out.error || '❌ คืนค่าสถิติไม่สำเร็จ', 'error');
    }
  } catch (e) {
    toast('⚠️ เกิดข้อผิดพลาดระหว่างคืนค่าสถิติ', 'error');
  }
}

async function deleteHistory(historyId) {
  const result = await Swal.fire({
    title: '🗑️ ลบประวัติ',
    text: 'ต้องการลบประวัติสถิตินี้ใช่หรือไม่?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ใช่, ลบเลย',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#d33'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    const res = await fetch('save.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ 
        type: 'delete_history', 
        history_id: historyId
      })
    });
    const out = await res.json();
    if (out.ok) {
      await Swal.fire({text:'✅ ลบประวัติเรียบร้อย',icon:'success',timer:1500,showConfirmButton:false});
      // ปิด backdrop ทั้งหมดก่อน
      document.querySelectorAll('.swal2-container').forEach(el => el.remove());
      document.body.classList.remove('swal2-shown', 'swal2-height-auto');
      // รีโหลด modal ใหม่
      const sportId = document.getElementById('currentSportId').value;
      const sportName = SPORTS.find(s => s.id == sportId)?.name || '';
      await openAthletics(sportId, sportName);
    } else {
      toast(out.error || '❌ ลบประวัติไม่สำเร็จ', 'error');
    }
  } catch (e) {
    toast('⚠️ เกิดข้อผิดพลาดระหว่างลบประวัติ', 'error');
  }
}

loadInit();
</script>
</body>
</html>
