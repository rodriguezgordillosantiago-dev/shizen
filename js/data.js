/* ── Shizen — capa de comunicación con el backend PHP ─────────────────── */

function _resolveUrl(path) {
  if (path === 'php/entregas.php' && window.repartidorApiUrl) {
    return window.repartidorApiUrl;
  }
  const inPages = window.location.pathname.includes('/pages/');
  const inPhp = window.location.pathname.includes('/php/');
  if (inPages) {
    return '../' + path;
  }
  if (inPhp) {
    return path.replace(/^php\//, '');
  }
  return path;
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
  const label = boton.querySelector(".nav-status-label");
  if (label) {
    label.textContent = activo ? "Activo" : "Inactivo";
  }
}

function iniciarBotonEstadoRepartidor() {
  const boton = document.getElementById(
    "delivery-status-toggle",
  );
  if (!boton) return;
  boton.addEventListener("click", cambiarEstadoRepartidor);
  actualizarBotonEstadoRepartidor();
}

// ── Servicios API conectados a PHP y MySQL ──
const MAX_ACTIVOS = 4;

async function cargarEntregas(tipo) {
  const url = _resolveUrl('php/entregas.php') + '?type=' + encodeURIComponent(tipo);
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
  const url = _resolveUrl('php/entregas.php') + '?action=stats';
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

function fmt(n) {
  return "$" + Number(n).toLocaleString("es-CO");
}

function nowTime() {
  return new Date().toLocaleTimeString("es-CO", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

// Actualiza los badges de la barra de navegación consultando la BD
async function updateNavBadges() {
  const baList = [document.getElementById("badge-activos"), document.getElementById("nav-badge-activos")].filter(Boolean);
  const bnList = [document.getElementById("badge-notificaciones"), document.getElementById("nav-badge-avisos")].filter(Boolean);

  // Badge de pedidos activos
  if (baList.length > 0) {
    try {
      const res = await cargarEntregas("active");
      const ca = (res.items || []).length;
      baList.forEach(ba => {
        ba.textContent = ca > 9 ? "9+" : ca;
        ba.style.display = ca > 0 ? "block" : "none";
      });
    } catch (e) {}
  }

  // Badge de notificaciones (avisos)
  if (bnList.length > 0) {
    try {
      const resp = await fetch(_resolveUrl('php/notificaciones.php'), {
        credentials: "same-origin",
        headers: { Accept: 'application/json' }
      });
      if (resp.ok) {
        const notifData = await resp.json();
        const items = notifData.items || [];
        const unreadCount = items.filter(n => Number(n.leida) === 0).length;
        bnList.forEach(bn => {
          if (unreadCount > 0) {
            bn.textContent = unreadCount > 9 ? "9+" : unreadCount;
            bn.style.display = "block";
          } else {
            bn.textContent = "";
            bn.style.display = "none";
          }
        });
      }
    } catch (e) {}
  }
}

document.addEventListener("DOMContentLoaded", () => {
  iniciarBotonEstadoRepartidor();
  updateNavBadges();
  setInterval(updateNavBadges, 15000);
});
