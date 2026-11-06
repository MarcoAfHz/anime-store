// ── MODAL ──────────────────────────────────────────────────────

function abrirModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function cerrarModalOverlay(event, id) {
    if (event.target.id === id) {
        cerrarModal(id);
    }
}

// Cerrar modal con ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.sp-modal-overlay.active')
            .forEach(m => cerrarModal(m.id));
    }
});

// ── TABS LOGIN / REGISTRO ──────────────────────────────────────

function cambiarTab(tab) {
    const formLogin    = document.getElementById('formLogin');
    const formRegistro = document.getElementById('formRegistro');
    const tabLogin     = document.getElementById('tabLogin');
    const tabRegistro  = document.getElementById('tabRegistro');

    if (tab === 'login') {
        formLogin.style.display    = 'block';
        formRegistro.style.display = 'none';
        tabLogin.classList.add('active');
        tabRegistro.classList.remove('active');
    } else {
        formLogin.style.display    = 'none';
        formRegistro.style.display = 'block';
        tabLogin.classList.remove('active');
        tabRegistro.classList.add('active');
    }

    // Abrir modal si no está abierto
    abrirModal('modalLogin');
}

// ── TOGGLE PASSWORD ────────────────────────────────────────────

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(inputId + 'Icon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// ── MENÚ HAMBURGUESA ───────────────────────────────────────────

function toggleMenu() {
    const menu    = document.getElementById('mobileMenu');
    const overlay = document.getElementById('drawerOverlay');
    const btn     = document.getElementById('btnHamburger');

    menu.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';

    const icon = btn.querySelector('i');
    icon.classList.toggle('bi-list');
    icon.classList.toggle('bi-x');
}

// Cerrar menú al hacer click fuera
document.addEventListener('click', (e) => {
    const menu = document.getElementById('mobileMenu');
    const btn  = document.getElementById('btnHamburger');
    if (menu && !menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.remove('active');
        const icon = btn.querySelector('i');
        icon.classList.add('bi-list');
        icon.classList.remove('bi-x');
    }
});

// ── DROPDOWN ───────────────────────────────────────────────────

document.querySelectorAll('.dropdown').forEach(drop => {
    drop.addEventListener('mouseenter', () => {
        drop.querySelector('.sp-dropdown')?.classList.add('visible');
    });
    drop.addEventListener('mouseleave', () => {
        drop.querySelector('.sp-dropdown')?.classList.remove('visible');
    });
});

// ── FLASH MESSAGE AUTO CERRAR ──────────────────────────────────

const flash = document.getElementById('flashMessage');
if (flash) {
    setTimeout(() => flash.remove(), 4000);
}

// ── FORMULARIOS VIA AJAX ───────────────────────────────────────

// Login
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn  = loginForm.querySelector('button[type=submit]');
        const data = new FormData(loginForm);

        btn.disabled    = true;
        btn.textContent = 'Iniciando sesión...';

        try {
            const res  = await fetch(loginForm.action, { method: 'POST', body: data });
            const json = await res.json();

            if (json.success) {
                cerrarModal('modalLogin');
                mostrarToast('¡Bienvenido!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                mostrarError(loginForm, json.error);
                btn.disabled    = false;
                btn.textContent = 'Iniciar sesión';
            }
        } catch {
            mostrarError(loginForm, 'Error de conexión. Inténtalo de nuevo.');
            btn.disabled    = false;
            btn.textContent = 'Iniciar sesión';
        }
    });
}

// Registro
const registroForm = document.getElementById('registroForm');
if (registroForm) {
    registroForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn  = registroForm.querySelector('button[type=submit]');
        const data = new FormData(registroForm);

        btn.disabled    = true;
        btn.textContent = 'Creando cuenta...';

        try {
            const res  = await fetch(registroForm.action, { method: 'POST', body: data });
            const json = await res.json();

            if (json.success) {
                cerrarModal('modalLogin');
                mostrarToast('¡Cuenta creada! Bienvenido.', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                mostrarError(registroForm, json.error);
                btn.disabled    = false;
                btn.textContent = 'Crear cuenta';
            }
        } catch {
            mostrarError(registroForm, 'Error de conexión. Inténtalo de nuevo.');
            btn.disabled    = false;
            btn.textContent = 'Crear cuenta';
        }
    });
}

// ── HELPERS ────────────────────────────────────────────────────

function mostrarError(form, mensaje) {
    let error = form.querySelector('.sp-form-error');
    if (!error) {
        error = document.createElement('p');
        error.className = 'sp-form-error';
        form.prepend(error);
    }
    error.textContent = mensaje;
}

function mostrarToast(mensaje, tipo = 'success') {
    const toast = document.createElement('div');
    toast.className = `sp-toast sp-toast-${tipo}`;
    toast.textContent = mensaje;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('visible'), 10);
    setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function actualizarContadorCarrito(total) {
    const badge = document.querySelector('.sp-badge-count');
    if (total > 0) {
        if (badge) {
            badge.textContent = total;
        } else {
            const icon = document.querySelector('.sp-carrito-icon');
            if (icon) {
                const span = document.createElement('span');
                span.className   = 'sp-badge-count';
                span.textContent = total;
                icon.appendChild(span);
            }
        }
    } else {
        badge?.remove();
    }
}