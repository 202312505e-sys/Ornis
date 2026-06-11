/* ================================================================
   ORNIS v4.0 — JavaScript Unificado
   ================================================================ */
'use strict';

/* ── Aplicar tema ANTES del render (evita flash) ────────────── */
(function(){
  const t = localStorage.getItem('ornis-theme') ||
    (window.matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
  document.documentElement.setAttribute('data-theme', t);
})();

/* ── Theme toggle ───────────────────────────────────────────── */
function initTheme() {
  document.querySelectorAll('.theme-toggle').forEach(btn => {
    const sync = () => {
      const dark = document.documentElement.getAttribute('data-theme') === 'dark';
      const ico = btn.querySelector('.ico'), lbl = btn.querySelector('.lbl');
      if(ico) ico.textContent = dark ? '☀️' : '🌙';
      if(lbl) lbl.textContent = dark ? 'Claro' : 'Oscuro';
    };
    sync();
    btn.addEventListener('click', () => {
      const cur  = document.documentElement.getAttribute('data-theme');
      const next = cur === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('ornis-theme', next);
      sync();
    });
  });
}

/* ── Navbar scroll ──────────────────────────────────────────── */
function initNavbar() {
  const nb = document.getElementById('navbar');
  if (!nb) return;
  const fn = () => nb.classList.toggle('scrolled', window.scrollY > 40);
  window.addEventListener('scroll', fn, { passive: true });
  fn();
}

/* ── Hero slideshow ─────────────────────────────────────────── */
function initHero() {
  const slides = document.querySelectorAll('.hero-slide');
  if (!slides.length) return;
  let idx = 0;
  setInterval(() => {
    slides[idx].classList.remove('active');
    idx = (idx + 1) % slides.length;
    slides[idx].classList.add('active');
  }, 5500);
}

/* ── Fade-in observer ───────────────────────────────────────── */
function initFade() {
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('visible'); io.unobserve(e.target); }});
  }, { threshold: 0.1 });
  document.querySelectorAll('.fade-in').forEach(el => io.observe(el));
}

