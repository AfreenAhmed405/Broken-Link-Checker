function populateTable(tableData) {
    data = JSON.parse(tableData);
    console.log(data);
    const sortableTable = new SortableTable();
    sortableTable.setTable(document.querySelector('#brokenLinkTable'));

    data.forEach((row) => {
        row.guide_owner_name = `<a href="mailto:${row.guide_owner_email}">${row.guide_owner_name}</a>`;
        row.siteimprove_url = `<a href="${row.siteimprove_url}">${row.siteimprove_url}</a>`;
        row.guide_name = `<a href="${row.guide_url}">${row.guide_name}</a>`;
    });

    sortableTable.setData(data);
    sortableTable.events()
        .on('sort', (event) => {
            console.log(`[SortableTable#onSort]
        event.colId=${event.colId}
        event.sortDir=${event.sortDir}
        event.data=\n${JSON.stringify(event.data)}`);
    });
}

function export2csv() {
    let data = "";
    const tableData = [];
    const rows = document.querySelectorAll("table tr");
  
    // Iterate over each row
    for (const row of rows) {
        const rowData = [];
        for (const [index, column] of row.querySelectorAll("th, td").entries()) {
            const cellData = column.innerText.replace(/"/g, '""');
            if (cellData.includes(",") || cellData.includes("\n")) {
                rowData.push('"' + cellData + '"');
            } else {
                rowData.push(cellData);
            }
        }
        tableData.push(rowData.join(","));
    }
  
    data += tableData.join("\n");
    
    // Create a blob and download the CSV
    const a = document.createElement("a");
    a.href = URL.createObjectURL(new Blob([data], { type: "text/csv;charset=utf-8;" }));
    a.setAttribute("download", "data.csv");
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}