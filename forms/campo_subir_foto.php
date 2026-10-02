<?php
/**
 * campo_subir_foto.php — Campo de subida de imagen/foto reutilizable.
 *
 * Variables configurables antes de incluir este archivo:
 *   $uploadId    — ID del input (ej: 'foto-repartidor' o 'logo-negocio')
 *   $uploadName  — Nombre del campo POST (por defecto igual a $uploadId)
 *   $uploadLabel — Texto explicativo (ej: 'Subir foto del repartidor')
 *   $uploadAccept— Tipos permitidos (por defecto 'image/jpeg,image/png,image/webp')
 *   $uploadReq   — Booleano si es obligatorio (por defecto true)
 *   $uploadAlt   — Alt para la vista previa (por defecto 'Vista previa de la imagen')
 *
 * Uso:
 *   <?php
 *     $uploadId = 'foto-repartidor';
 *     $uploadName = 'foto_repartidor';
 *     $uploadLabel = 'Subir foto del repartidor';
 *     include __DIR__ . '/campo_subir_foto.php';
 *   ?>
 */

$_up_id     = htmlspecialchars($uploadId ?? 'file', ENT_QUOTES, 'UTF-8');
$_up_name   = htmlspecialchars($uploadName ?? ($uploadId ?? 'file'), ENT_QUOTES, 'UTF-8');
$_up_label  = htmlspecialchars($uploadLabel ?? 'Subir foto', ENT_QUOTES, 'UTF-8');
$_up_accept = htmlspecialchars($uploadAccept ?? 'image/jpeg,image/png,image/webp', ENT_QUOTES, 'UTF-8');
$_up_alt    = htmlspecialchars($uploadAlt ?? 'Vista previa de la imagen', ENT_QUOTES, 'UTF-8');
$_up_req    = !isset($uploadReq) || $uploadReq;
?>
<div class="photo-upload-box">
  <label for="<?= $_up_id ?>" class="custum-file-upload" id="<?= $_up_id ?>-btn">
    <img id="<?= $_up_id ?>-preview" class="photo-preview-img" style="display: none;" alt="<?= $_up_alt ?>" />
    <div class="custum-file-upload-content" id="<?= $_up_id ?>-placeholder">
      <div class="icon">
        <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
          <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
          <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
          <g id="SVGRepo_iconCarrier">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 1C9.73478 1 9.48043 1.10536 9.29289 1.29289L3.29289 7.29289C3.10536 7.48043 3 7.73478 3 8V20C3 21.6569 4.34315 23 6 23H7C7.55228 23 8 22.5523 8 22C8 21.4477 7.55228 21 7 21H6C5.44772 21 5 20.5523 5 20V9H10C10.5523 9 11 8.55228 11 8V3H18C18.5523 3 19 3.44772 19 4V9C19 9.55228 19.4477 10 20 10C20.5523 10 21 9.55228 21 9V4C21 2.34315 19.6569 1 18 1H10ZM9 7H6.41421L9 4.41421V7ZM14 15.5C14 14.1193 15.1193 13 16.5 13C17.8807 13 19 14.1193 19 15.5V16V17H20C21.1046 17 22 17.8954 22 19C22 20.1046 21.1046 21 20 21H13C11.8954 21 11 20.1046 11 19C11 17.8954 11.8954 17 13 17H14V16V15.5ZM16.5 11C14.142 11 12.2076 12.8136 12.0156 15.122C10.2825 15.5606 9 17.1305 9 19C9 21.2091 10.7909 23 13 23H20C22.2091 23 24 21.2091 24 19C24 17.1305 22.7175 15.5606 20.9844 15.122C20.7924 12.8136 18.858 11 16.5 11Z" fill="currentColor"></path>
          </g>
        </svg>
      </div>
      <div class="text">
        <span><?= $_up_label ?></span>
      </div>
    </div>
    <input id="<?= $_up_id ?>" type="file" name="<?= $_up_name ?>" accept="<?= $_up_accept ?>"<?= $_up_req ? ' required' : '' ?> />
  </label>
  <div class="photo-filename" id="<?= $_up_id ?>-filename">Ningún archivo seleccionado</div>
</div>
<?php unset($_up_id, $_up_name, $_up_label, $_up_accept, $_up_alt, $_up_req, $uploadId, $uploadName, $uploadLabel, $uploadAccept, $uploadReq, $uploadAlt); ?>
