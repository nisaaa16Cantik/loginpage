<?php
// ============================================================
//  KONFIGURASI DATABASE — sesuaikan dengan milikmu
// ============================================================
$DB_HOST = "localhost";
$DB_NAME = "nisa_database";
$DB_USER = "root";
$DB_PASS = "";

// ============================================================
//  KONEKSI
// ============================================================
try {
    $conn = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8", $DB_USER, $DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// ============================================================
//  AUTO-CREATE TABLE mahasiswa
// ============================================================
$conn->exec("CREATE TABLE IF NOT EXISTS mahasiswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    jurusan VARCHAR(100) NOT NULL,
    angkatan YEAR NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ============================================================
//  SESSION & CEK LOGIN
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_system.php");
    exit;
}

// ============================================================
//  LOGIKA CRUD
// ============================================================
$message = "";
$msgType = "";
$editData = null;
$action = $_GET['action'] ?? 'list';
$id     = $_GET['id']     ?? null;

// --- TAMBAH ---
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nim      = trim($_POST['nim']);
    $nama     = trim($_POST['nama']);
    $jurusan  = trim($_POST['jurusan']);
    $angkatan = trim($_POST['angkatan']);

    if (empty($nim) || empty($nama) || empty($jurusan) || empty($angkatan)) {
        $message = "Semua field wajib diisi!";
        $msgType = "error";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO mahasiswa (nim, nama, jurusan, angkatan) VALUES (?,?,?,?)");
            $stmt->execute([$nim, $nama, $jurusan, $angkatan]);
            $message = "Data mahasiswa berhasil ditambahkan!";
            $msgType = "success";
        } catch (PDOException $e) {
            $message = "NIM sudah terdaftar!";
            $msgType = "error";
        }
    }
}

// --- HAPUS ---
if ($action === 'hapus' && $id) {
    $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: mahasiswa.php?msg=hapus");
    exit;
}

// --- AMBIL DATA UNTUK EDIT ---
if ($action === 'edit' && $id) {
    $stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- PROSES UPDATE ---
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim      = trim($_POST['nim']);
    $nama     = trim($_POST['nama']);
    $jurusan  = trim($_POST['jurusan']);
    $angkatan = trim($_POST['angkatan']);
    $edit_id  = $_POST['edit_id'];

    try {
        $stmt = $conn->prepare("UPDATE mahasiswa SET nim=?, nama=?, jurusan=?, angkatan=? WHERE id=?");
        $stmt->execute([$nim, $nama, $jurusan, $angkatan, $edit_id]);
        header("Location: mahasiswa.php?msg=update");
        exit;
    } catch (PDOException $e) {
        $message = "NIM sudah dipakai mahasiswa lain!";
        $msgType = "error";
        $action  = 'edit';
        $editData = ['id'=>$edit_id,'nim'=>$nim,'nama'=>$nama,'jurusan'=>$jurusan,'angkatan'=>$angkatan];
    }
}

// --- PESAN REDIRECT ---
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'hapus')  { $message = "Data berhasil dihapus.";   $msgType = "success"; }
    if ($_GET['msg'] === 'update') { $message = "Data berhasil diperbarui."; $msgType = "success"; }
}

