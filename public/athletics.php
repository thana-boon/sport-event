<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

if (!function_exists('e')) { function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
function flash($k,$v=null){ if($v===null){ $x=$_SESSION['__flash'][$k]??null; unset($_SESSION['__flash'][$k]); return $x; } $_SESSION['__flash'][$k]=$v; }

$pdo = db();
$yearId = active_year_id($pdo);
if (!$yearId) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้ Active</div></main>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// ------------ helpers ------------
const COLORS = ['เขียว','ส้ม','ชมพู','ฟ้า'];

function safeCommit(PDO $pdo){ if ($pdo->inTransaction()) $pdo->commit(); }
function safeRollback(PDO $pdo){ if ($pdo->inTransaction()) $pdo->rollBack(); }

function bgColorHex($c){
  switch($c){
    case 'เขียว': return '#4caf50';
    case 'ฟ้า':   return '#2196f3';
    case 'ชมพู':  return '#e91e63';
    case 'ส้ม':   return '#ff9800';
    default:      return '#9e9e9e';
  }
}

// ------------ load athletics sports ------------
$st = $pdo->prepare("
  SELECT s.id, s.name, s.gender, s.participant_type, s.grade_levels, s.team_size, sc.name AS cat_name
  FROM sports s
  JOIN sport_categories sc ON sc.id=s.category_id
  WHERE s.year_id = ? AND s.is_active = 1 AND sc.name LIKE '%กรีฑ%'
  ORDER BY s.id
");
$st->execute([$yearId]);
$sports = $st->fetchAll(PDO::FETCH_ASSOC);
$spMap = [];
foreach($sports as $row){ $spMap[(int)$row['id']] = $row; }

// ------------ core db ops ------------
function clear_heats(PDO $pdo, int $yearId, int $sportId){
  $q = $pdo->prepare("SELECT id FROM track_heats WHERE year_id=? AND sport_id=?");
  $q->execute([$yearId, $sportId]);
  $heatIds = $q->fetchAll(PDO::FETCH_COLUMN);
  if ($heatIds){
    $in = implode(',', array_fill(0, count($heatIds), '?'));
    $pdo->prepare("DELETE FROM track_lane_assignments WHERE heat_id IN ($in)")->execute($heatIds);
  }
  $pdo->prepare("DELETE FROM track_heats WHERE year_id=? AND sport_id=?")->execute([$yearId, $sportId]);
}

// ------------ actions ------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  // สุ่มทั้งหมด - แบบหมุนเวียนสี
  if ($action === 'gen_all') {
    try {
      if (!$pdo->inTransaction()) $pdo->beginTransaction();
      
      // สุ่มลำดับสีเริ่มต้น
      $baseColors = COLORS;
      shuffle($baseColors);
      
      $totalHeats = 0;
      $totalLanes = 0;
      $sportDetails = [];
      
      foreach ($sports as $index => $sp) {
        $sid = (int)$sp['id'];
        $isRelay = ($sp['participant_type'] === 'ทีม');
        $teamSize = (int)($sp['team_size'] ?? 2);
        
        // คำนวณจำนวนลู่
        if ($isRelay) {
          $lanesPerColor = 1; // ผลัด: 1 ลู่/สี = 4 ลู่
        } else {
          $lanesPerColor = $teamSize; // เดี่ยว: team_size ลู่/สี
        }
        $lanesUsed = $lanesPerColor * 4;
        
        // หมุนเวียนสีตามลำดับรายการ (เลื่อน 1 ตำแหน่งในแต่ละรายการ)
        $rotateAmount = $index % 4;
        $currentColors = $baseColors;
        for ($i = 0; $i < $rotateAmount; $i++) {
          $first = array_shift($currentColors);
          $currentColors[] = $first;
        }
        
        // ล้างของเดิม
        clear_heats($pdo, $yearId, $sid);
        
        // สร้าง heat ใหม่
        $insHeat = $pdo->prepare("INSERT INTO track_heats (year_id, sport_id, heat_no, lanes_used, created_at) VALUES (?, ?, 1, ?, NOW())");
        $insHeat->execute([$yearId, $sid, $lanesUsed]);
        $heatId = (int)$pdo->lastInsertId();
        
        // กำหนดสีลงลู่ - แบบใหม่: ลู่ 1-4 ไม่ซ้ำสี, ลู่ 5-8 วนซ้ำตาม 1-4
        $laneNo = 1;
        $laneDetails = [];
        $insLane = $pdo->prepare("INSERT INTO track_lane_assignments (heat_id, lane_no, color, registration_id, created_at) VALUES (?, ?, ?, NULL, NOW())");
        
        // วนตาม lanesPerColor รอบ (เช่น ถ้า team_size=2 จะวน 2 รอบ)
        for ($round = 0; $round < $lanesPerColor; $round++) {
          // ในแต่ละรอบให้วางสีทั้ง 4 สีตามลำดับ
          foreach ($currentColors as $color) {
            $insLane->execute([$heatId, $laneNo, $color]);
            $laneDetails[] = "ลู่{$laneNo}:{$color}";
            $laneNo++;
            $totalLanes++;
          }
        }
        
        $totalHeats++;
        $lbl = $isRelay ? 'ผลัด' : 'เดี่ยว';
        $sportDetails[] = "{$sp['name']} ({$lbl}, {$lanesUsed}ลู่)";
      }
      
      safeCommit($pdo);
      
      log_activity('CREATE', 'track_heats', null, 
        sprintf("สุ่มลู่กรีฑาทั้งหมด: %d รายการ, %d ลู่ | [%s] | ปี ID:%d", 
          $totalHeats, $totalLanes, implode(', ', $sportDetails), $yearId));
      
      flash('ok', "สุ่มลู่ทั้งหมดสำเร็จ\n{$totalHeats} รายการ, {$totalLanes} ลู่");
    } catch (Throwable $e) {
      safeRollback($pdo);
      log_activity('ERROR', 'track_heats', null, "สุ่มลู่กรีฑาทั้งหมดไม่สำเร็จ: " . $e->getMessage());
      flash('err', 'สุ่มทั้งหมดไม่สำเร็จ: ' . $e->getMessage());
    }
    header('Location: ' . BASE_URL . '/athletics.php'); exit;
  }
  
  // ล้างทั้งหมด
  if ($action === 'clear_all') {
    try {
      if (!$pdo->inTransaction()) $pdo->beginTransaction();
      
      $totalDeleted = 0;
      $sportDetails = [];
      
      foreach ($sports as $sp) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM track_heats WHERE year_id=? AND sport_id=?");
        $countStmt->execute([$yearId, (int)$sp['id']]);
        $count = (int)$countStmt->fetchColumn();
        
        if ($count > 0) {
          $lbl = ($sp['participant_type'] === 'ทีม') ? 'ผลัด' : 'เดี่ยว';
          $sportDetails[] = "{$sp['name']} ({$lbl}, {$count}ฮีต)";
          $totalDeleted += $count;
        }
        
        clear_heats($pdo, $yearId, (int)$sp['id']);
      }
      
      safeCommit($pdo);
      
      log_activity('DELETE', 'track_heats', null, 
        sprintf("ล้างลู่กรีฑาทั้งหมด: %d ฮีต | [%s] | ปี ID:%d", 
          $totalDeleted, implode(', ', $sportDetails), $yearId));
      
      flash('ok', "ล้างลู่ทั้งหมดแล้ว\nลบ {$totalDeleted} ฮีต");
    } catch (Throwable $e) {
      safeRollback($pdo);
      log_activity('ERROR', 'track_heats', null, "ล้างลู่กรีฑาทั้งหมดไม่สำเร็จ: " . $e->getMessage());
      flash('err', 'ล้างทั้งหมดไม่สำเร็จ: ' . $e->getMessage());
    }
    header('Location: ' . BASE_URL . '/athletics.php'); exit;
  }
}

