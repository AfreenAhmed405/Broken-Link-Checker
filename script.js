function populateTable(tableData) {
    const sortableTable = new SortableTable();
    sortableTable.setTable(document.querySelector('#brokenLinkTable'));

    Object.entries(tableData).forEach(([ownerName, data]) => {
        data.guide_owner_name = `<a href="mailto:${data.guide_owner_email}?subject=LibGuides: Broken Links and Misspellings">${ownerName}</a>`;
        data.unformatted_urls = data.siteimprove_urls;
        data.siteimprove_urls = data.siteimprove_urls
            .map(url => `<a href="${url}" target="_blank">${url}</a>`)
            .join('<br><br>');
        
        data.copy = `<button type="button" class="btn btn-dark m-1" onclick="copyToClipboard('${data.unformatted_urls}', 'url')"><small>Copy URLs</small></button>
                     <button type="button" class="btn btn-dark m-1" onclick="copyToClipboard('${data.unformatted_urls}', 'email')"><small>Email Body</small></button>`;
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

function copyToClipboard(text, type) {
    if (type == "url") {
        const plainText = text.replace(/<\/?[^>]+(>|$)/g, "");
        navigator.clipboard.writeText(plainText)
        .then(() => {
            alert("URLs copied to clipboard!");
        }).catch(err => {
            console.error("Failed to copy text: ", err);
        });
    } else if (type == 'email') {
        fetch('email.html')
            .then(response => response.text())
            .then(emailContent => {
                const urlsArray = text.split(',');

                /** Uncomment for URLs to be in a list */ 
                let urlsTable = '<ul>';
                if (typeof text === 'string') {
                    text = [text];
                }
                urlsArray.forEach(url => {
                    url = url.trim();
                    if (url) {
                        urlsTable += `<li><a href="${url}" target="_blank">${url}</a></li>`;
                    }
                });
                urlsTable += '</ul>';


                /** Uncomment for URLS to be in a table */ 
                // let urlsTable = '<table border="1" cellpadding="5" cellspacing="0" style="width: 50%;">';
                // urlsTable += '<thead><tr><th>Broken Links</th></tr></thead><tbody>';
                
                // if (typeof text === 'string') {
                //     text = [text];
                // }

                // urlsArray.forEach(url => {
                //     url = url.trim();
                //     if (url) {
                //         urlsTable += `<tr><td><a href="${url}" target="_blank">${url}</a></td></tr>`;
                //     }
                // });
                // urlsTable += '</tbody></table>';

                const emailWithTable = emailContent.replace('<!-- TABLE_PLACEHOLDER -->', urlsTable);
                openEmailWindow(emailWithTable);
            })
            .catch(error => {
                console.error('Error fetching email.html:', error);
            });
    }
}

function openEmailWindow(emailContent) {
    const emailWindow = window.open('', '_blank', 'width=600,height=400,resizable,scrollbars');
    emailWindow.document.write(emailContent);
    emailWindow.document.close();
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

