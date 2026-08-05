<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Gaji Pokok Karyawan</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/foot.css">
    <style>
        .container {
            margin-top: 50px;
        }
        .inp{
            width:87%;
            margin-left:3%;
            display:inline;
            border: 1px solid #bfbfbf;
            border-radius:5px;
            padding:5px;
        }
        .up{
            display:inline;
        }
        .back-button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            margin-top:20px;
        }
        
        .back-button:hover{
            background-color: #0063cc;
            color: white;
            text-decoration: none;
        }

        /* Style for the container */
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        @media screen and (max-width: 768px) {
            .inp{
                width:80%;
            }
            h2{
                font-size:20px;
                font-weight:semi-bold;
                color:#1a1a1a;
            }
        }
    </style>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('.currency-input').on('input', function(event) {
                var inputValue = $(this).val().replace(/[^\d]/g, ''); // Remove non-numeric characters
                var formattedValue = formatCurrency(inputValue);
                $(this).val(formattedValue);
            });

            function formatCurrency(amount) {
                var parts = amount.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ','); // Add commas every 3 digits
                return parts.join('.');
            }
        });
    </script>

</head>
<body>
    <div class="container">
        <div class="header-container">
            <h2>Input Gaji Pokok Karyawan</h2>
            <a href="sa-data-karyawan.php" class="back-button">Back</a>
        </div>
        <form action="sa-proses-input-gaji.php" method="POST">
            <table class="table">
                <tr>
                    <th>Nama Karyawan</th>
                    <th>Jabatan</th>
                    <th>Gaji Pokok</th>
                </tr>
                <!-- Loop untuk menampilkan data karyawan dari database -->
                <?php
                // Menghubungkan ke database
                include 'conn.php';

                // Query untuk mengambil data karyawan
                $query = "SELECT * FROM karyawan ORDER BY nama ASC";
                $result = $conn->query($query);

                if (!$result) {
                    die("Query execution failed: " . $conn->error);
                }

                while ($karyawan = $result->fetch_assoc()) {
                if($karyawan['nip'] != '001'  AND $karyawan['nip'] != '70326' AND $karyawan['gaji_pokok'] == 0) :
                    echo "<tr>";
                    echo "<td>" . $karyawan['nama'] . "</td>";
                    echo "<td>" . $karyawan['jabatan'] . "</td>";
                    echo "<td>";
                    echo '<input type="hidden" name="nip[]" value="' . $karyawan['nip'] . '">';
                    echo 'Rp. <input type="text" class="inp currency-input" name="gaji_pokok[]">';
                    echo "</td>";
                    echo "</tr>";
                    endif;
                }
                ?>
                <tr>
                    <td colspan="3">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="reset" class="btn btn-default">Reset</button>
                    </td>
                </tr>
            </table>
        </form>
    </div>

<div class="footer">
    Copyrights © Gravitti Technology 2023<br>All Rights Reserved
</div>
</body>
</html>
