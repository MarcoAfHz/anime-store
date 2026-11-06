<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/includes/auth.php';

$redirect = $_GET['redirect'] ?? '';

if (esta_logueado()) {
    redirigir_url(url_retorno_segura($redirect));
}

$titulo_pagina    = 'Crear cuenta';
$meta_descripcion = 'Crea tu cuenta gratis en ShopPeruvian y compra ropa, figuras y accesorios anime.';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="sp-auth-page">
    <div class="sp-auth-card">
        <div class="sp-modal-logo">SHOP<span>ANIME</span></div>
        <p class="sp-auth-sub">Crea tu cuenta en menos de un minuto</p>

        <form action="<?= BASE_URL ?>/api/auth.php" method="POST" id="registroPageForm">
            <input type="hidden" name="accion" value="registro">
            <input type="hidden" name="redirect" value="<?= sanitizar($redirect) ?>">
            <?= csrf_campo() ?>
            <div class="sp-form-group">
                <label>Nombre completo</label>
                <input type="text" name="nombre" placeholder="Tu nombre" autocomplete="name" required minlength="2" autofocus>
            </div>
            <div class="sp-form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="tu@email.com" autocomplete="email" required>
            </div>
            <div class="sp-form-group">
                <label>Contraseña</label>
                <div class="sp-input-password">
                    <input type="password" name="password" id="registroPagePassword" placeholder="Mínimo 8 caracteres" autocomplete="new-password" required minlength="8">
                    <button type="button" onclick="togglePassword('registroPagePassword')">
                        <i class="bi bi-eye" id="registroPagePasswordIcon"></i>
                    </button>
                </div>
            </div>
            <label style="display:flex;gap:8px;align-items:flex-start;font-size:12px;color:var(--text-secondary);margin-bottom:16px;cursor:pointer">
                <input type="checkbox" required style="accent-color:var(--accent);margin-top:3px">
                <span>Acepto los <a href="<?= BASE_URL ?>/terminos" target="_blank" style="color:var(--accent)">términos y condiciones</a>
                y la <a href="<?= BASE_URL ?>/privacidad" target="_blank" style="color:var(--accent)">política de privacidad</a>.</span>
            </label>
            <button type="submit" class="sp-btn-primary w-100">Crear cuenta</button>
        </form>

        <div class="sp-auth-ventajas">
            <span><i class="bi bi-box-seam"></i>Sigue el estado de tus pedidos</span>
            <span><i class="bi bi-heart"></i>Guarda tus favoritos en la lista de deseos</span>
            <span><i class="bi bi-lightning"></i>Compra más rápido con tus datos guardados</span>
        </div>

        <p class="sp-auth-alt">
            ¿Ya tienes cuenta?
            <a href="<?= BASE_URL ?>/login<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>">Inicia sesión</a>
        </p>
    </div>
</div>

<script>
document.getElementById('registroPageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const btn  = form.querySelector('button[type=submit]');
    btn.disabled    = true;
    btn.textContent = 'Creando cuenta...';

    try {
        const res  = await fetch(form.action, { method: 'POST', body: new FormData(form) });
        const json = await res.json();
        if (json.success) {
            mostrarToast('¡Cuenta creada! Bienvenido.', 'success');
            setTimeout(() => location.href = json.redirect, 700);
            return;
        }
        mostrarError(form, json.error);
    } catch {
        mostrarError(form, 'Error de conexión. Inténtalo de nuevo.');
    }
    btn.disabled    = false;
    btn.textContent = 'Crear cuenta';
});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
