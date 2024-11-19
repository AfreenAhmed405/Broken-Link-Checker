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
        const columns = row.querySelectorAll("td, th");
        
        if (columns.length >= 2) {
            const firstColumn = columns[0].innerText.replace(/"/g, '""');  // Get the first column text
            let secondColumn = columns[1].innerHTML.replace(/"/g, '""');  // Get the second column HTML content

            // Check if the second column has <br> tags indicating multiple lines
            if (secondColumn.includes("<br>")) {
                // Split the multiline cell (in this case, the second cell) by <br><br> tags
                const cellLines = secondColumn.split("<br><br>");

                // For each line in the second column, create a new row with the same first column value
                for (const line of cellLines) {
                    const rowDataCopy = [];
                    rowDataCopy.push(firstColumn);  // First column value (e.g., "Owner")

                    // Extract text inside <a> tags from the second column (line)
                    const regex = /<a[^>]*>(.*?)<\/a>/;
                    const match = line.match(regex);
                    let anchorText = "";
                    if (match) {
                        anchorText = match[1];  // The text inside the <a> tag
                    } else {
                        anchorText = line.trim();  // If there's no <a> tag, just take the line as is
                    }

                    // Check if the anchorText has commas or newlines, and quote it if necessary
                    if (anchorText.includes(",") || anchorText.includes("\n")) {
                        rowDataCopy.push('"' + anchorText + '"');
                    } else {
                        rowDataCopy.push(anchorText);
                    }

                    // Add the row data to the tableData array
                    tableData.push(rowDataCopy.join(","));
                }
            } else {
                // If the second column doesn't have <br> tags, just add the row as is
                rowData.push(firstColumn);

                // Extract text inside <a> tags from the second column (if any)
                const regex = /<a[^>]*>(.*?)<\/a>/;
                const match = secondColumn.match(regex);
                let anchorText = "";
                if (match) {
                    anchorText = match[1];  // The text inside the <a> tag
                } else {
                    anchorText = secondColumn.trim();  // If there's no <a> tag, just take the content as is
                }

                // Check if the anchorText has commas or newlines, and quote it if necessary
                if (anchorText.includes(",") || anchorText.includes("\n")) {
                    rowData.push('"' + anchorText + '"');
                } else {
                    rowData.push(anchorText);
                }

                // Add the row data to the tableData array
                tableData.push(rowData.join(","));
            }
        }
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

