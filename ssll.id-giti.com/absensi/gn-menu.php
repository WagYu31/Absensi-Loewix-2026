<?php
    if ($role === 'admin') {
    ?>
            <ul id="gn-menu" class="gn-menu-main">
                <li class="gn-trigger">
                    <a class="gn-icon gn-icon-menu"><span>Menu</span></a>
                    <nav class="gn-menu-wrapper">
                        <div class="gn-scroller">
                            <ul class="gn-menu">
                                <li>
                                    <a href="../admin-profile.php"><i class="fa-solid fa-users" id="mn"></i>Profile</a>
                                    <ul class="gn-submenu">
                                        <li><a href="../adm-riwayat-gaji.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Slip Gaji</a></li>
                                    </ul>
                                </li>
                                <li><a href="../data-karyawan.php"><i class="fa-solid fa-archive" id="mnn"></i>Data Karyawan</a></li>
                                <li><a href="../tunjangan-karyawan.php"><i class="fa-solid fa-hand-holding-dollar" id="mnnn"></i>Biaya Pengganti</a></li>

                                <li>
                                    <a href="../denda-karyawan.php"><i class="fa-solid fa-receipt" id="mnnn"></i>Denda</a>
                                    <ul class="gn-submenu">
                                        <li><a href="add-shifting.php"><i class="fa-solid fa-caret-right" id="mn2"></i>1. Data Shifting</a></li>
                                        <li><a href="index.php"><i class="fa-solid fa-caret-right" id="mn2"></i>2. Upload Absensi</a></li>
                                        <li><a href="req_shift.php"><i class="fa-solid fa-caret-right" id="mn2"></i>3. Request Shifting</a></li>
                                        <li><a href="req_absen.php"><i class="fa-solid fa-caret-right" id="mn2"></i>4. Absen Manual</a></li>
                                        <li><a href="data-absen.php"><i class="fa-solid fa-caret-right" id="mn2"></i>5. Validasi Data Absen</a></li>
                                        <!--<li><a href="req_jam_kerja.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Request Jam Kerja</a></li>-->
                                    </ul>
                                </li>

                            </ul>
                        </div><!-- /gn-scroller -->
                    </nav>
                </li>
                <li><a class="codrops-icon" href="../data-karyawan.php"><i class="fa-solid fa-g" id="gt"></i><span>ravitti Technology</span></a></li>
                <li>
                    <?php
                    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
                    if ($referer) {
                        echo '<a class="codrops-icon codrops-icon-prev" href="' . $referer . '"><span>Previous</span></a>';
                    } else {
                        echo '<a class="codrops-icon codrops-icon-prev disabled" href="#"><span>Previous</span></a>';
                    }
                    ?>
                </li>
                <li><a class="codrops-icon" href="logout.php"><i class="fa-solid fa-right-to-bracket" id="mnn"></i><span>Log Out</span></a></li>
                <!-- <li><a class="codrops-icon codrops-icon-drop" href="http://tympanus.net/codrops/?p=16030"><span>Back to the Codrops Article</span></a></li> -->
            </ul>

    <?php
    } else if ($role === 'superadmin') {
    ?>
        <ul id="gn-menu" class="gn-menu-main">
            <li class="gn-trigger">
                <a class="gn-icon gn-icon-menu"><span>Menu</span></a>
                <nav class="gn-menu-wrapper">
                    <div class="gn-scroller">
                        <ul class="gn-menu">
                            <li>
                                <a href="../sa-data-karyawan.php"><i class="fa-solid fa-users" id="mn"></i>Data Karyawan</a>
                                <ul class="gn-submenu">
                                    <li><a href="../tambah-data-karyawan.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Tambah Data Karyawan</a></li>
                                    <li><a href="../sa-tambah-data.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Akun Karyawan</a></li>
                                </ul>
                            </li>
                            <li><a href="../sa-bonus.php"><i class="fa-solid fa-trophy" id="mnn"></i>Bonus</a></li>
                            
                            <li>
                                <a href="../denda-karyawan.php"><i class="fa-solid fa-receipt" id="mnnn"></i>Denda</a>
                                <ul class="gn-submenu">
                                    <li><a href="add-shifting.php"><i class="fa-solid fa-caret-right" id="mn2"></i>1. Data Shifting</a></li>
                                    <li><a href="index.php"><i class="fa-solid fa-caret-right" id="mn2"></i>2. Upload Absensi</a></li>
                                    <li><a href="req_shift.php"><i class="fa-solid fa-caret-right" id="mn2"></i>3. Request Shifting</a></li>
                                    <li><a href="req_absen.php"><i class="fa-solid fa-caret-right" id="mn2"></i>4. Absen Manual</a></li>
                                    <li><a href="data-absen.php"><i class="fa-solid fa-caret-right" id="mn2"></i>5. Validasi Data Absen</a></li>
                                </ul>
                            </li>

                            <li><a href="../denda-karyawan.php"><i class="fa-solid fa-receipt" id="mnnn"></i>Denda</a></li>
                            <li><a href="../sa-cashbon.php"><i class="fa-solid fa-hand-holding-dollar" id="mnn"></i>Cashbon</a></li>
                            <li><a href="../tunjangan-karyawan.php"><i class="fa-solid fa-file-invoice" id="mnnn"></i>Biaya Pengganti</a></li>
                            <li>
                                <a class="gn-icon gn-icon-archive">Laporan</a>
                                <ul class="gn-submenu">
                                    <li><a href="../laporan-gaji.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Gaji</a></li>
                                    <li><a href="../laporan-cashbon.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Cashbon</a></li>
                                    <li><a href="../laporan-denda.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Denda</a></li>
                                    <li><a href="../laporan-pengganti.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Biaya Pengganti</a></li>
                                </ul>
                            </li>

                        </ul>
                    </div><!-- /gn-scroller -->
                </nav>
            </li>
            <li><a class="codrops-icon" href="../laporan-gaji.php"><i class="fa-solid fa-g" id="gt"></i><span>ravitti Technology</span></a></li>
            <li>
                <?php
                $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
                if ($referer) {
                    echo '<a class="codrops-icon codrops-icon-prev" href="' . $referer . '"><span>Previous</span></a>';
                } else {
                    echo '<a class="codrops-icon codrops-icon-prev disabled" href="#"><span>Previous</span></a>';
                }
                ?>
            </li>
            <li><a class="codrops-icon" href="../logout.php"><i class="fa-solid fa-right-to-bracket" id="mnn"></i><span>Log Out</span></a></li>
            <!-- <li><a class="codrops-icon codrops-icon-drop" href="http://tympanus.net/codrops/?p=16030"><span>Back to the Codrops Article</span></a></li> -->
        </ul>

        <?php } ?>