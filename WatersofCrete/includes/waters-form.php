<?php

global $yearID;

/**
 * Fetch resources for a given year from the API.
 */
function get_month_resources($year_id) {

    $url = "https://data.apdkritis.gov.gr/el/api/3/action/package_show?id=$year_id&page=0"; 
    
    // Fetch data from the API
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        error_log('API Error: ' . $response->get_error_message());
        return [];
    }

    // Retrieve and clean the response body
    $body = wp_remote_retrieve_body($response);
    $body = preg_replace('/^\xEF\xBB\xBF/', '', $body); // Remove UTF-8 BOM
    $body = trim($body);

    // Decode JSON response
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON Decode Error: ' . json_last_error_msg());
        return [];
    }

    // Initialize the months array
    $months = [];

    // Check if 'result' key exists and iterate over its entries
    if (isset($data['result']) && is_array($data['result'])) {
        foreach ($data['result'] as $result) {
            if (isset($result['resources']) && is_array($result['resources'])) {
                foreach ($result['resources'] as $resource) {
                    $name = $resource['name'] ?? '';
                    $id = $resource['id'] ?? '';
                    $format = strtolower($resource['format'] ?? '');
                    $mimetype = strtolower($resource['mimetype'] ?? '');

                    // Match only CSV files
                    if ($format === 'csv' || $mimetype === 'text/csv') {
                        if (preg_match('/(Μαΐου|Ιούνιος|Ιούλιος|Αύγουστος|Σεπτέμβριος|Οκτώβριος|ΜΑΪΟΣ|Mάϊος|ΙΟΥΝΙΟΣ|Ιουνίου|ΙΟΥΛΙΟΣ|Ιουλίου|ΑΥΓΟΥΣΤΟΣ|Αυγούστου|ΣΕΠΤΕΜΒΡΙΟΣ|Σεπτεμβρίου|ΟΚΤΩΒΡΙΟΣ|Οκτωβρίου)/iu', $name, $matches)) {
                            $month = $matches[1];
                            $months[$month] = $id;
                            error_log("Matched Month: $month with ID: $id");
                        }
                    }
                }
            }
        }
    } else {
        error_log("No 'result' key found in API response or 'result' is not an array.");
    }

    error_log("Final Months Array: " . print_r($months, true));

    return $months;
}




// AJAX handler to return month IDs dynamically
add_action('wp_ajax_get_months_by_year', 'ajax_get_months_by_year');
add_action('wp_ajax_nopriv_get_months_by_year', 'ajax_get_months_by_year');

function ajax_get_months_by_year() {
    $year_id = isset($_POST['year_id']) ? sanitize_text_field($_POST['year_id']) : '';
    //if($year_id != '')
        $months = get_month_resources($year_id);
    //else
    //    $months = get_month_resources($year_id);
    wp_send_json_success($months);
    wp_die();
}

// AJAX function to fetch municipalities based on selected year and month
add_action('wp_ajax_get_municipalities_by_filters', 'get_municipalities_by_filters');
add_action('wp_ajax_nopriv_get_municipalities_by_filters', 'get_municipalities_by_filters');

function get_municipalities_by_filters() {
    if (!isset($_POST['year']) || !isset($_POST['month'])) {
        wp_send_json_error(['message' => 'Invalid request.']);
        wp_die();
    }

    $selectedYear = sanitize_text_field($_POST['year']);
    $selectedMonth = sanitize_text_field($_POST['month']);
    $resourceID = $selectedMonth;

    if (!$resourceID) {
        wp_send_json_error(['message' => 'No resource ID found.']);
        wp_die();
    }

    // API request to fetch municipalities based on selected filters
    $url = "https://data.apdkritis.gov.gr/el/api/action/datastore/search.json?resource_id=$resourceID&limit=100";

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

    $response = wp_remote_get($url);
    // Clean the raw result and decode JSON
    $result = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $result);
    $data = json_decode($result, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        echo 'Error decoding JSON: ' . json_last_error_msg();
        return $items;
    }

    $municipalities = [];

    if ($data && isset($data['result']['records'])) {
        foreach ($data['result']['records'] as $record) {
            if (!empty($record['municipal']) && !in_array($record['municipal'], $municipalities)) {
                $municipalities[] = $record['municipal'];
            }
        }
    }

    if (!empty($municipalities)) {
        wp_send_json_success($municipalities);
    } else {
        wp_send_json_error(['message' => 'No municipalities found.']);
    }

    wp_die();
}

