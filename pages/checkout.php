<?php
$titulo_pagina = 'Finalizar compra';
$css_extra     = 'carrito';
require_once '../includes/header.php';
requiere_login();

$conn    = conectar();
$carrito = carrito_obtener();

if (empty($carrito)) redirigir('carrito');

$items    = [];
$subtotal = 0;

foreach ($carrito as $key => $item) {
    $id   = (int) $item['id_producto'];
    $prod = $conn->query("
        SELECT p.*,
               (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen_principal
        FROM productos p
        WHERE p.id_producto = $id AND p.activo = 1
    ")->fetch_assoc();

    if ($prod) {
        $prod['key']      = $key;
        $prod['cantidad'] = $item['cantidad'];
        $prod['talla']    = $item['talla'];
        $prod['subtotal'] = $prod['precio'] * $item['cantidad'];
        $subtotal        += $prod['subtotal'];
        $items[]          = $prod;
    }
}

$descuento   = descuento_cupon_actual($subtotal);
$id_usuario  = (int) $_SESSION['usuario']['id_usuario'];

// Datos del usuario
$usuario = $conn->query("
    SELECT u.*, p.nombre AS provincia_nombre, d.nombre AS departamento_nombre, d.costo_envio
    FROM usuarios u
    LEFT JOIN provincias p ON u.id_provincia = p.id_provincia
    LEFT JOIN departamentos d ON p.id_departamento = d.id_departamento
    WHERE u.id_usuario = $id_usuario
")->fetch_assoc();

$costo_envio = (float) ($usuario['costo_envio'] ?? 0);
$total       = max(0, $subtotal + $costo_envio - $descuento);

// Departamentos y provincias para el formulario
$departamentos = $conn->query("SELECT * FROM departamentos ORDER BY nombre ASC");
?>

<div class="sp-container" style="padding-top:32px;padding-bottom:48px">

    <nav class="sp-breadcrumb">
        <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span>
        <a href="<?= BASE_URL ?>/carrito">Carrito</a> <span>/</span>
        <span>Finalizar compra</span>
    </nav>

    <h1 class="sp-page-title">Finalizar compra</h1>

    <div class="sp-checkout-layout">

        <!-- ── FORMULARIO ──────────────────────────────────── -->
        <div class="sp-checkout-form">

            <!-- Datos de envío -->
            <div class="sp-checkout-bloque">
                <h3 class="sp-checkout-titulo">
                    <i class="bi bi-geo-alt"></i> Datos de envío
                </h3>

                <div class="sp-form-row">
                    <div class="sp-form-group">
                        <label>Nombre completo</label>
                        <input type="text" id="nombre" value="<?= sanitizar($usuario['nombre']) ?>" required>
                    </div>
                    <div class="sp-form-group">
                        <label>Teléfono</label>
                        <input type="tel" id="telefono" value="<?= sanitizar($usuario['telefono'] ?? '') ?>" placeholder="+51 999 999 999" required>
                    </div>
                </div>

                <div class="sp-form-row">
                    <div class="sp-form-group">
                        <label>Departamento</label>
                        <select id="selectDepartamento" onchange="cargarProvincias()">
                            <option value="">Selecciona departamento</option>
                            <?php while ($dep = $departamentos->fetch_assoc()): ?>
                            <option value="<?= $dep['id_departamento'] ?>"
                                    data-envio="<?= $dep['costo_envio'] ?>"
                                    <?= ($usuario['departamento_nombre'] ?? '') === $dep['nombre'] ? 'selected' : '' ?>>
                                <?= sanitizar($dep['nombre']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="sp-form-group">
                        <label>Provincia</label>
                        <select id="selectProvincia" onchange="actualizarEnvio()">
                            <option value="">Selecciona provincia</option>
                            <?php if (!empty($usuario['id_provincia'])): ?>
                            <option value="<?= $usuario['id_provincia'] ?>" selected>
                                <?= sanitizar($usuario['provincia_nombre']) ?>
                            </option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="sp-form-group">
                    <label>Dirección completa</label>
                    <input type="text" id="direccion" value="<?= sanitizar($usuario['direccion'] ?? '') ?>"
                           placeholder="Calle, número, referencia..." required>
                </div>

                <div class="sp-form-group">
                    <label>Notas del pedido <span style="color:var(--text-muted)">(opcional)</span></label>
                    <textarea id="notas" rows="3" placeholder="Instrucciones especiales para la entrega..."></textarea>
                </div>
            </div>

            <!-- Método de pago -->
            <div class="sp-checkout-bloque">
                <h3 class="sp-checkout-titulo">
                    <i class="bi bi-credit-card"></i> Método de pago
                </h3>

                <div class="sp-pago-opciones">
                    <label class="sp-pago-opcion active" id="opcionYape">
                        <input type="radio" name="metodo_pago" value="yape" checked>
                        <div class="sp-pago-opcion-inner">
                            <i class="bi bi-qr-code"></i>
                            <div>
                                <strong>Yape QR</strong>
                                <span>Escanea el QR y paga al instante</span>
                            </div>
                        </div>
                    </label>

                    <label class="sp-pago-opcion" id="opcionEfectivo">
                        <input type="radio" name="metodo_pago" value="efectivo">
                        <div class="sp-pago-opcion-inner">
                            <i class="bi bi-cash"></i>
                            <div>
                                <strong>Efectivo</strong>
                                <span>Paga en efectivo al recibir tu pedido</span>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Info Yape -->
                <div id="infoYape" class="sp-yape-info">
                    <i class="bi bi-info-circle"></i>
                    Al confirmar el pedido recibirás el QR de Yape para completar el pago.
                    Tu pedido se procesará una vez confirmemos el pago.
                </div>

                <!-- Info Efectivo -->
                <div id="infoEfectivo" class="sp-yape-info" style="display:none">
                    <i class="bi bi-info-circle"></i>
                    Pagarás en efectivo cuando recibas tu pedido. Ten el monto exacto listo.
                </div>
            </div>

        </div>

        <!-- ── RESUMEN ─────────────────────────────────────── -->
        <div class="sp-checkout-resumen">
            <h3 class="sp-resumen-titulo">Tu pedido</h3>

            <!-- Productos -->
            <div class="sp-checkout-productos">
                <?php foreach ($items as $item): ?>
                <div class="sp-checkout-item">
                    <div class="sp-checkout-item-img">
                        <img src="<?= imagen_producto($item['imagen_principal']) ?>"
                             alt="<?= sanitizar($item['nombre']) ?>">
                        <span class="sp-checkout-qty"><?= $item['cantidad'] ?></span>
                    </div>
                    <div class="sp-checkout-item-info">
                        <p><?= sanitizar($item['nombre']) ?></p>
                        <?php if (!empty($item['talla'])): ?>
                        <small>Talla: <?= sanitizar($item['talla']) ?></small>
                        <?php endif; ?>
                    </div>
                    <span class="sp-checkout-item-precio"><?= formatear_precio($item['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="sp-resumen-divider"></div>

            <div class="sp-resumen-linea">
                <span>Subtotal</span>
                <span><?= formatear_precio($subtotal) ?></span>
            </div>

            <div class="sp-resumen-linea">
                <span>Envío</span>
                <span id="costoEnvio">
                    <?= $costo_envio > 0 ? formatear_precio($costo_envio) : '<span style="color:var(--success)">A calcular</span>' ?>
                </span>
            </div>

            <?php if ($descuento > 0): ?>
            <div class="sp-resumen-linea sp-resumen-descuento">
                <span>Descuento</span>
                <span>-<?= formatear_precio($descuento) ?></span>
            </div>
            <?php endif; ?>

            <div class="sp-resumen-divider"></div>

            <div class="sp-resumen-linea sp-resumen-total">
                <span>Total</span>
                <span id="totalFinal"><?= formatear_precio($total) ?></span>
            </div>

            <button class="sp-btn-primary w-100 sp-btn-checkout" onclick="confirmarPedido()">
                <i class="bi bi-check-circle"></i> Confirmar pedido
            </button>

            <a href="<?= BASE_URL ?>/carrito" class="sp-btn-limpiar w-100" style="text-align:center;margin-top:8px">
                <i class="bi bi-arrow-left"></i> Volver al carrito
            </a>
        </div>

    </div>
</div>

<script>
// BASE_URL ya la define assets/js/carrito.js
const subtotal  = <?= $subtotal ?>;
const descuento = <?= $descuento ?>;

// Método de pago
document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.sp-pago-opcion').forEach(o => o.classList.remove('active'));
        radio.closest('.sp-pago-opcion').classList.add('active');
        document.getElementById('infoYape').style.display     = radio.value === 'yape'     ? 'flex' : 'none';
        document.getElementById('infoEfectivo').style.display = radio.value === 'efectivo' ? 'flex' : 'none';
    });
});

// Cargar provincias
async function cargarProvincias() {
    const depId = document.getElementById('selectDepartamento').value;
    const sel   = document.getElementById('selectProvincia');
    sel.innerHTML = '<option value="">Cargando...</option>';

    if (!depId) { sel.innerHTML = '<option value="">Selecciona provincia</option>'; return; }

    const res  = await fetch(`${BASE_URL}/api/productos.php?accion=provincias&id_departamento=${depId}`);
    const data = await res.json();

    sel.innerHTML = '<option value="">Selecciona provincia</option>';
    data.forEach(p => {
        sel.innerHTML += `<option value="${p.id_provincia}">${p.nombre}</option>`;
    });

    actualizarEnvio();
}

// Actualizar costo de envío
function actualizarEnvio() {
    const depSelect = document.getElementById('selectDepartamento');
    const selOption = depSelect.options[depSelect.selectedIndex];
    const envio     = parseFloat(selOption?.dataset.envio || 0);
    const total     = Math.max(0, subtotal + envio - descuento);

    document.getElementById('costoEnvio').innerHTML = envio > 0
        ? `S/ ${envio.toFixed(2)}`
        : '<span style="color:var(--success)">Gratis</span>';

    document.getElementById('totalFinal').textContent = `S/ ${total.toFixed(2)}`;
}

// Confirmar pedido
async function confirmarPedido() {
    const nombre     = document.getElementById('nombre').value.trim();
    const telefono   = document.getElementById('telefono').value.trim();
    const direccion  = document.getElementById('direccion').value.trim();
    const provincia  = document.getElementById('selectProvincia').value;
    const metodo     = document.querySelector('input[name="metodo_pago"]:checked').value;
    const notas      = document.getElementById('notas').value.trim();

    if (!nombre || !telefono || !direccion || !provincia) {
        mostrarToast('Completa todos los datos de envío', 'error');
        return;
    }

    const btn = document.querySelector('.sp-btn-checkout');
    btn.disabled    = true;
    btn.innerHTML   = '<i class="bi bi-hourglass-split"></i> Procesando...';

    const data = new FormData();
    data.append('nombre',      nombre);
    data.append('telefono',    telefono);
    data.append('direccion',   direccion);
    data.append('id_provincia', provincia);
    data.append('metodo_pago', metodo);
    data.append('notas',       notas);

    try {
        const res  = await fetch(`${BASE_URL}/api/checkout.php`, { method: 'POST', body: data });
        const json = await res.json();

        if (json.success) {
            window.location.href = `${BASE_URL}/pedido-confirmado?id=${json.id_pedido}`;
        } else {
            mostrarToast(json.error || 'Error al procesar el pedido', 'error');
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirmar pedido';
        }
    } catch {
        mostrarToast('Error de conexión', 'error');
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirmar pedido';
    }
}
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>