/* ── Mapa inicio (Leaflet) + Top Lugares sidebar ────────────── */
function initMapaInicio() {
  if (!window.L || !document.getElementById('mapaCusco')) return;

  const map = L.map('mapaCusco', { zoomControl: true, scrollWheelZoom: false })
    .setView([-13.35, -72.10], 9);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>'
  }).addTo(map);

  const pinIcon = (n, color) => L.divIcon({
    html: `<div style="background:${color||'#1A8FAF'};color:#fff;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;box-shadow:0 3px 10px rgba(0,0,0,.45);border:2px solid #fff;">${n}</div>`,
    className: '', iconSize: [34,34], iconAnchor: [17,17]
  });

  // Static puntos fallback
  const staticPuntos = [
    { lat:-13.1633, lng:-72.5455, nombre:'Santuario Machu Picchu', total:400, zoom:13 },
    { lat:-13.5170, lng:-71.9784, nombre:'Cusco (ciudad)',          total:120, zoom:13 },
    { lat:-13.3167, lng:-72.1167, nombre:'Valle Sagrado',           total:280, zoom:12 },
    { lat:-13.5500, lng:-71.8667, nombre:'Huasao – Polylepis',      total:95,  zoom:12 },
    { lat:-13.0500, lng:-72.4000, nombre:'Aguas Calientes',         total:200, zoom:13 },
    { lat:-13.4100, lng:-72.2500, nombre:'Ollantaytambo',           total:150, zoom:12 },
    { lat:-13.1500, lng:-71.5900, nombre:'Pisac – Andenes',         total:110, zoom:12 },
    { lat:-13.6700, lng:-71.9900, nombre:'Pikillaqta',              total:75,  zoom:12 },
    { lat:-13.1760, lng:-72.0050, nombre:'Chinchero',               total:85,  zoom:12 },
    { lat:-14.0900, lng:-71.9300, nombre:'Paruro – Acomayo',        total:60,  zoom:11 },
  ];

  const rankColors = ['#d4a017','#a0a0a0','#cd7f32'];
  const allMarkers = {};

  function buildMapAndSidebar(puntos) {
    const maxTotal = puntos.length ? Math.max(...puntos.map(p => p.total)) : 1;
    const listEl = document.getElementById('topLugaresList');
    const puntosEl = document.getElementById('mapaPuntos');
    if (listEl) listEl.innerHTML = '';
    if (puntosEl) puntosEl.innerHTML = '';

    puntos.forEach((p, idx) => {
      if (!p.lat || !p.lng) return;
      // Map marker
      const color = idx < 3 ? rankColors[idx] : '#1A8FAF';
      const m = L.marker([+p.lat, +p.lng], { icon: pinIcon(idx+1, color) }).addTo(map);
      m.bindPopup(`<div style="min-width:150px;padding:4px 0;font-family:sans-serif">
        <strong style="color:#1A8FAF">#${idx+1} ${p.nombre}</strong>
        <br><small style="color:#888">${p.total} avistamientos</small>
      </div>`);
      allMarkers[p.nombre] = { m, lat:+p.lat, lng:+p.lng, zoom: p.zoom||13 };

      // Sidebar item
      if (listEl) {
        const item = document.createElement('div');
        item.className = 'tl-item';
        const pct = Math.round((p.total / maxTotal) * 100);
        const rankClass = idx === 0 ? 'gold' : idx === 1 ? 'silver' : idx === 2 ? 'bronze' : '';
        item.innerHTML = `
          <div class="tl-rank ${rankClass}">${idx+1}</div>
          <div class="tl-info">
            <div class="tl-nombre" title="${p.nombre}">${p.nombre}</div>
            <div class="tl-bar-wrap"><div class="tl-bar" style="width:${pct}%"></div></div>
          </div>
          <span class="tl-badge">${p.total}</span>`;
        item.addEventListener('click', () => flyTo(p.nombre));
        listEl.appendChild(item);
      }

      // Pills row
      if (puntosEl) {
        const pill = document.createElement('span');
        pill.className = 'mapa-punto' + (idx === 0 ? ' pin-activo' : '');
        pill.textContent = p.nombre;
        pill.addEventListener('click', () => {
          document.querySelectorAll('.mapa-punto').forEach(b => b.classList.remove('pin-activo'));
          pill.classList.add('pin-activo');
          flyTo(p.nombre);
        });
        puntosEl.appendChild(pill);
      }
    });

    // Open first popup
    const first = puntos[0];
    if (first && allMarkers[first.nombre]) allMarkers[first.nombre].m.openPopup();
  }

  function flyTo(nombre) {
    const entry = allMarkers[nombre];
    if (entry) {
      map.flyTo([entry.lat, entry.lng], entry.zoom, { duration: 1.2 });
      entry.m.openPopup();
    }
  }

  // Try loading from API first
  fetch('api_top_lugares.php')
    .then(r => r.json())
    .then(data => {
      if (data && data.length >= 3) {
        // Merge zoom from static data
        data.forEach(p => {
          const s = staticPuntos.find(sp => Math.abs(+p.lat - sp.lat) < 0.05 && Math.abs(+p.lng - sp.lng) < 0.05);
          p.zoom = s ? s.zoom : 13;
        });
        buildMapAndSidebar(data);
      } else {
        buildMapAndSidebar(staticPuntos);
      }
    })
    .catch(() => buildMapAndSidebar(staticPuntos));
}

/* ── Mapa registro (pin clickeable + GPS) ───────────────────── */
function initMapaRegistro() {
  if (!window.L || !document.getElementById('mapaRegistro')) return;

  const map = L.map('mapaRegistro', { scrollWheelZoom: false })
    .setView([-13.52, -71.98], 10);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '© OpenStreetMap'
  }).addTo(map);

  let pin = null;
  const coordsEl  = document.getElementById('coordsDisplay');
  const latInput  = document.getElementById('reg_lat');
  const lngInput  = document.getElementById('reg_lng');
  const ubInput   = document.getElementById('reg_ubicacion_texto');

  function setPin(lat, lng, label) {
    if (pin) map.removeLayer(pin);
    pin = L.marker([lat, lng]).addTo(map);
    if (coordsEl) coordsEl.textContent = `Lat: ${lat.toFixed(6)}   Lng: ${lng.toFixed(6)}`;
    if (latInput) latInput.value = lat.toFixed(6);
    if (lngInput) lngInput.value = lng.toFixed(6);
    if (ubInput && (!ubInput.value || ubInput.dataset.autoset === 'gps')) {
      ubInput.value = label || `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
      ubInput.dataset.autoset = 'gps';
    }
  }

  // Click en mapa
  map.on('click', e => {
    setPin(e.latlng.lat, e.latlng.lng, '');
    if (ubInput) ubInput.dataset.autoset = 'manual';
  });

  // GPS button
  const gpsBtn = document.getElementById('btnGPS');
  if (gpsBtn) {
    gpsBtn.addEventListener('click', () => {
      if (!navigator.geolocation) {
        alert('Tu navegador no soporta geolocalización.');
        return;
      }
      gpsBtn.classList.add('loading');
      gpsBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Obteniendo GPS…';

      navigator.geolocation.getCurrentPosition(
        async (pos) => {
          const lat = pos.coords.latitude;
          const lng = pos.coords.longitude;
          map.flyTo([lat, lng], 15, { duration: 1.4 });
          // Geocodificación inversa con Nominatim
          let label = '';
          try {
            const r = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=es`);
            const d = await r.json();
            label = d.display_name
              ? d.display_name.split(',').slice(0,3).join(', ')
              : '';
          } catch { label = ''; }
          setPin(lat, lng, label);
          if (ubInput && label) {
            ubInput.value = label;
            ubInput.dataset.autoset = 'gps';
          }
          gpsBtn.classList.remove('loading');
          gpsBtn.innerHTML = '<i class="fa-solid fa-location-dot"></i> Ubicación obtenida ✓';
          setTimeout(() => { gpsBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Usar mi ubicación GPS'; }, 3000);
        },
        (err) => {
          gpsBtn.classList.remove('loading');
          gpsBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Usar mi ubicación GPS';
          alert('No se pudo obtener la ubicación. Asegúrate de dar permiso.');
          console.error(err);
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });
  }
}

