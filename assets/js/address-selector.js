/**
 * Philippine Address Selector
 * Integrates with PSGC Cloud API for cascading dropdowns
 * Project: Client Aid Management System (CAMS)
 */

document.addEventListener('DOMContentLoaded', () => {
    const apiBase = 'https://psgc.cloud/api';

    // Address Selectors
    const addrRegion = document.getElementById('addr_region');
    const addrProvince = document.getElementById('addr_province');
    const addrMunicipality = document.getElementById('addr_municipality');
    const addrBarangay = document.getElementById('addr_barangay');

    // Birthplace Selectors
    const bpProvince = document.getElementById('bp_province');
    const bpMunicipality = document.getElementById('bp_municipality');

    // Helper to clear and add placeholder
    const resetSelect = (select, text) => {
        select.innerHTML = `<option value="">-- Select ${text} --</option>`;
        select.disabled = true;
    };

    const cleanLocationName = (str) => {
        if (!str) return '';
        return str.toString().trim()
            .toLowerCase()
            .replace(/\b(city of|city|municipality of|municipality|bgy|barangay|brgy)\b/g, '')
            .replace(/[^a-z0-9]/g, '')
            .trim();
    };

    const isLocationMatch = (name1, name2) => {
        if (!name1 || !name2) return false;
        const n1 = name1.toString().trim().toLowerCase();
        const n2 = name2.toString().trim().toLowerCase();
        
        // 1. Exact match
        if (n1 === n2) return true;
        
        // 2. Substring match
        if (n1.includes(n2) || n2.includes(n1)) return true;
        
        // 3. Cleaned normalized match
        const cn1 = cleanLocationName(n1);
        const cn2 = cleanLocationName(n2);
        if (cn1 && cn2 && cn1 === cn2) return true;
        
        return false;
    };

    // Helper to fetch and populate
    const populateSelect = async (select, endpoint, text, nameKey = 'name') => {
        if (!select) return;
        select.innerHTML = `<option value="">Loading ${text}...</option>`;
        select.disabled = true;

        try {
            const response = await fetch(`${apiBase}/${endpoint}`);
            const data = await response.json();
            
            select.innerHTML = `<option value="">-- Select ${text} --</option>`;
            let foundSaved = false;
            
            data.sort((a, b) => a[nameKey].localeCompare(b[nameKey])).forEach(item => {
                const option = document.createElement('option');
                option.value = item[nameKey];
                option.dataset.code = item.code;
                option.textContent = item[nameKey];
                
                if (select.dataset.savedValue && isLocationMatch(item[nameKey], select.dataset.savedValue)) {
                    option.selected = true;
                    foundSaved = true;
                }
                
                select.appendChild(option);
            });
            select.disabled = false;

            // If we found and selected a saved value, trigger the change event to load next level
            if (foundSaved) {
                select.dispatchEvent(new Event('change'));
            }
        } catch (error) {
            console.error(`Error fetching ${text}:`, error);
            select.innerHTML = `<option value="">Error loading ${text}</option>`;
        }
    };

    // --- Address Logic ---

    // Function to load regions if not already loaded
    const loadRegions = () => {
        if (addrRegion && addrRegion.dataset.preset === "true") {
            addrRegion.dataset.preset = "false";
            populateSelect(addrRegion, 'regions', 'Region');
        }
    };

    // Initial load: Regions
    if (addrRegion && !addrRegion.dataset.preset) {
        populateSelect(addrRegion, 'regions', 'Region');
    }

    if (addrRegion) {
        addrRegion.addEventListener('focus', loadRegions);
        addrRegion.addEventListener('mousedown', loadRegions);
    }

    addrRegion?.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const code = selectedOption ? selectedOption.dataset.code : null;
        resetSelect(addrProvince, 'Province');
        resetSelect(addrMunicipality, 'Municipality');
        resetSelect(addrBarangay, 'Barangay');
        
        if (code) {
            addrProvince.dataset.preset = "false";
            populateSelect(addrProvince, `regions/${code}/provinces`, 'Province');
        }
    });

    addrProvince?.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const code = selectedOption ? selectedOption.dataset.code : null;
        resetSelect(addrMunicipality, 'Municipality');
        resetSelect(addrBarangay, 'Barangay');
        
        if (code) {
            addrMunicipality.dataset.preset = "false";
            populateSelect(addrMunicipality, `provinces/${code}/cities-municipalities`, 'Municipality');
        }
    });

    addrMunicipality?.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const code = selectedOption ? selectedOption.dataset.code : null;
        resetSelect(addrBarangay, 'Barangay');
        
        if (code) {
            addrBarangay.dataset.preset = "false";
            populateSelect(addrBarangay, `cities-municipalities/${code}/barangays`, 'Barangay');
        }
    });

    // --- Birthplace Logic ---
    
    // Function to load provinces if not already loaded
    const loadBpProvinces = () => {
        if (bpProvince && bpProvince.dataset.preset === "true") {
            bpProvince.dataset.preset = "false";
            populateSelect(bpProvince, 'provinces', 'Province');
        }
    };

    // Initial load for Birthplace Provinces
    if (bpProvince && !bpProvince.dataset.preset) {
        populateSelect(bpProvince, 'provinces', 'Province');
    }
    
    if (bpProvince) {
        bpProvince.addEventListener('focus', loadBpProvinces);
        bpProvince.addEventListener('mousedown', loadBpProvinces);
    }

    bpProvince?.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const code = selectedOption ? selectedOption.dataset.code : null;
        resetSelect(bpMunicipality, 'Municipality/City');
        
        if (code) {
            bpMunicipality.dataset.preset = "false";
            populateSelect(bpMunicipality, `provinces/${code}/cities-municipalities`, 'Municipality/City');
        }
    });
});
