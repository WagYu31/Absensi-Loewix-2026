function exportToExcel() {
    var table = document.getElementById("employees");
    var excelData = '<table style="border-collapse: collapse; width: 100%; border: 1px solid #333;">';

    var columnWidths = {
        0: '10%',
        1: '30%',
        2: '20%',
        3: '20%',
        4: '20%'
    };

    // Header
    excelData += '<thead>';
    excelData += '<tr style="font-weight: bold; background-color: #e6e6e6;">';
    for (var j = 0; j < table.rows[0].cells.length; j++) {
        var cellText = table.rows[0].cells[j].innerText;
        var cellWidth = getMaxCellWidth(table, j);
        var customWidth = columnWidths[j] || '';
        
        excelData += `<th colspan="5" style="border: 1px solid #ddd; padding: 10px; text-align:center; ${customWidth}">${cellText}</th>`;
    }
    excelData += '</tr>';
    excelData += '</thead>';
    
    // Body
    excelData += '<tbody>';
    var totalGajiKeseluruhan = 0;
    for (var i = 1; i < table.rows.length; i++) {
        var rowStyle = i === table.rows.length - 1 ? ' style="font-weight: bold; background-color: #ffff99;"' : '';
        excelData += `<tr${rowStyle}>`;
        for (var j = 0; j < table.rows[i].cells.length - 1; j++) {
            var cellValue = table.rows[i].cells[j].innerText;
            excelData += `<td ${getCellStyle(j)}>${cellValue}</td>`;
            if (j === 4 && i > 1) {
                totalGajiKeseluruhan += parseFloat(cellValue);
            }
        }
        excelData += '</tr>';
    }
    excelData += '</tbody>';

    // Total Gaji Row
    excelData += '<tfoot>';
    excelData += '<tr>';
    excelData += '<td style="border: 1px solid #ddd; padding: 8px;"></td>'; // Empty last column
    excelData += '</tr>';
    excelData += '</tfoot>';

    excelData += '</table>';

    // Filename and Export
    var selectedMonth = document.getElementById("bulan").value;
    var selectedYear = document.getElementById("tahun").value;
    
    var monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    var selectedMonthName = monthNames[parseInt(selectedMonth) - 1];

    var filename = 'Laporan_Biaya_Pengganti_' + selectedMonthName + '_' + selectedYear + '.xls';

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


// Fungsi pemformatan sel terpisah
function getCellStyle(j) {
    var style = 'style="border: 1px solid #333; padding: 8px;';
    
    if (j === 1 || j === 4) {
        style += ' font-weight: bold;';
    } else if (j === 0) {
        style += ' mso-number-format:\'0\'; text-align:center;';
    } else if (j === 4){
        style += ' background-color: #ffff99;';
    }
    
    style += '"';
    
    return style;
}

function getMaxCellWidth(table, columnIndex) {
    var maxCellWidth = 0;

    for (var i = 0; i < table.rows.length; i++) {
        var cellWidth = table.rows[i].cells[columnIndex].clientWidth;
        if (cellWidth > maxCellWidth) {
            maxCellWidth = cellWidth;
        }
    }

    return maxCellWidth;
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

    var filename = 'Laporan_Biaya_Pengganti_' + selectedMonthName + '_' + selectedYear + '.csv';
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

    var filename = 'Laporan_Biaya_Pengganti_' + selectedMonthName + '_' + selectedYear + '.txt';
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