// ------------ page ------------
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
$ok = flash('ok'); $err = flash('err');
?>

<!-- เพิ่ม SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  .swal2-popup {
    font-family: 'Kanit', sans-serif;
  }
  .lane-card {
    background: white;
    border-radius: 0.5rem;
    padding: 0.75rem;
    text-align: center;
    border: 2px solid;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
</style>

<main class="container py-4">
  <?php if($ok): ?>
    <script>
      Swal.fire({
        icon: 'success',
        title: 'สำเร็จ!',
        html: '<?= str_replace("\n", "<br>", addslashes($ok)) ?>',
        confirmButtonText: 'ตรวจสอบ',
        confirmButtonColor: '#0d6efd',
        timer: 3000,
        timerProgressBar: true
      });
    </script>
  <?php endif; ?>
  
  <?php if($err): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด!',
        html: '<?= str_replace("\n", "<br>", addslashes($err)) ?>',
        confirmButtonText: 'ตรวจสอบ',
        confirmButtonColor: '#dc3545'
      });
    </script>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1">🏃‍♂️ จัดลู่กรีฑา</h5>
      <div class="text-muted small">
        สุ่มครั้งเดียวทั้งหมด - รายการแรกสุ่มลำดับสี, รายการถัดไปหมุนเวียนสี
      </div>
    </div>
    <div class="d-flex gap-2">
      <form method="post" id="genAllForm">
        <input type="hidden" name="action" value="gen_all">
        <button type="button" class="btn btn-success" onclick="confirmGenAll()">
          <i class="bi bi-shuffle"></i> สุ่มทั้งหมด
        </button>
      </form>
      <form method="post" id="clearAllForm">
        <input type="hidden" name="action" value="clear_all">
        <button type="button" class="btn btn-outline-danger" onclick="confirmClearAll()">
          <i class="bi bi-x-circle"></i> ล้างทั้งหมด
        </button>
      </form>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>กีฬา</th>
            <th>เพศ</th>
            <th>รูปแบบ</th>
            <th>ชั้นที่เปิด</th>
            <th class="text-center">ลู่ที่ใช้</th>
            <th class="text-end">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$sports): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">ยังไม่มีกรีฑาที่เปิดใช้งาน</td></tr>
          <?php endif; ?>
          <?php foreach($sports as $s): 
            $sid = (int)$s['id'];
            $isRelay = ($s['participant_type'] === 'ทีม');
            $teamSize = (int)($s['team_size'] ?? 2);
            $lanesPerColor = $isRelay ? 1 : $teamSize;
            $totalLanes = $lanesPerColor * 4;
          ?>
            <tr>
              <td class="fw-semibold"><?php echo e($s['name']); ?></td>
              <td><?php echo e($s['gender']); ?></td>
              <td><?php echo e($s['participant_type']); ?></td>
              <td><span class="text-muted"><?php echo e($s['grade_levels']?:'-'); ?></span></td>
              <td class="text-center">
                <span class="badge bg-info"><?= $totalLanes ?> ลู่</span>
                <br><small class="text-muted"><?= $lanesPerColor ?> ลู่/สี</small>
              </td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#v<?= $sid ?>">
                  <i class="bi bi-eye"></i> ดูลู่
                </button>
              </td>
            </tr>

            <!-- Modal ดูลู่ -->
            <div class="modal fade" id="v<?= $sid ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">
                      ลู่วิ่ง — <?= e($s['name']) ?> 
                      <span class="badge bg-secondary"><?= $isRelay ? 'ผลัด' : 'เดี่ยว' ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <?php
                      $qh=$pdo->prepare("SELECT id, lanes_used FROM track_heats WHERE year_id=? AND sport_id=? ORDER BY id DESC LIMIT 1");
                      $qh->execute([$yearId, $sid]);
                      $heat=$qh->fetch(PDO::FETCH_ASSOC);
                      
                      if (!$heat){
                        echo '<div class="alert alert-warning">ยังไม่ได้สุ่มลู่ กรุณากดปุ่ม "สุ่มทั้งหมด"</div>';
                      } else {
                        $qa=$pdo->prepare("SELECT lane_no, color FROM track_lane_assignments WHERE heat_id=? ORDER BY lane_no");
                        $qa->execute([(int)$heat['id']]);
                        $lanes=$qa->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!$lanes){ 
                          echo '<div class="text-muted">ยังไม่มีการจัดลู่</div>'; 
                        } else {
                          echo '<div class="row g-3">';
                          foreach($lanes as $ln){
                            $bg = bgColorHex($ln['color']);
                            echo '<div class="col-6 col-md-3">';
                            echo '<div class="lane-card" style="border-color: '.$bg.'; color: '.$bg.';">';
                            echo '<div class="h3 mb-1">' . (int)$ln['lane_no'] . '</div>';
                            echo '<div class="fw-semibold">สี' . e($ln['color']) . '</div>';
                            echo '</div></div>';
                          }
                          echo '</div>';
                        }
                      }
                    ?>
                  </div>
                  <div class="modal-footer">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
                  </div>
                </div>
              </div>
            </div>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script>
  async function confirmGenAll() {
    const result = await Swal.fire({
      icon: 'question',
      title: 'สุ่มลู่ทั้งหมด?',
      html: 'สุ่มลู่วิ่งสำหรับทุกรายการกรีฑา<br><span class="text-warning fw-bold">ลู่เดิม (ถ้ามี) จะถูกลบและสุ่มใหม่</span><br><br><small class="text-muted">รายการแรกจะสุ่มลำดับสี แล้วแต่ละรายการจะหมุนเวียนสีไปทีละ 1 ตำแหน่ง</small>',
      showCancelButton: true,
      confirmButtonText: 'สุ่มเลย',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#198754',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    });
    
    if (result.isConfirmed) {
      // แสดง animation กำลังสุ่ม
      showRandomizingAnimation();
      
      // Submit form หลังจาก animation เสร็จและรอ 2 วินาที
      setTimeout(() => {
        document.getElementById('genAllForm').submit();
      }, 5000); // 3 วิสำหรับ animation + 2 วิแสดงผล
    }
  }
  
  function showRandomizingAnimation() {
    const colors = ['เขียว', 'ส้ม', 'ชมพู', 'ฟ้า'];
    const colorStyles = {
      'เขียว': '#4caf50',
      'ส้ม': '#ff9800',
      'ชมพู': '#e91e63',
      'ฟ้า': '#2196f3'
    };
    
    let shuffleInterval;
    let currentAssignments = []; // ลู่ 1-8 กับสีที่ถูกสุ่ม
    let shuffleCount = 0;
    const maxShuffles = 20;
    const totalLanes = 8;
    
    // สร้างการกำหนดลู่เริ่มต้น
    for (let i = 0; i < totalLanes; i++) {
      currentAssignments[i] = colors[i % 4];
    }
    
    Swal.fire({
      title: '<div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🏃 กำลังจัดลู่วิ่ง... 🏁</div>',
      html: `
        <div style="padding: 20px;">
          <div id="trackDisplay" style="margin: 30px 0;">
            ${Array.from({length: totalLanes}, (_, i) => {
              const lane = i + 1;
              const color = currentAssignments[i];
              return `
                <div class="track-lane" data-lane="${lane}" style="
                  display: flex;
                  align-items: center;
                  margin: 8px 0;
                  padding: 12px;
                  background: linear-gradient(90deg, ${colorStyles[color]} 0%, ${colorStyles[color]}dd 100%);
                  border-radius: 8px;
                  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                  transition: all 0.3s;
                  position: relative;
                  overflow: hidden;
                ">
                  <div style="
                    background: white;
                    color: #333;
                    font-weight: bold;
                    padding: 8px 16px;
                    border-radius: 6px;
                    min-width: 80px;
                    text-align: center;
                    margin-right: 15px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                  ">ลู่ ${lane}</div>
                  <div class="lane-color" style="
                    color: white;
                    font-weight: bold;
                    font-size: 1.1rem;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
                  ">สี${color}</div>
                  <div class="runner-icon" style="
                    position: absolute;
                    right: 20px;
                    font-size: 1.5rem;
                  ">🏃</div>
                </div>
              `;
            }).join('')}
          </div>
          <div style="margin: 20px 0;">
            <div class="progress" style="height: 8px; border-radius: 10px; background: #e9ecef;">
              <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background: linear-gradient(90deg, #198754, #20c997);"></div>
            </div>
          </div>
          <div id="shuffleCounter" style="color: #6c757d; font-size: 0.9rem; margin-top: 10px;">กำลังจัดลู่วิ่ง...</div>
        </div>
      `,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      width: '600px',
      didOpen: () => {
        const progressBar = document.getElementById('progressBar');
        const shuffleCounter = document.getElementById('shuffleCounter');
        const trackLanes = document.querySelectorAll('.track-lane');
        
        // Animation สุ่มสีในลู่
        shuffleInterval = setInterval(() => {
          shuffleCount++;
          const progress = Math.min((shuffleCount / maxShuffles) * 100, 95);
          progressBar.style.width = progress + '%';
          
          // สุ่มสีใหม่สำหรับแต่ละลู่
          for (let i = 0; i < totalLanes; i++) {
            currentAssignments[i] = colors[Math.floor(Math.random() * colors.length)];
          }
          
          // Update แต่ละลู่ด้วย animation
          trackLanes.forEach((lane, idx) => {
            const newColor = currentAssignments[idx];
            lane.style.transform = 'translateX(-10px)';
            lane.style.opacity = '0.7';
            
            setTimeout(() => {
              lane.style.background = `linear-gradient(90deg, ${colorStyles[newColor]} 0%, ${colorStyles[newColor]}dd 100%)`;
              lane.querySelector('.lane-color').textContent = 'สี' + newColor;
              lane.style.transform = 'translateX(0)';
              lane.style.opacity = '1';
            }, 100);
          });
          
          shuffleCounter.innerHTML = `กำลังจัดลู่วิ่ง... <strong style="color: #198754;">${shuffleCount}/${maxShuffles}</strong>`;
          
          if (shuffleCount >= maxShuffles) {
            clearInterval(shuffleInterval);
            progressBar.style.width = '100%';
            shuffleCounter.innerHTML = '<strong style="color: #198754;">✓ เสร็จสิ้น! กำลังบันทึกข้อมูล...</strong>';
            
            // เพิ่ม final animation เมื่อเสร็จ
            trackLanes.forEach((lane, idx) => {
              setTimeout(() => {
                lane.style.boxShadow = '0 4px 12px rgba(25, 135, 84, 0.4)';
                lane.style.transform = 'scale(1.02)';
                
                setTimeout(() => {
                  lane.style.transform = 'scale(1)';
                }, 200);
              }, idx * 50);
            });
          }
        }, 150);
      }
    });
  }

  async function confirmClearAll() {
    const result = await Swal.fire({
      icon: 'warning',
      title: 'ล้างลู่ทั้งหมด?',
      html: 'ลบการจัดลู่ทั้งหมด<br><span class="text-danger fw-bold">ไม่สามารถกู้คืนได้!</span>',
      showCancelButton: true,
      confirmButtonText: 'ยืนยันการลบ',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    });
    
    if (result.isConfirmed) {
      document.getElementById('clearAllForm').submit();
    }
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
