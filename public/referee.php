<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'กำหนดคะแนน & รวมคะแนน';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<style>
  /* Custom styles สำหรับหน้านี้เท่านั้น */
  .color-chip { display:inline-flex; align-items:center; gap:.5rem; padding:.25rem .5rem; border-radius:999px; background:#eef2f7; font-weight:500; }
  .color-dot { width:.8rem; height:.8rem; border-radius:50%; display:inline-block; }
  .score { font-variant-numeric: tabular-nums; }
  .table-striped>tbody>tr:nth-of-type(odd){ background-color: #fbfdff; }
</style>

<main class="container py-4">
  <div class="row g-4">
    <!-- ส่วนที่ 1: บันทึกผลการแข่งขัน -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0">บันทึกผลการแข่งขัน</h5>
            <a href="<?= BASE_URL ?>/referee/index.php" class="btn btn-outline-primary btn-sm" target="_blank">
              เปิดหน้าบันทึกผลแบบเต็ม →
            </a>
          </div>
          <iframe 
            src="<?= BASE_URL ?>/referee/index.php" 
            style="width:100%; height:600px; border:1px solid #e5e7eb; border-radius:0.5rem;"
            title="บันทึกผลการแข่งขัน">
          </iframe>
        </div>
      </div>
    </div>

    <!-- ส่วนที่ 2: กำหนดคะแนน -->
    <div class="col-12 col-xl-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0">กำหนดคะแนนต่ออันดับ</h5>
            <span class="text-muted small">ต่อ <b>หมวดกีฬา</b> (ใช้กับปีนี้เท่านั้น)</span>
          </div>
          <div id="ruleList" class="vstack gap-3"></div>
          <div class="text-end mt-3">
            <button id="btnSaveRules" class="btn btn-primary">บันทึกการตั้งค่า</button>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-7">
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h5 class="mb-3">เงื่อนไขรวมคะแนน</h5>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small mb-1">เลือกหมวดกีฬา</label>
              <div id="catChecks" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div class="col-12">
              <label class="form-label small mb-1">เลือกระดับชั้น</label><br>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="lvP" value="P" checked>
                <label class="form-check-label" for="lvP">ประถม (ป..)</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="lvS" value="S" checked>
                <label class="form-check-label" for="lvS">มัธยม (ม..)</label>
              </div>
            </div>
            <div class="col-12 text-end">
              <button id="btnCompute" class="btn btn-success">รวมคะแนน</button>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0">สรุปคะแนนรวม (ปีปัจจุบัน)</h5>
            <button id="btnExport" class="btn btn-outline-secondary btn-sm">ส่งออก CSV</button>
          </div>
          <div class="table-responsive">
            <table class="table table-striped align-middle">
              <thead class="table-light"><tr>
                <th style="width:140px">สี</th>
                <th class="text-end" style="width:140px">คะแนนรวม</th>
                <th>รายละเอียด (ต่อหมวด)</th>
              </tr></thead>
              <tbody id="scoreBody"><tr><td colspan="3" class="text-muted text-center">ยังไม่มีข้อมูล</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
const API = 'referee_api.php';
const COLORS=[{name:'ส้ม',hex:'#FFA726'},{name:'เขียว',hex:'#4CAF50'},{name:'ชมพู',hex:'#EC407A'},{name:'ฟ้า',hex:'#29B6F6'}];

async function init(){
  const res = await fetch(API+'?fn=init',{cache:'no-store'});
  const data = await res.json();
  if(!data.ok) { alert(data.error||'โหลดไม่สำเร็จ'); return; }
  renderRules(data.categories, data.rules);
  renderCategories(data.categories);
}
function renderRules(categories, rules){
  const holder=document.getElementById('ruleList'); holder.innerHTML='';
  const ruleMap = Object.fromEntries(rules.map(r => [String(r.category_id), r]));
  categories.forEach(cat => {
    const r = ruleMap[String(cat.id)] || {rank1:5,rank2:3,rank3:2,rank4:1};
    const row = document.createElement('div');
    row.className='p-3 border rounded-3';
    row.innerHTML = `
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div><b>${cat.name}</b></div>
        <span class="text-muted small">Category ID: ${cat.id}</span>
      </div>
      <div class="row g-2 align-items-center">
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">อันดับ 1</label>
          <input type="number" class="form-control form-control-sm" value="${r.rank1}" data-cat="${cat.id}" data-rank="1">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">อันดับ 2</label>
          <input type="number" class="form-control form-control-sm" value="${r.rank2}" data-cat="${cat.id}" data-rank="2">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">อันดับ 3</label>
          <input type="number" class="form-control form-control-sm" value="${r.rank3}" data-cat="${cat.id}" data-rank="3">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">อันดับ 4</label>
          <input type="number" class="form-control form-control-sm" value="${r.rank4}" data-cat="${cat.id}" data-rank="4">
        </div>
      </div>`;
    holder.appendChild(row);
  });

  document.getElementById('btnSaveRules').onclick = async ()=>{
    const inputs = Array.from(document.querySelectorAll('input[data-cat][data-rank]'));
    const payload = {};
    inputs.forEach(ip => {
      const cat = ip.dataset.cat; const rk = ip.dataset.rank; const val = parseInt(ip.value||'0',10)||0;
      if(!payload[cat]) payload[cat] = {category_id: parseInt(cat,10)};
      payload[cat]['rank'+rk] = val;
    });
    const res = await fetch(API+'?fn=save_rules',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({rules:Object.values(payload)})});
    const out = await res.json(); alert(out.ok?'บันทึกสำเร็จ':'บันทึกไม่สำเร็จ');
  };
}
function renderCategories(categories){
  const box = document.getElementById('catChecks'); box.innerHTML='';
  categories.forEach(cat => {
    const id='cat'+cat.id;
    box.insertAdjacentHTML('beforeend',`
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" id="${id}" value="${cat.id}" checked>
        <label class="form-check-label" for="${id}">${cat.name}</label>
      </div>`);
  });
  document.getElementById('btnCompute').onclick = computeScores;
  document.getElementById('btnExport').onclick = () => exportCSV(',', 'scores.csv');
}
function selectedCategories(){
  return Array.from(document.querySelectorAll('#catChecks input:checked')).map(ip => parseInt(ip.value,10));
}
function selectedLevels(){
  const lv = [];
  if(document.getElementById('lvP').checked) lv.push('P');
  if(document.getElementById('lvS').checked) lv.push('S');
  return lv;
}
async function computeScores(){
  const cats = selectedCategories(); const levels = selectedLevels();
  const res = await fetch(API+'?fn=compute', {method:'POST',headers:{'Content-Type':'application/json'}, body: JSON.stringify({categories: cats, levels: levels})});
  const d = await res.json(); if(!d.ok){ alert(d.error||'คำนวณไม่สำเร็จ'); return; }
  renderScores(d.summary, d.breakdown);
}
function renderScores(summary, breakdown){
  const tb = document.getElementById('scoreBody'); tb.innerHTML='';
  const order = ['ส้ม','เขียว','ชมพู','ฟ้า'];
  order.forEach(col => {
    const total = summary[col] || 0;
    const det = breakdown[col] || {};
    const detailText = Object.keys(det).length ? Object.entries(det).map(([catName,pt])=>`${catName}: ${pt}`).join(' • ') : '-';
    tb.insertAdjacentHTML('beforeend',`
      <tr>
        <td><span class="color-chip"><i class="color-dot" style="background:${(COLORS.find(c=>c.name===col)||{}).hex||'#ccc'}"></i>${col}</span></td>
        <td class="text-end score">${total}</td>
        <td class="text-muted">${detailText}</td>
      </tr>`);
  });
}
function exportCSV(delim = ',', filename = 'scores.csv') {
  const rows = Array.from(document.querySelectorAll('#scoreBody tr')).map(tr =>
    Array.from(tr.children).map(td => td.innerText.replace(/\s+/g, ' ').trim())
  );
  const header = ['สี','คะแนนรวม','รายละเอียด'];
  const toLine = (arr) => arr.map((v) => {
    const s = String(v).replace(/\r?\n/g, ' ').replace(/"/g, '""');
    return (delim === ',') ? `"${s}"` : s;
  }).join(delim);
  const prefix = (delim === ',') ? 'sep=,\r\n' : '';
  const lines  = [toLine(header)].concat(rows.map(toLine)).join('\r\n');
  const BOM    = '\uFEFF';
  const blob   = new Blob([BOM + prefix + lines], {type: 'text/csv;charset=utf-8;'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(a.href);
}

init();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
