<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['referee']) || (($_SESSION['referee']['role'] ?? '') !== 'referee')) {
  header('Location: ' . BASE_URL . '/referee/login.php'); exit;
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ผู้ตัดสิน | บันทึกผล</title>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{ --brand:#0ea5e9; --brand-600:#0284c7; }
    body{ font-family:'Kanit', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
    header.app{ background:linear-gradient(90deg, var(--brand), var(--brand-600)); color:#fff; }
    .badge-saved{ background:#22c55e }
    .badge-nosave{ background:#94a3b8 }
    .color-chip{ display:inline-flex; align-items:center; gap:.5rem; padding:.25rem .5rem; border-radius:999px; background:#eef2f7; font-weight:500; }
    .color-dot{ width:.8rem; height:.8rem; border-radius:50%; display:inline-block; }
  </style>
</head>
<body>
<header class="app">
  <div class="container py-3 d-flex justify-content-between align-items-center">
    <div class="fs-5 fw-semibold">โหมดผู้ตัดสิน</div>
    <a class="btn btn-sm btn-light" href="<?= BASE_URL ?>/referee/logout.php">ออกจากระบบ</a>
  </div>
</header>

<main class="container py-4">
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1">ประเภทกีฬา</label>
          <select id="filterCat" class="form-select"><option value="">ทั้งหมด</option></select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1">ระดับชั้น</label>
          <select id="filterLevel" class="form-select">
            <option value="">ทั้งหมด</option>
            <option value="P">ประถม (ป..)</option>
            <option value="S">มัธยม (ม..)</option>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1">ค้นหา</label>
          <input id="filterQ" class="form-control" placeholder="พิมพ์ชื่อกีฬา">
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="sportsTable">
          <thead class="table-light">
            <tr>
              <th style="width:60px" class="text-center">#</th>
              <th>ชื่อกีฬา</th>
              <th style="width:180px">หมวด</th>
              <th style="width:200px">ระดับชั้น</th>
              <th style="width:140px" class="text-center">สถานะ</th>
              <th style="width:140px" class="text-center">จัดการ</th>
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
        <h5 class="modal-title" id="modalTitle">บันทึกผล</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        <button type="button" class="btn btn-primary" id="btnSave">บันทึก</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let SPORTS=[], COLORS=[{name:'ส้ม',hex:'#FFA726'},{name:'เขียว',hex:'#4CAF50'},{name:'ชมพู',hex:'#EC407A'},{name:'ฟ้า',hex:'#29B6F6'}];

function toast(m){ alert(m); }
function levelType(txt){ const t=(txt||'').trim(); if(t.startsWith('ป')) return 'P'; if(t.startsWith('ม')) return 'S'; return ''; }

async function loadInit(){
  const [sRes, cRes] = await Promise.all([ fetch('api_sports.php'), fetch('api_colors.php') ]);
  const s = await sRes.json(); const c = await cRes.json();
  if(!s.ok) return toast(s.error||'โหลดกีฬาไม่สำเร็จ'); SPORTS = s.sports||[];
  if(c.ok && c.colors) COLORS = c.colors;

  const cats = Array.from(new Map(SPORTS.map(x => [String(x.category_id), x.category_name])));
  const selCat = document.getElementById('filterCat');
  selCat.innerHTML = '<option value="">ทั้งหมด</option>' + cats.map(([id,name])=>`<option value="${id}">${name}</option>`).join('');
  
  // เลือก default เป็นหมวด "กีฬาสากล" ถ้ามี
  (function(){
    let defaultVal = '';
    const found = SPORTS.find(s => (s.category_name || '') === 'กีฬาสากล');
    if (found) defaultVal = String(found.category_id);
    if (!defaultVal) {
      const opt = Array.from(selCat.options).find(o => o.text === 'กีฬาสากล');
      if (opt) defaultVal = opt.value;
    }
    if (defaultVal) selCat.value = defaultVal;
  })();

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

  if(!filtered.length){ tb.innerHTML='<tr><td colspan="6" class="text-center text-muted">ไม่พบข้อมูล</td></tr>'; return; }

  filtered.forEach((s,idx)=>{
    const saved = s.saved ? '<span class="badge badge-saved">บันทึกแล้ว</span>' : '<span class="badge badge-nosave">ยังไม่บันทึก</span>';
    const isAth = /กรีฑ/.test(s.category_name);
    const tr=document.createElement('tr');
    tr.innerHTML=`
      <td class="text-center">${idx+1}</td>
      <td class="fw-semibold">${s.name}</td>
      <td>${s.category_name}</td>
      <td>${s.grade_levels||'-'}</td>
      <td class="text-center">${saved}</td>
      <td class="text-center" style="white-space:nowrap">
        <div class="btn-group btn-group-sm" role="group" aria-label="actions">
          <button class="btn btn-primary" onclick="openResult(${s.id}, ${isAth?1:0}, '${s.name.replace(/'/g,"\\'")}')">บันทึกผล</button>
          ${s.saved ? `<button class="btn btn-outline-danger" onclick="deleteResult(${s.id}, '${s.name.replace(/'/g,"\\'")}')">ลบผล</button>` : ''}
        </div>
      </td>`;
    tb.appendChild(tr);
  });
}

document.getElementById('filterQ').addEventListener('input', renderTable);
document.getElementById('filterCat').addEventListener('input', renderTable);
document.getElementById('filterLevel').addEventListener('input', renderTable);

async function openResult(sportId, isAthletics, sportName){
  if(isAthletics) return openAthletics(sportId, sportName);

  const opts=['','1','2','3','4'].map(v=>`<option value="${v}">${v||'-'}</option>`).join('');
  const rows = COLORS.map(c=>`
    <tr>
      <td><span class="color-chip"><i class="color-dot" style="background:${c.hex}"></i>${c.name}</span></td>
      <td style="width:140px"><select class="form-select form-select-sm rank-sel" data-color="${c.name}">${opts}</select></td>
    </tr>`).join('');
  document.getElementById('modalTitle').textContent='บันทึกผล — '+sportName;
  document.getElementById('modalBody').innerHTML = `
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>สี</th><th style="width:140px">อันดับ</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
  const modal=new bootstrap.Modal(document.getElementById('resultModal')); modal.show();

  // preload
  try{
    const r=await fetch('save.php?ajax=load_non_athletics&sport_id='+sportId,{cache:'no-store'});
    const d=await r.json(); if(d.ok && d.ranks){ document.querySelectorAll('.rank-sel').forEach(sel=>{ if(d.ranks[sel.dataset.color]) sel.value=String(d.ranks[sel.dataset.color]); }); }
  }catch(e){}

  document.getElementById('btnSave').onclick = async ()=>{
    const ranks={}; document.querySelectorAll('.rank-sel').forEach(sel=>{ if(sel.value) ranks[sel.dataset.color]=parseInt(sel.value,10); });
    const res=await fetch('save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({type:'non_athletics',sport_id:sportId,ranks})});
    const out=await res.json(); toast(out.ok?'บันทึกสำเร็จ':(out.error||'บันทึกไม่สำเร็จ')); if(out.ok){ modal.hide(); loadInit(); }
  };
}

// >>> Corrected openAthletics function with 'สี' column and proper template strings <<<
async function openAthletics(sportId, sportName){
  document.getElementById('modalTitle').textContent = 'บันทึกผล — ' + sportName;
  document.getElementById('modalBody').innerHTML = '<div class="text-muted">กำลังโหลด...</div>';
  const modal = new bootstrap.Modal(document.getElementById('resultModal'));
  modal.show();

  const r = await fetch('fetch_athletics.php?sport_id=' + sportId, {cache:'no-store'});
  const d = await r.json();
  if (!d.ok) { document.getElementById('modalBody').textContent = d.error || 'โหลดไม่สำเร็จ'; return; }

  const isRelay = !!d.is_relay;
  const colorHex = Object.fromEntries(COLORS.map(c => [c.name, c.hex]));

  // สร้าง rows — ถ้าเป็น relay ให้ไม่แสดงคอลัมน์ชื่อ (แสดงแค่สี)
  const rows = (d.lanes||[]).map(l => {
    const colorCell = l.color ? `<span class="color-chip"><i class="color-dot" style="background:${colorHex[l.color]||'#999'}"></i>${l.color}</span>` : '-';
    if (isRelay) {
      return `
      <tr>
        <td class="text-center" style="width:56px">${l.lane_no}<input type="hidden" name="lane_no[]" value="${l.lane_no}"></td>
        <td style="width:140px">${colorCell}</td>
        <td style="width:120px"><input type="text" class="form-control form-control-sm" name="time[]" value="${l.time_sec ?? ''}" placeholder="12.25"></td>
        <td style="width:100px"><input type="number" class="form-control form-control-sm" name="rank[]" value="${l.rank ?? ''}" min="1"></td>
        <td class="text-center" style="width:70px"><input type="checkbox" class="form-check-input" name="is_record[]"></td>
      </tr>`;
    } else {
      return `
      <tr>
        <td class="text-center" style="width:56px">${l.lane_no}<input type="hidden" name="lane_no[]" value="${l.lane_no}"></td>
        <td style="width:120px">${colorCell}</td>
        <td>${l.display_name || '<span class="text-muted">ยังไม่เลือกผู้เล่น</span>'}</td>
        <td style="width:120px"><input type="text" class="form-control form-control-sm" name="time[]" value="${l.time_sec ?? ''}" placeholder="12.25"></td>
        <td style="width:100px"><input type="number" class="form-control form-control-sm" name="rank[]" value="${l.rank ?? ''}" min="1"></td>
        <td class="text-center" style="width:70px"><input type="checkbox" class="form-check-input" name="is_record[]"></td>
      </tr>`;
    }
  }).join('');

  const best = d.best || { holder:'', time_sec:'', year:'' };
  // ปรับ header: ถ้าเป็น relay เอา column ชื่อออก (แสดงแค่ สี/เวลา/อันดับ/สถิติ)
  const headerCols = isRelay
    ? `<th class="text-center" style="width:60px">ลู่</th><th style="width:140px">สี</th><th style="width:120px">เวลา</th><th style="width:100px">อันดับ</th><th class="text-center" style="width:70px">ทำลายสถิติ</th>`
    : `<th class="text-center" style="width:60px">ลู่</th><th style="width:120px">สี</th><th>ชื่อ - นามสกุล / ทีม</th><th style="width:120px">เวลา</th><th style="width:100px">อันดับ</th><th class="text-center" style="width:70px">ทำลายสถิติ</th>`;

  document.getElementById('modalBody').innerHTML = `
    <div class="mb-2 small text-muted">กรีฑา • ${sportName}</div>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr>${headerCols}</tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    <div class="border-top pt-3">
      <div class="row g-3">
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1">ชื่อผู้ครองสถิติ (ปัจจุบัน/ใหม่)</label>
          <input id="bestName" class="form-control form-control-sm" value="${best.holder||''}" placeholder="เช่น เด็กชายตัวอย่าง">
        </div>
        <div class="col-6 col-md-4">
          <label class="form-label small mb-1">เวลาสถิติเดิม/ใหม่</label>
          <input id="bestTime" class="form-control form-control-sm" value="${best.time_sec||''}" placeholder="เช่น 12.25">
        </div>
        <div class="col-6 col-md-4">
          <label class="form-label small mb-1">ปีการศึกษา (พ.ศ.)</label>
          <input id="bestYear" class="form-control form-control-sm" value="${best.year||''}" placeholder="2568">
        </div>
      </div>
    </div>`;

  document.getElementById('btnSave').onclick = async () => {
    const lanes = [];
    document.querySelectorAll('#resultModal tbody tr').forEach(tr => {
      lanes.push({
        lane_no: parseInt(tr.querySelector('input[name="lane_no[]"]').value,10),
        time: (tr.querySelector('input[name="time[]"]').value||'').trim(),
        rank: (tr.querySelector('input[name="rank[]"]').value||'').trim()
      });
    });
    const payload = {
      type:'athletics', sport_id:sportId, lanes,
      best_name: document.getElementById('bestName').value||'',
      best_time: document.getElementById('bestTime').value||'',
      best_year: parseInt(document.getElementById('bestYear').value||'0',10) || null
    };
    const res = await fetch('save.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    const out = await res.json(); toast(out.ok ? 'บันทึกสำเร็จ' : (out.error || 'บันทึกล้มเหลว'));
    if (out.ok) { modal.hide(); loadInit(); }
  };
}

async function deleteResult(sportId, sportName){
  if (!confirm('ลบผลของ "' + sportName + '" ใช่หรือไม่?')) return;
  try {
    const res = await fetch('save.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ type: 'delete_result', sport_id: sportId })
    });
    const out = await res.json();
    if (out.ok) {
      toast('ลบผลเรียบร้อย');
      await loadInit();
    } else {
      toast(out.error || 'ลบผลไม่สำเร็จ');
    }
  } catch (e) {
    toast('เกิดข้อผิดพลาดระหว่างลบผล');
  }
}

loadInit();
</script>
</body>
</html>
