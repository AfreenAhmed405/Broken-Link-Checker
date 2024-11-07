<?php

  // ----------------------------------------------------------------
  // OAUTH 2.0 CONFIGURATION
  // ----------------------------------------------------------------
  function generateToken() {
    $GET_LIBGUIDES = "Authorization: Bearer ";
    $GET_BROKEN_LINKS = "Authorization: Basic bW1vaGthbWthckB0YW11LmVkdTo3YzVkZTk1MDFhMjc4NjllMGUyYTU2MGY1YmRmMjA1OA==";
    $tokenUrl = 'https://lgapi-us.libapps.com/1.2/oauth/token';  // URL for token endpoint
    $clientId = '823';                                           // Client ID
    $clientSecret = 'fa6f233d0078409effa2073d15e804fe';          // Client Secret

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
      CURLOPT_URL => 'https://api.eu.siteimprove.com/v2/sites/1348467636/quality_assurance/links/pages_with_broken_links?page=1&page_size=5&group_id=14333507148',
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

    // Generate table entries
    $file = 'all-brokenlinks.json';
    $handle = fopen($file, 'w');
    fclose($handle);

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

        if($libguide_root == $guide_friendly_url) {  
          $rowData = [
            'guide_owner_email' => $guide_owner_email,
            'guide_owner_name' => $guide_owner_name,
            'siteimprove_url' => $siteimprove_url,
            'guide_name' => $guide_name,
            'guide_url' => $guide_friendly_url
          ]; 
        } else if($libguide_root == $guide_url) {
          $rowData = [
            'guide_owner_email' => $guide_owner_email,
            'guide_owner_name' => $guide_owner_name,
            'siteimprove_url' => $siteimprove_url,
            'guide_name' => $guide_name,
            'guide_url' => $guide_url
          ];
        }
      }

      // Write to temp file -- could change to an in-memory db?
      $handle = fopen($file, 'c+');
      $fileContents = fread($handle, 8192); // Read the whole file

      if (empty($fileContents)) {
        fwrite($handle, "[\n");             // If file is empty, add '['
      } else {
          fseek($handle, 0, SEEK_END);      // Move to the end of the file
          fwrite($handle, ",\n");           // Add a comma to separate the rows
      }
      fwrite($handle, json_encode($rowData));
      fclose($handle);
    } 
    file_put_contents($file, "\n]", FILE_APPEND); // Close file with ']' 

    $fileRead = file_get_contents($file);
    return json_encode([
      "fileContents" => $fileRead,
      "token" => $responseData['access_token']
    ]);
  }

  // Check if this file is called through AJAX
  if (isset($_POST['action']) && $_POST['action'] == 'generate_token') {
    echo generateToken(); 
  }
  
?>