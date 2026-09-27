/**
 * map.js — Mapa GIS Leaflet para o painel administrativo
 * Exibe sensores com marcadores coloridos por status de alerta
 */

const SatiMap = {
  map: null,
  markers: {},
  regionId: 0,

  init(containerId = 'sati-map', lat = -3.119, lng = -60.021, zoom = 12) {
    if (!document.getElementById(containerId)) return;

    this.map = L.map(containerId, {
      center:          [lat, lng],
      zoom:            zoom,
      zoomControl:     true,
      attributionControl: true,
    });

    // Tile layer OpenStreetMap — gratuito, sem API key
    // Aplica filtro CSS dark via classe no container
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> | SATI-Alerta',
      maxZoom: 19,
      subdomains: 'abc',
    }).addTo(this.map);

    // Aplica filtro CSS para tema escuro sem precisar de API
    const mapEl = document.getElementById(containerId);
    if (mapEl) {
      const tilePane = this.map.getPanes().tilePane;
      if (tilePane) {
        tilePane.style.filter = 'invert(1) hue-rotate(200deg) saturate(0.6) brightness(0.85)';
      }
    }

    this.loadSensors();
    this.loadAlertZones();

    // Atualiza a cada 60s
    setInterval(() => this.loadSensors(), 60000);
  },


  async loadSensors() {
    const url = `${SATI.BASE_URL}/api/v1/sensors${this.regionId ? '?region_id=' + this.regionId : ''}`;
    try {
      const res  = await fetch(url);
      const data = await res.json();
      this.renderSensors(data.sensors ?? []);
    } catch (e) {
      console.warn('[Map] Erro ao carregar sensores:', e.message);
    }
  },

  renderSensors(sensors) {
    // Remove marcadores antigos que não existem mais
    const newIds = sensors.map(s => s.id);
    Object.keys(this.markers).forEach(id => {
      if (!newIds.includes(parseInt(id))) {
        this.markers[id].remove();
        delete this.markers[id];
      }
    });

    sensors.forEach(sensor => {
      const alertLevel = this.getSensorAlertLevel(sensor);
      const icon = this.createSensorIcon(sensor, alertLevel);

      if (this.markers[sensor.id]) {
        this.markers[sensor.id].setLatLng([sensor.latitude, sensor.longitude]);
        this.markers[sensor.id].setIcon(icon);
      } else {
        const marker = L.marker([sensor.latitude, sensor.longitude], { icon })
          .bindPopup(this.buildPopup(sensor))
          .addTo(this.map);
        this.markers[sensor.id] = marker;
      }
    });
  },

  getSensorAlertLevel(sensor) {
    const value = parseFloat(sensor.last_value);
    if (isNaN(value)) return 'offline';
    // Heurística visual baseada no tipo
    if (sensor.type_code === 'WATER_LEVEL') {
      if (value >= 250) return 'emergency';
      if (value >= 200) return 'critical';
      if (value >= 150) return 'warning';
    }
    if (sensor.type_code === 'AIR_QUALITY') {
      if (value >= 150) return 'emergency';
      if (value >= 80)  return 'critical';
      if (value >= 50)  return 'warning';
    }
    if (sensor.type_code === 'SOIL_MOISTURE') {
      if (value <= 10) return 'critical';
      if (value <= 20) return 'warning';
    }
    return sensor.is_active ? 'safe' : 'offline';
  },

  createSensorIcon(sensor, level) {
    const colors = {
      safe:      '#06d6a0',
      warning:   '#ffd166',
      critical:  '#f77f00',
      emergency: '#ef233c',
      offline:   '#4a6080',
      info:      '#48cae4',
    };

    const typeIcons = {
      'WATER_LEVEL':   '💧',
      'AIR_QUALITY':   '💨',
      'SOIL_MOISTURE': '🌱',
      'AIR_HUMIDITY':  '🌤',
      'RADIATION':     '☀️',
    };

    const color   = colors[level] ?? colors.safe;
    const emoji   = typeIcons[sensor.type_code] ?? '📡';
    const pulseCls = ['warning','critical','emergency'].includes(level) ? 'sensor-pulse' : '';

    return L.divIcon({
      className: 'sati-sensor-icon',
      html: `
        <div class="${pulseCls}" style="
          position:relative;
          width:32px; height:32px;
          background:${color}22;
          border:2px solid ${color};
          border-radius:50%;
          display:flex; align-items:center; justify-content:center;
          font-size:13px;
          box-shadow: 0 0 ${level==='emergency'?'12px':'6px'} ${color}88;
          transition: all .3s;
        ">${emoji}</div>
      `,
      iconSize:   [32, 32],
      iconAnchor: [16, 16],
    });
  },

  buildPopup(sensor) {
    const lastValue = sensor.last_value != null
      ? `<strong>${parseFloat(sensor.last_value).toFixed(1)} ${sensor.unit ?? ''}</strong>`
      : '<span style="color:#7a90aa">Sem leitura</span>';

    const lastSeen = sensor.last_seen_at
      ? SATI.timeAgo(sensor.last_seen_at)
      : 'Nunca';

    const battery = sensor.battery_pct != null
      ? `<i class="bi bi-battery-half"></i> ${sensor.battery_pct}%`
      : '';

    return `
      <div style="min-width:180px">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:.4rem">${sensor.name ?? sensor.eui}</div>
        <div style="color:#7a90aa;font-size:.75rem;margin-bottom:.5rem">${sensor.type_name} &middot; ${sensor.region_name}</div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-size:.72rem;color:#7a90aa;text-transform:uppercase;letter-spacing:.06em">Última Leitura</div>
            <div style="font-size:1.1rem">${lastValue}</div>
          </div>
          <div style="text-align:right;font-size:.72rem;color:#7a90aa">
            <div>${lastSeen}</div>
            <div>${battery}</div>
          </div>
        </div>
        <div style="margin-top:.5rem;font-size:.7rem;color:#4a6080">EUI: ${sensor.eui}</div>
        <div style="font-size:.7rem;color:#4a6080">📍 ${sensor.location_desc ?? `${sensor.latitude}, ${sensor.longitude}`}</div>
      </div>
    `;
  },

  // Carrega polígonos de zonas de risco
  async loadAlertZones() {
    // Placeholder para zonas de risco em GeoJSON
    // Em produção, busca de /api/v1/risk-maps e renderiza polígonos coloridos
    const riskColors = {
      'flood':   '#0077b6',
      'drought': '#f77f00',
      'fire':    '#ef233c',
    };

    try {
      const res  = await fetch(`${SATI.BASE_URL}/api/v1/risk-maps`);
      const data = await res.json();
      (data.risk_maps ?? []).forEach(rm => {
        if (rm.geojson_data) {
          L.geoJSON(JSON.parse(rm.geojson_data), {
            style: {
              color:       riskColors[rm.risk_type] ?? '#fff',
              fillOpacity: 0.15,
              weight:      2,
              dashArray:   '5,5',
            },
          }).addTo(this.map);
        }
      });
    } catch (_) { /* sem zonas */ }
  },

  // Recentra o mapa em uma coordenada
  flyTo(lat, lng, zoom = 15) {
    this.map?.flyTo([lat, lng], zoom, { duration: 1.5 });
  },
};

document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('sati-map');
  if (el) {
    const lat = parseFloat(el.dataset.lat ?? -3.119);
    const lng = parseFloat(el.dataset.lng ?? -60.021);
    SatiMap.init('sati-map', lat, lng, 12);
  }
});
