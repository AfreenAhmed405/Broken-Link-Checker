function populateTable(tableData) {
    const sortableTable = new SortableTable();
    sortableTable.setTable(document.querySelector('#brokenLinkTable'));

    Object.entries(tableData).forEach(([ownerName, data]) => {
        data.guide_owner_name = `<a href="mailto:${data.guide_owner_email}">${ownerName}</a>`;
        data.unformatted_urls = data.siteimprove_urls;
        data.siteimprove_urls = data.siteimprove_urls
            .map(url => `<a href="${url}" target="_blank">${url}</a>`)
            .join('<br><br>');

        data.wysiwygContent = `
            <strong>Owner:</strong>${ownerName}<br>
            <strong>Links:</strong><br>
            ${data.unformatted_urls}
        `;

        console.log(data.wysiwygContent);
        
        // TODO: Add the "Copy URLs" and "Copy - Email" buttons
        data.copy = `
            <button type="button" class="btn btn-dark m-1" onclick="copyToClipboard('${data.unformatted_urls}')">Copy URLs</button>
            <button type="button" class="btn btn-dark m-1" onclick="copyToClipboardToEmail(\`${data.wysiwygContent}\`)">Copy - Email</button>
        `;
    });

    sortableTable.setData(Object.values(tableData)); 
    sortableTable.events()
        .on('sort', (event) => {
            console.log(`[SortableTable#onSort]
        event.colId=${event.colId}
        event.sortDir=${event.sortDir}
        event.data=\n${JSON.stringify(event.data)}`);
    });
}

function copyToClipboard(text) {
    console.log(text);
    const plainText = text.replace(/<\/?[^>]+(>|$)/g, "");
    navigator.clipboard.writeText(plainText)
    .then(() => {
        alert("URLs copied to clipboard!");
    }).catch(err => {
        console.error("Failed to copy text: ", err);
    });
}

function copyToClipboardToEmail(text) {
    console.log(text);
    const textArea = document.createElement("textarea");
    textArea.value = content;
    document.body.appendChild(textArea);
    textArea.select();
    textArea.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand('copy');
    document.body.removeChild(textArea);
    alert("Email content copied to clipboard!");
}

function export2csv() {
    let data = "";
    const tableData = [];
    const rows = document.querySelectorAll("table tr");
  
    // Iterate over each row
    for (const row of rows) {
        const rowData = [];
        for (const [index, column] of row.querySelectorAll("th, td").entries()) {
            console.log(index, column);
            if (index < 2) {
                console.log(index, column);
                const cellData = column.innerText.replace(/"/g, '""');
                if (cellData.includes(",") || cellData.includes("\n")) {
                    rowData.push('"' + cellData + '"');
                } else {
                    rowData.push(cellData);
                }
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

