<?php

  // ----------------------------------------------------------------
  // OAUTH 2.0 CONFIGURATION
  // ----------------------------------------------------------------
  function generateToken() {
    $env = parse_ini_file('tokens.env');  
    $GET_LIBGUIDES = "Authorization: Bearer ";
    $GET_BROKEN_LINKS = $env["GET_BROKEN_LINKS"];
    $tokenUrl = 'https://lgapi-us.libapps.com/1.2/oauth/token';  // URL for token endpoint
    $clientId = $env["CLIENT_ID"];                               // Client ID
    $clientSecret = $env["CLIENT_SECRET"];                       // Client Secret

    $postFields = [
      'grant_type'    => 'client_credentials',         
      'client_id'     => $clientId,
      'client_secret' => $clientSecret,
    ];

    // Generate API Token
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $tokenUrl,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => http_build_query($postFields),
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/x-www-form-urlencoded'
      )
    ));
    $response = curl_exec($curl);

    if(curl_errno($curl)) {
        echo 'cURL error: ' . curl_error($curl);
        exit;
    }

    $responseData = json_decode($response, true);
    $GET_LIBGUIDES = $GET_LIBGUIDES . $responseData['access_token'];

    // ----------------------------------------------------------------
    // GET DATA
    // ----------------------------------------------------------------

    // Get all LibGuides Content
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://lgapi-us.libapps.com/1.2/guides?expand=owner',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        $GET_LIBGUIDES
      )
    ));
    $libguides = curl_exec($curl);
    $libguides = json_decode($libguides);

    // Get all SiteImprove Content
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.eu.siteimprove.com/v2/sites/1348467636/quality_assurance/links/pages_with_broken_links?page=1&page_size=25&group_id=14333507148',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        $GET_BROKEN_LINKS
      ),
    ));
    $broken_links = curl_exec($curl);
    curl_close($curl);
    $broken_links = json_decode($broken_links);
    $broken_links = $broken_links->items;

    // Create owner hashmap
    $hashmap = [];

    // Generate table entries
    foreach($broken_links as $broken_link) {
      $pageId = $broken_link->id;
      $pagecurl_url = "https://api.eu.siteimprove.com/v2/sites/1348467636/quality_assurance/links/pages_with_broken_links/" . $pageId . "/broken_links?page=1&page_size=10";

      $page_curl = curl_init();
      curl_setopt_array($page_curl, array(
        CURLOPT_URL => $pagecurl_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            $GET_BROKEN_LINKS
        )
      ));

      $broken_page = curl_exec($page_curl);
      curl_close($page_curl);
      $broken_page = json_decode($broken_page);
      if ($broken_page -> items) {
        $link_id = ($broken_page -> items)[0] -> id;
      } else {
        $link_id = "";
      }
      $siteimprove_url = "https://my2.siteimprove.com/Inspector/77878/QualityAssurance/Page?" . substr($broken_link -> _siteimprove -> page_report -> href, 70) . "#/Issue/BrokenLinks/" . $link_id;
      
      $libguide_url = $broken_link->url;
      $libguide_id = substr($libguide_url , 35 , 6);
      // TODO: alternatively, search between 'g=' and '&p=' ... this would be more robust
      preg_match('/https:\/\/tamu.libguides.com\/c.php\?g=\d+|https:\/\/tamu.libguides.com\/\w+/', $libguide_url, $matches);
      $libguide_root = $matches[0];
      $broken_link_array[$siteimprove_url] = $libguide_root;

      foreach($libguides as $guide) {
        $guide_name = $guide->name;
        $guide_url = $guide->url;
        $guide_friendly_url = $guide->friendly_url;
        $guide_owner_email = $guide->owner->email;
        $guide_owner_first_name = $guide->owner->first_name;
        $guide_owner_last_name = $guide->owner->last_name;
        $guide_owner_name = $guide_owner_first_name . ' ' . $guide_owner_last_name;

        if($libguide_root == $guide_friendly_url || $libguide_root == $guide_url) {  
          if (!isset($hashmap[$guide_owner_name])) {
            $hashmap[$guide_owner_name] = [
              'guide_owner_email' => $guide_owner_email,
              'siteimprove_urls' => []
            ];
          }
  
          if (!in_array($siteimprove_url, $hashmap[$guide_owner_name]['siteimprove_urls'])) {
            $hashmap[$guide_owner_name]['siteimprove_urls'][] = $siteimprove_url;
          }

        }
      }
    } 

    $reportData = [
      'reportDate' => date('Y-m-d'), // Current date
      'brokenLinks' => $hashmap // The list of broken links and their owners
    ];

    // Store the report in a JSON file
    file_put_contents('new_report.json', json_encode($reportData, JSON_PRETTY_PRINT));

    return json_encode([
      "token" => $responseData['access_token'], 
      "hashMap" => $hashmap
    ]);
  }

  // Check if this file is called through AJAX
  if (isset($_POST['action']) && $_POST['action'] == 'generate_token') {
    echo generateToken(); 
  }

  // Retrieves Report Dates from JSON
  if (isset($_POST['action']) && $_POST['action'] == 'get_dates') {
    $dateFile = 'report_dates.json';
    if (file_exists($dateFile)) {
        $data = file_get_contents($dateFile);
        echo $data;
    } else {
        echo json_encode(["error" => "Date file not found"]);
    }
    exit;
  }

  // Updates Report Dates from JSON
  if (isset($_POST['action']) && $_POST['action'] == 'update_dates') {
    $dateFile = 'report_dates.json';
    $newData = [
        "lastReportGenerated" => $_POST['lastReportGenerated'],
        "nextReportDue" => $_POST['nextReportDue']
    ];
    
    if (file_put_contents($dateFile, json_encode($newData, JSON_PRETTY_PRINT))) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => "Failed to update date file"]);
    }
    exit;
  }
  
?>