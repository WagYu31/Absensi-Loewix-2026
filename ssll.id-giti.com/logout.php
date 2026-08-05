<?php
	session_start();

	// Cek apakah pengguna telah login
	if (!isset($_SESSION['username'])) {
	    // Jika tidak ada sesi pengguna, arahkan ke halaman login atau halaman lainnya
	    header('Location: index.php');
	    exit();
	}

	if (isset($_POST['logout'])) {
	    // Hapus semua data sesi pengguna
	    session_destroy();

	    // Redirect ke halaman login atau halaman lainnya setelah logout
	    header('Location: index.php');
	    exit();
	}
?>