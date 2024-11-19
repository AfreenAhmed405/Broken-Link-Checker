# SiteImprove Broken Links Report
> This web application automatically identifies and tracks broken links across all LibGuides hosted by Texas A&M University Libraries. It integrates with SiteImprove to scan and report broken links, and includes an emailing system that notifies each guide owner of their broken links with a detailed report. The tool streamlines the process of link maintenance, saving time for library staff and ensuring that all resources are up-to-date and fully functional.

## Table of Contents
- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)


## Features
- **Automated Link Scanning**: Integrates with SiteImprove to identify broken links across all LibGuides.
- **Generation of Detailed Reports**: Generates detailed reports that include broken links along with owner details.
- **Email Notifications**: Sends email alerts to guide owners with a detailed report of the broken links found.
- **Comprehensive Reporting**: Provides a summary of all broken links, categorized by the guide they belong to, with actionable details.


## Installation and Setup
Follow these steps to get your development environment set up.

1. **Install XAMPP**:  
   Download and install [XAMPP](https://www.apachefriends.org/index.html) for your operating system. XAMPP is a free and open-source cross-platform web server solution stack package, which includes Apache, MySQL, and PHP.

2. **Navigate to the `htdocs` directory**:  
   After installing XAMPP, open the `htdocs` folder where all your web applications will reside. The default path is:  
   `C:\xampp\htdocs`

3. **Clone the repository**:  
   Clone the project repository into the `htdocs` directory using Git. In your terminal, run:
   ```bash
   git clone https://github.com/AfreenAhmed405/Broken-Link-Checker.git

4. **Add secrets to token.env**:  
   Create a token.env in the project root directory and add these secrets: GET_BROKEN_LINKS=, CLIENT_ID=, CLIENT_SECRET=

5. **Start XAMPP**:  
    Open the XAMPP Control Panel, and start the Apache server to serve your application.


## Usage
Once the application is installed and running, follow these steps to use the tool:

1. **Start the Apache Server**:  
   If you haven't already, open the XAMPP Control Panel and start the Apache server to serve your application.

2. **Automated Link Scanning**:  
   The application is configured to scan LibGuides for broken links on clicking the 'Generate Report' button. Once a token is generated, a table should automatically populate with 100 broken links.

3. **Downloadable Report**:  
   Clicking on the 'Download CSV' button will download a CSV file with the table contents on the webpage

4. **Email alerts**:  
   Click on the owner name to open a new email in your default email client. Click on the 'Email Body' button to open a new tab with the email contents. Copy the page content and paste it into your message.