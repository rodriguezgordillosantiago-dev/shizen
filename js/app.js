/* ── SHIZEN HOME - JavaScript Principal ──────────────────────────────────
   Contiene únicamente las funciones esenciales para el flujo de la app:
   - Carrito de compras y Checkout en vivo
   - Notificaciones y estado de pedidos en tiempo real
   - Modales de usuario, perfil y notificaciones
   - Interacciones de interfaz (menú móvil, mostrar contraseña, estrellas)
────────────────────────────────────────────────────────────────────────── */

/* ── Carrito y Checkout ────────────────────────────────────────────── */
var cart = JSON.parse(
  localStorage.getItem("shizenCart") || "[]",
);
var activeCartBusinessId = null;
var selectedCheckoutBusinessId = null;

var UI_TEXT = {
  emptyCart: "Tu carrito está vacío.",
  decrease: "Disminuir cantidad",
  increase: "Aumentar cantidad",
};

function formatMoney(value) {
  return "$" + Number(value).toLocaleString("es-CO");
}

function saveCart() {
  localStorage.setItem("shizenCart", JSON.stringify(cart));
  updateCartCount();
  syncCartToDB();
}

function syncCartToDB() {
  var syncUrl =
    window.shizenSyncCartUrl || "php/sync_carrito.php";
  fetch(syncUrl, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(cart),
  }).catch(function (err) {
    console.warn("Cart DB sync error:", err);
  });
}

function loadCartFromDB() {
  var syncUrl =
    window.shizenSyncCartUrl || "php/sync_carrito.php";
  fetch(syncUrl)
    .then(function (res) {
      if (!res.ok)
        throw new Error(
          "Error cargando carrito (" + res.status + ")",
        );
      return res.json();
    })
    .then(function (data) {
      if (
        data &&
        data.logged_in &&
        Array.isArray(data.items)
      ) {
        cart = data.items
          .map(function (item) {
            var id = Number(item.id);
            var quantity = Number(item.quantity);
            var price = Number(item.price);
            if (
              !Number.isFinite(id) ||
              id <= 0 ||
              !Number.isFinite(quantity) ||
              quantity <= 0 ||
              !Number.isFinite(price) ||
              price < 0
            ) {
              return null;
            }
            return {
              id: id,
              businessId: Number(item.businessId) || 0,
              quantity: quantity,
              price: price,
              name: String(item.name || "Producto"),
              image: String(
                item.image || "assets/logo.png",
              ),
              restaurant: String(
                item.restaurant || "Restaurante Shizen",
              ),
              addedAt: Number(item.addedAt) || Date.now(),
            };
          })
          .filter(function (item) {
            return item !== null;
          });

        localStorage.setItem(
          "shizenCart",
          JSON.stringify(cart),
        );
        updateCartCount();
        renderCart();
      }
    })
    .catch(function (err) {
      console.warn("Cart DB load error:", err);
    });
}

function updateCartCount() {
  var total = cart.reduce(function (sum, item) {
    return sum + item.quantity;
  }, 0);
  ["cartCount", "cartCountMenu"].forEach(function (id) {
    var count = document.getElementById(id);
    if (count) count.textContent = total;
  });
}

function groupCartByRestaurant() {
  var groupsMap = {};
  cart.forEach(function (item) {
    var key = String(
      item.businessId || item.restaurant || "general",
    );
    if (!groupsMap[key]) {
      groupsMap[key] = {
        businessId: item.businessId || 0,
        restaurantName:
          item.restaurant || "Restaurante Shizen",
        updatedAt: item.addedAt || 0,
        items: [],
      };
    }
    groupsMap[key].items.push(item);
    if ((item.addedAt || 0) > groupsMap[key].updatedAt) {
      groupsMap[key].updatedAt = item.addedAt || 0;
    }
  });

  var groupsArray = [];
  for (var k in groupsMap) {
    if (
      Object.prototype.hasOwnProperty.call(groupsMap, k)
    ) {
      groupsArray.push(groupsMap[k]);
    }
  }
  groupsArray.sort(function (a, b) {
    return b.updatedAt - a.updatedAt;
  });
  return groupsArray;
}

function showCartList() {
  activeCartBusinessId = null;
  selectedCheckoutBusinessId = null;
  renderCart();
}

function selectCartGroup(businessId) {
  activeCartBusinessId = businessId;
  selectedCheckoutBusinessId = businessId;
  renderCart();
}

