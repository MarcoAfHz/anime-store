// ── CARRITO ────────────────────────────────────────────────────

const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '';

// ── AÑADIR AL CARRITO ──────────────────────────────────────────

async function agregarAlCarrito(id_producto, cantidad = 1, talla = null) {
    const data = new FormData();
    data.append('accion',      'agregar');
    data.append('id_producto', id_producto);
    data.append('cantidad',    cantidad);
    if (talla) data.append('talla', talla);

    try {
        const res  = await fetch(`${BASE_URL}/api/carrito.php`, { method: 'POST', body: data });
        const json = await res.json();

        if (json.success) {
            actualizarContadorCarrito(json.total_items);
            mostrarToast('Producto añadido al carrito', 'success');
            animarIconoCarrito();
        } else {
            mostrarToast(json.error || 'Error al añadir al carrito', 'error');
        }
    } catch {
        mostrarToast('Error de conexión', 'error');
    }
}

// ── QUITAR DEL CARRITO ─────────────────────────────────────────

async function quitarDelCarrito(key) {
    const data = new FormData();
    data.append('accion', 'quitar');
    data.append('key',    key);

    try {
        const res  = await fetch(`${BASE_URL}/api/carrito.php`, { method: 'POST', body: data });
        const json = await res.json();

        if (json.success) {
            actualizarContadorCarrito(json.total_items);
            const fila = document.getElementById('fila-' + key);
            if (fila) {
                fila.style.opacity  = '0';
                fila.style.transform = 'translateX(-20px)';
                fila.style.transition = 'all 0.3s ease';
                setTimeout(() => {
                    fila.remove();
                    actualizarTotalesCarrito(json.subtotal, json.total, json.descuento);
                    verificarCarritoVacio();
                }, 300);
            }
        } else {
            mostrarToast(json.error || 'Error al quitar producto', 'error');
        }
    } catch {
        mostrarToast('Error de conexión', 'error');
    }
}

// ── ACTUALIZAR CANTIDAD ────────────────────────────────────────

async function actualizarCantidad(key, cantidad) {
    if (cantidad < 1) {
        quitarDelCarrito(key);
        return;
    }

    const data = new FormData();
    data.append('accion',   'actualizar');
    data.append('key',      key);
    data.append('cantidad', cantidad);

    try {
        const res  = await fetch(`${BASE_URL}/api/carrito.php`, { method: 'POST', body: data });
        const json = await res.json();

        if (json.success) {
            actualizarContadorCarrito(json.total_items);
            actualizarTotalesCarrito(json.subtotal, json.total, json.descuento);

            // Actualizar subtotal de la fila
            const subtotalFila = document.getElementById('subtotal-' + key);
            if (subtotalFila) subtotalFila.textContent = 'S/ ' + parseFloat(json.subtotal_item).toFixed(2);
        } else {
            mostrarToast(json.error || 'Error al actualizar', 'error');
        }
    } catch {
        mostrarToast('Error de conexión', 'error');
    }
}

// ── VACIAR CARRITO ─────────────────────────────────────────────

async function vaciarCarrito() {
    if (!confirm('¿Seguro que quieres vaciar el carrito?')) return;

    const data = new FormData();
    data.append('accion', 'vaciar');

    try {
        const res  = await fetch(`${BASE_URL}/api/carrito.php`, { method: 'POST', body: data });
        const json = await res.json();

        if (json.success) {
            actualizarContadorCarrito(0);
            verificarCarritoVacio(true);
        }
    } catch {
        mostrarToast('Error de conexión', 'error');
    }
}

// ── CUPÓN ──────────────────────────────────────────────────────

