<?php
$titulo_pagina    = 'Política de privacidad';
$meta_descripcion = 'Cómo Anime Store trata y protege tus datos personales conforme a la Ley N.º 29733.';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="sp-legal">
    <nav class="sp-breadcrumb">
        <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span>
        <span>Política de privacidad</span>
    </nav>

    <h1 class="sp-page-title">Política de privacidad</h1>
    <p class="sp-legal-fecha">Última actualización: <?= date('d/m/Y', filemtime(__FILE__)) ?></p>

    <p>
        En <strong><?= TIENDA_NOMBRE ?></strong> respetamos tu privacidad. Esta política explica qué datos recogemos,
        para qué los usamos y qué derechos tienes, conforme a la Ley N.º 29733 de Protección de Datos Personales del Perú.
    </p>

    <h2>1. Responsable</h2>
    <p>
        <?= TIENDA_NOMBRE ?>, con domicilio en <?= TIENDA_DIRECCION ?>.
        Contacto: <a href="mailto:<?= TIENDA_EMAIL ?>"><?= TIENDA_EMAIL ?></a>.
    </p>

    <h2>2. Datos que recogemos</h2>
    <ul>
        <li><strong>Datos de cuenta:</strong> nombre, email y contraseña (guardada cifrada, nunca en texto plano).</li>
        <li><strong>Datos de envío:</strong> teléfono, dirección, provincia y departamento.</li>
        <li><strong>Datos de compra:</strong> productos, importes, método de pago y comprobantes de Yape que subas.</li>
        <li><strong>Actividad en la tienda:</strong> lista de deseos y reseñas que publiques.</li>
    </ul>
    <p>No almacenamos datos de tarjetas bancarias.</p>

    <h2>3. Para qué los usamos</h2>
    <ul>
        <li>Gestionar tu cuenta y tus pedidos.</li>
        <li>Coordinar la entrega y confirmar los pagos.</li>
        <li>Atender consultas, cambios y reclamos.</li>
        <li>Mostrar tus reseñas públicamente con tu nombre (solo si decides publicarlas).</li>
    </ul>

    <h2>4. Con quién los compartimos</h2>
    <p>
        Solo con las empresas de mensajería necesarias para entregar tu pedido. No vendemos ni cedemos tus datos
        a terceros con fines comerciales.
    </p>

    <h2>5. Cuánto tiempo los guardamos</h2>
    <p>
        Mientras tu cuenta esté activa y, después, durante los plazos que exija la normativa tributaria y de consumo.
    </p>

    <h2>6. Cookies</h2>
    <p>
        Usamos únicamente cookies técnicas necesarias para mantener tu sesión y tu carrito. No usamos cookies de publicidad.
    </p>

    <h2>7. Tus derechos</h2>
    <p>
        Puedes ejercer tus derechos de acceso, rectificación, cancelación y oposición (ARCO) escribiendo a
        <a href="mailto:<?= TIENDA_EMAIL ?>"><?= TIENDA_EMAIL ?></a>. También puedes actualizar tus datos en cualquier momento
        desde <a href="<?= BASE_URL ?>/mi-cuenta">Mi cuenta</a>.
    </p>

    <div class="sp-legal-aviso">
        <i class="bi bi-shield-check"></i>
        Tus contraseñas se guardan cifradas y la web usa protección contra accesos no autorizados.
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
