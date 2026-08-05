function exportToExcel() {
    var table = document.getElementById("employees");
    var excelData = '<table style="border-collapse: collapse; border: 1px solid black;">'; // Mengubah nilai border

    for (var i = 0; i < table.rows.length; i++) {
        excelData += '<tr>';
        for (var j = 0; j < table.rows[i].cells.length; j++) {
            excelData += '<td style="border: 1px solid black;">' + table.rows[i].cells[j].innerText + '</td>'; // Mengubah nilai border
        }
        excelData += '</tr>';
    }

    excelData += '</table>';

    var selectedMonth = document.getElementById("bulan").value;
    var selectedYear = document.getElementById("tahun").value;
    
    var monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    var selectedMonthName = monthNames[parseInt(selectedMonth) - 1];

    var filename = 'Laporan_Gaji_' + selectedMonthName + '_' + selectedYear + '.xls';

    if (navigator.msSaveBlob) {
        // IE 10+
        var blob = new Blob([excelData], { type: 'application/vnd.ms-excel' });
        navigator.msSaveBlob(blob, filename);
    } else {
        // Other browsers
        var a = document.createElement('a');
        a.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(excelData);
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
}



function exportToCSV() {
    var table = document.getElementById("employees");
    var csvData = '';

    for (var i = 0; i < table.rows.length; i++) {
        for (var j = 0; j < table.rows[i].cells.length; j++) {
            csvData += table.rows[i].cells[j].innerText + ',';
        }
        csvData += '\n';
    }

    var selectedMonth = document.getElementById("bulan").value;
    var selectedYear = document.getElementById("tahun").value;
    
    var monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    var selectedMonthName = monthNames[parseInt(selectedMonth) - 1];

    var filename = 'Laporan_Gaji_' + selectedMonthName + '_' + selectedYear + '.csv';
    var blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });

    if (navigator.msSaveBlob) {
        // IE 10+
        navigator.msSaveBlob(blob, filename);
    } else {
        // Other browsers
        var link = document.createElement("a");
        if (link.download !== undefined) {
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
}


function exportToTXT() {
    var table = document.getElementById("employees");
    var txtData = '';

    for (var i = 0; i < table.rows.length; i++) {
        for (var j = 0; j < table.rows[i].cells.length; j++) {
            txtData += table.rows[i].cells[j].innerText + '\t';
        }
        txtData += '\n';
    }

    var selectedMonth = document.getElementById("bulan").value;
    var selectedYear = document.getElementById("tahun").value;
    
    var monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    var selectedMonthName = monthNames[parseInt(selectedMonth) - 1];

    var filename = 'Laporan_Gaji_' + selectedMonthName + '_' + selectedYear + '.txt';
    var blob = new Blob([txtData], { type: 'text/plain;charset=utf-8;' });

    if (navigator.msSaveBlob) {
        // IE 10+
        navigator.msSaveBlob(blob, filename);
    } else {
        // Other browsers
        var link = document.createElement("a");
        if (link.download !== undefined) {
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
}


    function printPage() {
        var css = '@page { size: landscape; }',
            head = document.head || document.getElementsByTagName('head')[0],
            style = document.createElement('style');

        style.type = 'text/css';
        style.media = 'print';

        if (style.styleSheet){
        style.styleSheet.cssText = css;
        } else {
        style.appendChild(document.createTextNode(css));
        }

        head.appendChild(style);

        window.print();
    }