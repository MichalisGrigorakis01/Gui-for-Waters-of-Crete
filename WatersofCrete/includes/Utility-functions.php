<?php
//merge shortcodes
function WatersOfCrete_combined_functions($atts){
    extract( shortcode_atts( array(
		'display_form' => 'true'
	), $atts ) );
    if($display_form === 'false'){
        $output = render_table_waters($atts);
    }elseif ($display_form === 'true' || $display_form === ''){
        $output = WatersOfCrete_form($atts);
        $output .= render_table_waters($atts);
    }else{
        echo 'Invalid input in parameter display_form';
    }
    return $output;
}

//pagination
if (!function_exists('paginate_range')) {
    function paginate_range($current_page_D, $total_pages, $range = 2) {
        $start = max(1, $current_page_D - $range);
        $end = min($total_pages, $current_page_D + $range);
        $pages = [];

        if ($start > 1) {
            $pages[] = 1;
            if ($start > 2) $pages[] = '...';
        }

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($end < $total_pages) {
            if ($end < $total_pages - 1) $pages[] = '...';
            $pages[] = $total_pages;
        }

        return $pages;
    }
}
function cache_get($cache_key, $cache_dir = __DIR__ . '/cache', $expiration = 3600) {
    $cache_file = $cache_dir . '/' . md5($cache_key) . '.cache';
    if (file_exists($cache_file) && (filemtime($cache_file) > (time() - $expiration))) {
        return file_get_contents($cache_file);
    }
    return false;
}

function cache_set($cache_key, $data, $cache_dir = __DIR__ . '/cache') {
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);  // Create cache directory if it does not exist
    }
    $cache_file = $cache_dir . '/' . md5($cache_key) . '.cache';
    file_put_contents($cache_file, $data);
}

function get_org_name() {
    $orgid = getorgid();
    if($orgid){
        $cache_key = "org_name_{$orgid}";
        $cached = cache_get($cache_key);
        if ($cached !== false) {
            $cached = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $cached);
            $data = json_decode($cached, true);
            if (isset($data['result'][0]['title'])) {
                return $data['result'][0]['title'];
            } else {
                return '';
            }
        }
    }
    //$temp = getorgid();
    //$temp_q = getqueryparams();
    //$id_ = getmonthid();
    if($orgid) {
        $year_title = getyearids();
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

        $year_id = isset($year_mapping[$year_title]) ? $year_mapping[$year_title] : null;
        $url = "https://data.apdkritis.gov.gr/el/api/3/action/package_show?id=$year_id&page=0";
    }else{
        $year_title = getactualyearid();
        $url = "https://data.apdkritis.gov.gr/el/api/3/action/package_show?id=$year_title&page=0";
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);

    $result = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $result);
    $data = json_decode($result, true);

    if (isset($data['result'][0]['title'])) {
        cache_set($cache_key, $result);
        return $data['result'][0]['title'];
    } else {
        return 'Please Fill the Form Above';
    }
}

function get_coasts() {
    $orgid = getorgid();
    
    // Retrieve selected filters from SESSION
    $selectedMunicipality = isset($_SESSION['municipal']) ? sanitize_text_field($_SESSION['municipal']) : '';
    $selectedWave = isset($_SESSION['wave']) ? sanitize_text_field($_SESSION['wave']) : '';
    $selectedClean = isset($_SESSION['clean']) ? sanitize_text_field($_SESSION['clean']) : '';

    if ($orgid) {
        $cache_key = "org_units_{$orgid}";
        $cached = cache_get($cache_key);
        if ($cached !== false) {
            $data = json_decode($cached, true);
            $units = [];
        }
    }

    if ($orgid) {
        $url = "https://data.apdkritis.gov.gr/el/api/action/datastore/search.json?resource_id=$orgid&limit=100";
    } else {
        $temp_q = getqueryparams();
        $id_ = getmonthid();
        $url = "https://data.apdkritis.gov.gr/el/api/action/datastore/search.json?resource_id=$id_&$temp_q&limit=100";
    }

    // Fetch data from API
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);

    // Clean and decode JSON response
    $result = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $result);
    $data = json_decode($result, true);

    cache_set($cache_key, $result);
    $units = [];

    if (isset($data['result']['records'])) {
        foreach ($data['result']['records'] as $record) {
            if (!empty($record['coast'])) {

                // **Apply Municipality Filter**
                if (!empty($selectedMunicipality) && isset($record['municipal']) && $record['municipal'] !== $selectedMunicipality) {
                    continue; // Skip if the municipality doesn't match
                }

                // **Apply Wave Filter**
                if (!empty($selectedWave) && isset($record['wave']) && trim($record['wave']) !== trim($selectedWave)) {
                    continue; // Skip if the wave condition doesn't match
                }

                // **Apply Cleanliness Filter**
                $tar = $record['tar'] ?? 'ΟΧΙ';
                $glass = $record['glass'] ?? 'ΟΧΙ';
                $plastic = $record['plastic'] ?? 'ΟΧΙ';
                $caoutchouc = $record['caoutchouc'] ?? 'ΟΧΙ';
                $garbage = $record['garbage'] ?? 'ΟΧΙ';

                // Count how many values are "ΝΑΙ"
                $yesCount = count(array_filter([$tar, $glass, $plastic, $caoutchouc, $garbage], function ($value) {
                    return trim(strtoupper($value)) === 'ΝΑΙ';
                }));

                if (!empty($selectedClean)) {
                    if ($selectedClean == "clean" && $yesCount == 0) {
                        $units[$record['coast']] = $record['coast']; // Only Clean Coasts
                    } elseif ($selectedClean == "semi-clean" && $yesCount > 0 && $yesCount < 3) {
                        $units[$record['coast']] = $record['coast']; // Semi-Clean Coasts
                    } elseif ($selectedClean == "not-clean" && $yesCount >= 3) {
                        $units[$record['coast']] = $record['coast']; // Not Clean Coasts
                    }
                } else {
                    // If no cleanliness filter is selected, return all coasts
                    $units[$record['coast']] = $record['coast'];
                }
            }
        }
    }

    return $units;
}