/* ── Autocomplete taxonomía ─────────────────────────────────── */
function initAutocomplete() {
  const input    = document.getElementById('nombre_ave_input');
  const list     = document.getElementById('autocomplete_list');
  const codeInp  = document.getElementById('species_code_input');
  const sciInp   = document.getElementById('sci_name_input');
  if (!input || !list) return;

  let timer = null, selIdx = -1;

  input.addEventListener('input', () => {
    clearTimeout(timer);
    selIdx = -1;
    const q = input.value.trim();
    if (q.length < 2) { list.innerHTML = ''; list.style.display = 'none'; return; }
    timer = setTimeout(async () => {
      try {
        const r = await fetch(`buscar_aves.php?q=${encodeURIComponent(q)}`);
        const data = await r.json();
        list.innerHTML = '';
        if (!data.length) { list.style.display = 'none'; return; }
        data.forEach((ave, i) => {
          const li = document.createElement('li');
          li.dataset.idx = i;
          const fam = ave.family ? `<span class="ac-fam">${ave.family.split(' ')[0]}</span>` : '';
          li.innerHTML = `${fam}<strong>${ave.primary_com_name}</strong><span class="ac-sci">${ave.sci_name}</span>`;
          li.addEventListener('mousedown', e => { e.preventDefault(); selectAve(ave); });
          list.appendChild(li);
        });
        list.style.display = 'block';
      } catch { list.style.display = 'none'; }
    }, 260);
  });

  function selectAve(ave) {
    input.value = ave.primary_com_name;
    if (codeInp) codeInp.value = ave.species_code;
    if (sciInp)  sciInp.value  = ave.sci_name;
    list.innerHTML = ''; list.style.display = 'none';
  }

  // Navegación teclado
  input.addEventListener('keydown', e => {
    const items = list.querySelectorAll('li');
    if (!items.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); selIdx = Math.min(selIdx+1, items.length-1); highlight(items); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); selIdx = Math.max(selIdx-1, 0); highlight(items); }
    else if (e.key === 'Enter' && selIdx >= 0) { e.preventDefault(); items[selIdx].dispatchEvent(new Event('mousedown')); }
    else if (e.key === 'Escape') { list.innerHTML = ''; list.style.display = 'none'; }
  });
  function highlight(items) {
    items.forEach((li,i) => li.classList.toggle('ac-active', i === selIdx));
    if (items[selIdx]) items[selIdx].scrollIntoView({ block: 'nearest' });
  }

  document.addEventListener('click', e => {
    if (!input.contains(e.target) && !list.contains(e.target)) {
      list.innerHTML = ''; list.style.display = 'none';
    }
  });
}

/* ── Upload preview ─────────────────────────────────────────── */
function initUpload() {
  const zone = document.getElementById('uploadZone');
  const inp  = document.getElementById('foto_input');
  const prev = document.getElementById('uploadPreview');
  const img  = document.getElementById('uploadPreviewImg');
  if (!zone || !inp) return;

  zone.addEventListener('click', () => inp.click());
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dz-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('dz-over'));
  zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('dz-over'); show(e.dataTransfer.files[0]); });
  inp.addEventListener('change', () => show(inp.files[0]));

  function show(file) {
    if (!file) return;
    const r = new FileReader();
    r.onload = e => { if(img) img.src = e.target.result; if(prev) prev.style.display = 'block'; };
    r.readAsDataURL(file);
  }
}

