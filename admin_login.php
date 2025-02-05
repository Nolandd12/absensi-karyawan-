<?php
session_start();
include('config.php');

// Redirect ke login jika belum masuk
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Pastikan koneksi ke database tidak bermasalah
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Menghapus absensi jika permintaan POST diterima
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_attendance'])) {
    // Pastikan hanya admin yang dapat menghapus
    if ($_SESSION['role'] === 'admin') {
        $attendance_id = intval($_POST['attendance_id']); // Ambil ID absensi yang akan dihapus

        $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
        $stmt->bind_param("i", $attendance_id);

        if ($stmt->execute()) {
            echo "<script>alert('Data absensi berhasil dihapus!');</script>";
        } else {
            echo "<script>alert('Terjadi kesalahan: " . $stmt->error . "');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Anda tidak memiliki izin untuk menghapus data!');</script>";
    }
}

// Menangani check-in dan check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_in']) || isset($_POST['check_out'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $position = $conn->real_escape_string($_POST['position']);
        $time = date('Y-m-d H:i:s');
        $status = isset($_POST['check_in']) ? 'Datang' : 'Pulang';

        // Simpan data ke database
        $stmt = $conn->prepare("INSERT INTO attendance (name, position, time, status) VALUES (?, ?, ?, ?)");
        
        if (!$stmt) {
            die("<script>
                Swal.fire('Error', 'Kesalahan SQL: " . $conn->error . "', 'error');
            </script>");
        }

        $stmt->bind_param("ssss", $name, $position, $time, $status);

        if ($stmt->execute()) {
            echo "<script>
                Swal.fire('Berhasil', 'Absensi berhasil dicatat!', 'success').then(() => {
                    window.location.reload();
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire('Error', 'Terjadi kesalahan: " . $stmt->error . "', 'error');
            </script>";
        }

        $stmt->close();
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Karyawan</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('https://kelpamoyanan.kotabogor.go.id/imgup/web/galeri/101852.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Pastikan tinggi body 100% dari viewport */
        }

        .container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            width: 420px;
            text-align: center;
            overflow-y: auto;
        }

        h2 {
            font-size: 24px;
            color: #333;
        }

        form input, form select, form button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
            margin-bottom: 10px;
            background-color: skyblue;
        }

        form button {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        form button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            background-color: white;
        }

        table th, table td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
            background-color: palegreen;
        }

        table th {
            background-color: #007bff;
            color: white;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .logout-button {
            background-color: red;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border: none;
            padding: 12px;
            border-radius: 5px;
            width: 100%;
        }

        .logout-button:hover {
            background-color: darkred;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Absensi Karyawan Kelurahan Pamoyanan</h2>
        <form method="POST">
            <input type="text" name="name" placeholder="Nama Karyawan" required><br>
            <select name="position" required>
                <option value="Lurah">Lurah</option>
                <option value="Sekretaris Lurah">Sekretaris Lurah</option>
                <option value="Kasi Pem">Kasi Pem</option>
                <option value="Kasi Ekang">Kasi Ekbang</option>
                <option value="Kasi Kemas">Kasi Kemas</option>
                <option value="Staf">Staf</option>
            </select><br>
            <button type="submit" name="check_in">Datang</button>
            <button type="submit" name="check_out">Pulang</button>
        </form>
        <h2>Laporan Absensi</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Waktu</th>
                <th>Status</th>
                <th>Aksi</th> <!-- Tambahkan kolom untuk aksi -->
            </tr>
            <?php
            $result = $conn->query("SELECT * FROM attendance ORDER BY time DESC");
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['position']}</td>
                        <td>{$row['time']}</td>
                        <td>{$row['status']}</td>
                        <td>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='attendance_id' value='{$row['id']}'>
                                <button type='submit' name='delete_attendance'>Hapus</button>
                            </form>
                        </td>
                      </tr>";
            }
            ?>
        </table>
        <button id="logout-button" class="logout-button">Logout</button>
    </div>

    <script>
        document.getElementById('logout-button').addEventListener('click', function() {
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: "Apakah Anda yakin ingin logout?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Logout!',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika pengguna mengkonfirmasi, kirim form logout
                    const form = document.createElement('form');
                    form.method = 'POST';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'logout';
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>