function addToCart(product) {
  var busId = product.businessId || product.id_negocio || 0;
  var restName =
    product.restaurant ||
    (busId
      ? "Restaurante #" + busId
      : "Restaurante Shizen");
  var now = Date.now();

  var existing = cart.find(function (item) {
    return item.id === product.id;
  });
  if (existing) {
    existing.quantity += 1;
    existing.addedAt = now;
    if (!existing.businessId) existing.businessId = busId;
    if (!existing.restaurant)
      existing.restaurant = restName;
  } else {
    cart.push({
      id: product.id,
      name: product.name,
      price: product.price,
      restaurant: restName,
      businessId: busId,
      image: product.image || "assets/logo.png",
      quantity: 1,
      addedAt: now,
    });
  }
  saveCart();
  renderCart();

  if (typeof Swal !== "undefined") {
    Swal.fire({
      title: "¡Añadido!",
      text: "El producto se agregó a tu carrito.",
      icon: "success",
      showConfirmButton: false,
      timer: 1800,
      timerProgressBar: true
    });
  }
}

function renderCart() {
  var itemsContainer = document.getElementById("cartItems");
  var total = document.getElementById("cartTotal");
  var totalRow = document.getElementById("cartTotalRow");
  var checkout = document.getElementById("checkoutButton");
  if (!itemsContainer || !total) return;

  if (!cart.length) {
    activeCartBusinessId = null;
    itemsContainer.innerHTML =
      '<p class="cart-empty">' + UI_TEXT.emptyCart + "</p>";
    total.textContent = formatMoney(0);
    if (totalRow) totalRow.style.display = "none";
    if (checkout) {
      checkout.disabled = true;
      checkout.style.display = "none";
    }
    return;
  }

  var groups = groupCartByRestaurant();

  if (activeCartBusinessId !== null) {
    var targetGroup = groups.find(function (g) {
      return (
        String(g.businessId) ===
        String(activeCartBusinessId)
      );
    });

    if (!targetGroup) {
      activeCartBusinessId = null;
      renderCart();
      return;
    }

    var groupSubtotal = targetGroup.items.reduce(function (
      sum,
      item,
    ) {
      return sum + item.price * item.quantity;
    }, 0);

    var itemsHtml = targetGroup.items
      .map(function (item) {
        return (
          '<div class="cart-item">' +
          '<img class="cart-item-image" src="' +
          (item.image || "assets/logo.png") +
          '" alt="' +
          item.name +
          '">' +
          '<div class="cart-item-info">' +
          "<strong>" +
          item.name +
          "</strong>" +
          "<small>" +
          formatMoney(item.price) +
          " / unidad</small>" +
          "<span>Subtotal: " +
          formatMoney(item.price * item.quantity) +
          "</span>" +
          "</div>" +
          '<div class="cart-quantity">' +
          '<button type="button" aria-label="' +
          UI_TEXT.decrease +
          '" onclick="changeCartQuantity(' +
          item.id +
          ',-1)">-</button>' +
          "<b>" +
          item.quantity +
          "</b>" +
          '<button type="button" aria-label="' +
          UI_TEXT.increase +
          '" onclick="changeCartQuantity(' +
          item.id +
          ',1)">+</button>' +
          "</div>" +
          "</div>"
        );
      })
      .join("");

    itemsContainer.innerHTML =
      '<button type="button" class="btn-back-carts" onclick="showCartList()">← Volver a mis carritos</button>' +
      '<div class="cart-group-card" data-business-id="' +
      targetGroup.businessId +
      '">' +
      '<div class="cart-group-header">' +
      '<div class="cart-group-title">🏬 ' +
      targetGroup.restaurantName +
      "</div>" +
      "</div>" +
      '<div class="cart-group-items">' +
      itemsHtml +
      "</div>" +
      '<div class="cart-group-footer">' +
      '<div class="cart-group-subtotal"><span>Subtotal Pedido:</span><strong>' +
      formatMoney(groupSubtotal) +
      "</strong></div>" +
      '<div class="cart-group-actions"><button type="button" class="btn-cart-clear" aria-label="Vaciar" onclick="clearBusinessCart(' +
      targetGroup.businessId +
      ')"><svg viewBox="0 0 448 512" class="svgIcon" aria-hidden="true"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path></svg><span class="btn-cart-clear-label">Vaciar</span></button></div>' +
      "</div>" +
      "</div>";

    total.textContent = formatMoney(groupSubtotal);
    if (totalRow) totalRow.style.display = "";
    if (checkout) {
      checkout.disabled = false;
      checkout.style.display = "";
    }
    return;
  }

  itemsContainer.innerHTML = groups
    .map(function (group, gIdx) {
      var isNewestGroup = gIdx === 0 && groups.length > 1;
      var groupSubtotal = group.items.reduce(function (
        sum,
        item,
      ) {
        return sum + item.price * item.quantity;
      }, 0);
      var totalItemsCount = group.items.reduce(function (
        sum,
        item,
      ) {
        return sum + item.quantity;
      }, 0);
      var cartNumber = groups.length - gIdx;

      return (
        '<div class="cart-selector-box" onclick="selectCartGroup(' +
        group.businessId +
        ')">' +
        '<div class="cart-box-header">' +
        '<span class="cart-box-name">🛒 Carrito #' +
        cartNumber +
        " — 🏬 " +
        group.restaurantName +
        "</span>" +
        (isNewestGroup
          ? '<span class="cart-group-badge">🔥 MÁS RECIENTE</span>'
          : "") +
        "</div>" +
        '<div class="cart-box-details">' +
        '<span class="cart-box-count">📦 ' +
        totalItemsCount +
        " plato(s)</span>" +
        '<span class="cart-box-total">Total: <strong>' +
        formatMoney(groupSubtotal) +
        "</strong></span>" +
        "</div>" +
        '<div class="cart-box-arrow">Ver platos y pagar &rarr;</div>' +
        "</div>"
      );
    })
    .join("");

  if (totalRow) totalRow.style.display = "none";
  if (checkout) {
    checkout.disabled = true;
    checkout.style.display = "none";
  }
}

