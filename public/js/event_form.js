let map;
let marker;

function initMap() {
  console.log('Initialisation de la carte...');
  
  const mapElement = document.getElementById('map');
  if (!mapElement) {
    console.error('Élément #map introuvable !');
    return;
  }

  try {
   
    const latField = document.getElementById('event_location_latitude');
    const lngField = document.getElementById('event_location_longitude');
    
    const existingLat = latField?.value ? parseFloat(latField.value) : 48.5734;
    const existingLng = lngField?.value ? parseFloat(lngField.value) : 7.7521;
    
    console.log('Coordonnées initiales:', existingLat, existingLng);
    
  
    map = L.map('map').setView([existingLat, existingLng], 12);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    console.log('Carte initialisée avec succès');

    
    if (latField?.value && lngField?.value) {
      console.log('Mode édition détecté - placement du marqueur existant');
      setMarker(existingLat, existingLng);
      map.setView([existingLat, existingLng], 15);
    }

    map.on('click', (e) => {
      console.log('Clic sur la carte:', e.latlng);
      setMarker(e.latlng.lat, e.latlng.lng);
    });

    setTimeout(() => {
      map.invalidateSize();
    }, 200);

  } catch (error) {
    console.error('Erreur lors de l\'initialisation de la carte:', error);
  }
}

function setMarker(lat, lng) {
  console.log('Placement du marqueur:', lat, lng);
  
  if (marker) {
    map.removeLayer(marker);
  }

  marker = L.marker([lat, lng], { draggable: true }).addTo(map);
  map.setView([lat, lng], 15);

  updateCoordinates(lat, lng);

  marker.on('dragend', () => {
    const pos = marker.getLatLng();
    console.log('Marqueur déplacé:', pos);
    updateCoordinates(pos.lat, pos.lng);
  });
}

function updateCoordinates(lat, lng) {
  const latField = document.getElementById('event_location_latitude');
  const lngField = document.getElementById('event_location_longitude');
  
  console.log('Champs lat/lng trouvés:', !!latField, !!lngField);
  
  if (latField && lngField) {
    latField.value = Number(lat).toFixed(8);
    lngField.value = Number(lng).toFixed(8);
    
    console.log('Coordonnées mises à jour:', latField.value, lngField.value);
    
    updatePreviewCoords();
  } else {
    console.error('Champs latitude/longitude introuvables');
  }
}


