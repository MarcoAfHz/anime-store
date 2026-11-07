<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/includes/auth.php';

$redirect = $_GET['redirect'] ?? '';

// Si ya tiene sesión, no tiene sentido mostrar el login
if (esta_logueado()) {
    redirigir_url(url_retorno_segura($redirect));
}

$titulo_pagina    = 'Iniciar sesión';
$meta_descripcion = 'Accede a tu cuenta de Anime Store para ver tus pedidos y tu lista de deseos.';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="sp-auth-page">
    <div class="sp-auth-card">
        <div class="sp-modal-logo">ANIME<span>STORE</span></div>
        <p class="sp-auth-sub">Inicia sesión para continuar con tu compra</p>

        <form action="<?= BASE_URL ?>/api/auth.php" method="POST" id="loginPageForm">
            <input type="hidden" name="accion" value="login">
            <input type="hidden" name="redirect" value="<?= sanitizar($redirect) ?>">
            <?= csrf_campo() ?>
            <div class="sp-form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="tu@email.com" autocomplete="email" required autofocus>
            </div>
            <div class="sp-form-group">
                <label>Contraseña</label>
                <div class="sp-input-password">
                    <input type="password" name="password" id="loginPagePassword" placeholder="••••••••" autocomplete="current-password" required>
                    <button type="button" onclick="togglePassword('loginPagePassword')">
                        <i class="bi bi-eye" id="loginPagePasswordIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="sp-btn-primary w-100">Iniciar sesión</button>
        </form>

        <p class="sp-auth-alt">
            ¿No tienes cuenta?
            <a href="<?= BASE_URL ?>/registro<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>">Regístrate gratis</a>
        </p>
    </div>
</div>

<script>
document.getElementById('loginPageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const btn  = form.querySelector('button[type=submit]');
    btn.disabled    = true;
    btn.textContent = 'Iniciando sesión...';

    try {
        const res  = await fetch(form.action, { method: 'POST', body: new FormData(form) });
        const json = await res.json();
        if (json.success) {
            mostrarToast('¡Bienvenido, ' + json.nombre + '!', 'success');
            setTimeout(() => location.href = json.redirect, 600);
            return;
        }
        mostrarError(form, json.error);
    } catch {
        mostrarError(form, 'Error de conexión. Inténtalo de nuevo.');
    }
    btn.disabled    = false;
    btn.textContent = 'Iniciar sesión';
});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
