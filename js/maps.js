// Google Maps implementation for BorrowMyCharger

let markers = [];

let map, editMap, marker, editMarker;

function initMap() {
    const defaultLocation = { lat: 26.2285, lng: 50.5860 }; // Default to Manama, Bahrain coordinates
    const mapContainer = document.getElementById("map");

    if (mapContainer) {
        console.log("Initializing main map");

        // Create the map
        map = new google.maps.Map(mapContainer, {
            zoom: 13,
            center: defaultLocation,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true,
            styles: [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                }
            ]
        });

        // Add click listener to place a marker and update coordinates
        map.addListener("click", (event) => {
            placeMarker(event.latLng, map, false);
            updateCoordinateFields(event.latLng);
            geocodeLocation(event.latLng);
        });

        // Initialize Google Places Autocomplete for the search bar
        const searchInput = document.getElementById("searchLocation");
        if (searchInput) {
            const autocomplete = new google.maps.places.Autocomplete(searchInput);
            autocomplete.bindTo("bounds", map);

            // Listen for the place_changed event
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) {
                    console.error("No geometry found for the selected place.");
                    return;
                }

                // Center the map on the selected place
                map.setCenter(place.geometry.location);
                map.setZoom(14);

                console.log("Selected place:", place.name, place.geometry.location);
            });
        }

        // Try to get the user's current location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    console.log("User location found:", userLocation);

                    // Center the map on the user's location
                    map.setCenter(userLocation);

                    // Add a larger blue circle to represent the user's location
                    new google.maps.Circle({
                        strokeColor: "#007bff",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: "#007bff",
                        fillOpacity: 0.25,
                        map: map,
                        center: userLocation,
                        radius: 130 // Radius in meters
                    });
                },
                (error) => {
                    console.warn("Geolocation failed:", error.message);
                    console.log("Falling back to default location:", defaultLocation);
                    map.setCenter(defaultLocation);
                }
            );
        } else {
            console.warn("Geolocation is not supported by this browser.");
            map.setCenter(defaultLocation);
        }

        // Load charging points
        loadChargingPoints();
    }
}

// Initialize edit map if it exists
function initEditMap() {
    const editMapContainer = document.getElementById("edit_map");

    if (editMapContainer) {
        console.log("Initializing edit map");

        const defaultLocation = { lat: 26.2285, lng: 50.5860 };

        // Create the edit map
        editMap = new google.maps.Map(editMapContainer, {
            zoom: 13,
            center: defaultLocation,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true,
            styles: [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                }
            ]
        });

        // Center on the existing point if available
        if (document.getElementById("edit_latitude") && document.getElementById("edit_longitude")) {
            const lat = parseFloat(document.getElementById("edit_latitude").value);
            const lng = parseFloat(document.getElementById("edit_longitude").value);

            if (!isNaN(lat) && !isNaN(lng)) {
                const location = new google.maps.LatLng(lat, lng);
                editMap.setCenter(location);
                placeMarker(location, editMap, true);
            }
        }

        // Add click listener to place a marker and update coordinates
        editMap.addListener("click", (event) => {
            placeMarker(event.latLng, editMap, true);
            updateEditCoordinateFields(event.latLng);
            geocodeEditLocation(event.latLng);
        });
    }
}

// Place a single marker on the map (for selection)
function placeMarker(location, mapObj, isEdit = false) {
    console.log("Placing marker at", location.lat(), location.lng(), isEdit ? "on edit map" : "on main map");

    // Determine which marker to use
    let currentMarker = isEdit ? editMarker : marker;

    // Clear any existing marker
    if (currentMarker) {
        currentMarker.setMap(null);
    }

    // Create a new marker
    currentMarker = new google.maps.Marker({
        position: location,
        map: mapObj,
        draggable: true,
        animation: google.maps.Animation.DROP
    });

    // Add drag listener to update coordinates
    currentMarker.addListener("dragend", function () {
        if (isEdit) {
            updateEditCoordinateFields(currentMarker.getPosition());
            geocodeEditLocation(currentMarker.getPosition());
        } else {
            updateCoordinateFields(currentMarker.getPosition());
            geocodeLocation(currentMarker.getPosition());
        }
    });

    // Store the marker
    if (isEdit) {
        editMarker = currentMarker;
    } else {
        marker = currentMarker;
    }
}

