document.addEventListener('DOMContentLoaded', function() {
    const yearDropdown = document.getElementById('year');
    const monthDropdown = document.getElementById('month');
    const municipalDropdown = document.getElementById('municipal');
    const coastDropdown = document.getElementById('coast');
    const waveDropdown = document.getElementById('wave');
    const cleanDropdown = document.getElementById('clean');
    const IDField = document.getElementById('stationcode');
    const descriptionDropdown = document.getElementById('description');


    if (IDField) {
        IDField.addEventListener('input', function () {
            let IDValue = IDField.value.trim();

            if (IDValue !== '') {
                // Disable all other fields
                yearDropdown.disabled = true;
                monthDropdown.disabled = true;
                municipalDropdown.disabled = true;
                coastDropdown.disabled = true;
                waveDropdown.disabled = true;
                cleanDropdown.disabled = true;
                descriptionDropdown.disabled = true;

                // AJAX request to search for stationcode
                fetch(dm_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=search_by_stationcode&stationcode=${encodeURIComponent(IDValue)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        yearDropdown.value = data.year;
                        monthDropdown.innerHTML = `<option value="${data.month_id}" selected>${data.month_name}</option>`;
                        coastDropdown.innerHTML = `<option value="${data.coast}" selected>${data.coast}</option>`;
                    } else {
                        //alert("Stationcode not found.");
                        IDField.value = ''; // Reset ID field
                        enableAllFields();
                    }
                })
                .catch(error => {
                    console.error("Error fetching stationcode:", error);
                    enableAllFields();
                });
            } else {
                enableAllFields();
            }
        });
    }

    function enableAllFields() {
        yearDropdown.disabled = false;
        monthDropdown.disabled = false;
        municipalDropdown.disabled = false;
        coastDropdown.disabled = false;
        waveDropdown.disabled = false;
        cleanDropdown.disabled = false;
        descriptionDropdown.disabled = false;
    }

    if (yearDropdown) {
        yearDropdown.addEventListener('change', function() {
            const selectedYearId = yearDropdown.value;

            // Clear and reset the Month dropdown
            monthDropdown.innerHTML = '<option value="">Loading...</option>';

            if (selectedYearId) {
                // AJAX request to fetch months dynamically
                fetch(dm_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_months_by_year&year_id=${selectedYearId}`
                })
                .then(response => response.json())
                .then(data => {
                    monthDropdown.innerHTML = '<option value="">– Επιλογή Μήνα –</option>';
                    if (data.success) {
                        for (const [month, id] of Object.entries(data.data)) {
                            const option = document.createElement('option');
                            option.value = id;
                            option.textContent = month;
                            monthDropdown.appendChild(option);
                        }
                    } else {
                        monthDropdown.innerHTML = '<option value="">No months found</option>';
                    }
                })
                .catch(() => {
                    monthDropdown.innerHTML = '<option value="">Error fetching months</option>';
                });
            } else {
                monthDropdown.innerHTML = '<option value="">– Επιλογή Μήνα –</option>';
            }
        });
    }

    function updateMunicipalities() {
        const selectedYearId = yearDropdown.value;
        const selectedMonthId = monthDropdown.value;

        // Clear and reset the Municipal dropdown
        municipalDropdown.innerHTML = '<option value="">Loading...</option>';

        if (selectedYearId && selectedMonthId) {
            // AJAX request to fetch municipalities dynamically
            fetch(dm_ajax_obj.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_municipalities_by_filters&year=${selectedYearId}&month=${selectedMonthId}`
            })
            .then(response => response.json())
            .then(data => {
                municipalDropdown.innerHTML = '<option value="">– Επιλογή Δήμου –</option>';
                if (data.success) {
                    data.data.forEach(municipal => {
                        const option = document.createElement('option');
                        option.value = municipal;
                        option.textContent = municipal;
                        municipalDropdown.appendChild(option);
                    });
                } else {
                    municipalDropdown.innerHTML = '<option value="">No municipalities found</option>';
                }
            })
            .catch(() => {
                municipalDropdown.innerHTML = '<option value="">Error fetching municipalities</option>';
            });
        } else {
            municipalDropdown.innerHTML = '<option value="">– Επιλογή Δήμου –</option>';
        }
    }

    if (yearDropdown && monthDropdown) {
        yearDropdown.addEventListener('change', updateMunicipalities);
        monthDropdown.addEventListener('change', updateMunicipalities);
    }

    function updateDescriptions() {
        const selectedYear = yearDropdown.value;
        const selectedMonth = monthDropdown.value;
        const selectedMunicipality = municipalDropdown.value;
        const selectedWave = waveDropdown.value;
        const selectedClean = cleanDropdown.value;
        //const descriptionDropdown = document.getElementById("description");
    
        descriptionDropdown.innerHTML = '<option value="">Φόρτωση...</option>'; // Show loading text
    
        let requestBody = `action=get_descriptions_by_filters&year=${encodeURIComponent(selectedYear)}&month=${encodeURIComponent(selectedMonth)}`;
        
        if (selectedMunicipality) {
            requestBody += `&municipal=${encodeURIComponent(selectedMunicipality)}`;
        }
        if (selectedWave) {
            requestBody += `&wave=${encodeURIComponent(selectedWave)}`;
        }
        if (selectedClean) {
            requestBody += `&clean=${encodeURIComponent(selectedClean)}`;
        }
    
        fetch(dm_ajax_obj.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: requestBody
        })
        .then(response => response.json())
        .then(data => {
            descriptionDropdown.innerHTML = '<option value="">-- Επιλογή Περιγραφής --</option>';
            
            if (data.success) {
                data.data.forEach(description => {
                    const option = document.createElement('option');
                    option.value = description;
                    option.textContent = description;
                    descriptionDropdown.appendChild(option);
                });
            } else {
                descriptionDropdown.innerHTML = '<option value="">No descriptions found</option>';
            }
        })
        .catch(() => {
            descriptionDropdown.innerHTML = '<option value="">Error fetching descriptions</option>';
        });
    }
    

    function updateCoasts() {
        const selectedYear = yearDropdown.value;
        const selectedMonth = monthDropdown.value;
        const selectedMunicipality = municipalDropdown.value;
        const selectedWave = waveDropdown.value;
        const selectedClean = cleanDropdown.value;
        const selectedDescription = descriptionDropdown.value;

        // Clear and reset the Coast dropdown
        coastDropdown.innerHTML = '<option value="">Loading...</option>';

        // Prepare AJAX request body
        let requestBody = `action=get_coasts_by_filters&year=${encodeURIComponent(selectedYear)}&month=${encodeURIComponent(selectedMonth)}`;
        if (selectedMunicipality) {
            requestBody += `&municipal=${encodeURIComponent(selectedMunicipality)}`;
        }
        if (selectedWave) {
            requestBody += `&wave=${encodeURIComponent(selectedWave)}`;
        }
        if (selectedClean) {
            requestBody += `&clean=${encodeURIComponent(selectedClean)}`;
        }
        if(selectedDescription) {
            requestBody += `&description=${encodeURIComponent(selectedDescription)}`;
        }

        // Debugging: Log request
        console.log("Sending AJAX request:", requestBody);

        // AJAX request to fetch coasts dynamically
        fetch(dm_ajax_obj.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: requestBody
        })
        .then(response => response.json())
        .then(data => {
            console.log("Received response:", data); // Debugging

            coastDropdown.innerHTML = '<option value="">– Επιλογή Ακτής –</option>';
            if (data.success) {
                data.data.forEach(coast => {
                    const option = document.createElement('option');
                    option.value = coast;
                    option.textContent = coast;
                    coastDropdown.appendChild(option);
                });
            } else {
                console.error("Error fetching coasts:", data.message);
                coastDropdown.innerHTML = '<option value="">No coasts found</option>';
            }
        })
        .catch(error => {
            console.error("AJAX request failed:", error);
            coastDropdown.innerHTML = '<option value="">No coasts found</option>';
        });
    }

    // Update coasts when Year, Month, or Municipality changes
    if (yearDropdown) yearDropdown.addEventListener('change', updateCoasts);
    if (monthDropdown) monthDropdown.addEventListener('change', updateCoasts);
    if (municipalDropdown) municipalDropdown.addEventListener('change', updateCoasts);
    if (waveDropdown) waveDropdown.addEventListener('change', updateCoasts);
    if (cleanDropdown) cleanDropdown.addEventListener('change',updateCoasts);
    if (descriptionDropdown) descriptionDropdown.addEventListener('change', updateCoasts);

    if (yearDropdown) yearDropdown.addEventListener('change', updateDescriptions);
    if (monthDropdown) monthDropdown.addEventListener('change', updateDescriptions);
    if (municipalDropdown) municipalDropdown.addEventListener('change', updateDescriptions);
    if (waveDropdown) waveDropdown.addEventListener('change', updateDescriptions);
    if (cleanDropdown) cleanDropdown.addEventListener('change',updateDescriptions);

});




