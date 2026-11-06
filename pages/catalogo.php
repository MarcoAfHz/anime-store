<?php
$titulo_pagina    = 'Catálogo';
$meta_descripcion = 'Explora nuestro catálogo de importaciones anime y urban style. Ropa, figuras y accesorios exclusivos.';
$css_extra        = 'catalogo';
require_once '../includes/header.php';

$conn = conectar();

// ── FILTROS ────────────────────────────────────────────────────
$where      = ['p.activo = 1'];
$categoria  = (int)    ($_GET['categoria']  ?? 0);
$q          = sanitizar($_GET['q']          ?? '');
$talla      = sanitizar($_GET['talla']      ?? '');
$precio_min = (float)  ($_GET['precio_min'] ?? 0);
$precio_max = (float)  ($_GET['precio_max'] ?? 9999);
$orden      = sanitizar($_GET['orden']      ?? 'reciente');
$oferta     = (int)    ($_GET['oferta']     ?? 0);
$destacado  = (int)    ($_GET['destacado']  ?? 0);
$pagina     = max(1, (int) ($_GET['pagina'] ?? 1));

if ($categoria > 0)      $where[] = "p.id_categoria = $categoria";
if (!empty($q))          $where[] = "p.nombre LIKE '%" . escapar($q) . "%'";
if (!empty($talla))      $where[] = "EXISTS (SELECT 1 FROM producto_tallas pt WHERE pt.id_producto = p.id_producto AND pt.talla = '" . escapar($talla) . "' AND pt.stock > 0)";
if ($precio_min > 0)     $where[] = "p.precio >= $precio_min";
if ($precio_max < 9999)  $where[] = "p.precio <= $precio_max";
if ($destacado)          $where[] = "p.destacado = 1";
if ($oferta)             $where[] = "p.precio_anterior > p.precio AND p.stock > 0";

$where_sql = implode(' AND ', $where);

$orden_sql = match($orden) {
    'precio_asc'  => 'p.precio ASC',
    'precio_desc' => 'p.precio DESC',
    'nombre'      => 'p.nombre ASC',
    'valoracion'  => 'puntuacion_media DESC',
    default       => 'p.fecha_creacion DESC',
};

// Total de productos
$total_result = $conn->query("SELECT COUNT(*) as total FROM productos p WHERE $where_sql");
$total        = (int) $total_result->fetch_assoc()['total'];
$paginacion   = paginar($total, $pagina);

