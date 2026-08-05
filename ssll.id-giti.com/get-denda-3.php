                        <?php
                        while ($dnd = $result5->fetch_assoc()):
                            if ($dnd['ket1'] === 'Denda'):
                                // echo "<td>" . $dnd['tanggal'] ."</td>";
                                echo "<tr><td> " . $dnd['keterangan'] ."</td>";
                                echo "<td>Rp " . number_format($dnd['jumlah'], 0, ',', '.') . "</td></tr>";
                            endif;
                        endwhile;
                        ?>