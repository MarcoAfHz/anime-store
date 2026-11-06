<?php
$titulo_pagina = 'Mi cuenta';
$css_extra     = 'cuenta';
require_once '../includes/header.php';
requiere_login();

$conn       = conectar();
$id_usuario = (int) $_SESSION['usuario']['id_usuario'];
$tab        = sanitizar($_GET['tab'] ?? 'perfil');

// Datos del usuario
$usuario = $conn->query("
    SELECT u.*, p.nombre AS provincia_nombre, p.id_provincia,
           d.nombre AS departamento_nombre, d.id_departamento
    FROM usuarios u
    LEFT JOIN provincias p ON u.id_provincia = p.id_provincia
    LEFT JOIN departamentos d ON p.id_departamento = d.id_departamento
    WHERE u.id_usuario = $id_usuario
")->fetch_assoc();

// Pedidos
$pedidos = $conn->query("
    SELECT p.*, c.codigo AS comprobante
    FROM pedidos p
    LEFT JOIN comprobantes c ON c.id_pedido = p.id_pedido
    WHERE p.id_usuario = $id_usuario
    ORDER BY p.fecha DESC
");

// Departamentos
$departamentos = $conn->query("SELECT * FROM departamentos ORDER BY nombre ASC");

// Wishlist
$wishlist = $conn->query("
    SELECT w.*, p.nombre, p.precio,
           (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen_principal,
           COALESCE(AVG(r.puntuacion), 0) AS puntuacion_media,
           COUNT(r.id_resena) AS total_resenas
    FROM wishlist w
    JOIN productos p ON w.id_producto = p.id_producto
    LEFT JOIN resenas r ON r.id_producto = p.id_producto
    WHERE w.id_usuario = $id_usuario AND p.activo = 1
    GROUP BY w.id_wishlist
    ORDER BY w.fecha_agregado DESC
");
?>

<div class="sp-container" style="padding-top:32px;padding-bottom:48px">

    <h1 class="sp-page-title">Mi cuenta</h1>

    <div class="sp-cuenta-layout">

        <!-- Sidebar -->
        <aside class="sp-cuenta-sidebar">
            <div class="sp-cuenta-avatar">
                <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
            </div>
            <p class="sp-cuenta-nombre"><?= sanitizar($usuario['nombre']) ?></p>
            <p class="sp-cuenta-email"><?= sanitizar($usuario['email']) ?></p>

            <nav class="sp-cuenta-nav">
                <a href="?tab=perfil" class="<?= $tab === 'perfil' ? 'active' : '' ?>">
                    <i class="bi bi-person"></i> Mi perfil
                </a>
                <a href="?tab=pedidos" class="<?= $tab === 'pedidos' ? 'active' : '' ?>">
                    <i class="bi bi-box"></i> Mis pedidos
                    <?php if ($pedidos->num_rows > 0): ?>
                    <span class="sp-cuenta-badge"><?= $pedidos->num_rows ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=wishlist" class="<?= $tab === 'wishlist' ? 'active' : '' ?>">
                    <i class="bi bi-heart"></i> Lista de deseos
                    <?php if ($wishlist->num_rows > 0): ?>
                    <span class="sp-cuenta-badge"><?= $wishlist->num_rows ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=password" class="<?= $tab === 'password' ? 'active' : '' ?>">
                    <i class="bi bi-lock"></i> Cambiar contraseña
                </a>
                <a href="<?= BASE_URL ?>/logout" class="sp-cuenta-logout">
                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                </a>
            </nav>
        </aside>

        <!-- Contenido -->
        <div class="sp-cuenta-content">

            <!-- ── PERFIL ──────────────────────────────────── -->
            <?php if ($tab === 'perfil'): ?>
            <div class="sp-checkout-bloque">
                <h3 class="sp-checkout-titulo"><i class="bi bi-person"></i> Mi perfil</h3>

                <form id="formPerfil">
                    <div class="sp-form-row">
                        <div class="sp-form-group">
                            <label>Nombre completo</label>
                            <input type="text" id="p_nombre" value="<?= sanitizar($usuario['nombre']) ?>" required>
                        </div>
                        <div class="sp-form-group">
                            <label>Teléfono</label>
                            <input type="tel" id="p_telefono" value="<?= sanitizar($usuario['telefono'] ?? '') ?>" placeholder="+51 999 999 999">
                        </div>
                    </div>

                    <div class="sp-form-group">
                        <label>Email</label>
                        <input type="email" value="<?= sanitizar($usuario['email']) ?>" disabled style="opacity:0.6">
                    </div>

                    <div class="sp-form-row">
                        <div class="sp-form-group">
                            <label>Departamento</label>
                            <select id="p_departamento" onchange="cargarProvincias('p_departamento', 'p_provincia')">
                                <option value="">Selecciona departamento</option>
                                <?php $departamentos->data_seek(0); while ($dep = $departamentos->fetch_assoc()): ?>
                                <option value="<?= $dep['id_departamento'] ?>"
                                        <?= ($usuario['id_departamento'] ?? '') == $dep['id_departamento'] ? 'selected' : '' ?>>
                                    <?= sanitizar($dep['nombre']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="sp-form-group">
                            <label>Provincia</label>
                            <select id="p_provincia">
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
                        <label>Dirección</label>
                        <input type="text" id="p_direccion" value="<?= sanitizar($usuario['direccion'] ?? '') ?>" placeholder="Calle, número, referencia...">
                    </div>

                    <button type="button" class="sp-btn-primary" onclick="guardarPerfil()">
                        <i class="bi bi-check-circle"></i> Guardar cambios
                    </button>
                </form>
            </div>

            <!-- ── PEDIDOS ─────────────────────────────────── -->
            <?php elseif ($tab === 'pedidos'): ?>
            <div class="sp-checkout-bloque">
                <h3 class="sp-checkout-titulo"><i class="bi bi-box"></i> Mis pedidos</h3>

                <?php if ($pedidos->num_rows === 0): ?>
                <div class="sp-sin-resultados" style="padding:40px">
                    <i class="bi bi-box"></i>
                    <h3>Aún no tienes pedidos</h3>
                    <p>¡Empieza a comprar en nuestra tienda!</p>
                    <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-primary">Ver catálogo</a>
                </div>
                <?php else: ?>
                <div class="sp-pedidos-lista">
                    <?php while ($pedido = $pedidos->fetch_assoc()): ?>
                    <div class="sp-pedido-card" onclick="togglePedido(<?= $pedido['id_pedido'] ?>)">
                        <div class="sp-pedido-header">
                            <div>
                                <span class="sp-pedido-codigo"><?= sanitizar($pedido['comprobante'] ?? '#'.$pedido['id_pedido']) ?></span>
                                <span class="sp-pedido-fecha"><?= formatear_fecha($pedido['fecha']) ?></span>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px">
                                <span class="sp-estado sp-estado-<?= $pedido['estado'] ?>">
                                    <?= ucfirst($pedido['estado']) ?>
                                </span>
                                <span class="sp-pedido-total"><?= formatear_precio($pedido['total']) ?></span>
                                <i class="bi bi-chevron-down sp-pedido-chevron" id="chevron-<?= $pedido['id_pedido'] ?>"></i>
                            </div>
                        </div>

                        <!-- Detalle desplegable -->
                        <div class="sp-pedido-detalle" id="detalle-<?= $pedido['id_pedido'] ?>" style="display:none">
                            <?php
                            $det = $conn->query("
                                SELECT dp.*, p.nombre,
                                       (SELECT url FROM imagenes_producto WHERE id_producto = dp.id_producto AND principal = 1 LIMIT 1) AS imagen
                                FROM detalle_pedido dp
                                JOIN productos p ON dp.id_producto = p.id_producto
                                WHERE dp.id_pedido = {$pedido['id_pedido']}
                            ");
                            while ($d = $det->fetch_assoc()):
                            ?>
                            <div class="sp-checkout-item">
                                <div class="sp-checkout-item-img">
                                    <img src="<?= imagen_producto($d['imagen']) ?>" alt="<?= sanitizar($d['nombre']) ?>">
                                    <span class="sp-checkout-qty"><?= $d['cantidad'] ?></span>
                                </div>
                                <div class="sp-checkout-item-info">
                                    <p><?= sanitizar($d['nombre']) ?></p>
                                    <?php if (!empty($d['talla'])): ?>
                                    <small>Talla: <?= sanitizar($d['talla']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <span class="sp-checkout-item-precio"><?= formatear_precio($d['precio_unit'] * $d['cantidad']) ?></span>
                            </div>
                            <?php endwhile; ?>

                            <div class="sp-pedido-info">
                                <span><i class="bi bi-<?= $pedido['metodo_pago'] === 'yape' ? 'qr-code' : 'cash' ?>"></i>
                                    <?= $pedido['metodo_pago'] === 'yape' ? 'Yape QR' : 'Efectivo' ?>
                                </span>
                                <span><i class="bi bi-geo-alt"></i> <?= sanitizar($pedido['dir_envio']) ?></span>
                                <a href="<?= BASE_URL ?>/pedido-confirmado?id=<?= $pedido['id_pedido'] ?>" onclick="event.stopPropagation()" style="color:var(--accent);font-weight:600">
                                    <i class="bi bi-receipt"></i> Ver pedido<?= $pedido['metodo_pago'] === 'yape' && $pedido['estado'] === 'pendiente' ? ' / subir pago Yape' : '' ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── WISHLIST ────────────────────────────────── -->
            <?php elseif ($tab === 'wishlist'): ?>
            <div class="sp-checkout-bloque">
                <h3 class="sp-checkout-titulo"><i class="bi bi-heart"></i> Lista de deseos</h3>

                <?php if ($wishlist->num_rows === 0): ?>
                <div class="sp-sin-resultados" style="padding:40px">
                    <i class="bi bi-heart"></i>
                    <h3>Tu lista de deseos está vacía</h3>
                    <p>Guarda productos para comprarlos después.</p>
                    <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-primary">Ver catálogo</a>
                </div>
                <?php else: ?>
                <div class="sp-productos-grid" style="grid-template-columns:repeat(3,1fr)">
                    <?php while ($prod = $wishlist->fetch_assoc()): ?>
                        <?php include ROOT_PATH . '/includes/card_producto.php'; ?>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── CONTRASEÑA ──────────────────────────────── -->
            <?php elseif ($tab === 'password'): ?>
            <div class="sp-checkout-bloque">
                <h3 class="sp-checkout-titulo"><i class="bi bi-lock"></i> Cambiar contraseña</h3>

                <div class="sp-form-group">
                    <label>Contraseña actual</label>
                    <input type="password" id="pass_actual" placeholder="••••••••">
                </div>
                <div class="sp-form-group">
                    <label>Nueva contraseña</label>
                    <input type="password" id="pass_nueva" placeholder="Mínimo 8 caracteres" minlength="8">
                </div>
                <div class="sp-form-group">
                    <label>Confirmar nueva contraseña</label>
                    <input type="password" id="pass_confirmar" placeholder="Repite la nueva contraseña">
                </div>
                <button type="button" class="sp-btn-primary" onclick="cambiarPassword()">
                    <i class="bi bi-check-circle"></i> Cambiar contraseña
                </button>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// BASE_URL ya la define assets/js/carrito.js

// Toggle pedido
function togglePedido(id) {
    const det  = document.getElementById('detalle-' + id);
    const chev = document.getElementById('chevron-' + id);
    const open = det.style.display !== 'none';
    det.style.display  = open ? 'none' : 'block';
    chev.style.transform = open ? '' : 'rotate(180deg)';
}

// Cargar provincias
async function cargarProvincias(depId, provId) {
    const depSel = document.getElementById(depId);
    const provSel = document.getElementById(provId);
    const id = depSel.value;
    provSel.innerHTML = '<option value="">Cargando...</option>';
    if (!id) { provSel.innerHTML = '<option value="">Selecciona provincia</option>'; return; }
    const res  = await fetch(`${BASE_URL}/api/productos.php?accion=provincias&id_departamento=${id}`);
    const data = await res.json();
    provSel.innerHTML = '<option value="">Selecciona provincia</option>';
    data.forEach(p => provSel.innerHTML += `<option value="${p.id_provincia}">${p.nombre}</option>`);
}

// Guardar perfil
async function guardarPerfil() {
    const data = new FormData();
    data.append('accion',       'actualizar_perfil');
    data.append('nombre',       document.getElementById('p_nombre').value);
    data.append('telefono',     document.getElementById('p_telefono').value);
    data.append('id_provincia', document.getElementById('p_provincia').value);
    data.append('direccion',    document.getElementById('p_direccion').value);

    const res  = await fetch(`${BASE_URL}/api/cuenta.php`, { method: 'POST', body: data });
    const json = await res.json();
    json.success ? mostrarToast('Perfil actualizado', 'success') : mostrarToast(json.error, 'error');
}

// Cambiar contraseña
async function cambiarPassword() {
    const actual    = document.getElementById('pass_actual').value;
    const nueva     = document.getElementById('pass_nueva').value;
    const confirmar = document.getElementById('pass_confirmar').value;

    if (nueva !== confirmar) { mostrarToast('Las contraseñas no coinciden', 'error'); return; }
    if (nueva.length < 8)    { mostrarToast('Mínimo 8 caracteres', 'error'); return; }

    const data = new FormData();
    data.append('accion',           'cambiar_password');
    data.append('password_actual',  actual);
    data.append('password_nueva',   nueva);

    const res  = await fetch(`${BASE_URL}/api/cuenta.php`, { method: 'POST', body: data });
    const json = await res.json();
    if (json.success) {
        mostrarToast('Contraseña actualizada', 'success');
        document.getElementById('pass_actual').value = '';
        document.getElementById('pass_nueva').value  = '';
        document.getElementById('pass_confirmar').value = '';
    } else {
        mostrarToast(json.error, 'error');
    }
}
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>