// AJAX function to fetch coasts based on selected filters
add_action('wp_ajax_get_coasts_by_filters', 'get_coasts_by_filters');
add_action('wp_ajax_nopriv_get_coasts_by_filters', 'get_coasts_by_filters');

function get_coasts_by_filters() {
    if (!isset($_POST['year']) || !isset($_POST['month'])) {
        wp_send_json_error(['message' => 'Invalid request.']);
        wp_die();
    }

    $selectedYear = sanitize_text_field($_POST['year']);
    $selectedMonth = sanitize_text_field($_POST['month']);
    $selectedMunicipality = isset($_POST['municipal']) ? sanitize_text_field($_POST['municipal']) : '';
    $selectedWave = isset($_POST['wave']) ? sanitize_text_field($_POST['wave']) : '';
    $selectedClean = isset($_POST['clean']) ? sanitize_text_field($_POST['clean']) : '';
    $selectedDescription = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
    $resourceID = $selectedMonth;

    if (!$resourceID) {
        wp_send_json_error(['message' => 'No resource ID found.']);
        wp_die();
    }

    // API request to fetch coasts based on selected filters
    $url = "https://data.apdkritis.gov.gr/el/api/action/datastore/search.json?resource_id=$resourceID&limit=100";

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);
    if ($result === false) {
        echo 'cURL error: ' . curl_error($curl);
        curl_close($curl);
        wp_die();
    }
    curl_close($curl);

    // Clean and decode JSON response
    $result = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $result);
    $data = json_decode($result, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        echo 'Error decoding JSON: ' . json_last_error_msg();
        wp_die();
    }

    $coasts = [];

    if ($data && isset($data['result']['records'])) {
        foreach ($data['result']['records'] as $record) {
            if (!empty($record['coast'])) {
                // Apply Municipality filter
                if ($selectedMunicipality && isset($record['municipal']) && $record['municipal'] !== $selectedMunicipality) {
                    continue;
                }

                // Apply Wave filter (Check for exact match in API response)
                if ($selectedWave && isset($record['wave']) && trim($record['wave']) !== trim($selectedWave)) {
                    continue;
                }

                if($selectedDescription && isset($record['description']) && $record['description'] !== $selectedDescription) {
                    continue;
                }

                if ($selectedCoast && isset($record['coast']) && $record['coast'] !== $selectedCoast) {
                    continue; // Skip if Coast doesn't match
                }

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
                        $coasts[] = $record['coast']; // Clean coasts only
                    } elseif ($selectedClean == "semi-clean" && $yesCount > 0 && $yesCount < 3) {
                        $coasts[] = $record['coast']; // Semi-Clean coasts only
                    } elseif ($selectedClean == "not-clean" && $yesCount >= 3) {
                        $coasts[] = $record['coast']; // Not Clean coasts only
                    }
                } else {
                    // If no cleanliness filter is selected, return all coasts
                    $coasts[] = $record['coast'];
                }

                // Add coast only if it's unique
               /* if (!in_array($record['coast'], $coasts)) {
                    $coasts[] = $record['coast'];
                }
               */     
            }
        }
    }

    if (!empty($coasts)) {
        wp_send_json_success($coasts);
    } else {
        //wp_send_json_error(['message' => 'No coasts found.']);
    }

    wp_die();
}

add_action('wp_ajax_search_by_stationcode', 'search_by_stationcode');
add_action('wp_ajax_nopriv_search_by_stationcode', 'search_by_stationcode');

function search_by_stationcode() {
    if (!isset($_POST['stationcode'])) {
        wp_send_json_error(['message' => 'No stationcode provided.']);
        wp_die();
    }

    $stationcode = sanitize_text_field($_POST['stationcode']);
    
    // Define available years and corresponding resource IDs
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
        // Fetch all months for this year
        $months = get_month_resources($resourceID);

        foreach ($months as $month_name => $month_id) {
            // Query API for this year-month combination
            $url = "https://data.apdkritis.gov.gr/el/api/action/datastore/search.json?resource_id=$month_id&limit=100";
            $response = wp_remote_get($url);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if ($data && isset($data['result']['records'])) {
                foreach ($data['result']['records'] as $record) {
                    if (isset($record['stationcode']) && $record['stationcode'] === $stationcode) {
                        wp_send_json_success([
                            'year' => $year,
                            'month_name' => $month_name,
                            'month_id' => $month_id,
                            'coast' => $record['coast']
                        ]);
                        wp_die();
                    }
                }
            }
        }
    }

    wp_send_json_error(['message' => 'Stationcode not found.']);
    wp_die();
}

