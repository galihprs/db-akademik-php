<?php
ob_start();
session_start();

$self = basename($_SERVER['PHP_SELF']);

// 1. KONEKSI DATABASE
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_akademik';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

// 2. ENDPOINT UNTUK UPDATE SESSION BAHASA
if (isset($_GET['ajax_lang'])) {
    $lang_code = $_GET['ajax_lang'];
    if ($lang_code === 'id' || $lang_code === 'en') {
        $_SESSION['lang'] = $lang_code;
    }
    exit;
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'id';
}

$translations = [
    'id' => [
        'title'          => 'Sistem Informasi Manajemen Mahasiswa',
        'welcome'        => 'Selamat Datang',
        'logout'         => 'Keluar',
        'add_data'       => 'Tambah Mahasiswa',
        'nim'            => 'NIM',
        'nama'           => 'Nama Lengkap',
        'jurusan'        => 'Jurusan',
        'email'          => 'Email',
        'action'         => 'Aksi',
        'edit'           => 'Ubah',
        'delete'         => 'Hapus',
        'save'           => 'Simpan',
        'back'           => 'Kembali',
        'success_add'    => 'Data mahasiswa berhasil ditambahkan!',
        'success_update' => 'Data mahasiswa berhasil diperbarui!',
        'success_delete' => 'Data mahasiswa berhasil dihapus!',
        'err_empty'      => 'Semua kolom wajib diisi dengan benar!',
        'err_email'      => 'Format alamat email tidak valid!',
        'login_title'    => 'Masuk Sebagai Admin',
        'username'       => 'Username',
        'password'       => 'Password',
        'login_btn'      => 'Masuk',
        'err_login'      => 'Username atau Password salah!',
        'empty_data'     => 'Belum ada data tersedia.',
        'confirm_del'    => 'Yakin ingin menghapus data ini?'
    ],
    'en' => [
        'title'          => 'Student Management Information System',
        'welcome'        => 'Welcome',
        'logout'         => 'Logout',
        'add_data'       => 'Add Student',
        'nim'            => 'Student ID (NIM)',
        'nama'           => 'Full Name',
        'jurusan'        => 'Major',
        'email'          => 'Email',
        'action'         => 'Action',
        'edit'           => 'Edit',
        'delete'         => 'Delete',
        'save'           => 'Save',
        'back'           => 'Back',
        'success_add'    => 'Student data successfully added!',
        'success_update' => 'Student data successfully updated!',
        'success_delete' => 'Student data successfully deleted!',
        'err_empty'      => 'All fields are required!',
        'err_email'      => 'Invalid email address format!',
        'login_title'    => 'Login As Admin',
        'username'       => 'Username',
        'password'       => 'Password',
        'login_btn'      => 'Login',
        'err_login'      => 'Invalid Username or Password!',
        'empty_data'     => 'No data available.',
        'confirm_del'    => 'Are you sure you want to delete this data?'
    ]
];
$lang = $translations[$_SESSION['lang']];

// 3. LOGIKA AKSI & ROUTING
$action = isset($_GET['action']) ? $_GET['action'] : '';
$page   = isset($_GET['page']) ? $_GET['page'] : 'list';
$msg    = isset($_GET['msg']) ? $_GET['msg'] : '';

// A. Proses Logout
if ($action == 'logout') {
    session_unset();
    session_destroy();
    header("Location: $self");
    exit;
}

// B. Proses Login (POST)
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['do_login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if ($username === 'galih' && $password === '302006') {
        $_SESSION['login'] = true;
        $_SESSION['username'] = $username;
        header("Location: $self");
        exit;
    } else {
        $login_error = $lang['err_login'];
    }
}

$is_logged_in = isset($_SESSION['login']) && $_SESSION['login'] === true;