function clearBusinessCart(businessId) {
  cart = cart.filter(function (item) {
    return (
      String(item.businessId || 0) !== String(businessId)
    );
  });
  activeCartBusinessId = null;
  saveCart();
  renderCart();
}

function changeCartQuantity(id, delta) {
  var item = cart.find(function (entry) {
    return entry.id === id;
  });
  if (!item) return;
  item.quantity += delta;
  item.addedAt = Date.now();
  if (item.quantity <= 0) {
    cart = cart.filter(function (entry) {
      return entry.id !== id;
    });
  }
  saveCart();
  renderCart();
}

function openCart(event) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  [
    "loginModal",
    "profileModal",
    "notificationModal",
    "checkoutModal",
    "ratingModal",
  ].forEach(function (id) {
    var overlay = document.getElementById(id);
    if (overlay) overlay.classList.remove("open");
  });
  var modal = document.getElementById("cartModal");
  if (modal) {
    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
  }
  renderCart();
}

function closeCart() {
  var modal = document.getElementById("cartModal");
  if (modal) {
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
  }
}

function handleCartOverlayClick(event) {
  if (event.target === document.getElementById("cartModal"))
    closeCart();
}

function openCheckout() {
  if (!cart.length) return;
  if (!window.shizenUser) {
    openAccessModal("index.php");
    return;
  }
  closeCart();
  var checkoutModal =
    document.getElementById("checkoutModal");
  if (checkoutModal) checkoutModal.classList.add("open");
}

function closeCheckout() {
  var checkoutModal =
    document.getElementById("checkoutModal");
  if (checkoutModal) checkoutModal.classList.remove("open");
}

function handleCheckoutOverlayClick(event) {
  if (
    event.target ===
    document.getElementById("checkoutModal")
  )
    closeCheckout();
}

function prepareCheckout(event) {
  if (!cart.length) {
    if (event) event.preventDefault();
    return;
  }
  var checkoutList = cart;
  if (selectedCheckoutBusinessId) {
    checkoutList = cart.filter(function (item) {
      return (
        String(item.businessId || 0) ===
        String(selectedCheckoutBusinessId)
      );
    });
  }
  if (!checkoutList.length) checkoutList = cart;

  var checkoutInput =
    document.getElementById("checkoutItems");
  if (checkoutInput) {
    checkoutInput.value = JSON.stringify(
      checkoutList.map(function (item) {
        return { id: item.id, quantity: item.quantity };
      }),
    );
  }
}