// Update latitude and longitude fields for add form
function updateCoordinateFields(location) {
    if (document.getElementById("latitude") && document.getElementById("longitude")) {
        document.getElementById("latitude").value = location.lat();
        document.getElementById("longitude").value = location.lng();
    }
}

// Update latitude and longitude fields for edit form
function updateEditCoordinateFields(location) {
    if (document.getElementById("edit_latitude") && document.getElementById("edit_longitude")) {
        document.getElementById("edit_latitude").value = location.lat();
        document.getElementById("edit_longitude").value = location.lng();
    }
}

// Load charging points from the server via AJAX
function loadChargingPoints() {
    console.log("Loading charging points...");

    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/getChargingPoints.php', true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    console.log("Charging points data:", data);

                    // Clear existing markers
                    clearMarkers();

                    // Add markers for each charging point
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(point => {
                            addChargingPointMarker(point);
                        });
                    } else {
                        console.log("No charging points found");
                    }
                } catch (e) {
                    console.error('Error parsing charging points JSON:', e);
                }
            } else {
                console.error('Error fetching charging points:', xhr.status, xhr.statusText);
            }
        }
    };
    xhr.send();
}

// Add a marker for a charging point
function addChargingPointMarker(point) {
    console.log("Adding marker for:", point.name, "at", point.latitude, point.longitude);

    const lat = parseFloat(point.latitude);
    const lng = parseFloat(point.longitude);

    if (isNaN(lat) || isNaN(lng)) {
        console.error("Invalid coordinates for point:", point);
        return;
    }

    const marker = new google.maps.Marker({
        position: { lat: lat, lng: lng },
        map: map,
        title: point.name
    });

    // Debugging: Log marker creation
    console.log("Marker created for:", point.name);

    // Add click listener to open modal with charging point details
    marker.addListener("click", () => {
        console.log("Marker clicked for:", point.name); // Debugging: Log marker click
        showChargingPointDetails(point);
    });

    markers.push(marker);
}

// Clear all markers from the map
function clearMarkers() {
    markers.forEach(marker => {
        marker.setMap(null);
    });
    markers = [];
}

// Initialize edit map if it exists
function initEditMap() {
    const editMapContainer = document.getElementById("edit_map");
    
    if (editMapContainer) {
        console.log("Initializing edit map");
        // Default to Manama, Bahrain coordinates
        const defaultLocation = { lat: 26.2285, lng: 50.5860 };
        
        // Create a separate map for the edit form
        editMap = new google.maps.Map(editMapContainer, {
            zoom: 13,
            center: defaultLocation,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true,
            styles: [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                }
            ]
        });
        
        // Center on the existing point if available
        if (document.getElementById('edit_latitude') && document.getElementById('edit_longitude')) {
            const lat = parseFloat(document.getElementById('edit_latitude').value);
            const lng = parseFloat(document.getElementById('edit_longitude').value);
            
            if (!isNaN(lat) && !isNaN(lng)) {
                const location = new google.maps.LatLng(lat, lng);
                editMap.setCenter(location);
                placeMarker(location, editMap, true);
            }
            
            // Add click listener for map
            editMap.addListener("click", (event) => {
                placeMarker(event.latLng, editMap, true);
                updateEditCoordinateFields(event.latLng);
                geocodeEditLocation(event.latLng);
            });
        }
        
        // Initialize search autocomplete for edit form
        const editInput = document.getElementById("edit_address");
        if (editInput) {
            console.log("Setting up autocomplete for edit form");
            const editSearchBox = new google.maps.places.SearchBox(editInput);
            
            // Listen for the event when user selects a prediction
            editSearchBox.addListener("places_changed", () => {
                const places = editSearchBox.getPlaces();
                
                if (places.length === 0) {
                    return;
                }
                
                const place = places[0];
                
                if (!place.geometry || !place.geometry.location) {
                    console.log("Returned place contains no geometry");
                    return;
                }
                
                // Center on the selected place
                editMap.setCenter(place.geometry.location);
                editMap.setZoom(16);
                
                // Place marker
                placeMarker(place.geometry.location, editMap, true);
                
                // Update coordinate fields
                document.getElementById('edit_latitude').value = place.geometry.location.lat();
                document.getElementById('edit_longitude').value = place.geometry.location.lng();
                
                // Set address
                document.getElementById('edit_address').value = place.formatted_address || place.name;
                
                // Try to extract postcode
                const postalCodeComponent = place.address_components ? 
                    place.address_components.find(component => component.types.includes('postal_code')) : null;
                
                if (postalCodeComponent && document.getElementById('edit_postcode')) {
                    document.getElementById('edit_postcode').value = postalCodeComponent.long_name;
                } else if (document.getElementById('edit_postcode') && document.getElementById('edit_postcode').value === '') {
                    // If no postcode found, set a default one for Bahrain
                    document.getElementById('edit_postcode').value = "Manama";
                }
                
                console.log("Place selected for edit:", place.name);
                console.log("Location:", place.geometry.location.lat(), place.geometry.location.lng());
            });
            
            // Prevent form submission on enter
            editInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    return false;
                }
            });
        }
    }
}