// AJAX function to fetch descriptions based on selected filters
add_action('wp_ajax_get_descriptions_by_filters', 'get_descriptions_by_filters');
add_action('wp_ajax_nopriv_get_descriptions_by_filters', 'get_descriptions_by_filters');

function get_descriptions_by_filters() {
    if (!isset($_POST['year']) || !isset($_POST['month'])) {
        wp_send_json_error(['message' => 'Invalid request.']);
        wp_die();
    }

    $selectedYear = sanitize_text_field($_POST['year']);
    $selectedMonth = sanitize_text_field($_POST['month']);
    $selectedMunicipality = isset($_POST['municipal']) ? sanitize_text_field($_POST['municipal']) : '';
    $selectedWave = isset($_POST['wave']) ? sanitize_text_field($_POST['wave']) : '';
    $selectedClean = isset($_POST['clean']) ? sanitize_text_field($_POST['clean']) : '';
    $resourceID = $selectedMonth;

    if (!$resourceID) {
        wp_send_json_error(['message' => 'No resource ID found.']);
        wp_die();
    }

    $url = "https://data.apdkritis.gov.gr/el/api/action/datastore/search.json?resource_id=$resourceID&limit=100";

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);
    if ($result === false) {
        echo 'cURL error: ' . curl_error($curl);
        curl_close($curl);
        wp_die();
    }
    curl_close($curl);

    $result = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $result);
    $data = json_decode($result, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        echo 'Error decoding JSON: ' . json_last_error_msg();
        wp_die();
    }

    $descriptions = [];

    if ($data && isset($data['result']['records'])) {
        foreach ($data['result']['records'] as $record) {
            if (!empty($record['description'])) {
                // Apply Municipality filter if selected
                if ($selectedMunicipality && isset($record['municipal']) && $record['municipal'] !== $selectedMunicipality) {
                    continue;
                }

                // Apply Wave filter if selected
                if ($selectedWave && isset($record['wave']) && trim($record['wave']) !== trim($selectedWave)) {
                    continue;
                }

                // Apply Cleanliness filter if selected
                $tar = $record['tar'] ?? 'ΟΧΙ';
                $glass = $record['glass'] ?? 'ΟΧΙ';
                $plastic = $record['plastic'] ?? 'ΟΧΙ';
                $caoutchouc = $record['caoutchouc'] ?? 'ΟΧΙ';
                $garbage = $record['garbage'] ?? 'ΟΧΙ';

                $yesCount = count(array_filter([$tar, $glass, $plastic, $caoutchouc, $garbage], function ($value) {
                    return trim(strtoupper($value)) === 'ΝΑΙ';
                }));

                if (!empty($selectedClean)) {
                    if ($selectedClean == "clean" && $yesCount == 0) {
                        $descriptions[] = $record['description'];
                    } elseif ($selectedClean == "semi-clean" && $yesCount > 0 && $yesCount < 3) {
                        $descriptions[] = $record['description'];
                    } elseif ($selectedClean == "not-clean" && $yesCount >= 3) {
                        $descriptions[] = $record['description'];
                    }
                } else {
                    // If no cleanliness filter is selected, return all descriptions
                    $descriptions[] = $record['description'];
                }

                // Ensure unique descriptions
                $descriptions = array_unique($descriptions);
            }
        }
    }

    if (!empty($descriptions)) {
        wp_send_json_success($descriptions);
    } else {
        wp_send_json_error(['message' => 'No descriptions found.']);
    }

    wp_die();
}