/* ── Notificaciones de Pedidos en Vivo ────────────────────────────── */
function consultarNotificacionesEnVivo() {
  if (!window.shizenUser) return;
  var notifUrl =
    window.shizenNotifUrl ||
    "php/notificaciones.php?json=1";
  fetch(notifUrl, {
    headers: { Accept: "application/json" },
  })
    .then(function (res) {
      if (!res.ok) return null;
      return res.json();
    })
    .then(function (data) {
      if (data && data.success) {
        actualizarBadgeNotificaciones(
          data.unread_count || 0,
        );
      }
    })
    .catch(function (err) {
      console.warn(
        "Error consultando notificaciones en vivo:",
        err,
      );
    });
}

function actualizarBadgeNotificaciones(unreadCount) {
  var badge = document.getElementById("notifBadge");
  if (badge) {
    badge.hidden = unreadCount <= 0;
  }
}

function iniciarNotificacionesEnVivo() {
  if (window.shizenUser) {
    consultarNotificacionesEnVivo();
    setInterval(consultarNotificacionesEnVivo, 8000);
  }
}

function openNotificationModal() {
  var modal = document.getElementById("notificationModal");
  if (modal) modal.classList.add("open");
}

function closeNotificationModal() {
  var modal = document.getElementById("notificationModal");
  if (modal) modal.classList.remove("open");
}

function handleNotificationOverlayClick(event) {
  if (event.target.id === "notificationModal")
    closeNotificationModal();
}

/* ── Modales de Usuario, Perfil y Accesos ────────────────────────── */
function openProfileModal() {
  var modal = document.getElementById("profileModal");
  if (!modal) return;
  ["loginModal", "notificationModal", "cartModal", "checkoutModal", "ratingModal"].forEach(function (id) {
    var other = document.getElementById(id);
    if (other) other.classList.remove("open");
  });
  modal.classList.add("open");
  modal.setAttribute("aria-hidden", "false");
}

function closeProfileModal() {
  var modal = document.getElementById("profileModal");
  if (modal) {
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
  }
}

function handleProfileOverlayClick(event) {
  if (event.target === document.getElementById("profileModal")) closeProfileModal();
}

function enableProfileEditing() {
  document.querySelectorAll("#profileModal .profile-field").forEach(function (field) {
    field.readOnly = false;
  });
  var editButton = document.getElementById("profileEditButton");
  var saveButton = document.getElementById("profileSaveButton");
  if (editButton) editButton.hidden = true;
  if (saveButton) saveButton.hidden = false;
}
var loginModalOpen = false;
function openLoginModal(redirectTo) {
  var modal = document.getElementById("loginModal");
  if (!modal) return;
  var redirectInput = document.getElementById("loginModalRedirect");
  if (redirectInput) redirectInput.value = redirectTo || "index.php";
  modal.classList.add("open");
  var ingresoButton = document.getElementById("ingresoBtn");
  if (ingresoButton) ingresoButton.classList.add("active");
  loginModalOpen = true;
}
function closeLoginModal() {
  var modal = document.getElementById("loginModal");
  if (modal) modal.classList.remove("open");
  var ingresoButton = document.getElementById("ingresoBtn");
  if (ingresoButton) ingresoButton.classList.remove("active");
  loginModalOpen = false;
}
function toggleLoginModal() {
  if (loginModalOpen) closeLoginModal();
  else openLoginModal();
}
function handleLoginOverlayClick(e) {
  if (e.target === document.getElementById("loginModal")) closeLoginModal();
}

function openAccessModal(redirectTo) {
  openLoginModal(redirectTo);
}

function goToRegistration() {
  closeLoginModal();
  var registrationSection =
    document.getElementById("registro");
  if (!registrationSection) {
    window.location.assign("index.php#registro");
    return;
  }
  registrationSection.scrollIntoView({
    behavior: "smooth",
    block: "start",
  });
}

/* ── UI Helpers (Password Toggle, Menu Móvil, Estrellas) ────────── */
function togglePassword(button) {
  var input = button.parentElement.querySelector("input");
  if (!input) return;
  var showPassword = input.type === "password";
  input.type = showPassword ? "text" : "password";
  button.classList.toggle("is-visible", showPassword);
  button.setAttribute(
    "aria-label",
    showPassword
      ? "Ocultar contraseña"
      : "Mostrar contraseña",
  );
  button.setAttribute(
    "title",
    showPassword
      ? "Ocultar contraseña"
      : "Mostrar contraseña",
  );
}