// Place a single marker on the map (for selection)
function placeMarker(location, mapObj, isEdit = false) {
    console.log("Placing marker at", location.lat(), location.lng(), isEdit ? "on edit map" : "on main map");
    
    // Determine which marker to use
    let currentMarker = isEdit ? editMarker : marker;
    
    // Clear any existing marker
    if (currentMarker) {
        currentMarker.setMap(null);
    }
    
    // Create a new marker
    currentMarker = new google.maps.Marker({
        position: location,
        map: mapObj,
        draggable: true,
        animation: google.maps.Animation.DROP
    });
    
    // Add drag listener to update coordinates
    currentMarker.addListener('dragend', function() {
        if (isEdit) {
            updateEditCoordinateFields(currentMarker.getPosition());
            geocodeEditLocation(currentMarker.getPosition());
        } else {
            updateCoordinateFields(currentMarker.getPosition());
            geocodeLocation(currentMarker.getPosition());
        }
    });
    
    // Store the marker
    if (isEdit) {
        editMarker = currentMarker;
    } else {
        marker = currentMarker;
    }
}

// Update latitude and longitude fields for add form
function updateCoordinateFields(location) {
    if (document.getElementById('latitude') && document.getElementById('longitude')) {
        document.getElementById('latitude').value = location.lat();
        document.getElementById('longitude').value = location.lng();
    }
}

// Update latitude and longitude fields for edit form
function updateEditCoordinateFields(location) {
    if (document.getElementById('edit_latitude') && document.getElementById('edit_longitude')) {
        document.getElementById('edit_latitude').value = location.lat();
        document.getElementById('edit_longitude').value = location.lng();
    }
}

// Reverse geocode a location to get address for add form
function geocodeLocation(location) {
    const geocoder = new google.maps.Geocoder();
    
    geocoder.geocode({ location: location }, (results, status) => {
        if (status === "OK" && results[0]) {
            if (document.getElementById('address')) {
                document.getElementById('address').value = results[0].formatted_address;
            }
            
            // Try to extract postcode
            const postalCodeComponent = results[0].address_components.find(component => 
                component.types.includes('postal_code'));
            if (postalCodeComponent && document.getElementById('postcode')) {
                document.getElementById('postcode').value = postalCodeComponent.long_name;
            } else if (document.getElementById('postcode') && document.getElementById('postcode').value === '') {
                // If no postcode found, set a default for Bahrain
                document.getElementById('postcode').value = "Manama";
            }
        }
    });
}

