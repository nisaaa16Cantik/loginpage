<?php
// ============================================================
//  KONFIGURASI DATABASE — sesuaikan dengan milikmu
// ============================================================
$DB_HOST = "localhost";
$DB_NAME = "nama_database";
$DB_USER = "root";
$DB_PASS = "";

// ============================================================
//  KONEKSI DATABASE
// ============================================================
try {
    $conn = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8", $DB_USER, $DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// ============================================================
//  AUTO-CREATE TABLE (jika belum ada)
// ============================================================
$conn->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ============================================================
//  SESSION & LOGIKA
// ============================================================
session_start();

$page    = $_GET['page'] ?? 'login';
$message = "";
$msgType = "";

// --- LOGOUT ---
if ($page === 'logout') {
    session_destroy();
    header("Location: ?page=login");
    exit;
}

// --- PROSES REGISTER ---
if ($page === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (empty($username) || empty($password)) {
        $message = "Username dan password wajib diisi.";
        $msgType = "error";
    } elseif ($password !== $confirm) {
        $message = "Password dan konfirmasi tidak cocok.";
        $msgType = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password minimal 6 karakter.";
        $msgType = "error";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            $message = "Username sudah dipakai, pilih yang lain.";
            $msgType = "error";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);
            $message = "Registrasi berhasil! Silakan login.";
            $msgType = "success";
            $page    = "login";
        }
    }
}

// --- PROSES LOGIN ---
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: ?page=dashboard");
        exit;
    } else {
        $message = "Username atau password salah.";
        $msgType = "error";
    }
}

// --- CEK AKSES DASHBOARD ---
if ($page === 'dashboard' && !isset($_SESSION['user_id'])) {
    header("Location: ?page=login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>
<?php
if ($page === 'login')     echo "Login";
elseif ($page === 'register') echo "Daftar";
else                          echo "Dashboard";
?>
</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:       #0d0f14;
    --surface:  #161923;
    --border:   #252b38;
    --accent:   #4f8ef7;
    --accent2:  #a78bfa;
    --text:     #e8eaf0;
    --muted:    #8892a4;
    --error:    #f87171;
    --success:  #4ade80;
    --radius:   14px;
  }

  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-image:
      radial-gradient(ellipse 60% 50% at 20% 20%, rgba(79,142,247,.12) 0%, transparent 70%),
      radial-gradient(ellipse 50% 40% at 80% 80%, rgba(167,139,250,.10) 0%, transparent 70%);
  }

  /* ── CARD ── */
  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: calc(var(--radius) + 4px);
    padding: 2.4rem 2.6rem;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 24px 60px rgba(0,0,0,.5);
    animation: fadeUp .45s cubic-bezier(.22,1,.36,1) both;
  }

  @keyframes fadeUp {
    from { opacity:0; transform: translateY(24px); }
    to   { opacity:1; transform: translateY(0);    }
  }

  .logo {
    width: 44px; height: 44px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    margin-bottom: 1.4rem;
  }

  h2 {
    font-size: 1.55rem;
    font-weight: 700;
    margin-bottom: .3rem;
    letter-spacing: -.02em;
  }

  .sub {
    font-size: .85rem;
    color: var(--muted);
    margin-bottom: 1.8rem;
  }

  /* ── FORM ── */
  .field { margin-bottom: 1rem; }

  label {
    display: block;
    font-size: .8rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: .4rem;
  }

  input[type=text],
  input[type=password] {
    width: 100%;
    padding: .7rem 1rem;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: inherit;
    font-size: .95rem;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
  }

  input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79,142,247,.18);
  }

  /* ── BUTTON ── */
  .btn {
    width: 100%;
    padding: .78rem;
    margin-top: .6rem;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    font-family: inherit;
    font-size: .95rem;
    font-weight: 600;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: opacity .2s, transform .15s;
    letter-spacing: .01em;
  }
  .btn:hover  { opacity: .88; transform: translateY(-1px); }
  .btn:active { transform: translateY(0); }

  /* ── ALERT ── */
  .alert {
    padding: .7rem 1rem;
    border-radius: 10px;
    font-size: .88rem;
    margin-bottom: 1rem;
    border: 1px solid;
  }
  .alert.error   { background: rgba(248,113,113,.1); border-color: rgba(248,113,113,.3); color: var(--error); }
  .alert.success { background: rgba(74,222,128,.1);  border-color: rgba(74,222,128,.3);  color: var(--success); }

  /* ── LINK KECIL ── */
  .switch {
    text-align: center;
    margin-top: 1.4rem;
    font-size: .85rem;
    color: var(--muted);
  }
  .switch a { color: var(--accent); text-decoration: none; font-weight: 600; }
  .switch a:hover { text-decoration: underline; }

  /* ── DASHBOARD ── */
  .dashboard { max-width: 480px; }

  .welcome-badge {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    background: rgba(79,142,247,.12);
    border: 1px solid rgba(79,142,247,.25);
    color: var(--accent);
    padding: .35rem .8rem;
    border-radius: 999px;
    font-size: .8rem;
    font-weight: 600;
    margin-bottom: 1.2rem;
  }

  .stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .8rem;
    margin: 1.6rem 0;
  }

  .stat-box {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1rem 1.1rem;
  }

  .stat-box .stat-label { font-size: .75rem; color: var(--muted); margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .05em; }
  .stat-box .stat-val   { font-size: 1.25rem; font-weight: 700; }

  .btn-logout {
    width: 100%;
    padding: .72rem;
    background: transparent;
    border: 1px solid var(--border);
    color: var(--muted);
    font-family: inherit;
    font-size: .9rem;
    font-weight: 600;
    border-radius: var(--radius);
    cursor: pointer;
    transition: border-color .2s, color .2s;
  }
  .btn-logout:hover { border-color: var(--error); color: var(--error); }