function toggleMobileMenu() {
  var menu = document.getElementById("mobileMenu");
  if (!menu) return;
  menu.classList.toggle("open");
  var btn = document.getElementById("hamburgerBtn");
  if (btn)
    btn.innerHTML = menu.classList.contains("open")
      ? "&#10005;"
      : "&#9776;";
}

function initializeRatingStars() {
  document
    .querySelectorAll(".rating-stars")
    .forEach(function (group) {
      var stars = Array.from(
        group.querySelectorAll(".rating-star"),
      );
      stars.forEach(function (star) {
        star
          .querySelector("input")
          .addEventListener("change", function () {
            var selected = Number(this.value);
            stars.forEach(function (entry) {
              entry.classList.toggle(
                "is-selected",
                Number(
                  entry.querySelector("input").value,
                ) <= selected,
              );
            });
          });
      });
    });
}

/* ── Inicialización ────────────────────────────────────────────────── */
function initializeApp() {
  updateCartCount();
  loadCartFromDB();
  iniciarNotificacionesEnVivo();
  initializeRatingStars();
  if (window.loginError || window.location.hash === "#login") {
    var redirectInput = document.getElementById("loginModalRedirect");
    openLoginModal(redirectInput ? redirectInput.value : "index.php");
  }
}

Promise.resolve(window.shizenLayoutReady).then(
  initializeApp,
);

/* ── Modal de Calificación (Negocio y Repartidor) ────────────────── */
var ratingState = {
  negocio: 0,
  repartidor: 0,
  negocioRated: false,
  repartidorRated: false,
};

function setRatingValue(target, score) {
  ratingState[target] = Number(score);
  var container = document.getElementById(target === "negocio" ? "ratingStarsNegocio" : "ratingStarsRepartidor");
  if (container) {
    var stars = container.querySelectorAll(".star");
    stars.forEach(function (star) {
      var val = Number(star.getAttribute("data-value"));
      var poly = star.querySelector("polygon");
      if (val <= score) {
        star.classList.add("active");
        if (poly) {
          poly.classList.remove("star-empty");
          poly.classList.add("star-filled");
        }
      } else {
        star.classList.remove("active");
        if (poly) {
          poly.classList.remove("star-filled");
          poly.classList.add("star-empty");
        }
      }
    });
  }

  var badge = document.getElementById(target === "negocio" ? "ratingBadgeNegocio" : "ratingBadgeRepartidor");
  if (badge) {
    var labels = { 5: "Excelente", 4: "Muy bueno", 3: "Bueno", 2: "Regular", 1: "Malo" };
    var classes = { 5: "badge-excellent", 4: "badge-good", 3: "badge-regular", 2: "badge-poor", 1: "badge-poor" };
    badge.textContent = labels[score] || "";
    badge.className = "badge " + (classes[score] || "badge-good");
    badge.style.display = score > 0 ? "inline-block" : "none";
  }
}

function setAvatarContent(elementId, imgUrl, fallbackText) {
  var el = document.getElementById(elementId);
  if (!el) return;
  if (imgUrl && String(imgUrl).trim() !== "") {
    el.innerHTML = "";
    var img = document.createElement("img");
    img.src = imgUrl;
    img.alt = fallbackText || "Avatar";
    img.onerror = function () {
      this.onerror = null;
      el.textContent = fallbackText || "•";
    };
    el.appendChild(img);
  } else {
    el.textContent = fallbackText || "•";
  }
}

