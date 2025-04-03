<?php

global $query_params;
global $temp_orgid;
global $temp_query;
global $month_id;
global $year_id;
global $yearac_id;
global $total_items;

function watersform($orgid = NULL, $page = 0, $size = 5, $id = NULL) {
    session_start();

    // Retrieve SESSION values or POST input
    $selectedID = isset($_SESSION['stationcode']) ? sanitize_text_field($_SESSION['stationcode']) : '';
    $selectedYear = isset($_SESSION['year']) ? sanitize_text_field($_SESSION['year']) : '';
    $selectedMonth = isset($_SESSION['month']) ? sanitize_text_field($_SESSION['month']) : '';
    $selectedMunicipality = isset($_SESSION['municipal']) ? sanitize_text_field($_SESSION['municipal']) : '';
    $selectedWave = isset($_SESSION['wave']) ? sanitize_text_field($_SESSION['wave']) : '';
    $selectedCoast = isset($_SESSION['coast']) ? sanitize_text_field($_SESSION['coast']) : '';
    $selectedDescription = isset($_SESSION['description']) ? sanitize_text_field($_SESSION['description']) : '';
    $resourceID = $selectedMonth;

    $items = [];
    
    // Build query parameters
    $query_params = http_build_query(array_filter([
        'clean' => isset($_SESSION['clean']) ? sanitize_text_field($_SESSION['clean']) : '',
        'municipal' => isset($_SESSION['municipal']) ? sanitize_text_field($_SESSION['municipal']) : '',
        'wave' => isset($_SESSION['wave']) ? sanitize_text_field($_SESSION['wave']) : '',
        'perunit' => isset($_SESSION['perunit']) ? sanitize_text_field($_SESSION['perunit']) : '',
        'coast' => isset($_SESSION['coast']) ? sanitize_text_field($_SESSION['coast']) : '',
        'description' => isset($_SESSION['description']) ? sanitize_text_field($_SESSION['description']) : '',
    ]));
    
    global $temp_query;
    $temp_query = $query_params;
    
    if($orgid)$selectedID = $orgid;
    // Set the correct API endpoint
    if (!empty($selectedID)) {
        $result = searchStationcode($selectedID);
        
        $orgid = $selectedID; //IMPORTANT

        //To access the orgid

        if ($result) {
            // Save found values in SESSION
            $_SESSION['year'] = $result['year'];
            $_SESSION['month'] = $result['month_id'];
            $_SESSION['coast'] = $result['coast'];

            global $temp_orgid;
            $temp_orgid = $result['month_id'];

            global $year_id;
            $year_id = $result['year'];

            //return [['stationcode' => $selectedID, 'year' => $result['year'], 'month' => $result['month_name'], 'coast' => $result['coast']]];
            return [$result];
        } else {
            echo "<p>Stationcode not found.</p>";
            return [];
        }
    } else {
        // Use the selected resource ID for a specific month
        $url = "https://data.apdkritis.gov.gr/el/api/action/datastore/search.json?resource_id=$resourceID&$query_params&limit=100";
        //echo $url;

        global $month_id;
        $month_id = $resourceID;

        global $yearac_id;
        $yearac_id = $selectedYear;
    }
    //echo "<pre>URLL: $url</pre>";

    // Fetch data using cURL
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);
    if ($result === false) {
        echo 'cURL error: ' . curl_error($curl);
        curl_close($curl);
        return $items;
    }
    curl_close($curl);

    // Clean the raw result and decode JSON
    $result = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $result);
    $data = json_decode($result, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        echo 'Error decoding JSON: ' . json_last_error_msg();
        return $items;
    }

    // Process the response
    if ($resourceID) {
        $total = isset($data['result']['total']) ? $data['result']['total'] : 0;
        $records = isset($data['result']['records']) ? $data['result']['records'] : [];
        $items = $records;
        $items['result']['total'] = $total;
    } else {
        $items[] = $data;
        $items['result']['total'] = 1;
    }

    $filtered_items = [];
		$actualSize = 0;
		
		foreach ($items as $record) {
			// Apply the same filtering logic as in the form
			if (!empty($_SESSION['municipal']) && $_SESSION['municipal'] !== $record['municipal']) {
				continue; // Skip records that don't match the selected municipality
			}
		
			if (!empty($_SESSION['coast']) && $_SESSION['coast'] !== $record['coast']) {
				continue; // Skip records that don't match the selected coast
			}
		
			if (!empty($_SESSION['description']) && $_SESSION['description'] !== $record['description']) {
				continue; // Skip records that don't match the selected description
			}
		
			if (!empty($_SESSION['wave']) && $_SESSION['wave'] !== $record['wave']) {
				continue; // Skip records that don't match the selected wave type
			}
		
			// Apply cleanliness filtering
			if (!empty($_SESSION['clean'])) {
				$tar = isset($record['tar']) ? trim($record['tar']) : 'ΟΧΙ';
				$glass = isset($record['glass']) ? trim($record['glass']) : 'ΟΧΙ';
				$plastic = isset($record['plastic']) ? trim($record['plastic']) : 'ΟΧΙ';
				$caoutchouc = isset($record['caoutchouc']) ? trim($record['caoutchouc']) : 'ΟΧΙ';
				$garbage = isset($record['garbage']) ? trim($record['garbage']) : 'ΟΧΙ';
		
				// Count how many values are "ΝΑΙ"
				$yesCount = count(array_filter([$tar, $glass, $plastic, $caoutchouc, $garbage], function ($value) {
					return strtoupper($value) === 'ΝΑΙ';
				}));
		
				if ($_SESSION['clean'] == "clean" && $yesCount > 0) {
					continue; // Only clean coasts (no "ΝΑΙ")
				} elseif ($_SESSION['clean'] == "semi-clean" && ($yesCount == 0 || $yesCount >= 3)) {
					continue; // Semi-clean: must be 1-2 "ΝΑΙ"
				} elseif ($_SESSION['clean'] == "not-clean" && $yesCount < 3) {
					continue; // Not-clean: must have 3+ "ΝΑΙ"
				}
			}
		
			// If all filters pass, add to the new filtered array
			$filtered_items[] = $record;
			$actualSize++;
		}
		
		// Update the items to only include filtered results
		$items = $filtered_items;
		
		// Set the actual total size for pagination
        global $total_items;
		$total_items = $actualSize;

    return $items;
}

