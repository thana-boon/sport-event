<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

if (!function_exists('e')) { function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }

$pdo = db();

// ===== ACTION: Export CSV =====
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  // Build WHERE clause (same as display)
  $where = [];
  $params = [];
  
  if (!empty($_GET['action'])) {
    $where[] = "action = ?";
    $params[] = $_GET['action'];
  }
  if (!empty($_GET['user_type'])) {
    $where[] = "user_type = ?";
    $params[] = $_GET['user_type'];
  }
  if (!empty($_GET['table'])) {
    $where[] = "table_name = ?";
    $params[] = $_GET['table'];
  }
  if (!empty($_GET['date_from'])) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $_GET['date_from'];
  }
  if (!empty($_GET['date_to'])) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $_GET['date_to'];
  }
  if (!empty($_GET['search'])) {
    $where[] = "(username LIKE ? OR details LIKE ? OR ip_address LIKE ?)";
    $searchTerm = '%' . trim($_GET['search']) . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
  }
  
  $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
  
  $stmt = $pdo->prepare("SELECT * FROM activity_logs $whereSQL ORDER BY created_at DESC");
  $stmt->execute($params);
  $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Generate CSV
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=activity_logs_' . date('Y-m-d_His') . '.csv');
  
  $output = fopen('php://output', 'w');
  fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
  
  // Header
  fputcsv($output, ['Timestamp', 'User', 'User Type', 'Action', 'Table', 'Record ID', 'Details', 'IP Address']);
  
  // Data
  foreach ($logs as $log) {
    fputcsv($output, [
      $log['created_at'],
      $log['username'],
      $log['user_type'],
      $log['action'],
      $log['table_name'],
      $log['record_id'],
      $log['details'],
      $log['ip_address']
    ]);
  }
  
  fclose($output);
  exit;
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filters
$filterAction = $_GET['action'] ?? '';
$filterUserType = $_GET['user_type'] ?? '';
$filterTable = $_GET['table'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build WHERE clause
$where = [];
$params = [];

if ($filterAction) {
  $where[] = "action = ?";
  $params[] = $filterAction;
}
if ($filterUserType) {
  $where[] = "user_type = ?";
  $params[] = $filterUserType;
}
if ($filterTable) {
  $where[] = "table_name = ?";
  $params[] = $filterTable;
}
if ($filterDateFrom) {
  $where[] = "DATE(created_at) >= ?";
  $params[] = $filterDateFrom;
}
if ($filterDateTo) {
  $where[] = "DATE(created_at) <= ?";
  $params[] = $filterDateTo;
}
if ($search) {
  $where[] = "(username LIKE ? OR details LIKE ? OR ip_address LIKE ?)";
  $searchTerm = '%' . $search . '%';
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $params[] = $searchTerm;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs $whereSQL");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);

// Get logs
$logsStmt = $pdo->prepare("
  SELECT * FROM activity_logs 
  $whereSQL
  ORDER BY created_at DESC 
  LIMIT $perPage OFFSET $offset
");
$logsStmt->execute($params);
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique values for filters
$actions = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$userTypes = $pdo->query("SELECT DISTINCT user_type FROM activity_logs ORDER BY user_type")->fetchAll(PDO::FETCH_COLUMN);
$tables = $pdo->query("SELECT DISTINCT table_name FROM activity_logs WHERE table_name != '' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Activity Logs';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

// Build export URL with current filters
$exportParams = [];
if ($filterAction) $exportParams[] = 'action=' . urlencode($filterAction);
if ($filterUserType) $exportParams[] = 'user_type=' . urlencode($filterUserType);
if ($filterTable) $exportParams[] = 'table=' . urlencode($filterTable);
if ($filterDateFrom) $exportParams[] = 'date_from=' . urlencode($filterDateFrom);
if ($filterDateTo) $exportParams[] = 'date_to=' . urlencode($filterDateTo);
if ($search) $exportParams[] = 'search=' . urlencode($search);
$exportParams[] = 'export=csv';
$exportURL = 'logs.php?' . implode('&', $exportParams);
?>

<style>
  .filter-card {
    border-radius: 0.75rem;
    border: 1px solid rgba(0,0,0,0.06);
  }
  .log-table {
    font-size: 0.875rem;
  }
  .log-details {
    max-width: 400px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .log-details:hover {
    white-space: normal;
    overflow: visible;
  }
  .action-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    font-weight: 600;
  }
  .user-type-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }
</style>

<main class="container-fluid py-4">
  <!-- Header -->
  <div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
      <h4 class="fw-bold mb-1">📜 Activity Logs</h4>
      <p class="text-muted small mb-0">บันทึกการกระทำทั้งหมดในระบบ</p>
    </div>
    <div>
      <a href="<?php echo e($exportURL); ?>" class="btn btn-success btn-sm">
        📥 Export CSV
      </a>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small">📊 Total Logs</div>
              <div class="h4 fw-bold mb-0"><?php echo number_format($totalLogs); ?></div>
            </div>
            <div style="font-size: 2rem; opacity: 0.5;">📋</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small">👥 Unique Users</div>
              <div class="h4 fw-bold mb-0">
                <?php
                $uniqueUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM activity_logs WHERE user_id IS NOT NULL")->fetchColumn();
                echo number_format($uniqueUsers);
                ?>
              </div>
            </div>
            <div style="font-size: 2rem; opacity: 0.5;">👤</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small">📅 Today's Activity</div>
              <div class="h4 fw-bold mb-0">
                <?php
                $todayLogs = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
                echo number_format($todayLogs);
                ?>
              </div>
            </div>
            <div style="font-size: 2rem; opacity: 0.5;">🕐</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small">⚠️ Errors</div>
              <div class="h4 fw-bold mb-0 text-danger">
                <?php
                $errors = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action LIKE '%ERROR%' OR action LIKE '%FAIL%'")->fetchColumn();
                echo number_format($errors);
                ?>
              </div>
            </div>
            <div style="font-size: 2rem; opacity: 0.5;">🚨</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="card filter-card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="" class="row g-3">
        <div class="col-md-2">
          <label class="form-label small fw-semibold">🔍 Search</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Username, IP, Details..." value="<?php echo e($search); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">⚡ Action</label>
          <select name="action" class="form-select form-select-sm">
            <option value="">All Actions</option>
            <?php foreach($actions as $act): ?>
              <option value="<?php echo e($act); ?>" <?php echo $filterAction === $act ? 'selected' : ''; ?>>
                <?php echo e($act); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">👤 User Type</label>
          <select name="user_type" class="form-select form-select-sm">
            <option value="">All Types</option>
            <?php foreach($userTypes as $type): ?>
              <option value="<?php echo e($type); ?>" <?php echo $filterUserType === $type ? 'selected' : ''; ?>>
                <?php echo e($type); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">📊 Table</label>
          <select name="table" class="form-select form-select-sm">
            <option value="">All Tables</option>
            <?php foreach($tables as $tbl): ?>
              <option value="<?php echo e($tbl); ?>" <?php echo $filterTable === $tbl ? 'selected' : ''; ?>>
                <?php echo e($tbl); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">📅 From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo e($filterDateFrom); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">📅 To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo e($filterDateTo); ?>">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary btn-sm">🔍 Filter</button>
          <a href="logs.php" class="btn btn-outline-secondary btn-sm">🔄 Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Logs Table -->
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover log-table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 14%;">⏰ Timestamp</th>
              <th style="width: 14%;">👤 User</th>
              <th style="width: 8%;">Type</th>
              <th style="width: 12%;">⚡ Action</th>
              <th style="width: 12%;">📊 Table</th>
              <th style="width: 30%;">📝 Details</th>
              <th style="width: 10%;">🌐 IP</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  <div style="font-size: 3rem; opacity: 0.3;">📭</div>
                  <p class="mb-0">No logs found</p>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach($logs as $log):
                // Action badge color
                $actionColor = 'secondary';
                if (stripos($log['action'], 'CREATE') !== false) $actionColor = 'success';
                elseif (stripos($log['action'], 'UPDATE') !== false) $actionColor = 'info';
                elseif (stripos($log['action'], 'DELETE') !== false) $actionColor = 'danger';
                elseif (stripos($log['action'], 'LOGIN') !== false) $actionColor = 'primary';
                elseif (stripos($log['action'], 'ERROR') !== false || stripos($log['action'], 'FAIL') !== false) $actionColor = 'danger';
                elseif (stripos($log['action'], 'EXPORT') !== false) $actionColor = 'warning';
                
                // User type badge color
                $userTypeColor = 'secondary';
                if ($log['user_type'] === 'admin') $userTypeColor = 'primary';
                elseif ($log['user_type'] === 'staff') $userTypeColor = 'success';
              ?>
              <tr>
                <td>
                  <div class="small"><?php echo date('d/m/Y', strtotime($log['created_at'])); ?></div>
                  <div class="text-muted" style="font-size: 0.75rem;"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></div>
                </td>
                <td>
                  <div class="fw-semibold"><?php echo e($log['username']); ?></div>
                  <?php if ($log['user_id']): ?>
                    <div class="text-muted" style="font-size: 0.7rem;">ID: <?php echo $log['user_id']; ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-<?php echo $userTypeColor; ?> user-type-badge">
                    <?php echo strtoupper(e($log['user_type'])); ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-<?php echo $actionColor; ?> action-badge">
                    <?php echo e($log['action']); ?>
                  </span>
                </td>
                <td>
                  <?php if ($log['table_name']): ?>
                    <code class="small"><?php echo e($log['table_name']); ?></code>
                    <?php if ($log['record_id']): ?>
                      <div class="text-muted" style="font-size: 0.7rem;">ID: <?php echo $log['record_id']; ?></div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="log-details" title="<?php echo e($log['details']); ?>">
                    <?php echo e($log['details'] ?: '—'); ?>
                  </div>
                </td>
                <td>
                  <code class="small"><?php echo e($log['ip_address']); ?></code>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
          <ul class="pagination pagination-sm justify-content-center mb-0">
            <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUserType ? '&user_type=' . urlencode($filterUserType) : ''; ?><?php echo $filterTable ? '&table=' . urlencode($filterTable) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">« Previous</a>
              </li>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
              <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUserType ? '&user_type=' . urlencode($filterUserType) : ''; ?><?php echo $filterTable ? '&table=' . urlencode($filterTable) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                  <?php echo $i; ?>
                </a>
              </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUserType ? '&user_type=' . urlencode($filterUserType) : ''; ?><?php echo $filterTable ? '&table=' . urlencode($filterTable) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Next »</a>
              </li>
            <?php endif; ?>
          </ul>
          <div class="text-center text-muted small mt-2">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?> (Total: <?php echo number_format($totalLogs); ?> logs)
          </div>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>