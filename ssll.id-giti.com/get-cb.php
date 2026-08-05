<?php
    while ($dnd = $result6->fetch_assoc()):
            // echo "<td>" . $dnd['tanggal'] . "</td>";
                echo "<td> Cicilan Ke-" . $dnd['cicilan'] . "</td>";
                echo "<td>Rp " . number_format($dnd['bayar'], 0, ',', '.') . "</td>";
                echo "";
    endwhile;
?>