/* ── Accordion galería ──────────────────────────────────────── */
function initAccordion() {
  document.querySelectorAll('.acc-panel').forEach(panel => {
    panel.addEventListener('click', function() {
      this.closest('.accordion').querySelectorAll('.acc-panel').forEach(p => p.classList.remove('active'));
      this.classList.add('active');
    });
  });
}

/* ── Tabs ───────────────────────────────────────────────────── */
function initTabs() {
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.tab;
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(id)?.classList.add('active');
    });
  });
}

/* ── Filtros avanzados ──────────────────────────────────────── */
function initFiltros() {
  const toggle = document.getElementById('filtrosToggle');
  const panel  = document.getElementById('filtrosPanel');
  if (!toggle || !panel) return;
  toggle.addEventListener('click', () => {
    panel.classList.toggle('open');
    const ico = toggle.querySelector('.ico');
    if (ico) ico.textContent = panel.classList.contains('open') ? '▲' : '▼';
  });
  document.getElementById('btnLimpiarFiltros')?.addEventListener('click', () => {
    panel.querySelectorAll('select').forEach(s => s.value = '');
  });
}

/* ── Modal ave ──────────────────────────────────────────────── */
function initModal() {
  const overlay = document.getElementById('modalAve');
  if (!overlay) return;

  document.getElementById('modalClose')?.addEventListener('click', () => overlay.classList.remove('open'));
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });

  let imgs = [], idx = 0;

  const render = () => {
    const el = document.getElementById('modalImg');
    if (el) el.src = imgs[idx] || '';
    const ctr = document.getElementById('imgCounter');
    if (ctr) ctr.textContent = imgs.length > 1 ? `${idx+1}/${imgs.length}` : '';
  };

  document.getElementById('modalPrev')?.addEventListener('click', () => { idx = (idx-1+imgs.length)%imgs.length; render(); });
  document.getElementById('modalNext')?.addEventListener('click', () => { idx = (idx+1)%imgs.length; render(); });

  window.openModalAve = (ave) => {
    imgs = ave.fotos?.length ? ave.fotos : [ave.foto || ''];
    idx  = 0;
    render();
    const set = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val || '—'; };
    set('modalNombre', ave.nombre);
    set('modalSci',    ave.sci);
    set('modalFamilia', ave.familia);
    set('modalOrden',   ave.orden);
    set('modalAltitud', ave.altitud);
    set('modalUbicacion', ave.ubicacion);
    set('modalFecha',   ave.fecha);
    set('modalClima',   ave.clima);
    set('modalComport', ave.comportamiento);
    set('modalConteo',  ave.conteo);
    set('modalNotas',   ave.notas || ave.descripcion);
    // Badges
    const b = document.getElementById('modalBadges');
    if (b) b.innerHTML = `
      ${ave.tag    ? `<span class="badge badge-g">${ave.tag}</span>` : ''}
      ${ave.conservacion ? `<span class="badge badge-b">${ave.conservacion}</span>` : ''}
      ${ave.habitat ? `<span class="badge badge-c">${ave.habitat}</span>` : ''}
    `;
    overlay.classList.add('open');
  };
}

/* ── Auth switch login/registro ─────────────────────────────── */
function initAuth() {
  const lp = document.getElementById('login-pane');
  const rp = document.getElementById('register-pane');
  if (!lp || !rp) return;
  document.getElementById('to-register')?.addEventListener('click', e => { e.preventDefault(); lp.classList.add('hidden'); rp.classList.remove('hidden'); });
  document.getElementById('to-login')?.addEventListener('click',    e => { e.preventDefault(); rp.classList.add('hidden'); lp.classList.remove('hidden'); });
  if (window.location.hash === '#registro') { lp.classList.add('hidden'); rp.classList.remove('hidden'); }
}

/* ── Confirmación eliminar ──────────────────────────────────── */
function initEliminar() {
  document.querySelectorAll('.btn-del[href]').forEach(btn => {
    btn.addEventListener('click', e => { if (!confirm('¿Eliminar este avistamiento?')) e.preventDefault(); });
  });
}

/* ── INIT ───────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initNavbar();
  initHero();
  initFade();
  initMapaInicio();
  initMapaRegistro();
  initAutocomplete();
  initUpload();
  initAccordion();
  initTabs();
  initFiltros();
  initModal();
  initAuth();
  initEliminar();
});