function display_table($items, $column, $column_translation, $rows_per_page_D) {
    for ($j = 0; $j < $rows_per_page_D; $j++) {
        if ($j < count($items)) { 
            $item = $items[$j]; // Get the item for the current row
            echo '<tr>';
            foreach ($column as $column_column) {
                $column_column = trim($column_column);
                $currentcolumn = $column_translation[$column_column] ?? $column_column; // Fallback if missing
                column_handler($currentcolumn, $item);
            }
            echo '</tr>';
        } else {
            break;
        }
    }
}

function column_handler($currentcolumn, $item) {
    // **Handle Coast (Ακτή)**
    if ($currentcolumn == 'coast') {
        $coast_name = isset($item['coast']) ? esc_html($item['coast']) : '-';
        if ($coast_name !== '-') {
            $google_maps_link = "https://www.google.com/maps/search/" . urlencode($coast_name);
            echo '<td><a href="' . $google_maps_link . '" target="_blank">' . $coast_name . '</a></td>';
        } else {
            echo '<td>-</td>';
        }
    }
    // **Handle Description (Περιγραφή)**
    else if ($currentcolumn == 'description') {
        echo '<td>' . esc_html($item['description'] ?? '-') . '</td>';
    }
    // **Handle Municipality (Δήμος)**
    else if ($currentcolumn == 'municipal') {
        echo '<td>' . esc_html($item['municipal'] ?? '-') . '</td>';
    }
    // **Handle Station Code**
    else if ($currentcolumn == 'id') {
        echo '<td>' . esc_html($item['stationcode'] ?? '-') . '</td>';
    }
    // **Handle Wave (Κύμα)**
    else if ($currentcolumn == 'wave') {
        echo '<td>' . esc_html($item['wave'] ?? '-') . '</td>';
    }
    // **Handle Cleanliness (Καθαριότητα)**
    else if ($currentcolumn == 'clean') {
        $clean_values = [
            'tar' => $item['tar'] ?? 'ΟΧΙ',
            'glass' => $item['glass'] ?? 'ΟΧΙ',
            'plastic' => $item['plastic'] ?? 'ΟΧΙ',
            'caoutchouc' => $item['caoutchouc'] ?? 'ΟΧΙ',
            'garbage' => $item['garbage'] ?? 'ΟΧΙ'
        ];
        // Count how many are "ΝΑΙ"
        $yes_count = count(array_filter($clean_values, fn($value) => trim(strtoupper($value)) === 'ΝΑΙ'));

        if ($yes_count == 0) {
            $clean_value = 'Καθαρή';
        } elseif ($yes_count >= 3) {
            $clean_value = 'Βρώμικη';
        } else {
            $clean_value = 'Μέτρια Καθαρή';
        }
        echo '<td>' . esc_html($clean_value) . '</td>';
    }
    // **Handle Sample Date (Ημ. Δείγματος)**
    else if ($currentcolumn == 'from_sampledate') {
        echo '<td>' . esc_html($item['sampledate'] ?? '-') . '</td>';
    }
    // **Handle Analysis Date (Ημ. Ανάλυσης)**
    else if ($currentcolumn == 'analysedate') {
        echo '<td>' . esc_html($item['analysedate'] ?? '-') . '</td>';
    }
    // **Handle perunit (Νομός)**
    else if($currentcolumn == 'perunit'){
        echo '<td>' . esc_html($item['perunit'] ?? '-') . '</td>';
    }
    // **If column is missing, print "-"**
    else {
        echo '<td>-</td>';
    }
}



add_shortcode('WatersofCrete_Archive', 'WatersOfCrete_combined_functions');