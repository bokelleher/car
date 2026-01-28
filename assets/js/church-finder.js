// assets/js/church-finder.js

let map;
let markers = [];
let infoWindow;
let churchData = [];
let userLocation = null;

// Declare function and make it globally accessible for Google Maps callback
function initChurchMap() {
    const mapElement = document.getElementById('car-church-map');
    if (!mapElement) return;
    
    const centerLat = parseFloat(mapElement.dataset.centerLat);
    const centerLng = parseFloat(mapElement.dataset.centerLng);
    const zoom = parseInt(mapElement.dataset.zoom);
    
    map = new google.maps.Map(mapElement, {
        center: { lat: centerLat, lng: centerLng },
        zoom: zoom,
        styles: [
            {
                featureType: "poi",
                elementType: "labels",
                stylers: [{ visibility: "off" }]
            }
        ]
    });
    
    infoWindow = new google.maps.InfoWindow();
    
    // Load churches
    loadChurches();
    
    // Set up event listeners
    setupEventListeners();
}

// Expose initChurchMap globally for Google Maps callback. Some browsers (e.g. Safari/Brave) may not
// automatically add function declarations to the global window object when scripts are loaded
// asynchronously. Assigning it explicitly ensures the callback is available.
window.initChurchMap = initChurchMap;

function loadChurches() {
    jQuery.ajax({
        url: car_finder.ajax_url,
        type: 'POST',
        data: {
            action: 'car_get_churches_json',
            nonce: car_finder.nonce
        },
        success: function(response) {
            if (response.success) {
                churchData = response.data;
                displayChurches(churchData);
            }
        }
    });
}

function displayChurches(churches) {
    clearMarkers();
    
    const bounds = new google.maps.LatLngBounds();
    
    churches.forEach((church, index) => {
        const position = {
            lat: church.latitude,
            lng: church.longitude
        };
        
        const marker = new google.maps.Marker({
            position: position,
            map: map,
            title: church.name,
            animation: google.maps.Animation.DROP
        });
        
        marker.addListener('click', () => {
            showChurchInfo(church, marker);
        });
        
        markers.push(marker);
        bounds.extend(position);
    });
    
    if (churches.length > 0 && !userLocation) {
        map.fitBounds(bounds);
    }
    
    updateChurchList(churches);
}

function showChurchInfo(church, marker) {
    const content = `
        <div class="car-info-window">
            <h3>${church.name}</h3>
            <p class="pastor">Pastor: ${church.pastor_name || 'N/A'}</p>
            <p class="address">${church.address}</p>
            <p class="phone">${church.phone || 'N/A'}</p>
            ${church.website ? `<p class="website"><a href="${church.website}" target="_blank">Visit Website</a></p>` : ''}
            ${church.service_times ? `<p class="service-times">Services: ${church.service_times}</p>` : ''}
            <div class="actions">
                <a href="${church.url}" class="btn-view-details">View Details</a>
                <button onclick="getDirections(${church.latitude}, ${church.longitude})" class="btn-directions">Get Directions</button>
            </div>
        </div>
    `;
    
    infoWindow.setContent(content);
    infoWindow.open(map, marker);
}

function updateChurchList(churches) {
    const listElement = document.getElementById('car-results-list');
    if (!listElement) return;
    
    if (churches.length === 0) {
        listElement.innerHTML = '<p>No churches found in this area.</p>';
        return;
    }
    
    let html = '';
    churches.forEach((church, index) => {
        const distance = userLocation ? calculateDistance(
            userLocation.lat,
            userLocation.lng,
            church.latitude,
            church.longitude
        ) : null;
        
        html += `
            <div class="car-church-item" data-index="${index}">
                <h4>${church.name}</h4>
                <p class="address">${church.address}</p>
                ${distance ? `<p class="distance">${distance.toFixed(1)} miles</p>` : ''}
                <p class="pastor">Pastor: ${church.pastor_name || 'N/A'}</p>
                <button onclick="centerOnChurch(${index})" class="btn-show-on-map">Show on Map</button>
            </div>
        `;
    });
    
    listElement.innerHTML = html;
}

function centerOnChurch(index) {
    const church = churchData[index];
    const position = {
        lat: church.latitude,
        lng: church.longitude
    };
    
    map.setCenter(position);
    map.setZoom(15);
    
    // Open info window
    showChurchInfo(church, markers[index]);
}

