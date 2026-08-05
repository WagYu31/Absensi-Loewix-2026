<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "karyawan") {
    header("Location: session-karyawan.php");
    exit();
}