async function aplicarCupon() {
    const input  = document.getElementById('inputCupon');
    const codigo = input?.value.trim();

    if (!codigo) {
        mostrarToast('Ingresa un código de cupón', 'error');
        return;
    }

    const data = new FormData();
    data.append('accion', 'cupon');
    data.append('codigo', codigo);

    const btnCupon = document.getElementById('btnCupon');
    if (btnCupon) {
        btnCupon.disabled    = true;
        btnCupon.textContent = 'Verificando...';
    }

    try {
        const res  = await fetch(`${BASE_URL}/api/carrito.php`, { method: 'POST', body: data });
        const json = await res.json();

        if (json.success) {
            mostrarToast('Cupón aplicado: -S/ ' + parseFloat(json.descuento).toFixed(2), 'success');
            actualizarTotalesCarrito(json.subtotal, json.total, json.descuento);

            // Mostrar fila de descuento
            const filaDescuento = document.getElementById('filaDescuento');
            if (filaDescuento) {
                filaDescuento.style.display = 'flex';
                document.getElementById('montoDescuento').textContent = '-S/ ' + parseFloat(json.descuento).toFixed(2);
            }

            input.disabled = true;
            if (btnCupon) {
                btnCupon.textContent = '✓ Aplicado';
                btnCupon.disabled    = true;
            }
        } else {
            mostrarToast(json.error || 'Cupón no válido', 'error');
            if (btnCupon) {
                btnCupon.disabled    = false;
                btnCupon.textContent = 'Aplicar';
            }
        }
    } catch {
        mostrarToast('Error de conexión', 'error');
        if (btnCupon) {
            btnCupon.disabled    = false;
            btnCupon.textContent = 'Aplicar';
        }
    }
}

// ── WISHLIST ───────────────────────────────────────────────────

async function toggleWishlist(id_producto, btn) {
    const data = new FormData();
    data.append('id_producto', id_producto);

    try {
        const res  = await fetch(`${BASE_URL}/api/wishlist.php`, { method: 'POST', body: data });
        const json = await res.json();

        if (json.success) {
            const icon = btn.querySelector('i');
            if (json.accion === 'agregado') {
                icon.classList.replace('bi-heart', 'bi-heart-fill');
                btn.classList.add('sp-wish-active');
                mostrarToast('Añadido a tu lista de deseos', 'success');
            } else {
                icon.classList.replace('bi-heart-fill', 'bi-heart');
                btn.classList.remove('sp-wish-active');
                mostrarToast('Quitado de tu lista de deseos', 'success');
            }
        } else if (json.login_required) {
            abrirModal('modalLogin');
        } else {
            mostrarToast(json.error || 'Error al actualizar wishlist', 'error');
        }
    } catch {
        mostrarToast('Error de conexión', 'error');
    }
}

// ── HELPERS ────────────────────────────────────────────────────

function actualizarTotalesCarrito(subtotal, total, descuento = 0) {
    const elSubtotal  = document.getElementById('carritoSubtotal');
    const elTotal     = document.getElementById('carritoTotal');
    const elDescuento = document.getElementById('carritoDescuento');

    if (elSubtotal)  elSubtotal.textContent  = 'S/ ' + parseFloat(subtotal).toFixed(2);
    if (elTotal)     elTotal.textContent      = 'S/ ' + parseFloat(total).toFixed(2);
    if (elDescuento) elDescuento.textContent  = '-S/ ' + parseFloat(descuento).toFixed(2);

    // Fila de descuento del cupón (se recalcula al cambiar el carrito)
    const filaDescuento  = document.getElementById('filaDescuento');
    const montoDescuento = document.getElementById('montoDescuento');
    if (filaDescuento && montoDescuento) {
        const d = parseFloat(descuento) || 0;
        filaDescuento.style.display = d > 0 ? 'flex' : 'none';
        montoDescuento.textContent  = '-S/ ' + d.toFixed(2);
    }
}

function verificarCarritoVacio(forzar = false) {
    const filas = document.querySelectorAll('.carrito-fila');
    const carritoVacio   = document.getElementById('carritoVacio');
    const carritoContenido = document.getElementById('carritoContenido');

    if (forzar || filas.length === 0) {
        if (carritoVacio)    carritoVacio.style.display    = 'block';
        if (carritoContenido) carritoContenido.style.display = 'none';
    }
}

function animarIconoCarrito() {
    const icon = document.querySelector('.sp-carrito-icon');
    if (icon) {
        icon.classList.add('sp-carrito-bounce');
        setTimeout(() => icon.classList.remove('sp-carrito-bounce'), 600);
    }
}

// ── CONTROLES DE CANTIDAD ──────────────────────────────────────

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-cantidad-mas')) {
        const key   = e.target.dataset.key;
        const input = document.getElementById('cantidad-' + key);
        if (input) {
            const nueva = parseInt(input.value) + 1;
            input.value = nueva;
            actualizarCantidad(key, nueva);
        }
    }

    if (e.target.classList.contains('btn-cantidad-menos')) {
        const key   = e.target.dataset.key;
        const input = document.getElementById('cantidad-' + key);
        if (input) {
            const nueva = parseInt(input.value) - 1;
            input.value = nueva;
            actualizarCantidad(key, nueva);
        }
    }
});