function openRatingModal(data) {
  var modal = document.getElementById("ratingModal");
  if (!modal) return;

  ["loginModal", "profileModal", "notificationModal", "cartModal", "checkoutModal"].forEach(function (id) {
    var other = document.getElementById(id);
    if (other) other.classList.remove("open");
  });

  if (data) {
    if (data.id) {
      var idInput = document.getElementById("ratingOrderId");
      if (idInput) idInput.value = data.id;
    }
    if (data.negocioNombre) {
      var nameEl = document.getElementById("ratingSecNombreNegocio");
      if (nameEl) nameEl.textContent = data.negocioNombre;
      var initNeg = data.negocioNombre.trim().charAt(0).toUpperCase() || "S";
      setAvatarContent("ratingHeroNegocio", data.negocioLogo, initNeg);
      setAvatarContent("ratingSecAvatarNegocio", data.negocioLogo, initNeg);
    }
    if (data.repartidorNombre) {
      var repNameEl = document.getElementById("ratingSecNombreRepartidor");
      if (repNameEl) repNameEl.textContent = data.repartidorNombre;
      var initRep = data.repartidorNombre.trim().charAt(0).toUpperCase() || "R";
      setAvatarContent("ratingHeroRepartidor", data.repartidorFoto, initRep);
      setAvatarContent("ratingSecAvatarRepartidor", data.repartidorFoto, initRep);
    }
    if (data.negocioRated) {
      ratingState.negocioRated = true;
      var secNeg = document.getElementById("ratingSecNegocio");
      if (secNeg) secNeg.style.display = "none";
    } else {
      ratingState.negocioRated = false;
      var secNeg2 = document.getElementById("ratingSecNegocio");
      if (secNeg2) secNeg2.style.display = "";
    }
    if (data.repartidorRated) {
      ratingState.repartidorRated = true;
      var secRep = document.getElementById("ratingSecRepartidor");
      if (secRep) secRep.style.display = "none";
    } else {
      ratingState.repartidorRated = false;
      var secRep2 = document.getElementById("ratingSecRepartidor");
      if (secRep2) secRep2.style.display = "";
    }
  }

  // Reiniciar estrellas visuales
  setRatingValue("negocio", 0);
  setRatingValue("repartidor", 0);

  modal.classList.add("open");
  modal.setAttribute("aria-hidden", "false");
}

function closeRatingModal() {
  var modal = document.getElementById("ratingModal");
  if (modal) {
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
  }
}

function handleRatingOverlayClick(event) {
  if (event.target === document.getElementById("ratingModal")) {
    closeRatingModal();
  }
}

function submitRatingModal() {
  var orderId = document.getElementById("ratingOrderId") ? document.getElementById("ratingOrderId").value : "";
  var csrfToken = document.getElementById("ratingCsrfToken") ? document.getElementById("ratingCsrfToken").value : "";
  var commentNegocio = document.getElementById("ratingCommentNegocio") ? document.getElementById("ratingCommentNegocio").value : "";
  var commentRepartidor = document.getElementById("ratingCommentRepartidor") ? document.getElementById("ratingCommentRepartidor").value : "";

  if (!orderId) {
    alert("No se especificó el número de pedido.");
    return;
  }

  if (!ratingState.negocioRated && ratingState.negocio === 0 && !ratingState.repartidorRated && ratingState.repartidor === 0) {
    if (typeof Swal !== "undefined") {
      Swal.fire({ icon: "warning", title: "Atención", text: "Por favor califica con estrellas al negocio o al repartidor." });
    } else {
      alert("Por favor califica con estrellas al negocio o al repartidor.");
    }
    return;
  }

  var submitBtn = document.getElementById("btnSubmitRating");
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Enviando...";
  }

  var formData = new FormData();
  formData.append("id", orderId);
  formData.append("csrf_token", csrfToken);
  if (!ratingState.negocioRated && ratingState.negocio > 0) {
    formData.append("puntuacion_negocio", ratingState.negocio);
    formData.append("comentario_negocio", commentNegocio);
  }
  if (!ratingState.repartidorRated && ratingState.repartidor > 0) {
    formData.append("puntuacion_repartidor", ratingState.repartidor);
    formData.append("comentario_repartidor", commentRepartidor);
  }

  fetch("php/calificar_pedido.php", {
    method: "POST",
    headers: { Accept: "application/json" },
    body: formData,
  })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Enviar";
      }
      if (data && data.success) {
        closeRatingModal();
        if (typeof Swal !== "undefined") {
          Swal.fire({
            icon: "success",
            title: "¡Muchas gracias!",
            text: data.mensaje || "Tu calificación ha sido registrada.",
            timer: 2000,
            showConfirmButton: false,
          }).then(function () {
            window.location.reload();
          });
        } else {
          alert(data.mensaje || "¡Muchas gracias por calificar!");
          window.location.reload();
        }
      } else {
        if (typeof Swal !== "undefined") {
          Swal.fire({ icon: "error", title: "Error", text: (data && data.mensaje) || "No se pudo registrar la calificación." });
        } else {
          alert((data && data.mensaje) || "No se pudo registrar la calificación.");
        }
      }
    })
    .catch(function (err) {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Enviar";
      }
      console.error("Error al enviar calificación:", err);
      alert("Hubo un inconveniente al enviar la calificación. Intenta de nuevo.");
    });
}

document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    closeLoginModal();
    closeRatingModal();
  }
});