</style>
</head>
<body>

<?php if ($page === 'login'): ?>
<!-- ===================== HALAMAN LOGIN ===================== -->
<div class="card">
  <div class="logo">🔐</div>
  <h2>Selamat datang</h2>
  <p class="sub">Masuk ke akun kamu untuk melanjutkan</p>

  <?php if ($message): ?>
    <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" action="?page=login">
    <div class="field">
      <label>Username</label>
      <input type="text" name="username" placeholder="Masukkan username" required autofocus>
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="Masukkan password" required>
    </div>
    <button class="btn" type="submit">Login &rarr;</button>
  </form>

  <p class="switch">Belum punya akun? <a href="?page=register">Daftar sekarang</a></p>
</div>


<?php elseif ($page === 'register'): ?>
<!-- ===================== HALAMAN REGISTER ===================== -->
<div class="card">
  <div class="logo">✨</div>
  <h2>Buat akun baru</h2>
  <p class="sub">Isi data di bawah untuk mendaftar</p>

  <?php if ($message): ?>
    <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" action="?page=register">
    <div class="field">
      <label>Username</label>
      <input type="text" name="username" placeholder="Pilih username unik" required autofocus>
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="Min. 6 karakter" required>
    </div>
    <div class="field">
      <label>Konfirmasi Password</label>
      <input type="password" name="confirm" placeholder="Ulangi password" required>
    </div>
    <button class="btn" type="submit">Daftar &rarr;</button>
  </form>

  <p class="switch">Sudah punya akun? <a href="?page=login">Login di sini</a></p>
</div>


<?php else: ?>
<!-- ===================== HALAMAN DASHBOARD ===================== -->
<div class="card dashboard">
  <div class="logo">🚀</div>
  <span class="welcome-badge">✓ Login berhasil</span>
  <h2>Halo, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
  <p class="sub">Kamu berhasil masuk ke dashboard.</p>

  <div class="stat-grid">
    <div class="stat-box">
      <div class="stat-label">Status</div>
      <div class="stat-val" style="color:var(--success)">Aktif ✓</div>
    </div>
    <div class="stat-box">
      <div class="stat-label">User ID</div>
      <div class="stat-val">#<?= $_SESSION['user_id'] ?></div>
    </div>
  </div>

  <form method="GET" action="">
    <input type="hidden" name="page" value="logout">
    <button class="btn-logout" type="submit">Logout</button>
  </form>
</div>

<?php endif; ?>

</body>
</html>
