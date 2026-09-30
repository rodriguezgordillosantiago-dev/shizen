/* ── Shizen — shared data layer ─────────────────── */

const _DEFAULTS = {
  activos: [],
  disponibles: [],
  historial: [],
  perfil: {
    nombre: "",
    apellido: "",
    telefono: "",
    localidad: "",
    vehiculo: "",
    calificacion: 0,
    entregasHoy: 0,
    gananciasHoy: 0,
  },
  mensajes: [],
};

function _resolveUrl(path) {
  if (path === 'php/entregas.php' && window.repartidorApiUrl) {
    return window.repartidorApiUrl;
  }
  if (window.location.pathname.includes('/pages/')) {
    return '../' + path;
  }
  return '/' + path;
}

function _get(key) {
  try {
    const s = localStorage.getItem("shizen_" + key);
    return s
      ? JSON.parse(s)
      : JSON.parse(JSON.stringify(_DEFAULTS[key]));
  } catch {
    return JSON.parse(JSON.stringify(_DEFAULTS[key]));
  }
}
function _set(key, val) {
  localStorage.setItem(
    "shizen_" + key,
    JSON.stringify(val),
  );
}

// Accessors
function getActivos() {
  return _get("activos");
}
function setActivos(v) {
  _set("activos", v);
}
function getDisponibles() {
  return _get("disponibles");
}
function setDisponibles(v) {
  _set("disponibles", v);
}
function getHistorial() {
  return _get("historial");
}
function setHistorial(v) {
  _set("historial", v);
}
function getPerfil() {
  return _get("perfil");
}
function setPerfil(v) {
  _set("perfil", v);
}
function getMensajes() {
  return _get("mensajes");
}
function setMensajes(v) {
  _set("mensajes", v);
}

// Estado de disponibilidad del repartidor (persistente entre pantallas).
function repartidorEstaActivo() {
  return (
    localStorage.getItem("shizen_repartidor_activo") !==
    "false"
  );
}

async function cambiarEstadoRepartidor() {
  try {
    const response = await fetch(_resolveUrl('php/entregas.php'), {
      method: "POST",
      body: new URLSearchParams({ action: "toggle" }),
      credentials: "same-origin",
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', Accept: 'application/json' }
    });
    const result = await response.json();
    if (!response.ok)
      throw new Error(
        result.error ||
          "No fue posible cambiar la disponibilidad.",
      );
    localStorage.setItem(
      "shizen_repartidor_activo",
      String(result.online),
    );
    actualizarBotonEstadoRepartidor();
    document.dispatchEvent(
      new CustomEvent("estadoRepartidorCambiado", {
        detail: { activo: result.online },
      }),
    );
    return result.online;
  } catch (error) {
    alert(error.message);
    return repartidorEstaActivo();
  }
}

function actualizarBotonEstadoRepartidor() {
  const boton = document.getElementById(
    "delivery-status-toggle",
  );
  if (!boton) return;

  const activo = repartidorEstaActivo();
  boton.classList.toggle("is-online", activo);
  boton.setAttribute("aria-pressed", String(activo));
  boton.setAttribute(
    "aria-label",
    activo
      ? "Desactivar estado de repartidor"
      : "Activar estado de repartidor",
  );
  boton.querySelector(".nav-status-label").textContent =
    activo ? "Activo" : "Inactivo";
}

function iniciarBotonEstadoRepartidor() {
  const boton = document.getElementById(
    "delivery-status-toggle",
  );
  if (!boton) return;
  boton.addEventListener("click", cambiarEstadoRepartidor);
  actualizarBotonEstadoRepartidor();
}

// Helpers
const MAX_ACTIVOS = 4;
async function cargarEntregas(tipo) {
  const url = _resolveUrl('php/entregas.php') + (window.repartidorApiUrl ? '?type=' : '?type=') + encodeURIComponent(tipo);
  const response = await fetch(url, {
    credentials: "same-origin",
    headers: { Accept: 'application/json' }
  });
  if (response.status === 401) {
    window.location.href = window.location.pathname.includes('/pages/') ? "../index.html" : "/";
    return { items: [] };
  }
  if (!response.ok)
    throw new Error("No fue posible cargar las entregas.");
  return response.json();
}

async function cargarEstadisticas() {
  const url = _resolveUrl('php/entregas.php') + (window.repartidorApiUrl ? '?type=stats' : '?action=stats');
  const response = await fetch(url, {
    credentials: "same-origin",
    headers: { Accept: 'application/json' }
  });
  const result = await response.json();
  if (!response.ok) {
    throw new Error(
      result.error ||
        "No fue posible cargar las estadísticas.",
    );
  }
  return result;
}

async function actualizarEntrega(action, id) {
  const body = new URLSearchParams({
    action,
    id_entrega: String(id),
  });
  const response = await fetch(_resolveUrl('php/entregas.php'), {
    method: "POST",
    body,
    credentials: "same-origin",
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', Accept: 'application/json' }
  });
  const result = await response.json();
  if (!response.ok || result.updated === false)
    throw new Error(
      result.error ||
        "No fue posible actualizar la entrega.",
    );
  return result;
}
function countActivos() {
  return getActivos().filter(
    (p) =>
      p.estado === "aceptado" || p.estado === "en_camino",
  ).length;
}
function cupoLleno() {
  return countActivos() >= MAX_ACTIVOS;
}

function fmt(n) {
  return "$" + Number(n).toLocaleString("es-CO");
}

function nowTime() {
  return new Date().toLocaleTimeString("es-CO", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

// Update nav badges on any page that has them
async function updateNavBadges() {
  const ba = document.getElementById("badge-activos");
  const bn = document.getElementById("badge-notificaciones");

  // Badge de pedidos activos
  if (ba) {
    try {
      const res = await cargarEntregas("active");
      const ca = (res.items || []).length;
      ba.textContent = ca > 9 ? "9+" : ca;
      ba.style.display = ca > 0 ? "flex" : "none";
    } catch (e) {
      /* Silencioso si falla */
    }
  }

  // Badge de notificaciones (punto naranja con conteo)
  if (bn) {
    try {
      const resp = await fetch(_resolveUrl('php/notificaciones.php'), {
        credentials: "same-origin",
        headers: { Accept: 'application/json' }
      });
      if (resp.ok) {
        const notifData = await resp.json();
        const items = notifData.items || [];
        const unreadCount = items.filter(n => Number(n.leida) === 0).length;
        if (unreadCount > 0) {
          bn.textContent = unreadCount > 9 ? "9+" : unreadCount;
          bn.style.display = "flex";
          bn.style.background = "#f97316";
        } else {
          bn.textContent = "";
          bn.style.display = "none";
        }
      }
    } catch (e) {
      /* Silencioso si falla */
    }
  }
}

document.addEventListener("DOMContentLoaded", () => {
  updateNavBadges();
  setInterval(updateNavBadges, 15000);
});
