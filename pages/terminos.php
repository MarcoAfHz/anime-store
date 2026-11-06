<?php
$titulo_pagina    = 'Términos y condiciones';
$meta_descripcion = 'Términos y condiciones de compra en ShopPeruvian: pedidos, pagos con Yape o efectivo, envíos y devoluciones.';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="sp-legal">
    <nav class="sp-breadcrumb">
        <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span>
        <span>Términos y condiciones</span>
    </nav>

    <h1 class="sp-page-title">Términos y condiciones</h1>
    <p class="sp-legal-fecha">Última actualización: <?= date('d/m/Y', filemtime(__FILE__)) ?></p>

    <p>
        Bienvenido a <strong><?= TIENDA_NOMBRE ?></strong>. Al navegar por nuestra web y realizar un pedido aceptas
        las condiciones que se describen a continuación. Te recomendamos leerlas antes de comprar.
    </p>

    <h2>1. Sobre nosotros</h2>
    <p>
        <?= TIENDA_NOMBRE ?> es una tienda online de importaciones de ropa, figuras y accesorios de anime y estilo urbano,
        con sede en <?= TIENDA_DIRECCION ?>. Puedes contactarnos en <a href="mailto:<?= TIENDA_EMAIL ?>"><?= TIENDA_EMAIL ?></a>
        o por WhatsApp al <?= TIENDA_TELEFONO ?>.
    </p>

    <h2>2. Cuenta de usuario</h2>
    <ul>
        <li>Para comprar necesitas una cuenta con datos reales y actualizados.</li>
        <li>Eres responsable de mantener la confidencialidad de tu contraseña.</li>
        <li>Podemos suspender cuentas que hagan un uso fraudulento de la tienda.</li>
    </ul>

    <h2>3. Productos y precios</h2>
    <ul>
        <li>Todos los precios se muestran en soles (<?= TIENDA_MONEDA ?>) e incluyen IGV.</li>
        <li>Las fotos son referenciales; colores y acabados pueden variar ligeramente.</li>
        <li>Las ofertas son válidas hasta agotar stock o hasta la fecha indicada.</li>
        <li>Nos reservamos el derecho de corregir errores evidentes de precio antes de confirmar un pedido.</li>
    </ul>

    <h2>4. Pedidos</h2>
    <p>
        Al confirmar tu pedido recibirás un código de comprobante (por ejemplo <strong>SP-<?= date('Y') ?>-00001</strong>).
        El pedido queda <strong>pendiente</strong> hasta que confirmemos el pago o, en caso de pago en efectivo,
        hasta que verifiquemos los datos de entrega.
    </p>

    <h2>5. Formas de pago</h2>
    <ul>
        <li><strong>Yape:</strong> realiza el pago al número indicado al finalizar la compra, usando tu código de pedido como concepto, y sube la captura del pago desde la página de confirmación.</li>
        <li><strong>Efectivo contra entrega:</strong> pagas al recibir el pedido. Ten el monto exacto listo.</li>
    </ul>
    <p>Los pedidos con Yape que no se paguen en 48 horas podrán cancelarse automáticamente.</p>

    <h2>6. Envíos</h2>
    <ul>
        <li>Realizamos envíos a todo el Perú. El costo depende del departamento y se muestra antes de confirmar.</li>
        <li>Los envíos dentro de Lima Metropolitana son gratuitos.</li>
        <li>Plazo estimado: Lima 1–3 días hábiles; provincias 3–7 días hábiles desde la confirmación del pago.</li>
    </ul>

    <h2>7. Cambios y devoluciones</h2>
    <ul>
        <li>Tienes <strong>7 días</strong> desde la entrega para solicitar un cambio de talla o devolución.</li>
        <li>El producto debe estar sin uso, con etiquetas y en su empaque original.</li>
        <li>Las figuras de colección solo se aceptan si llegaron dañadas (envía fotos al recibirlas).</li>
        <li>Si el error es nuestro, asumimos el costo del envío del cambio.</li>
    </ul>

    <h2>8. Libro de reclamaciones</h2>
    <p>
        Conforme al Código de Protección y Defensa del Consumidor (Ley N.º 29571), puedes registrar una queja o reclamo
        escribiéndonos a <a href="mailto:<?= TIENDA_EMAIL ?>"><?= TIENDA_EMAIL ?></a>. Te responderemos en un plazo máximo de 15 días hábiles.
    </p>

    <h2>9. Propiedad intelectual</h2>
    <p>
        Las marcas, personajes y logotipos de las series mostradas pertenecen a sus respectivos titulares
        y se utilizan únicamente para identificar los productos.
    </p>

    <div class="sp-legal-aviso">
        <i class="bi bi-info-circle"></i>
        ¿Tienes dudas sobre tu pedido? Escríbenos por WhatsApp al <strong><?= TIENDA_TELEFONO ?></strong> y te ayudamos.
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
