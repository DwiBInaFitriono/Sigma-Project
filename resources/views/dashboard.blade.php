@extends('layouts.dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('dashboard-content')
        <div class="panel-page">
            <header class="content-header">
                <div class="content-header-flex">
                    <div>
                        <p class="section-kicker">SIGMA Monitoring</p>
                        <h1>Panel Utama</h1>
                        <p>Pantau getaran gempa, koordinat GPS, dan histori sensor secara realtime tanpa refresh.</p>
                    </div>

                    <div class="datetime-widget">
                        <div id="realtime-date" class="date-display">{{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d M Y') }}</div>
                        <div id="realtime-clock" class="time-display">{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                    </div>
                </div>
            </header>

            <div class="summary-grid dashboard-summary-grid">
                <div class="glow-card stat-card">
                    <div class="card-title">Status Sinkron</div>
                    <div class="card-value card-value-status">Live Update Aktif</div>
                    <div class="card-desc">Polling otomatis setiap 5 detik</div>
                </div>
                <div class="glow-card stat-card meter-card meter-card-magnitude">
                    <div class="card-title">Magnitudo Getaran</div>
                    <div id="currentMagnitude" class="card-value">{{ number_format($currentAccel['magnitude'], 2) }}</div>
                    <div class="card-desc">Nilai PGA terkini — realtime</div>
                </div>

                <div class="glow-card stat-card">
                    <div class="card-title">Update Terakhir</div>
                    <div id="lastUpdatedAt" class="card-value card-value-time">{{ $lastUpdatedAt ?? '--' }}</div>
                    <div class="card-desc">Data terbaru dari server</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <section class="glow-card panel-card map-card" id="sensor-gps-card">
                    <div class="section-header">
                        <div>
                            <h2 class="section-title">GPS NEO-6M</h2>
                            <p class="section-subtitle">Lokasi perangkat ditampilkan di peta OpenStreetMap.</p>
                        </div>
                        <span id="gps-map-status-pill" class="status-pill {{ $gps['is_connected'] ? 'online' : 'offline' }}" style="{{ !$gps['is_connected'] ? 'background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2);' : '' }}">
                            {{ $gps['is_connected'] ? 'Online' : 'Terputus' }}
                        </span>
                    </div>

                    <div id="gps-map" class="sensor-map"></div>

                    <div class="dashboard-info-grid">
                        <div class="dashboard-info-card">
                            <p>Latitude</p>
                            <strong id="gpsLatitude">{{ number_format($gps['latitude'], 7) }}</strong>
                        </div>
                        <div class="dashboard-info-card">
                            <p>Longitude</p>
                            <strong id="gpsLongitude">{{ number_format($gps['longitude'], 7) }}</strong>
                        </div>
                        <div class="dashboard-info-card">
                            <p>Altitude</p>
                            <strong id="gpsAltitude">{{ number_format($gps['altitude'], 2) }} m</strong>
                        </div>
                        <div class="dashboard-info-card">
                            <p>Satellites</p>
                            <strong id="gpsSatellites">{{ $gps['satellites'] }}</strong>
                        </div>
                        <div class="dashboard-info-card">
                            <p>Status</p>
                            <strong id="gpsStatus">{{ $gps['status'] }}</strong>
                        </div>
                        <div class="dashboard-info-card">
                            <p>Waktu GPS</p>
                            <strong id="gpsRecordedAt">{{ $gps['recorded_at'] }}</strong>
                        </div>
                    </div>
                </section>

                <section class="glow-card panel-card">
                    <div class="section-header">
                        <div>
                            <h2 class="section-title">Sensor ADXL345</h2>
                            <p class="section-subtitle">Grafik realtime + ringkasan nilai sensor ADXL345.</p>
                        </div>
                        <span class="status-pill realtime">Realtime</span>
                    </div>

                    <div class="dashboard-info-grid" style="margin: 1.5rem 0;">
                        <div class="dashboard-info-card">
                            <p>NILAI X / Y / Z</p>
                            <strong id="currentAxes">{{ number_format($currentAccel['x'], 2) }} / {{ number_format($currentAccel['y'], 2) }} / {{ number_format($currentAccel['z'], 2) }}</strong>
                        </div>
                        <div class="dashboard-info-card">
                            <p>WAKTU SERVER</p>
                            <strong id="currentAccelTime">{{ $currentAccel['time'] }}</strong>
                        </div>
                    </div>

                    <div class="dashboard-chart-card">
                        <div id="accelChart" class="sensor-chart"></div>
                    </div>

                    <div class="dashboard-score-grid">
                        <div class="dashboard-score-card">
                            <p class="dashboard-score-card-title">Magnitudo Maksimum</p>
                            <p id="magnitudeMaximum" class="dashboard-score-card-value">{{ number_format($summary['maximum'], 2) }}</p>
                        </div>
                        <div class="dashboard-score-card">
                            <p class="dashboard-score-card-title">Rata-rata getaran</p>
                            <p id="magnitudeAverage" class="dashboard-score-card-value">{{ number_format($summary['average'], 2) }}</p>
                        </div>
                        <div class="dashboard-score-card">
                            <p class="dashboard-score-card-title">Jumlah sampel</p>
                            <p id="sampleCount" class="dashboard-score-card-value">{{ $summary['count'] }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <section class="glow-card panel-card log-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Log Sensor Terakhir</h2>
                        <p class="section-subtitle">10 sampel terbaru beserta level MMI dan status getaran — realtime.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>X</th>
                                <th>Y</th>
                                <th>Z</th>
                                <th>Magnitudo</th>
                                <th>Level MMI</th>
                                <th class="text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody id="sample-log-body">
                            @forelse($accelLogSamples as $sample)
                                @php
                                    $mag = (float) $sample['magnitude'];
                                    if ($mag < 0.15)      { $mmiLevel = 'I';      $mmiStatus = 'Aman';    $mmiColor = '#22c55e'; }
                                    elseif ($mag < 0.30)  { $mmiLevel = 'II-III'; $mmiStatus = 'Lemah';   $mmiColor = '#86efac'; }
                                    elseif ($mag < 0.60)  { $mmiLevel = 'IV';     $mmiStatus = 'Waspada'; $mmiColor = '#f59e0b'; }
                                    elseif ($mag < 1.00)  { $mmiLevel = 'V';      $mmiStatus = 'Bahaya!'; $mmiColor = '#f97316'; }
                                    else                  { $mmiLevel = 'VI+';    $mmiStatus = 'AWAS!';   $mmiColor = '#ef4444'; }
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $sample['time'] }}</td>
                                    <td>{{ number_format($sample['x'], 2) }}</td>
                                    <td>{{ number_format($sample['y'], 2) }}</td>
                                    <td>{{ number_format($sample['z'], 2) }}</td>
                                    <td>{{ number_format($sample['magnitude'], 4) }}</td>
                                    <td><span style="font-weight: 800; color: {{ $mmiColor }};">{{ $mmiLevel }}</span></td>
                                    <td class="text-right"><span style="font-weight: 700; color: {{ $mmiColor }};">{{ $mmiStatus }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted" style="text-align: center; padding: 2rem;">Belum ada getaran terdeteksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Table Log GPS -->
            <section class="glow-card panel-card log-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Log Lokasi GPS Terakhir</h2>
                        <p class="section-subtitle">10 sampel lokasi terbaru dari NEO-6M — realtime.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Altitude</th>
                                <th>Satelit</th>
                                <th class="text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody id="gps-log-body">
                            @forelse($gpsLogSamples as $gps)
                                <tr>
                                    <td class="text-muted">{{ $gps['time'] }}</td>
                                    <td>{{ number_format($gps['latitude'], 7) }}</td>
                                    <td>{{ number_format($gps['longitude'], 7) }}</td>
                                    <td>{{ number_format($gps['altitude'], 2) }} m</td>
                                    <td>{{ $gps['satellites'] }}</td>
                                    <td class="text-right" style="color: {{ str_contains($gps['status'], 'FIX') ? 'var(--sigma-accent)' : 'var(--sigma-muted)' }}">{{ $gps['status'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted" style="text-align: center; padding: 2rem;">Belum ada data lokasi masuk.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const initialDashboardData = {
            gps: @json($gps),
            currentAccel: @json($currentAccel),
            accelSamples: @json($accelSamples),
            accelLogSamples: @json($accelLogSamples),
            summary: @json($summary),
            lastUpdatedAt: @json($lastUpdatedAt),
            seismicEvents: @json($seismicEvents),
        };
        const dashboardDataUrl = @json($dashboardDataUrl);

        // Realtime polling: every 5 seconds (optimized for mobile)
        const REFRESH_MS = 5000;

        let accelChart = null;
        const mapState = { map: null, marker: null, seismicLayers: [] };

        function updateClock() {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const timeStr = `${hh}:${mm}:${ss}`;

            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const dateStr = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()}`;

            setText('realtime-clock', timeStr);
            setText('realtime-date', dateStr);
        }

        function formatNumber(value, digits = 2) {
            const numericValue = Number(value);
            if (!Number.isFinite(numericValue)) { return (0).toFixed(digits); }
            return numericValue.toFixed(digits);
        }

        function setText(id, value) {
            const element = document.getElementById(id);
            if (element) { element.textContent = value; }
        }

        function buildChartOptions(samples) {
            const isMobile = window.innerWidth <= 768;
            const isDark = document.documentElement.classList.contains('dark-mode');
            const labelColor = isDark ? '#c4a98a' : '#6b5545';
            const gridColor  = isDark ? 'rgba(194, 116, 62, 0.15)' : 'rgba(107, 85, 69, 0.12)';

            return {
                chart: {
                    type: 'line',
                    height: 340,
                    fontFamily: 'Plus Jakarta Sans, system-ui, sans-serif',
                    toolbar: { show: false },
                    animations: {
                        enabled: false,
                    },
                    background: 'transparent',
                    dropShadow: {
                        enabled: false,
                    },
                },
                series: [
                    { name: 'Magnitudo', type: 'area', data: samples.map((s) => s.magnitude) },
                ],
                xaxis: {
                    categories: samples.map((s) => s.time || '--'),
                    labels: {
                        style: { colors: labelColor, fontWeight: 600, fontSize: '11px' },
                        hideOverlappingLabels: true,
                        rotate: 0,
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: labelColor, fontWeight: 600, fontSize: '11px' },
                        formatter: (val) => val != null ? val.toFixed(1) : '0',
                    },
                },
                stroke: {
                    curve: isMobile ? 'straight' : 'smooth',
                    width: [3],
                    lineCap: 'round',
                },
                colors: ['#e63946'],
                fill: {
                    type: ['solid'],
                    opacity: [0.15],
                },
                markers: {
                    size: [5],
                    strokeWidth: [2],
                    strokeColors: ['#fff'],
                    hover: { size: 8, sizeOffset: 3 },
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4,
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: true } },
                    padding: { left: 8, right: 8, top: 0, bottom: 0 },
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: isDark ? 'dark' : 'light',
                    y: {
                        formatter: (val) => val != null ? val.toFixed(4) : '0',
                    },
                    style: { fontSize: '12px' },
                },
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'center',
                    labels: { colors: labelColor },
                    fontWeight: 700,
                    fontSize: '13px',
                    markers: {
                        size: 6,
                        shape: 'circle',
                        strokeWidth: 0,
                    },
                    itemMargin: { horizontal: 12, vertical: 4 },
                },
                dataLabels: { enabled: false },
            };
        }

        function renderChart(samples) {
            if (typeof ApexCharts === 'undefined') return;
            const chartElement = document.getElementById('accelChart');
            if (!chartElement) return;

            if (accelChart) {
                accelChart.updateOptions(
                    { xaxis: { categories: samples.map((s) => s.time || '--') } },
                    false, false
                );
                accelChart.updateSeries([
                    { name: 'Magnitudo', type: 'area', data: samples.map((s) => s.magnitude) },
                ]);
                return;
            }

            accelChart = new ApexCharts(chartElement, buildChartOptions(samples));
            accelChart.render();
        }

        // ─── Leaflet Map ──────────────────────────────────────────────────────
        const tileLight = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
        const tileDark  = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
        const tileAttribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>';

        let sigmaIcon = null;
        function getSigmaIcon() {
            if (sigmaIcon) return sigmaIcon;
            if (typeof L === 'undefined') return null;
            sigmaIcon = L.divIcon({
                className: 'sigma-map-marker',
                html: `<div style="width:28px;height:28px;background:var(--sigma-accent,#C2743E);border:3px solid #fff;border-radius:50% 50% 50% 0;transform:rotate(-45deg);box-shadow:0 2px 8px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;"><div style="width:8px;height:8px;background:#fff;border-radius:50%;transform:rotate(45deg);"></div></div>`,
                iconSize: [28, 28], iconAnchor: [14, 28], popupAnchor: [0, -30],
            });
            return sigmaIcon;
        }
        function getActiveTileUrl() {
            return document.documentElement.classList.contains('dark-mode') ? tileDark : tileLight;
        }

        function renderMap(gps, seismicEvents = []) {
            if (typeof L === 'undefined') return;
            const lat = Number(gps.latitude);
            const lng = Number(gps.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng) || (lat === 0 && lng === 0)) return;

            const popupHtml = `
                <div style="font-family:'Plus Jakarta Sans',sans-serif;min-width:180px;line-height:1.6;color:#1e293b;">
                    <div style="font-weight:800;font-size:14px;margin-bottom:6px;color:#C2743E;">📍 GPS NEO-6M</div>
                    <div style="font-size:12px;">
                        <b>Lat:</b> ${formatNumber(lat, 7)}<br>
                        <b>Lng:</b> ${formatNumber(lng, 7)}<br>
                        <b>Alt:</b> ${formatNumber(gps.altitude, 2)} m<br>
                        <b>Sat:</b> ${gps.satellites ?? 0}<br>
                        <b>Status:</b> ${gps.status ?? 'NO FIX'}
                    </div>
                </div>`;

            const icon = getSigmaIcon();
            if (!mapState.map) {
                mapState.map = L.map('gps-map', { center: [lat, lng], zoom: 15, scrollWheelZoom: true, zoomControl: false });
                L.control.zoom({ position: 'topright' }).addTo(mapState.map);
                mapState.tileLayer = L.tileLayer(getActiveTileUrl(), { attribution: tileAttribution, maxZoom: 19 }).addTo(mapState.map);
                mapState.circle = L.circle([lat, lng], { radius: 50, color: '#C2743E', fillColor: '#C2743E', fillOpacity: 0.15, weight: 1 }).addTo(mapState.map);
                const markerOptions = icon ? { icon } : {};
                mapState.marker = L.marker([lat, lng], markerOptions).addTo(mapState.map);
                mapState.marker.bindPopup(popupHtml).openPopup();
            } else {
                if (mapState.marker) { mapState.marker.setLatLng([lat, lng]); mapState.marker.setPopupContent(popupHtml); }
                if (mapState.circle) { mapState.circle.setLatLng([lat, lng]); }
                if (window.innerWidth > 768) {
                    mapState.map.flyTo([lat, lng], mapState.map.getZoom(), { duration: 1.5 });
                } else {
                    mapState.map.panTo([lat, lng]);
                }
            }

            // Remove old seismic markers/circles
            if (mapState.seismicLayers) {
                mapState.seismicLayers.forEach(layer => layer.remove());
            }
            mapState.seismicLayers = [];

            // Add new seismic circles/dots
            if (seismicEvents && Array.isArray(seismicEvents)) {
                seismicEvents.forEach(event => {
                    const evLat = Number(event.latitude);
                    const evLng = Number(event.longitude);
                    if (!Number.isFinite(evLat) || !Number.isFinite(evLng)) return;

                    const radius = Math.max(20, Number(event.magnitude) * 80);

                    const popupContent = `
                        <div style="font-family:'Plus Jakarta Sans',sans-serif;min-width:180px;line-height:1.6;color:#1e293b;">
                            <div style="font-weight:800;font-size:14px;margin-bottom:6px;color:${event.mmi_color || '#ef4444'};">🚨 Deteksi Getaran</div>
                            <div style="font-size:12px;">
                                <b>Waktu:</b> ${event.recorded_at}<br>
                                <b>Magnitudo:</b> ${formatNumber(event.magnitude, 4)}<br>
                                <b>Level MMI:</b> <span style="font-weight:800;color:${event.mmi_color};">${event.mmi_level}</span><br>
                                <b>Status:</b> <span style="font-weight:700;color:${event.mmi_color};">${event.mmi_status}</span><br>
                                <b>Device:</b> ${event.device_id || 'Unknown'}<br>
                                <b>Koordinat:</b> ${formatNumber(evLat, 7)}, ${formatNumber(evLng, 7)}
                            </div>
                        </div>`;

                    const circle = L.circle([evLat, evLng], {
                        radius: radius,
                        color: event.mmi_color || '#ef4444',
                        fillColor: event.mmi_color || '#ef4444',
                        fillOpacity: 0.15,
                        weight: 1.5,
                        dashArray: '5, 5'
                    }).addTo(mapState.map);

                    const centerMarker = L.circleMarker([evLat, evLng], {
                        radius: 6,
                        color: '#ffffff',
                        fillColor: event.mmi_color || '#ef4444',
                        fillOpacity: 1,
                        weight: 2
                    }).addTo(mapState.map).bindPopup(popupContent);

                    mapState.seismicLayers.push(circle);
                    mapState.seismicLayers.push(centerMarker);
                });
            }
        }

        const mapThemeObserver = new MutationObserver(() => {
            if (mapState.map && mapState.tileLayer) { mapState.tileLayer.setUrl(getActiveTileUrl()); }
        });
        mapThemeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        // ─── MMI Helper (mirrors main.ino thresholds) ─────────────────────────
        function getMmiForMagnitude(magnitude) {
            const m = Number(magnitude);
            if (m < 0.15) return { level: 'I',      status: 'Aman',    color: '#22c55e' };
            if (m < 0.30) return { level: 'II-III',  status: 'Lemah',   color: '#86efac' };
            if (m < 0.60) return { level: 'IV',      status: 'Waspada', color: '#f59e0b' };
            if (m < 1.00) return { level: 'V',       status: 'Bahaya!', color: '#f97316' };
            return               { level: 'VI+',      status: 'AWAS!',   color: '#ef4444' };
        }

        function renderSampleTable(samples) {
            const tbody = document.getElementById('sample-log-body');
            if (!tbody) return;

            if (!samples || samples.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-muted" style="text-align:center;padding:2rem;">Belum ada getaran terdeteksi.</td></tr>`;
                return;
            }

            let html = '';
            // Newest first, use real server timestamp
            [...samples].reverse().forEach((sample) => {
                const mmi = getMmiForMagnitude(sample.magnitude);
                html += `<tr>
                    <td class="text-muted">${sample.time ?? '--'}</td>
                    <td>${formatNumber(sample.x, 2)}</td>
                    <td>${formatNumber(sample.y, 2)}</td>
                    <td>${formatNumber(sample.z, 2)}</td>
                    <td>${formatNumber(sample.magnitude, 4)}</td>
                    <td><span style="font-weight:800;color:${mmi.color};">${mmi.level}</span></td>
                    <td class="text-right"><span style="font-weight:700;color:${mmi.color};">${mmi.status}</span></td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        function renderGpsTable(samples) {
            const tbody = document.getElementById('gps-log-body');
            if (!tbody) return;

            if (!samples || samples.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-muted" style="text-align:center;padding:2rem;">Belum ada data lokasi masuk.</td></tr>`;
                return;
            }

            let html = '';
            // Newest first
            [...samples].reverse().forEach((sample) => {
                const statusColor = String(sample.status).includes('FIX') ? 'var(--sigma-accent)' : 'var(--sigma-muted)';
                html += `<tr>
                    <td class="text-muted">${sample.time ?? '--'}</td>
                    <td>${formatNumber(sample.latitude, 7)}</td>
                    <td>${formatNumber(sample.longitude, 7)}</td>
                    <td>${formatNumber(sample.altitude, 2)} m</td>
                    <td>${sample.satellites ?? 0}</td>
                    <td class="text-right"><span style="font-weight:700;color:${statusColor};">${sample.status ?? 'NO FIX'}</span></td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        function applyDashboardData(data) {
            if (!data) return;

            const accel      = data.currentAccel    || {};
            const gps        = data.gps             || {};
            const summary    = data.summary          || {};
            const samples    = data.accelSamples     || [];
            // Log uses filtered samples (only where magnitude >= 0.15)
            const logSamples = data.accelLogSamples  || [];
            const gpsLogSamples = data.gpsLogSamples || [];
            const seismicEvents = data.seismicEvents || [];

            setText('currentMagnitude', formatNumber(accel.magnitude));
            setText('currentAxes', `${formatNumber(accel.x)} / ${formatNumber(accel.y)} / ${formatNumber(accel.z)}`);
            setText('currentAccelTime', accel.time ?? '--');
            setText('lastUpdatedAt', data.lastUpdatedAt ?? accel.time ?? '--');

            setText('gpsLatitude',   formatNumber(gps.latitude, 7));
            setText('gpsLongitude',  formatNumber(gps.longitude, 7));
            setText('gpsAltitude',   `${formatNumber(gps.altitude, 2)} m`);
            setText('gpsSatellites', gps.satellites ?? 0);
            setText('gpsStatus',     gps.status ?? 'Menunggu');

            const mapPill = document.getElementById('gps-map-status-pill');
            if (mapPill) {
                if (gps.is_connected) {
                    mapPill.textContent = 'Online';
                    mapPill.className = 'status-pill online';
                    mapPill.style = '';
                } else {
                    mapPill.textContent = 'Terputus';
                    mapPill.className = 'status-pill offline';
                    mapPill.style = 'background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2);';
                }
            }

            setText('gpsRecordedAt', gps.recorded_at ?? '--');

            setText('magnitudeMaximum', formatNumber(summary.maximum));
            setText('magnitudeAverage', formatNumber(summary.average));
            setText('sampleCount',      String(summary.count ?? 0));

            try { renderChart(samples); }        catch (e) { console.warn('[SIGMA] Chart error:', e); }
            try { renderMap(gps, seismicEvents); } catch (e) { console.warn('[SIGMA] Map error:', e); }
            try { renderSampleTable(logSamples); } catch (e) { console.warn('[SIGMA] Table error:', e); }
            try { renderGpsTable(gpsLogSamples); } catch (e) { console.warn('[SIGMA] GPS Table error:', e); }
        }

        // ─── Polling (1 second) ───────────────────────────────────────────────
        let isRefreshing = false;

        async function refreshDashboardData() {
            if (isRefreshing) return;
            if (document.hidden) {
                setTimeout(refreshDashboardData, REFRESH_MS);
                return;
            }
            isRefreshing = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000);

            try {
                const response = await fetch(`${dashboardDataUrl}?t=${Date.now()}`, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                clearTimeout(timeoutId);
                if (!response.ok) return;
                const data = await response.json();
                applyDashboardData(data);
            } catch (error) {
                clearTimeout(timeoutId);
                if (error.name !== 'AbortError') { console.warn('[SIGMA] Refresh failed:', error.message); }
            } finally {
                isRefreshing = false;
                setTimeout(refreshDashboardData, REFRESH_MS);
            }
        }

        // ─── Boot ─────────────────────────────────────────────────────────────
        (function startClock() {
            try { updateClock(); } catch (e) {}
            setInterval(function () { try { updateClock(); } catch (e) {} }, 1000);
        })();

        try { applyDashboardData(initialDashboardData); } catch (e) {
            console.error('[SIGMA] Error applying initial data:', e);
        }

        refreshDashboardData();

        // Dark mode observer — rebuild chart colors on theme toggle
        let lastChartSamples = initialDashboardData.accelSamples || [];
        const observer = new MutationObserver(() => {
            if (accelChart) {
                try {
                    const isDark = document.documentElement.classList.contains('dark-mode');
                    const labelColor = isDark ? '#c4a98a' : '#6b5545';
                    const gridColor  = isDark ? 'rgba(194, 116, 62, 0.15)' : 'rgba(107, 85, 69, 0.12)';
                    accelChart.updateOptions({
                        tooltip: { theme: isDark ? 'dark' : 'light' },
                        xaxis: { labels: { style: { colors: labelColor } } },
                        yaxis: { labels: { style: { colors: labelColor } } },
                        grid: { borderColor: gridColor },
                        legend: { labels: { colors: labelColor } },
                    }, false, false);
                } catch (e) {}
            }
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    });
</script>
@endpush