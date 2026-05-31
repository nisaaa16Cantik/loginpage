<?php
// ============================================================
//  KONFIGURASI DATABASE
// ============================================================
$DB_HOST = "localhost";
$DB_NAME = "nisa_database";
$DB_USER = "root";
$DB_PASS = "";

try {
    $conn = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8", $DB_USER, $DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

$conn->exec("CREATE TABLE IF NOT EXISTS mahasiswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    jurusan VARCHAR(100) NOT NULL,
    angkatan YEAR NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_system.php");
    exit;
}

$message = "";
$editData = null;
$action = $_GET['action'] ?? 'list';
$id     = $_GET['id']     ?? null;

// TAMBAH
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nim      = trim($_POST['nim']);
    $nama     = trim($_POST['nama']);
    $jurusan  = trim($_POST['jurusan']);
    $angkatan = trim($_POST['angkatan']);

    if (empty($nim) || empty($nama) || empty($jurusan) || empty($angkatan)) {
        $message = "Semua field wajib diisi!";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO mahasiswa (nim, nama, jurusan, angkatan) VALUES (?,?,?,?)");
            $stmt->execute([$nim, $nama, $jurusan, $angkatan]);
            $message = "Data berhasil ditambahkan!";
        } catch (PDOException $e) {
            $message = "NIM sudah terdaftar!";
        }
    }
}

// HAPUS
if ($action === 'hapus' && $id) {
    $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: mahasiswa.php");
    exit;
}

// AMBIL DATA EDIT
if ($action === 'edit' && $id) {
    $stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// UPDATE
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim      = trim($_POST['nim']);
    $nama     = trim($_POST['nama']);
    $jurusan  = trim($_POST['jurusan']);
    $angkatan = trim($_POST['angkatan']);
    $edit_id  = $_POST['edit_id'];

    try {
        $stmt = $conn->prepare("UPDATE mahasiswa SET nim=?, nama=?, jurusan=?, angkatan=? WHERE id=?");
        $stmt->execute([$nim, $nama, $jurusan, $angkatan, $edit_id]);
        header("Location: mahasiswa.php");
        exit;
    } catch (PDOException $e) {
        $message = "NIM sudah dipakai!";
        $action  = 'edit';
        $editData = ['id'=>$edit_id,'nim'=>$nim,'nama'=>$nama,'jurusan'=>$jurusan,'angkatan'=>$angkatan];
    }
}

// AMBIL SEMUA DATA
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE nim LIKE ? OR nama LIKE ? OR jurusan LIKE ? ORDER BY id DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $conn->query("SELECT * FROM mahasiswa ORDER BY id DESC");
}
$mahasiswaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>CRUD Mahasiswa</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 13px; margin: 10px; background: #fff; color: #000; }
  h2 { font-size: 15px; margin-bottom: 8px; }
  h3 { font-size: 13px; margin-bottom: 6px; }
  input[type=text], input[type=number] {
    border: 1px solid #999;
    padding: 2px 4px;
    font-size: 12px;
    width: 150px;
  }
  input[type=submit], button, a.btn {
    background: #ddd;
    border: 1px solid #999;
    padding: 2px 8px;
    font-size: 12px;
    cursor: pointer;
    text-decoration: none;
    color: #000;
  }
  input[type=submit]:hover, button:hover, a.btn:hover { background: #ccc; }
  table { border-collapse: collapse; width: 100%; margin-top: 10px; font-size: 12px; }
  th, td { border: 1px solid #999; padding: 4px 8px; text-align: left; }
  th { background: #eee; }
  .msg { color: red; font-size: 12px; margin-bottom: 6px; }
  .form-row { margin-bottom: 4px; }
  .form-row label { display: inline-block; width: 70px; font-size: 12px; }
  .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 6px; }
  .topbar span { font-size: 12px; }
</style>
</head>
<body>

<div class="topbar">
  <b>CRUD Mahasiswa</b>
  <span>Login sebagai: <b><?= htmlspecialchars($_SESSION['username']) ?></b> | <a href="login_system.php?page=logout">Logout</a></span>
</div>

<h2>Tambah Mahasiswa Baru</h2>

<?php if ($message): ?>
  <div class="msg"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($action === 'edit' && $editData): ?>
  <!-- FORM EDIT -->
  <form method="POST" action="mahasiswa.php?action=update">
    <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
    <div class="form-row"><label>NIM</label><input type="text" name="nim" value="<?= htmlspecialchars($editData['nim']) ?>" required></div>
    <div class="form-row"><label>Nama</label><input type="text" name="nama" value="<?= htmlspecialchars($editData['nama']) ?>" required></div>
    <div class="form-row"><label>Jurusan</label><input type="text" name="jurusan" value="<?= htmlspecialchars($editData['jurusan']) ?>" required></div>
    <div class="form-row"><label>Angkatan</label><input type="number" name="angkatan" value="<?= htmlspecialchars($editData['angkatan']) ?>" required></div>
    <div class="form-row"><label></label><input type="submit" value="Simpan"> <a href="mahasiswa.php" class="btn">Batal</a></div>
  </form>
<?php else: ?>
  <!-- FORM TAMBAH -->
  <form method="POST" action="mahasiswa.php">
    <div class="form-row"><label>NIM</label><input type="text" name="nim" required></div>
    <div class="form-row"><label>Nama</label><input type="text" name="nama" required></div>
    <div class="form-row"><label>Jurusan</label><input type="text" name="jurusan" required></div>
    <div class="form-row"><label>Angkatan</label><input type="number" name="angkatan" min="2000" max="2099" required></div>
    <div class="form-row"><label></label><input type="submit" name="tambah" value="Tambah Data"></div>
  </form>
<?php endif; ?>

<hr style="margin:12px 0">

<h3>Daftar Mahasiswa</h3>

<!-- SEARCH -->
<form method="GET" action="mahasiswa.php" style="margin-bottom:8px">
  Cari: <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="NIM / Nama / Jurusan">
  <input type="submit" value="Cari">
  <?php if ($search): ?><a href="mahasiswa.php" class="btn">Reset</a><?php endif; ?>
</form>

<table>
  <thead>
    <tr>
      <th>No</th>
      <th>NIM</th>
      <th>Nama</th>
      <th>Jurusan</th>
      <th>Angkatan</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($mahasiswaList)): ?>
      <tr><td colspan="6" style="text-align:center">Tidak ada data</td></tr>
    <?php else: ?>
      <?php foreach ($mahasiswaList as $i => $mhs): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($mhs['nim']) ?></td>
        <td><?= htmlspecialchars($mhs['nama']) ?></td>
        <td><?= htmlspecialchars($mhs['jurusan']) ?></td>
        <td><?= htmlspecialchars($mhs['angkatan']) ?></td>
        <td>
          <a href="mahasiswa.php?action=edit&id=<?= $mhs['id'] ?>" class="btn">Edit</a>
          <a href="mahasiswa.php?action=hapus&id=<?= $mhs['id'] ?>"
             class="btn"
             onclick="return confirm('Hapus data <?= htmlspecialchars($mhs['nama']) ?>?')">Hapus</a>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

</body>
</html>