function searchStationcode($stationcode) {
    $year_mapping = [
        '2023' => '09133157-1ce2-4996-9963-e6bb6e6db6b5',
        '2022' => '3634f175-00d6-4b85-904b-8f5cabdae5aa',
        '2021' => 'b80d183b-2c1c-4d2f-806f-8b9ec7bd61c8',
        '2020' => '0c7984bc-5971-49ae-b7b0-0f9ea494e4c5',
        '2019' => '1e1462c1-1f61-44f0-b128-d46ee57ffb4b',
        '2018' => '388268b6-7f72-4623-a2cb-e90110004e28',
        '2017' => '29530415-4d66-4e08-8452-e267d5eefd3b',
        '2016' => '94d41922-1e22-4dea-b120-da06b9b1e669'
    ];

    foreach ($year_mapping as $year => $resourceID) {
        $months = get_month_resources($resourceID);

        foreach ($months as $month_name => $month_id) {
            $url = "https://data.apdkritis.gov.gr/el/api/action/datastore/search.json?resource_id=$month_id&limit=100";
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);
            if ($result === false) {
                curl_close($curl);
                continue;
            }
            curl_close($curl);

            // **CLEAN AND DECODE RESPONSE**
            $result = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $result);
            $data = json_decode($result, true);

            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            // **SEARCH FOR STATIONCODE**
            if ($data && isset($data['result']['records'])) {
                foreach ($data['result']['records'] as $record) {
                    if (isset($record['stationcode']) && $record['stationcode'] === $stationcode) {
                        return array_merge(
                            $record, // All station data
                            [
                                'year' => $year,
                                'month_name' => $month_name,
                                'month_id' => $month_id,
                                'coast' => $record['coast']
                            ]
                        );
                        /*
                        return [
                            'year' => $year,
                            'month_name' => $month_name,
                            'month_id' => $month_id,
                            'coast' => $record['coast']
                        ];
                        */
                    }
                }
            }
        }
    }
    return null; // Stationcode not found
}

function getorgid(){
    global $temp_orgid;
    return $temp_orgid;
}

function getqueryparams(){
    global $temp_query;
    return $temp_query;
}

function getmonthid(){
    global $month_id;
    return $month_id;
}

function getyearids(){
    global $year_id;
    return $year_id;
}

function getactualyearid(){
    global $yearac_id;
    return $yearac_id;
}

function get_total_items(){
    global $total_items;
    return $total_items;
}