// C. Proses CRUD (Hanya jika sudah login)
$error_msg = '';
if ($is_logged_in) {
    // 1. Delete Data
    if ($action == 'delete') {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: {$self}?msg=deleted");
        exit;
    }

    // 2. Insert Data (Create) - Nama & Jurusan otomatis kapital semua
    if ($page == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['do_save'])) {
        $nim     = trim($_POST['nim']);
        $nama    = strtoupper(trim($_POST['nama']));
        $jurusan = strtoupper(trim($_POST['jurusan']));
        $email   = trim($_POST['email']);

        if (empty($nim) || empty($nama) || empty($jurusan) || empty($email)) {
            $error_msg = $lang['err_empty'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = $lang['err_email'];
        } else {
            $stmt = $conn->prepare("INSERT INTO mahasiswa (nim, nama, jurusan, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nim, $nama, $jurusan, $email);
            if ($stmt->execute()) {
                header("Location: {$self}?msg=added");
                exit;
            } else {
                $error_msg = "Database Error: " . $conn->error;
            }
        }
    }

    // 3. Update Data - Nama & Jurusan otomatis kapital
    if ($page == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['do_update'])) {
        $id      = intval($_POST['id']);
        $nim     = trim($_POST['nim']);
        $nama    = strtoupper(trim($_POST['nama']));
        $jurusan = strtoupper(trim($_POST['jurusan']));
        $email   = trim($_POST['email']);

        if (empty($nim) || empty($nama) || empty($jurusan) || empty($email)) {
            $error_msg = $lang['err_empty'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = $lang['err_email'];
        } else {
            $stmt = $conn->prepare("UPDATE mahasiswa SET nim = ?, nama = ?, jurusan = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nim, $nama, $jurusan, $email, $id);
            if ($stmt->execute()) {
                header("Location: {$self}?msg=updated");
                exit;
            } else {
                $error_msg = "Database Error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <title data-i18n="title"><?= $lang['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        
        .navbar-brand {
            display: inline-block;
            min-width: 420px;
            white-space: nowrap;
        }
        .app-heading {
            min-width: 450px;
        }
        .btn-action-custom {
            min-width: 170px;
            text-align: center;
        }
        table.fixed-table {
            table-layout: fixed;
            width: 100%;
        }
    </style>
</head>
<body class="bg-light">

<?php if (!$is_logged_in): ?>

    <!-- HALAMAN LOGIN -->
    <div class="container d-flex align-items-center justify-content-center" style="height: 100vh; max-width: 400px;">
        <div class="card shadow-sm w-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" onclick="changeLanguage('id')" id="btn-id" class="btn btn-sm btn-outline-secondary me-1 <?= $_SESSION['lang']=='id'?'active':'' ?>">ID</button>
                    <button type="button" onclick="changeLanguage('en')" id="btn-en" class="btn btn-sm btn-outline-secondary <?= $_SESSION['lang']=='en'?'active':'' ?>">EN</button>
                </div>
                <h3 class="text-center mb-4" data-i18n="login_title" style="min-height: 36px;"><?= $lang['login_title'] ?></h3>
                
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger"><?= $login_error ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= $self ?>">
                    <div class="mb-3">
                        <label class="form-label" data-i18n="username"><?= $lang['username'] ?></label>
                        <input type="text" name="username" class="form-control" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" data-i18n="password"><?= $lang['password'] ?></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" name="do_login" class="btn btn-primary w-100" data-i18n="login_btn"><?= $lang['login_btn'] ?></button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- DASHBOARD UTAMA -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?= $self ?>" data-i18n="title"><?= $lang['title'] ?></a>
            <div class="d-flex align-items-center ms-auto">
                <div class="me-3">
                    <button type="button" onclick="changeLanguage('id')" id="btn-id" class="btn btn-sm btn-outline-light <?= $_SESSION['lang']=='id'?'active':'' ?>">ID</button>
                    <button type="button" onclick="changeLanguage('en')" id="btn-en" class="btn btn-sm btn-outline-light <?= $_SESSION['lang']=='en'?'active':'' ?>">EN</button>
                </div>
                <span class="text-white me-3" id="welcome-text"><?= $lang['welcome'] ?>, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="<?= $self ?>?action=logout" class="btn btn-sm btn-danger btn-action-custom" data-i18n="logout"><?= $lang['logout'] ?></a>
            </div>
        </div>
    </nav>

    <div class="container">
        
        <?php if ($msg == 'added'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $lang['success_add'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($msg == 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $lang['success_update'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($msg == 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $lang['success_delete'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- 1. TABEL DATA (READ) -->
        <?php if ($page == 'list'): ?>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 data-i18n="title" class="app-heading mb-0"><?= $lang['title'] ?></h2>
                <a href="<?= $self ?>?page=create" class="btn btn-primary btn-action-custom" data-i18n="add_data"><?= $lang['add_data'] ?></a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle fixed-table">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 6%;">#</th>
                                    <th style="width: 22%;" data-i18n="nim"><?= $lang['nim'] ?></th>
                                    <th style="width: 28%;" data-i18n="nama"><?= $lang['nama'] ?></th>
                                    <th style="width: 24%;" data-i18n="jurusan"><?= $lang['jurusan'] ?></th>
                                    <th style="width: 20%;" data-i18n="email"><?= $lang['email'] ?></th>
                                    <th style="width: 15%;" data-i18n="action"><?= $lang['action'] ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $conn->query("SELECT * FROM mahasiswa ORDER BY id DESC");
                                if ($result->num_rows > 0):
                                    $no = 1;
                                    while ($row = $result->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nim']) ?></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><?= htmlspecialchars($row['jurusan']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td>
                                            <a href="<?= $self ?>?page=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning" data-i18n="edit"><?= $lang['edit'] ?></a>
                                            <a href="<?= $self ?>?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger btn-delete" data-i18n="delete" data-confirm="<?= htmlspecialchars($lang['confirm_del'], ENT_QUOTES) ?>"><?= $lang['delete'] ?></a>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted" data-i18n="empty_data"><?= $lang['empty_data'] ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- 2. TAMBAH DATA (CREATE) -->
        <?php elseif ($page == 'create'): ?>
            
            <div class="card shadow-sm mx-auto" style="max-width: 600px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 data-i18n="add_data" class="mb-0"><?= $lang['add_data'] ?></h3>
                        <a href="<?= $self ?>" class="btn btn-secondary btn-sm btn-action-custom" data-i18n="back"><?= $lang['back'] ?></a>
                    </div>
                    
                    <form method="POST" action="<?= $self ?>?page=create">
                        <div class="mb-3">
                            <label class="form-label" data-i18n="nim"><?= $lang['nim'] ?></label>
                            <input type="text" name="nim" class="form-control" value="<?= isset($_POST['nim']) ? htmlspecialchars($_POST['nim']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-i18n="nama"><?= $lang['nama'] ?></label>
                            <input type="text" name="nama" class="form-control" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-i18n="jurusan"><?= $lang['jurusan'] ?></label>
                            <input type="text" name="jurusan" class="form-control" value="<?= isset($_POST['jurusan']) ? htmlspecialchars($_POST['jurusan']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-i18n="email"><?= $lang['email'] ?></label>
                            <input type="email" name="email" class="form-control" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                        </div>
                        <button type="submit" name="do_save" class="btn btn-primary w-100" data-i18n="save"><?= $lang['save'] ?></button>
                    </form>
                </div>
            </div>

        <!-- 3. EDIT DATA (UPDATE) -->
        <?php 
        elseif ($page == 'edit'): 
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            
            if (!$data) {
                echo "<script>window.location='{$self}';</script>";
                exit;
            }
        ?>
            
            <div class="card shadow-sm mx-auto" style="max-width: 600px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 data-i18n="edit" class="mb-0"><?= $lang['edit'] ?> Data Mahasiswa</h3>
                        <a href="<?= $self ?>" class="btn btn-secondary btn-sm btn-action-custom" data-i18n="back"><?= $lang['back'] ?></a>
                    </div>
                    
                    <form method="POST" action="<?= $self ?>?page=edit&id=<?= $data['id'] ?>">
                        <input type="hidden" name="id" value="<?= $data['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label" data-i18n="nim"><?= $lang['nim'] ?></label>
                            <input type="text" name="nim" class="form-control" value="<?= htmlspecialchars($data['nim']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-i18n="nama"><?= $lang['nama'] ?></label>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-i18n="jurusan"><?= $lang['jurusan'] ?></label>
                            <input type="text" name="jurusan" class="form-control" value="<?= htmlspecialchars($data['jurusan']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-i18n="email"><?= $lang['email'] ?></label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email']) ?>" required>
                        </div>
                        <button type="submit" name="do_update" class="btn btn-primary w-100" data-i18n="save"><?= $lang['save'] ?></button>
                    </form>
                </div>
            </div>

        <?php endif; ?>

    </div>

<?php endif; ?>

<script>
    const translations = <?= json_encode($translations) ?>;
    const username = "<?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '' ?>";

    function changeLanguage(lang) {
        fetch('<?= $self ?>?ajax_lang=' + lang)
            .then(response => {
                document.documentElement.lang = lang;

                document.querySelectorAll('[data-i18n]').forEach(el => {
                    const key = el.getAttribute('data-i18n');
                    if (translations[lang][key]) {
                        el.textContent = translations[lang][key];
                    }
                });

                const welcomeEl = document.getElementById('welcome-text');
                if (welcomeEl) {
                    welcomeEl.textContent = (lang === 'id' ? 'Selamat datang' : 'Welcome') + ', ' + username;
                }

                document.querySelectorAll('.btn-delete').forEach(btn => {
                    btn.setAttribute('data-confirm', translations[lang]['confirm_del']);
                });

                document.getElementById('btn-id').classList.toggle('active', lang === 'id');
                document.getElementById('btn-en').classList.toggle('active', lang === 'en');
            });
    }

    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-delete')) {
            const confirmMsg = e.target.getAttribute('data-confirm');
            if (!confirm(confirmMsg)) {
                e.preventDefault();
            }
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
ob_end_flush(); 
?>