function setupEventListeners() {
    // Find nearby button
    const findButton = document.getElementById('car-find-nearby');
    if (findButton) {
        findButton.addEventListener('click', findNearbyChurches);
    }
    
    // Address search on enter
    const addressInput = document.getElementById('car-address-search');
    if (addressInput) {
        addressInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                findNearbyChurches();
            }
        });
    }
    
    // District filter - REMOVED
    // const districtFilter = document.getElementById('car-district-filter');
    // if (districtFilter) {
    //     districtFilter.addEventListener('change', filterChurches);
    // }

    // Service time filter - REMOVED  
    // const serviceFilter = document.getElementById('car-service-filter');
    // if (serviceFilter) {
    //     serviceFilter.addEventListener('change', filterChurches);
    // }ilter.addEventListener('change', filterChurches);
    
}

function findNearbyChurches() {
    const addressInput = document.getElementById('car-address-search');
    const address = addressInput ? addressInput.value : '';
    
    if (address) {
        // Geocode the address
        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ address: address }, (results, status) => {
            if (status === 'OK') {
                userLocation = {
                    lat: results[0].geometry.location.lat(),
                    lng: results[0].geometry.location.lng()
                };
                
                map.setCenter(results[0].geometry.location);
                filterByDistance();
                
                // Add user marker
                new google.maps.Marker({
                    position: results[0].geometry.location,
                    map: map,
                    title: 'Your Location',
                    icon: {
                        url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                    }
                });
            } else {
                alert('Location not found. Please try a different address.');
            }
        });
    } else {
        // Use browser geolocation
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    map.setCenter(userLocation);
                    filterByDistance();
                    
                    // Add user marker
                    new google.maps.Marker({
                        position: userLocation,
                        map: map,
                        title: 'Your Location',
                        icon: {
                            url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                        }
                    });
                },
                () => {
                    alert('Unable to get your location. Please enter an address.');
                }
            );
        } else {
            alert('Geolocation is not supported by your browser.');
        }
    }
}

function filterByDistance() {
    if (!userLocation) return;
    
    const radiusInput = document.getElementById('car-radius-filter');
    const radius = radiusInput ? parseFloat(radiusInput.value) : 25;
    
    const filteredChurches = churchData.filter(church => {
        const distance = calculateDistance(
            userLocation.lat,
            userLocation.lng,
            church.latitude,
            church.longitude
        );
        return distance <= radius;
    });
    
    // Sort by distance
    filteredChurches.sort((a, b) => {
        const distA = calculateDistance(userLocation.lat, userLocation.lng, a.latitude, a.longitude);
        const distB = calculateDistance(userLocation.lat, userLocation.lng, b.latitude, b.longitude);
        return distA - distB;
    });
    
    displayChurches(filteredChurches);
}

function filterChurches() {
    let filtered = [...churchData];
    
    // District filter
    const districtFilter = document.getElementById('car-district-filter');
    if (districtFilter && districtFilter.value) {
        filtered = filtered.filter(church => church.district === districtFilter.value);
    }
    
    // Service time filter (simplified - you'd need to parse service_times)
    const serviceFilter = document.getElementById('car-service-filter');
    if (serviceFilter && serviceFilter.value) {
        // This would require parsing the service_times field
        // For now, just a placeholder
    }
    
    // Apply distance filter if user location is set
    if (userLocation) {
        filterByDistance();
    } else {
        displayChurches(filtered);
    }
}

function calculateDistance(lat1, lng1, lat2, lng2) {
    const R = 3959; // Radius of Earth in miles
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = 
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function toRad(deg) {
    return deg * (Math.PI / 180);
}

function clearMarkers() {
    markers.forEach(marker => marker.setMap(null));
    markers = [];
}

function getDirections(lat, lng) {
    if (userLocation) {
        window.open(`https://www.google.com/maps/dir/${userLocation.lat},${userLocation.lng}/${lat},${lng}`);
    } else {
        window.open(`https://www.google.com/maps/search/?api=1&query=${lat},${lng}`);
    }
}

// Initialize when DOM is ready
jQuery(document).ready(function($) {
    // Additional jQuery-based initialization if needed
});

// Ensure initChurchMap is available globally for Google Maps callback
window.initChurchMap = initChurchMap;