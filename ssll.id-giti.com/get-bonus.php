<?php
    while ($tun_lain = $result2->fetch_assoc()):
        if ($tun_lain['ket1'] === 'bonus'):
            echo "<tr><td>" . $tun_lain['tanggal'] . "</td>";
            echo "<td>" . $tun_lain['keterangan'] . "</td>";
            echo "<td>Rp " . number_format($tun_lain['jumlah'], 0, ',', '.') . "</td>";
            echo "</tr>";
        endif;
    endwhile;
?>