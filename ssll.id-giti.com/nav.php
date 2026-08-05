<?php
    if ($role === 'admin') {
    ?>
        <div class="container no-print">
            <ul id="gn-menu" class="gn-menu-main">
                <li class="gn-trigger">
                    <a class="gn-icon gn-icon-menu"><span>Menu</span></a>
                    <nav class="gn-menu-wrapper">
                        <div class="gn-scroller">
                            <ul class="gn-menu">
                                <li>
                                    <a href="admin-profile.php"><i class="fa-solid fa-users" id="mn"></i>Profile</a>
                                    <ul class="gn-submenu">
                                        <li><a href="adm-riwayat-gaji.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Slip Gaji</a></li>
                                    </ul>
                                </li>
                                <li><a href="data-karyawan.php"><i class="fa-solid fa-archive" id="mnn"></i>Data Karyawan</a></li>
                                <li><a href="tunjangan-karyawan.php"><i class="fa-solid fa-hand-holding-dollar" id="mnnn"></i>Biaya Pengganti</a></li>
                                <li><a href="denda-karyawan.php"><i class="fa-solid fa-receipt" id="mnnn"></i>Denda</a></li>

                            </ul>
                        </div><!-- /gn-scroller -->
                    </nav>
                </li>
                <li><a class="codrops-icon" href="laporan-gaji.php"><i class="fa-solid fa-g" id="gt"></i><span>ravitti Technology</span></a></li>
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
        </div><!-- /container -->

    <?php
    } else if ($role === 'superadmin') {
    ?>
        <div class="container no-print">
            <ul id="gn-menu" class="gn-menu-main">
                <li class="gn-trigger">
                    <a class="gn-icon gn-icon-menu"><span>Menu</span></a>
                    <nav class="gn-menu-wrapper">
                        <div class="gn-scroller">
                            <ul class="gn-menu">
                                <!-- <li class="gn-search-item">
									<input placeholder="Search" type="search" class="gn-search">
									<a class="gn-icon gn-icon-search"><span>Search</span></a>
								</li> -->
                                <li>
                                    <a href="sa-data-karyawan.php"><i class="fa-solid fa-users" id="mn"></i>Data Karyawan</a>
                                    <ul class="gn-submenu">
                                        <li><a href="tambah-data-karyawan.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Tambah Data Karyawan</a></li>
                                        <li><a href="sa-tambah-data.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Akun Karyawan</a></li>
                                        <!-- <li><a class="gn-icon gn-icon-photoshop">Photoshop files</a></li> -->
                                    </ul>
                                </li>
                                <!-- <li><a class="gn-icon gn-icon-cog">Settings</a></li> -->
                                <li><a href="sa-bonus.php"><i class="fa-solid fa-trophy" id="mnn"></i>Bonus</a></li>
                                <li><a href="denda-karyawan.php"><i class="fa-solid fa-receipt" id="mnnn"></i>Denda</a></li>
                                <li><a href="sa-cashbon.php"><i class="fa-solid fa-hand-holding-dollar" id="mnn"></i>Cashbon</a></li>
                                <li><a href="tunjangan-karyawan.php"><i class="fa-solid fa-file-invoice" id="mnnn"></i>Biaya Pengganti</a></li>
                                <!-- <li><a class="gn-icon gn-icon-help" href="sa-cashbon.php">Cashbon</a></li> -->
                                <li>
                                    <a class="gn-icon gn-icon-archive">Laporan</a>
                                    <ul class="gn-submenu">
                                        <li><a href="laporan-gaji.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Gaji</a></li>
                                        <!-- <li><a href="laporan-gaji-mingguan.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Gaji Mingguan</a></li> -->
                                        <li><a href="laporan-cashbon.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Cashbon</a></li>
                                        <li><a href="laporan-denda.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Denda</a></li>
                                        <li><a href="laporan-pengganti.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Biaya Pengganti</a></li>
                                    </ul>
                                </li>

                            </ul>
                        </div><!-- /gn-scroller -->
                    </nav>
                </li>
                <li><a class="codrops-icon" href="laporan-gaji.php"><i class="fa-solid fa-g" id="gt"></i><span>ravitti Technology</span></a></li>
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
        </div><!-- /container -->
    <?php
    }
    ?>