// Productos
$productos = $conn->query("
    SELECT p.*, c.nombre AS categoria_nombre,
           COALESCE(AVG(r.puntuacion), 0) AS puntuacion_media,
           COUNT(r.id_resena) AS total_resenas,
           (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen_principal
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    LEFT JOIN resenas r ON r.id_producto = p.id_producto
    WHERE $where_sql
    GROUP BY p.id_producto
    ORDER BY $orden_sql
    LIMIT {$paginacion['por_pagina']} OFFSET {$paginacion['offset']}
");

// Categorías para filtro sidebar
$categorias = $conn->query("SELECT * FROM categorias WHERE activa = 1");

// Tallas disponibles
$tallas = $conn->query("SELECT DISTINCT talla FROM producto_tallas pt JOIN productos p ON pt.id_producto = p.id_producto WHERE p.activo = 1 ORDER BY FIELD(talla,'XS','S','M','L','XL','XXL'), CAST(talla AS UNSIGNED), talla");

// Nombre categoría activa
$nombre_categoria = '';
if ($categoria > 0) {
    $cat_result = $conn->query("SELECT nombre FROM categorias WHERE id_categoria = $categoria");
    if ($cat_result->num_rows > 0) $nombre_categoria = $cat_result->fetch_assoc()['nombre'];
}
?>

<div class="sp-catalogo-page">
    <div class="sp-container" style="padding-top:32px; padding-bottom:48px">

        <!-- Breadcrumb -->
        <nav class="sp-breadcrumb">
            <a href="<?= BASE_URL ?>">Inicio</a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/catalogo">Catálogo</a>
            <?php if ($nombre_categoria): ?>
            <span>/</span>
            <span><?= sanitizar($nombre_categoria) ?></span>
            <?php endif; ?>
            <?php if (!empty($q)): ?>
            <span>/</span>
            <span>Búsqueda: "<?= $q ?>"</span>
            <?php endif; ?>
        </nav>

        <!-- Botón filtros móvil -->
        <button class="sp-btn-filtros" onclick="abrirFiltros()">
            <i class="bi bi-funnel"></i> Filtros
        </button>

        <!-- Overlay -->
        <div class="sp-filtros-overlay" id="filtrosOverlay" onclick="cerrarFiltros()"></div>

        <!-- Drawer filtros móvil -->
        <div class="sp-sidebar-drawer" id="sidebarDrawer">
            <div class="sp-sidebar-drawer-header">
                <h4>FILTROS</h4>
                <button class="sp-sidebar-drawer-close" onclick="cerrarFiltros()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form method="GET" action="<?= BASE_URL ?>/catalogo">
                <?php if (!empty($q)): ?>
                <input type="hidden" name="q" value="<?= $q ?>">
                <?php endif; ?>

                <div class="sp-filtro-bloque">
                    <h4 class="sp-filtro-titulo">Categorías</h4>
                    <ul class="sp-filtro-lista">
                        <li><a href="<?= BASE_URL ?>/catalogo" class="<?= $categoria === 0 ? 'active' : '' ?>">Todos los productos</a></li>
                        <?php $categorias->data_seek(0); while ($cat = $categorias->fetch_assoc()): ?>
                        <li>
                            <a href="<?= BASE_URL ?>/catalogo?categoria=<?= $cat['id_categoria'] ?>"
                               class="<?= $categoria === (int)$cat['id_categoria'] ? 'active' : '' ?>">
                                <?= sanitizar($cat['nombre']) ?>
                                <span class="sp-filtro-count">
                                    <?php
                                    $c = $conn->query("SELECT COUNT(*) as t FROM productos WHERE id_categoria = {$cat['id_categoria']} AND activo = 1");
                                    echo $c->fetch_assoc()['t'];
                                    ?>
                                </span>
                            </a>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="sp-filtro-bloque">
                    <h4 class="sp-filtro-titulo">Precio (S/)</h4>
                    <div class="sp-filtro-precio">
                        <input type="number" name="precio_min" placeholder="Mín" value="<?= $precio_min > 0 ? $precio_min : '' ?>" min="0">
                        <span>—</span>
                        <input type="number" name="precio_max" placeholder="Máx" value="<?= $precio_max < 9999 ? $precio_max : '' ?>" min="0">
                    </div>
                </div>

                <?php $tallas->data_seek(0); if ($tallas->num_rows > 0): ?>
                <div class="sp-filtro-bloque">
                    <h4 class="sp-filtro-titulo">Talla</h4>
                    <div class="sp-filtro-tallas">
                        <a href="<?= BASE_URL ?>/catalogo" class="sp-talla <?= empty($talla) ? 'active' : '' ?>">Todas</a>
                        <?php $tallas->data_seek(0); while ($t = $tallas->fetch_assoc()): ?>
                        <a href="<?= BASE_URL ?>/catalogo?<?= http_build_query(array_merge($_GET, ['talla' => $t['talla']])) ?>"
                           class="sp-talla <?= $talla === $t['talla'] ? 'active' : '' ?>">
                            <?= sanitizar($t['talla']) ?>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($categoria > 0): ?>
                <input type="hidden" name="categoria" value="<?= $categoria ?>">
                <?php endif; ?>

                <button type="submit" class="sp-btn-primary w-100">
                    <i class="bi bi-funnel"></i> Aplicar filtros
                </button>

                <?php if (!empty($q) || $categoria > 0 || !empty($talla) || $precio_min > 0 || $precio_max < 9999): ?>
                <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-limpiar">
                    <i class="bi bi-x-circle"></i> Limpiar filtros
                </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="sp-catalogo-layout">

            <!-- ── SIDEBAR FILTROS DESKTOP ─────────────────── -->
            <aside class="sp-sidebar">
                <form method="GET" action="<?= BASE_URL ?>/catalogo" id="filtrosForm">

                    <?php if (!empty($q)): ?>
                    <input type="hidden" name="q" value="<?= $q ?>">
                    <?php endif; ?>

                    <div class="sp-filtro-bloque">
                        <h4 class="sp-filtro-titulo">Categorías</h4>
                        <ul class="sp-filtro-lista">
                            <li>
                                <a href="<?= BASE_URL ?>/catalogo<?= !empty($q) ? '?q='.$q : '' ?>"
                                   class="<?= $categoria === 0 ? 'active' : '' ?>">
                                    Todos los productos
                                </a>
                            </li>
                            <?php $categorias->data_seek(0); while ($cat = $categorias->fetch_assoc()): ?>
                            <li>
                                <a href="<?= BASE_URL ?>/catalogo?categoria=<?= $cat['id_categoria'] ?><?= !empty($q) ? '&q='.$q : '' ?>"
                                   class="<?= $categoria === (int)$cat['id_categoria'] ? 'active' : '' ?>">
                                    <?= sanitizar($cat['nombre']) ?>
                                    <span class="sp-filtro-count">
                                        <?php
                                        $c = $conn->query("SELECT COUNT(*) as t FROM productos WHERE id_categoria = {$cat['id_categoria']} AND activo = 1");
                                        echo $c->fetch_assoc()['t'];
                                        ?>
                                    </span>
                                </a>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>

                    <div class="sp-filtro-bloque">
                        <h4 class="sp-filtro-titulo">Precio (S/)</h4>
                        <div class="sp-filtro-precio">
                            <input type="number" name="precio_min" placeholder="Mín"
                                   value="<?= $precio_min > 0 ? $precio_min : '' ?>" min="0">
                            <span>—</span>
                            <input type="number" name="precio_max" placeholder="Máx"
                                   value="<?= $precio_max < 9999 ? $precio_max : '' ?>" min="0">
                        </div>
                    </div>

                    <!-- Tallas -->
                    <?php $tallas->data_seek(0); if ($tallas->num_rows > 0): ?>
                    <div class="sp-filtro-bloque">
                        <h4 class="sp-filtro-titulo">Talla</h4>
                        <div class="sp-filtro-tallas">
                            <a href="<?= BASE_URL ?>/catalogo?<?= http_build_query(array_merge($_GET, ['talla' => ''])) ?>"
                               class="sp-talla <?= empty($talla) ? 'active' : '' ?>">Todas</a>
                            <?php $tallas->data_seek(0); while ($t = $tallas->fetch_assoc()): ?>
                            <a href="<?= BASE_URL ?>/catalogo?<?= http_build_query(array_merge($_GET, ['talla' => $t['talla']])) ?>"
                               class="sp-talla <?= $talla === $t['talla'] ? 'active' : '' ?>">
                                <?= sanitizar($t['talla']) ?>
                            </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($categoria > 0): ?>
                    <input type="hidden" name="categoria" value="<?= $categoria ?>">
                    <?php endif; ?>

                    <button type="submit" class="sp-btn-primary w-100">
                        <i class="bi bi-funnel"></i> Aplicar filtros
                    </button>

                    <?php if (!empty($q) || $categoria > 0 || !empty($talla) || $precio_min > 0 || $precio_max < 9999): ?>
                    <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-limpiar">
                        <i class="bi bi-x-circle"></i> Limpiar filtros
                    </a>
                    <?php endif; ?>

                </form>
            </aside>

            <!-- ── CONTENIDO PRODUCTOS ────────────────────── -->
            <div class="sp-catalogo-content">

                <div class="sp-catalogo-header">
                    <p class="sp-catalogo-total">
                        <?= $total ?> producto<?= $total !== 1 ? 's' : '' ?>
                        <?= !empty($q) ? ' para "' . $q . '"' : '' ?>
                        <?= $nombre_categoria ? ' en ' . $nombre_categoria : '' ?>
                    </p>
                    <div class="sp-catalogo-orden">
                        <label>Ordenar:</label>
                        <select onchange="location.href=this.value">
                            <?php
                            $ordenes = [
                                'reciente'    => 'Más recientes',
                                'precio_asc'  => 'Precio: menor a mayor',
                                'precio_desc' => 'Precio: mayor a menor',
                                'nombre'      => 'Nombre A-Z',
                                'valoracion'  => 'Mejor valorados',
                            ];
                            foreach ($ordenes as $val => $label):
                                $url = BASE_URL . '/catalogo?' . http_build_query(array_merge($_GET, ['orden' => $val]));
                            ?>
                            <option value="<?= $url ?>" <?= $orden === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if ($productos->num_rows > 0): ?>
                <div class="sp-productos-grid sp-catalogo">
                    <?php while ($prod = $productos->fetch_assoc()): ?>
                        <?php include ROOT_PATH . '/includes/card_producto.php'; ?>
                    <?php endwhile; ?>
                </div>

                <?php if ($paginacion['total_paginas'] > 1): ?>
                <div class="sp-paginacion">
                    <?php if ($paginacion['hay_anterior']): ?>
                    <a href="<?= BASE_URL ?>/catalogo?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>"
                       class="sp-pag-btn"><i class="bi bi-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($p = 1; $p <= $paginacion['total_paginas']; $p++): ?>
                    <a href="<?= BASE_URL ?>/catalogo?<?= http_build_query(array_merge($_GET, ['pagina' => $p])) ?>"
                       class="sp-pag-btn <?= $p === $pagina ? 'active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if ($paginacion['hay_siguiente']): ?>
                    <a href="<?= BASE_URL ?>/catalogo?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>"
                       class="sp-pag-btn"><i class="bi bi-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="sp-sin-resultados">
                    <i class="bi bi-search"></i>
                    <h3>No encontramos productos</h3>
                    <p>Intenta con otros filtros o busca otro término.</p>
                    <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-primary">Ver todos los productos</a>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
function abrirFiltros() {
    document.getElementById('sidebarDrawer').classList.add('active');
    document.getElementById('filtrosOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function cerrarFiltros() {
    document.getElementById('sidebarDrawer').classList.remove('active');
    document.getElementById('filtrosOverlay').classList.remove('active');
    document.body.style.overflow = '';
}
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>