async function geocodeAddress() {
  const addressField = document.getElementById('event_location_address');
  const address = addressField?.value.trim();

  if (!address) {
    alert('Veuillez saisir une adresse.');
    return;
  }

  console.log('Géocodage de:', address);

  const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=${encodeURIComponent(address)}`;

  try {
    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' }
    });
    const data = await response.json();

    console.log('Résultat géocodage:', data);

    if (data && data.length > 0) {
      const { lat, lon, address: details } = data[0];
      
      setMarker(parseFloat(lat), parseFloat(lon));

      fillAddressFields(details);
    } else {
      alert('Adresse introuvable. Veuillez vérifier votre saisie.');
    }
  } catch (error) {
    console.error('Erreur de géocodage:', error);
    alert('Erreur lors de la recherche de l\'adresse.');
  }
}

function fillAddressFields(details) {
  console.log('Détails de l\'adresse:', details);
  
  const cityField = document.getElementById('event_location_city');
  const postalCodeField = document.getElementById('event_location_postalCode');
  const countryField = document.getElementById('event_location_country');

  if (cityField && !cityField.value && details.city) {
    cityField.value = details.city || details.town || details.village || '';
    cityField.dispatchEvent(new Event('input'));
  }

  if (postalCodeField && !postalCodeField.value && details.postcode) {
    postalCodeField.value = details.postcode || '';
  }

  if (countryField && !countryField.value && details.country) {
    countryField.value = details.country || '';
  }
}


function setupPreview() {
  console.log('Configuration de la prévisualisation...');
  
  bindPreviewField('event_title', 'preview-title');
  
  bindPreviewField('event_eventDate', 'preview-date', formatDateTime);
  
  bindPreviewField('event_estimateDuration', 'preview-duration', (value) => {
    return value ? `${value} minutes` : '—';
  });
  
  bindPreviewField('event_distance', 'preview-distance', (value) => {
    return value ? `${value} km` : '—';
  });
  
  const levelField = document.getElementById('event_requiredLevel');
  if (levelField) {
    if (levelField.tagName === 'SELECT') {
      bindPreviewSelect('event_requiredLevel', 'preview-level');
    } else {
      bindPreviewField('event_requiredLevel', 'preview-level');
    }
  }
  
  bindPreviewField('event_location_locationName', 'preview-location');
  
  bindPreviewField('event_location_address', 'preview-address');
  
  updatePreviewCoords();
  
  console.log('Prévisualisation configurée');
}

function bindPreviewField(fieldId, previewId, formatter = null) {
  const field = document.getElementById(fieldId);
  const preview = document.getElementById(previewId);
  
  if (!field) {
    console.warn(`Champ ${fieldId} introuvable`);
    return;
  }
  
  if (!preview) {
    console.warn(`Aperçu ${previewId} introuvable`);
    return;
  }
  
  console.log(`Liaison ${fieldId} -> ${previewId}`);
  
  const update = () => {
    let value = field.value;
    if (formatter) {
      value = formatter(value);
    }
    preview.textContent = value || '—';
  };
  
  field.addEventListener('input', update);
  field.addEventListener('change', update);
  update();
}

function bindPreviewSelect(fieldId, previewId) {
  const field = document.getElementById(fieldId);
  const preview = document.getElementById(previewId);
  
  if (!field) {
    console.warn(`Select ${fieldId} introuvable`);
    return;
  }
  
  if (!preview) {
    console.warn(`Aperçu ${previewId} introuvable`);
    return;
  }
  
  console.log(`Liaison select ${fieldId} -> ${previewId}`);
  
  const update = () => {
    const selectedOption = field.options[field.selectedIndex];
    preview.textContent = selectedOption?.text || '—';
  };
  
  field.addEventListener('change', update);
  update();
}

function updatePreviewCoords() {
  const latField = document.getElementById('event_location_latitude');
  const lngField = document.getElementById('event_location_longitude');
  const preview = document.getElementById('preview-coords');
  
  if (latField && lngField && preview) {
    const lat = latField.value;
    const lng = lngField.value;
    
    if (lat && lng) {
      preview.textContent = `${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`;
    } else {
      preview.textContent = '—';
    }
  }
}

function formatDateTime(isoString) {
  if (!isoString) return '—';
  
  try {
    const date = new Date(isoString);
    if (isNaN(date.getTime())) return '—';
    
    const dateStr = date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
    const timeStr = date.toLocaleTimeString('fr-FR', {
      hour: '2-digit',
      minute: '2-digit'
    });
    
    return `${dateStr} à ${timeStr}`;
  } catch (e) {
    console.error('Erreur formatage date:', e);
    return isoString;
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {

  init();
}

function init() {
  console.log('=== Initialisation du formulaire d\'événement ===');
  
  if (typeof L === 'undefined') {
    console.error('Leaflet n\'est pas chargé !');
    return;
  }
  
  initMap();
  
  setupPreview();
  
  const geocodeBtn = document.getElementById('btn-geocode');
  if (geocodeBtn) {
    console.log('Bouton géocodage trouvé');
    geocodeBtn.addEventListener('click', geocodeAddress);
  } else {
    console.warn('Bouton géocodage introuvable');
  }
  
  const dateField = document.getElementById('event_eventDate');
  if (dateField && typeof flatpickr !== 'undefined') {
    console.log('Initialisation de Flatpickr');
    flatpickr(dateField, {
      locale: "fr",
      enableTime: true,
      dateFormat: "Y-m-d H:i",
      time_24hr: true,
      minuteIncrement: 15,
      minDate: "today",
      onChange: () => {
        const preview = document.getElementById('preview-date');
        if (preview) {
          preview.textContent = formatDateTime(dateField.value);
        }
      }
    });
  } else if (!dateField) {
    console.warn('Champ date introuvable');
  } else {
    console.warn('Flatpickr n\'est pas chargé');
  }
  
  console.log('=== Initialisation terminée ===');
}

window.setMarker = setMarker;
