<div class="view document-page">
  <div class="join-page">
    <div class="join-left" style="background:linear-gradient(135deg,#bf360c,#f57c00)">
      <div class="join-left-bg" style="background-image:url('https://images.unsplash.com/photo-1611068562065-994ba66501ba?w=800&fit=crop&auto=format')"></div>
      <div class="join-left-content">
        <img src="../assets/logo_repartidor.png" alt="Shizen" class="join-left-logo" />
        <span class="join-left-badge">Para repartidores</span>
        <h2>Gana dinero recorriendo la ciudad</h2>
        <p>Sé repartidor Shizen: horarios flexibles, mejores tarifas y una comunidad que cuida el planeta.</p>
        <div class="benefit-grid">
          <div class="benefit-card">
            <div class="b-icon">💰</div>
            <div class="b-title">Ganancias top</div>
            <div class="b-desc">Las mejores tarifas del mercado</div>
          </div>
          <div class="benefit-card">
            <div class="b-icon">⌛</div>
            <div class="b-title">Horarios libres</div>
            <div class="b-desc">Trabaja cuando quieras</div>
          </div>
          <div class="benefit-card">
            <div class="b-icon">🏥</div>
            <div class="b-title">Seguro incluido</div>
            <div class="b-desc">Accidentes cubiertos en cada domicilio</div>
          </div>
          <div class="benefit-card">
            <div class="b-icon">📱</div>
            <div class="b-title">App intuitiva</div>
            <div class="b-desc">Gestiona todo desde tu celular</div>
          </div>
        </div>
      </div>
    </div>
   <div class="join-right">
      <div class="join-form-wrap document-form-wrap">
        <div class="form-header-nav">
          <a
            class="vol"
            href="registro_repartidor.php"
            aria-label="Volver a los datos del repartidor"
            title="Volver a los datos del repartidor"
          >
            <svg
              viewBox="0 0 32 32"
              width="38"
              height="38"
              fill="none"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <g stroke="currentColor" stroke-width="4">
                <path d="m15 22-6-6 6-6" />
                <path d="M24 16H9" />
              </g>
            </svg>
          </a>
          <div class="progress-steps">
            <div class="step-dot done">✓</div>
            <div class="step-line done"></div>
            <div class="step-dot active">2</div>
          </div>
        </div>
        <form method="post" action="documentos_repartidor.php" enctype="multipart/form-data">
          <h2>Sube tus documentos</h2>
          <p class="form-sub">Aceptamos archivos PDF. La foto es solo para identificarte.</p>
          <div class="form-group">
            <label for="cedula">Número de cédula</label>
            <input class="form-input" id="cedula" type="text" name="cedula" inputmode="numeric" pattern="[0-9]{6,12}" placeholder="Número de identificación" required /><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
          </div>
          <?php
            $uploadId = 'foto-repartidor';
            $uploadName = 'foto_repartidor';
            $uploadLabel = 'Subir foto del repartidor';
            $uploadAccept = 'image/*';
            $uploadReq = true;
            $uploadAlt = 'Vista previa de tu foto';
            include __DIR__ . '/campo_subir_foto.php';
          ?>
          <div class="document-upload-grid">
            <div class="document-upload-item">
              <span>Cédula</span>
              <input class="upload-hidden-input" id="documento-cedula" type="file" name="documento_cedula" accept="application/pdf" required /><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
              <label class="upload-pdf-button" for="documento-cedula" id="documento-cedula-label"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Cédula</span></label>
            </div>

            <div class="document-upload-item">
              <span>Licencia de conducción</span>
              <input class="upload-hidden-input" id="licencia" type="file" name="licencia_conduccion" accept="application/pdf" <?= $esMotorizado ? 'required' : '' ?> />
              <label class="upload-pdf-button" for="licencia" id="licencia-label"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Licencia</span></label>
            </div>
            <div class="document-upload-item">
              <span>Tarjeta de propiedad</span>
              <input class="upload-hidden-input" id="tarjeta" type="file" name="tarjeta_propiedad" accept="application/pdf" <?= $esMotorizado ? 'required' : '' ?> />
              <label class="upload-pdf-button" for="tarjeta" id="tarjeta-label"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Tarjeta</span></label>
            </div>
            <div class="document-upload-item">
              <span>SOAT vigente</span>
              <input class="upload-hidden-input" id="soat" type="file" name="soat" accept="application/pdf" <?= $esMotorizado ? 'required' : '' ?> />
              <label class="upload-pdf-button" for="soat" id="soat-label"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>SOAT</span></label>
            </div>
          </div>
          <button class="btn-primary-full" type="submit">Finalizar solicitud</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    var inputFoto = document.getElementById("foto-repartidor");
    var preview = document.getElementById("foto-repartidor-preview");
    var placeholder = document.getElementById("foto-repartidor-placeholder");
    var filename = document.getElementById("foto-repartidor-filename");
    var cedulaInput = document.getElementById("cedula");

    function ensureError(input, messageText) {
      var error = input.parentElement.querySelector(".field-error");
      if (!error) {
        error = document.createElement("p");
        error.className = "field-error";
        error.style.cssText = "color: #dc2626; font-size: 12px; margin: 8px 0 0;";
        input.parentElement.appendChild(error);
      }
      error.textContent = messageText || "";
      return error;
    }

    function validateCedula() {
      if (!cedulaInput) return;
      var value = cedulaInput.value.trim();
      var valid = /^\d{6,12}$/.test(value);
      cedulaInput.setCustomValidity(valid ? "" : "Ingresa una cédula válida.");
      ensureError(cedulaInput, cedulaInput.dataset.touched === "true" && !valid ? "Ingresa una cédula válida." : "");
    }

    if (cedulaInput) {
      cedulaInput.addEventListener("input", function () {
        cedulaInput.dataset.touched = "true";
        validateCedula();
      });
      cedulaInput.addEventListener("blur", function () {
        cedulaInput.dataset.touched = "true";
        validateCedula();
      });
    }

    if (inputFoto) {
      inputFoto.addEventListener("change", function () {
        if (this.files && this.files[0]) {
          var file = this.files[0];
          var valid = /\.(jpe?g|png|webp)$/i.test(file.name);
          this.setCustomValidity(valid ? "" : "La foto debe ser una imagen válida.");
          ensureError(this, this.dataset.touched === "true" && !valid ? "La foto debe ser una imagen válida." : "");
          if (!valid) return;
          preview.src = URL.createObjectURL(file);
          preview.style.display = "block";
          if (placeholder) placeholder.style.display = "none";
          if (filename) {
            filename.textContent = file.name;
            filename.classList.add("selected");
          }
        }
      });
      inputFoto.addEventListener("blur", function () {
        this.dataset.touched = "true";
      });
    }

    var pdfInputs = document.querySelectorAll(".document-upload-item input[type='file']");
    pdfInputs.forEach(function(input) {
      input.addEventListener("change", function() {
        var label = this.parentElement.querySelector(".upload-pdf-button");
        var file = this.files && this.files[0];
        var valid = !file || /\.pdf$/i.test(file.name);
        this.setCustomValidity(valid ? "" : "El archivo debe ser PDF.");
        ensureError(this, this.dataset.touched === "true" && !valid ? "El archivo debe ser PDF." : "");
        if (label && file) {
          label.textContent = valid ? "✓ " + file.name : label.dataset.defaultText || label.textContent;
          label.classList.toggle("uploaded", valid);
        }
      });
      input.addEventListener("blur", function () {
        this.dataset.touched = "true";
      });
      if (input.parentElement.querySelector(".upload-pdf-button")) {
        input.parentElement.querySelector(".upload-pdf-button").dataset.defaultText = input.parentElement.querySelector(".upload-pdf-button").textContent;
      }
    });

    validateCedula();
  })();
</script>
