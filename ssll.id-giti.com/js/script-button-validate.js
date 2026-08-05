
        // Function to check if the current date is the last day of the month
        // function isLastDayOfMonth() {
        //     var today = new Date();
        //     var tomorrow = new Date(today);
        //     tomorrow.setDate(today.getDate() + 1);
        //     return tomorrow.getDate() === 1;
        // }

        // Function to handle the "Validate" button click event
        function validateData() {
            // if (isLastDayOfMonth()) {
            //     var nip = "NIP Karyawan"; // Ganti dengan nilai NIP dari data karyawan yang ingin divalidasi

            //     // Do your validation logic here
            //     alert("Data berhasil di validasi!");
            // } else {
                
            //     // Show a warning message if clicked on a non-end-of-month day
            //     alert("Validasi hanya bisa dilakukan di akhir bulan!");
            // }

            var table = document.getElementById("employees");
            var rows = table.getElementsByTagName("tr");
        
            // Data untuk disimpan
            var dataToSave = [];
        
            // Loop untuk mengambil data dari setiap baris kecuali baris header
            for (var i = 1; i < rows.length - 1; i++) {
                var row = rows[i];
                var cells = row.getElementsByTagName("td");
        
                var nip = cells[0].innerText;
                var totalTunjanganLainnya = parseInt(cells[5].innerText.replace(/\D/g, ""));
                var totalDenda = parseInt(cells[6].innerText.replace(/\D/g, ""));
        
                // Tambahkan data ke array dataToSave
                dataToSave.push({
                    nip: nip,
                    total_tunjangan_lainnya: totalTunjanganLainnya,
                    total_denda: totalDenda,
                });
            }
        
            // Kirim permintaan AJAX ke script PHP untuk menyimpan data
            $.ajax({
                type: 'POST',
                url: 'save_data.php',
                data: {
                    dataToSave: dataToSave
                },
                success: function (response) {
                    alert(response); // Tampilkan pesan hasil dari script PHP
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }

        // Function to enable/disable the "Validate" button based on the current date
        // function checkValidationStatus() {
        //     var validateButton = document.getElementById("validateButton");
        //     if (isLastDayOfMonth()) {
        //         validateButton.disabled = false; // Enable the button
        //     } else {
        //         validateButton.disabled = true; // Disable the button
        //     }
        // }

        // Call the checkValidationStatus function on page load
        // window.onload = function() {
        //     checkValidationStatus();
        // };