function WatersOfCrete_form($atts) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $orgid = $atts['orgid'];
    
    //$coasts = get_coasts($orgid); // Function to retrieve coasts data
    
    // Year and month mapping
    $year_mapping = array(
        '2016' => '94d41922-1e22-4dea-b120-da06b9b1e669',
        '2017' => '29530415-4d66-4e08-8452-e267d5eefd3b',
        '2018' => '388268b6-7f72-4623-a2cb-e90110004e28',
        '2019' => '1e1462c1-1f61-44f0-b128-d46ee57ffb4b',
        '2020' => '0c7984bc-5971-49ae-b7b0-0f9ea494e4c5',
        '2021' => 'b80d183b-2c1c-4d2f-806f-8b9ec7bd61c8',
        '2022' => '3634f175-00d6-4b85-904b-8f5cabdae5aa',
        '2023' => '09133157-1ce2-4996-9963-e6bb6e6db6b5'
    );

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // If reset button is clicked, reset the session
        if (isset($_POST['destroy_session'])) {
            session_unset();
            session_destroy(); 
            wp_redirect($_SERVER['REQUEST_URI']); // Reload the page
            exit();
        }

        // Process the form normally (for the search functionality)
        $_SESSION['stationcode'] = isset($_POST['stationcode']) ? sanitize_text_field($_POST['stationcode']) : '';
        $_SESSION['clean'] = isset($_POST['clean']) ? sanitize_text_field($_POST['clean']) : '';
        $_SESSION['municipal'] = isset($_POST['municipal']) ? sanitize_text_field($_POST['municipal']) : '';
        $_SESSION['wave'] = isset($_POST['wave']) ? sanitize_text_field($_POST['wave']) : '';
        //if (isset($_POST['selected_coast'])) {
        //$_SESSION['coast'] = sanitize_text_field($_POST['selected_coast']); 
        //}
        $_SESSION['coast'] = isset($_POST['coast']) ? sanitize_text_field($_POST['coast']) : '';
        $_SESSION['year'] = isset($_POST['year']) ? sanitize_text_field($_POST['year']) : '';
        $_SESSION['month'] = isset($_POST['month']) ? sanitize_text_field($_POST['month']) : '';

        $_SESSION['description'] = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';

    }
    
    // Get session values to populate the form
    $selectedYear = isset($_SESSION['year']) ? $_SESSION['year'] : '';
    //$selectedMonth = isset($_SESSION['month']) ? $_SESSION['month'] : ''; //might be wrong
    $availableMonths = $selectedYear ? get_month_resources($selectedYear) : [];
    $selectedDescription = isset($_SESSION['description']) ? $_SESSION['description'] : '';
    //$coastSelected = isset($_SESSION['coast']) ? explode(';', $_SESSION['coast']) : [];
    $stationcode = isset($_SESSION['stationcode']) ? $_SESSION['stationcode'] : '';
    $clean = isset($_SESSION['clean']) ? $_SESSION['clean'] : '';
    $municipal = isset($_SESSION['municipal']) ? $_SESSION['municipal'] : '';
    $wave = isset($_SESSION['wave']) ? $_SESSION['wave'] : '';
    $coast = isset($_SESSION['coast']) ? $_SESSION['coast'] : '';
    $description = isset($_SESSION['description']) ? $_SESSION['description'] : '';

    //$coasts = get_coasts($ID); // Function to retrieve coasts data

    ob_start(); 
    ?>

    <form id="waters-form" method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
        <div id="waters-fields">
            <div class="woc-field-group">
                <label for="stationcode">Stationcode:</label>
                <input type="text" id="stationcode" name="stationcode" value="<?php echo esc_attr($stationcode); ?>">
            </div>
            
            <!-- Year Dropdown -->
            <div class="woc-field-group">
                <label for="year">Έτος:</label>
                <select id="year" name="year">
                    <option value="">-- Επιλογή Έτους --</option>
                    <?php foreach ($year_mapping as $year => $id): ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($id, $selectedYear); ?>><?php echo esc_html($year); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Month Dropdown -->
            <div class="woc-field-group">
                <label for="month">Μήνας:</label>
                <select id="month" name="month">
                    <option value="">-- Επιλογή Μήνα --</option>
                    <?php
                    if (!empty($availableMonths)) {
                        foreach ($availableMonths as $month => $id) {
                            $selected = (!empty($_SESSION['month']) && $_SESSION['month'] == $id) ? 'selected' : '';
                            echo "<option value='" . esc_attr($id) . "' $selected>" . esc_html($month) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <!--Municipal -->
            <div class="woc-field-group">
            <label for="municipal">Δήμος:</label>
            <select name="municipal" id="municipal">
                <option value="">-- Επιλογή Δήμου --</option>
                <?php if (!empty($_SESSION['municipal'])): ?>
                    <option value="<?php echo esc_attr($_SESSION['municipal']); ?>" selected><?php echo esc_html($_SESSION['municipal']); ?></option>
                <?php endif; ?>
            </select>
            </div>
            
            <!--Clean -->
            <div class="woc-field-group">
            <label for="clean">Καθαριότητα:</label>
            <select name="clean" id="clean">
                <option value="">-- Επιλογή Καθαριότητας --</option>
                <option value="clean" <?php echo (isset($_SESSION['clean']) && $_SESSION['clean'] == 'clean') ? 'selected' : ''; ?>>Καθαρό</option>
                <option value="semi-clean" <?php echo (isset($_SESSION['clean']) && $_SESSION['clean'] == 'semi-clean') ? 'selected' : ''; ?>>Μερικώς Καθαρό</option>
                <option value="not-clean" <?php echo (isset($_SESSION['clean']) && $_SESSION['clean'] == 'not-clean') ? 'selected' : ''; ?>>Μη Καθαρό</option>
            </select>
            </div>
            
            <!--Wave -->
            <div class="woc-field-group">
            <label for="wave">Κύμα:</label>
            <select name="wave" id="wave">
                <option value="">-- Επιλογή Κύματος --</option>
                <option value="Ήρεμη" <?php echo (isset($_SESSION['wave']) && $_SESSION['wave'] == 'Ήρεμη') ? 'selected' : ''; ?>>Ήρεμη θάλασσα</option>
                <option value="Ελαφρά Κυματώδης" <?php echo (isset($_SESSION['wave']) && $_SESSION['wave'] == 'Ελαφρά Κυματώδης') ? 'selected' : ''; ?>>Ελαφρά Κυματώδης</option>
                <option value="Πολύ Κυματώδης" <?php echo (isset($_SESSION['wave']) && $_SESSION['wave'] == 'Πολύ Κυματώδης') ? 'selected' : ''; ?>>Πολύ Κυματώδης</option>
            </select>
            </div>
            
            <!--Description -->
            <div class="woc-field-group">
                <label for="description">Περιγραφή:</label>
                <select name="description" id="description">
                    <option value="">-- Επιλογή Περιγραφής --</option>
                    <?php if (!empty($_SESSION['description'])): ?>
                        <option value="<?php echo esc_attr($_SESSION['description']); ?>" selected>
                            <?php echo esc_html($_SESSION['description']); ?>
                        </option>
                    <?php endif; ?>
                </select>
            </div>
            
            <!--Coast -->
            <div class="woc-field-group">
            <label for="coast">Ακτή:</label>
            <select name="coast" id="coast">
                <option value="">-- Επιλογή Ακτής --</option>
                <?php if (!empty($_SESSION['coast'])): ?>
                    <option value="<?php echo esc_attr($_SESSION['coast']); ?>" selected><?php echo esc_html($_SESSION['coast']); ?></option>
                <?php endif; ?>
            </select>
            </div>                
        </div>
        
        <div class="woc-info-container">
            <button class="woc-info-button" disabled><i>i</i></button>
            <div class="woc-date_rules">
                <p> ·Εισάγετε πρώτα ένα Έτος και έναν Μήνα της επιλογής σας.<br>
                    ·Αν δεν οριστεί τιμή για Έτος και Μήνα, δεν μπορούν να εμφανιστούν Ακτές.<br>
                    ·Εφόσον έχει επιλεχθεί Έτος και Μήνας, ο χρήστης μπορεί να πατήσει Αναζήτηση <br> &nbsp; και να δει τα αποτελέσματα των Ακτών.<br>
                    ·Ο Χρήστης έχει την δυνατότητα να επιλέξει φίλτρα για να μειώσει τα πιθανά αποτελέσματα.<br>
                    ·Ο Χρήστης μπορεί επιπλέον να επιλέξει απευθείας μια παραλία της επιλογής του<br> &nbsp; και να δει τα αποτελέσματα στον πίνακα παρακάτω.<br>
                    ·Ο Χρήστης έχει την επιλογή να συμπληρώσει επίσης ένα StationCode μίας παραλίας (εφόσον το γνωρίζει)<br> &nbsp; και να εμφανιστεί η παραλία αυτή στην πιο πρόσφατη χρονολογία.<br>&nbsp; Σε αυτήν την περίπτωση
                        αγνοούνται όλα τα υπόλοιπα πεδία.<br>
                    ·Ο Χρήστης έχει την επιλογή να επιλέξει που επιθυμεί να βρίσκεται αυτή η παραλία. <br> &nbsp; πχ: 470 μ. από το δυτικό άκρο της ακτής

                </p>
            </div>
        </div>

        <div class="woc-button-container">
            <button type="submit" id="autoSubmitButton">Αναζήτηση</button>
            <button type="submit" name="destroy_session" style="background-color: gray; color: white;">Επαναφορά Φόρμας</button>
        </div>
    </form>

    <?php
    return ob_get_clean();
}

function GetYearId(){
    global $yearID;
    return $yearID;
}