// --- AMBIL SEMUA DATA + SEARCH ---
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE nim LIKE ? OR nama LIKE ? OR jurusan LIKE ? ORDER BY id DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $conn->query("SELECT * FROM mahasiswa ORDER BY id DESC");
}
$mahasiswaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalData = count($mahasiswaList);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Mahasiswa</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap');

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:      #f0f2f7;
    --surface: #ffffff;
    --accent:  #2563eb;
    --accent2: #7c3aed;
    --dark:    #0f172a;
    --text:    #1e293b;
    --muted:   #64748b;
    --border:  #e2e8f0;
    --error:   #ef4444;
    --success: #16a34a;
    --radius:  12px;
  }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    background-image:
      radial-gradient(ellipse 70% 40% at 90% 10%, rgba(37,99,235,.07) 0%, transparent 60%),
      radial-gradient(ellipse 50% 30% at 10% 90%, rgba(124,58,237,.06) 0%, transparent 60%);
  }

  /* ── NAVBAR ── */
  nav {
    background: var(--dark);
    padding: .9rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 2px 20px rgba(0,0,0,.2);
  }

  .nav-brand {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.15rem;
    color: #fff;
    display: flex; align-items: center; gap: .6rem;
  }

  .nav-brand span {
    background: linear-gradient(135deg, #60a5fa, #a78bfa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .nav-right { display: flex; align-items: center; gap: 1rem; }

  .nav-user {
    font-size: .85rem;
    color: #94a3b8;
  }

  .nav-user strong { color: #e2e8f0; }

  .btn-nav {
    padding: .4rem .9rem;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.12);
    color: #cbd5e1;
    border-radius: 8px;
    font-size: .82rem;
    font-family: inherit;
    cursor: pointer;
    text-decoration: none;
    transition: background .2s;
  }
  .btn-nav:hover { background: rgba(255,255,255,.15); color: #fff; }

  /* ── CONTAINER ── */
  .container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
  }

  /* ── PAGE HEADER ── */
  .page-header {
    margin-bottom: 1.8rem;
    animation: fadeUp .4s ease both;
  }

  .page-header h1 {
    font-family: 'Syne', sans-serif;
    font-size: 1.9rem;
    font-weight: 800;
    color: var(--dark);
    letter-spacing: -.03em;
  }

  .page-header p { color: var(--muted); font-size: .9rem; margin-top: .3rem; }

  /* ── STATS ── */
  .stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    animation: fadeUp .4s .1s ease both;
  }

  .stat-chip {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: .6rem 1.1rem;
    font-size: .83rem;
    color: var(--muted);
    display: flex; align-items: center; gap: .4rem;
  }

  .stat-chip strong { color: var(--accent); font-size: 1rem; }

  /* ── ALERT ── */
  .alert {
    padding: .75rem 1rem;
    border-radius: 10px;
    font-size: .88rem;
    margin-bottom: 1.2rem;
    border: 1px solid;
    animation: fadeUp .3s ease both;
  }
  .alert.error   { background: #fef2f2; border-color: #fecaca; color: var(--error); }
  .alert.success { background: #f0fdf4; border-color: #bbf7d0; color: var(--success); }

  /* ── CARD ── */
  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: calc(var(--radius) + 4px);
    padding: 1.6rem;
    box-shadow: 0 1px 12px rgba(0,0,0,.06);
    animation: fadeUp .4s .15s ease both;
  }

  .card h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 1.2rem;
    color: var(--dark);
    display: flex; align-items: center; gap: .5rem;
  }

  /* ── FORM GRID ── */
  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .9rem;
  }

  @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }

  .field { display: flex; flex-direction: column; gap: .35rem; }

  label {
    font-size: .75rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .06em;
  }

  input[type=text], input[type=number], select {
    padding: .65rem .9rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-family: inherit;
    font-size: .9rem;
    color: var(--text);
    background: var(--bg);
    outline: none;
    transition: border-color .2s, box-shadow .2s;
  }

  input:focus, select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    background: #fff;
  }

  .form-actions {
    display: flex;
    gap: .7rem;
    margin-top: 1rem;
    grid-column: 1 / -1;
  }

  .btn {
    padding: .65rem 1.4rem;
    border: none;
    border-radius: var(--radius);
    font-family: inherit;
    font-size: .88rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .2s, transform .15s;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: .4rem;
  }
  .btn:hover  { opacity: .85; transform: translateY(-1px); }
  .btn:active { transform: translateY(0); }

  .btn-primary { background: var(--accent); color: #fff; }
  .btn-warning { background: #f59e0b; color: #fff; }
  .btn-danger  { background: var(--error); color: #fff; }
  .btn-ghost   { background: var(--bg); border: 1px solid var(--border); color: var(--muted); }

  /* ── SEARCH BAR ── */
  .toolbar {
    display: flex;
    gap: .8rem;
    align-items: center;
    margin-bottom: 1rem;
  }

  .search-wrap {
    position: relative;
    flex: 1;
  }

  .search-wrap input {
    width: 100%;
    padding-left: 2.4rem;
  }

  .search-icon {
    position: absolute;
    left: .8rem; top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: .9rem;
    pointer-events: none;
  }

  /* ── TABLE ── */
  .table-wrap {
    overflow-x: auto;
    border-radius: var(--radius);
    border: 1px solid var(--border);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: .88rem;
  }

  thead {
    background: var(--dark);
    color: #fff;
  }

  th {
    padding: .85rem 1rem;
    text-align: left;
    font-family: 'Syne', sans-serif;
    font-weight: 600;
    font-size: .8rem;
    letter-spacing: .05em;
    text-transform: uppercase;
  }

  td {
    padding: .8rem 1rem;
    border-top: 1px solid var(--border);
    vertical-align: middle;
  }

  tr:hover td { background: #f8fafc; }

  .nim-badge {
    background: rgba(37,99,235,.08);
    color: var(--accent);
    padding: .2rem .6rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: .82rem;
    font-family: monospace;
  }

  .angkatan-badge {
    background: rgba(124,58,237,.08);
    color: var(--accent2);
    padding: .2rem .6rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: .82rem;
  }

  .action-btns { display: flex; gap: .5rem; }

  .btn-sm {
    padding: .35rem .8rem;
    font-size: .8rem;
    border-radius: 8px;
  }

  .empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--muted);
  }

  .empty-state .icon { font-size: 2.5rem; margin-bottom: .8rem; }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .layout { display: grid; grid-template-columns: 380px 1fr; gap: 1.5rem; align-items: start; }
  @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav>
  <div class="nav-brand">🎓 <span>SiMahasiswa</span></div>
  <div class="nav-right">
    <span class="nav-user">Halo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
    <a href="login_system.php?page=logout" class="btn-nav">Logout</a>
  </div>
</nav>

<div class="container">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <h1>Data Mahasiswa</h1>
    <p>Kelola data mahasiswa — tambah, edit, dan hapus data dengan mudah</p>
  </div>

  <!-- STATS -->
  <div class="stats">
    <div class="stat-chip">Total: <strong><?= $conn->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn() ?></strong> mahasiswa</div>
    <?php if ($search): ?>
      <div class="stat-chip">Hasil pencarian: <strong><?= $totalData ?></strong> ditemukan</div>
    <?php endif; ?>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="layout">

    <!-- FORM TAMBAH / EDIT -->
    <div class="card" style="animation-delay:.05s">
      <?php if ($action === 'edit' && $editData): ?>
        <h2>✏️ Edit Data Mahasiswa</h2>
        <form method="POST" action="mahasiswa.php?action=update">
          <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
          <div class="form-grid">
            <div class="field">
              <label>NIM</label>
              <input type="text" name="nim" value="<?= htmlspecialchars($editData['nim']) ?>" required>
            </div>
            <div class="field">
              <label>Nama Lengkap</label>
              <input type="text" name="nama" value="<?= htmlspecialchars($editData['nama']) ?>" required>
            </div>
            <div class="field">
              <label>Jurusan</label>
              <input type="text" name="jurusan" value="<?= htmlspecialchars($editData['jurusan']) ?>" required>
            </div>
            <div class="field">
              <label>Angkatan</label>
              <input type="number" name="angkatan" min="2000" max="2099" value="<?= htmlspecialchars($editData['angkatan']) ?>" required>
            </div>
            <div class="form-actions">
              <button class="btn btn-warning" type="submit">💾 Simpan Perubahan</button>
              <a href="mahasiswa.php" class="btn btn-ghost">Batal</a>
            </div>
          </div>
        </form>
      <?php else: ?>
        <h2>➕ Tambah Mahasiswa</h2>
        <form method="POST" action="mahasiswa.php">
          <div class="form-grid">
            <div class="field">
              <label>NIM</label>
              <input type="text" name="nim" placeholder="Contoh: 2024001" required>
            </div>
            <div class="field">
              <label>Nama Lengkap</label>
              <input type="text" name="nama" placeholder="Nama mahasiswa" required>
            </div>
            <div class="field">
              <label>Jurusan</label>
              <input type="text" name="jurusan" placeholder="Contoh: Informatika" required>
            </div>
            <div class="field">
              <label>Angkatan</label>
              <input type="number" name="angkatan" placeholder="Contoh: 2024" min="2000" max="2099" required>
            </div>
            <div class="form-actions">
              <button class="btn btn-primary" type="submit" name="tambah">➕ Tambah Data</button>
            </div>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <!-- TABEL DATA -->
    <div class="card" style="animation-delay:.1s">
      <h2>📋 Daftar Mahasiswa</h2>

      <!-- SEARCH -->
      <div class="toolbar">
        <form method="GET" action="mahasiswa.php" style="display:flex;gap:.7rem;flex:1">
          <div class="search-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" name="search" placeholder="Cari NIM, nama, atau jurusan..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <button class="btn btn-primary" type="submit">Cari</button>
          <?php if ($search): ?>
            <a href="mahasiswa.php" class="btn btn-ghost">Reset</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>NIM</th>
              <th>Nama</th>
              <th>Jurusan</th>
              <th>Angkatan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($mahasiswaList)): ?>
              <tr>
                <td colspan="6">
                  <div class="empty-state">
                    <div class="icon">🎓</div>
                    <div><?= $search ? "Tidak ada hasil untuk \"$search\"" : "Belum ada data mahasiswa" ?></div>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($mahasiswaList as $i => $mhs): ?>
              <tr>
                <td style="color:var(--muted);font-size:.8rem"><?= $i + 1 ?></td>
                <td><span class="nim-badge"><?= htmlspecialchars($mhs['nim']) ?></span></td>
                <td><strong><?= htmlspecialchars($mhs['nama']) ?></strong></td>
                <td><?= htmlspecialchars($mhs['jurusan']) ?></td>
                <td><span class="angkatan-badge"><?= htmlspecialchars($mhs['angkatan']) ?></span></td>
                <td>
                  <div class="action-btns">
                    <a href="mahasiswa.php?action=edit&id=<?= $mhs['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                    <a href="mahasiswa.php?action=hapus&id=<?= $mhs['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Hapus data <?= htmlspecialchars($mhs['nama']) ?>?')">🗑️ Hapus</a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
</body>
</html>