// Reverse geocode a location to get address for edit form
function geocodeEditLocation(location) {
    const geocoder = new google.maps.Geocoder();
    
    geocoder.geocode({ location: location }, (results, status) => {
        if (status === "OK" && results[0]) {
            if (document.getElementById('edit_address')) {
                document.getElementById('edit_address').value = results[0].formatted_address;
            }
            
            // Try to extract postcode
            const postalCodeComponent = results[0].address_components.find(component => 
                component.types.includes('postal_code'));
            if (postalCodeComponent && document.getElementById('edit_postcode')) {
                document.getElementById('edit_postcode').value = postalCodeComponent.long_name;
            } else if (document.getElementById('edit_postcode') && document.getElementById('edit_postcode').value === '') {
                // If no postcode found, set a default for Bahrain
                document.getElementById('edit_postcode').value = "Manama";
            }
        }
    });
}

// Filter markers by type
function filterMarkersByType(type) {
    console.log("Filtering markers by type:", type);

    // If all types selected, just reload all points
    if (type === 'all') {
        loadChargingPoints();
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/getChargingPoints.php?type=' + encodeURIComponent(type), true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    console.log("Filtered data:", data);

                    // Clear existing markers
                    clearMarkers();

                    // Add markers for filtered points
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(point => {
                            addChargingPointMarker(point);
                        });
                    } else {
                        console.log("No charging points found for type:", type);
                    }
                } catch (e) {
                    console.error('Error parsing filtered charging points JSON:', e);
                }
            } else {
                console.error('Error fetching filtered charging points:', xhr.status, xhr.statusText);
            }
        }
    };
    xhr.send();
}

// Initialize event listeners for modals
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for edit modal
    const editModal = document.getElementById('editChargingPointModal');
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', function () {
            console.log("Edit modal shown, initializing map");
            // Give the modal time to fully render
            setTimeout(() => {
                if (typeof editMap === 'undefined' || editMap === null) {
                    initEditMap();
                } else {
                    // Trigger a resize event to make sure the map displays correctly
                    google.maps.event.trigger(editMap, 'resize');
                    
                    // Center on the existing point if available
                    if (document.getElementById('edit_latitude') && document.getElementById('edit_longitude')) {
                        const lat = parseFloat(document.getElementById('edit_latitude').value);
                        const lng = parseFloat(document.getElementById('edit_longitude').value);
                        if (!isNaN(lat) && !isNaN(lng)) {
                            const location = new google.maps.LatLng(lat, lng);
                            editMap.setCenter(location);
                            placeMarker(location, editMap, true);
                        }
                    }
                }
            }, 500);
        });
    }
});

function showChargingPointDetails(point) {
    console.log("Showing details for:", point);

    // Populate the modal with charging point details
    const modalTitle = document.getElementById("chargingPointModalLabel");
    const modalBody = document.getElementById("chargingPointModalBody");
    const modalFooter = document.getElementById("chargingPointModalFooter");

    if (!modalTitle || !modalBody || !modalFooter) {
        console.error("Modal elements not found!");
        return;
    }

    modalTitle.textContent = point.name;

    modalBody.innerHTML = `
        <p><strong>Location:</strong> ${point.address}</p>
        <p><strong>Postcode:</strong> ${point.postcode}</p>
        <p><strong>Price:</strong> ${point.price_per_kwh} BD/kWh</p>
        <p><strong>Availability:</strong> ${point.isAvailable ? "Available" : "Not Available"}</p>
        <p><strong>Type:</strong> ${point.charger_type || "Not specified"}</p>
        <p><strong>Coordinates:</strong> ${point.latitude}, ${point.longitude}</p>
    `;

    // Add "Book Now" button for users
    modalFooter.innerHTML = "";
    if (point.userRole === "user") {
        modalFooter.innerHTML = `
            <button class="btn btn-primary" onclick="bookChargingPoint(${point.charge_point_id})">Book Now</button>
        `;
    }

    // Debugging: Log modal content
    console.log("Modal content populated for:", point.name);

    // Show the modal
    const chargingPointModal = new bootstrap.Modal(document.getElementById("chargingPointModal"));
    chargingPointModal.show();
}

function bookChargingPoint(chargePointId) {
    console.log("Booking charging point:", chargePointId);

    // Redirect to booking page or send an AJAX request to book the charging point
    window.location.href = `booking.php?charge_point_id=${chargePointId}`;
}