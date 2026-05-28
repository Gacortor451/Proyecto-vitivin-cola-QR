<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$logueado = estaLogueado();
$rol = getRolActual();
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="index-contenedor" style="max-width: 900px; margin: 0 auto; padding: 20px;">

    <h1 class="index-titulo" style="margin-bottom: 30px;">
        Sistema de Trazabilidad de Lotes
    </h1>

    <p class="index-descripcion" style="margin-bottom: 50px; line-height: 1.6;">
        Bienvenido al sistema de trazabilidad mediante códigos QR.  
        Esta plataforma permite consultar la información completa de cada lote, incluyendo su origen,
        proceso de producción, eventos registrados y comentarios de otros usuarios.  
        El objetivo es ofrecer transparencia total en la cadena de producción y facilitar la supervisión
        por parte de los distintos roles del sistema.
    </p>

    <?php if ($logueado): ?>
        <div class="index-usuario" style="margin-bottom: 60px;">
            <p style="font-size: 1.1rem;">
                <strong>Has iniciado sesión como:</strong> <?php echo ucfirst($rol); ?>
            </p>

            <div class="index-botones" style="margin-top: 20px;">

                <?php if ($rol === 'admin'): ?>
                    <a class="btn" href="/admin/admin.php">Ir al panel de administración</a>

                <?php elseif ($rol === 'auditor'): ?>
                    <a class="btn" href="/auditor/auditor.php">Ir al panel de auditoría</a>

                <?php elseif ($rol === 'empleado'): ?>
                    <a class="btn" href="/personal.php">Ir al panel del personal</a>

                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>

    <!-- BLOQUE 1 -->
    <section class="index-info-extra" style="margin-bottom: 70px;">
        <h2 style="margin-bottom: 20px;">¿Cómo funciona el sistema?</h2>

        <p style="line-height: 1.6; margin-bottom: 20px;">
            Cada lote dispone de un código QR único que permite acceder directamente a su información.
            Desde la página del lote, cualquier usuario puede consultar:
        </p>

        <ul style="line-height: 1.8; margin-left: 20px;">
            <li>Información general del producto</li>
            <li>Datos de producción y bodega</li>
            <li>Trazabilidad completa del proceso</li>
            <li>Eventos registrados por empleados</li>
            <li>Comentarios de otros usuarios</li>
            <li>Likes y valoración del lote</li>
        </ul>
    </section>

    <!-- BLOQUE 2 -->
    <section class="index-info-extra" style="margin-bottom: 70px;">
        <h2 style="margin-bottom: 20px;">Acceso mediante QR</h2>

        <p style="line-height: 1.6;">
            El sistema está optimizado para su uso desde dispositivos móviles.  
            Al escanear el código QR de un lote, el usuario accede directamente a su página.  
            Si necesita iniciar sesión, el sistema recuerda automáticamente el lote que estaba consultando
            y lo devuelve a él tras autenticarse, evitando que tenga que volver a escanear el código.
        </p>
    </section>

    <!-- BLOQUE 3 -->
    <section class="index-info-extra" style="margin-bottom: 70px;">
        <h2 style="margin-bottom: 20px;">Roles del sistema</h2>

        <p style="line-height: 1.6; margin-bottom: 20px;">
            El sistema cuenta con distintos roles, cada uno con permisos específicos:
        </p>

        <ul style="line-height: 1.8; margin-left: 20px;">
            <li><strong>Usuario normal:</strong> consulta lotes, comenta y da likes.</li>
            <li><strong>Empleado:</strong> registra eventos y actualiza la trazabilidad.</li>
            <li><strong>Auditor:</strong> revisa la información registrada y valida procesos.</li>
            <li><strong>Administrador:</strong> gestiona usuarios, roles y configuración general.</li>
        </ul>
    </section>

    <!-- BLOQUE 4 -->
    <section class="index-info-extra" style="margin-bottom: 70px;">
        <h2 style="margin-bottom: 20px;">Objetivo del proyecto</h2>

        <p style="line-height: 1.6;">
            Este sistema ha sido desarrollado como parte de un proyecto académico orientado a demostrar
            la integración de tecnologías web, bases de datos y control de acceso mediante roles.
            La trazabilidad es un aspecto fundamental en sectores como el alimentario y el vitivinícola,
            donde la transparencia y la seguridad son esenciales.
        </p>
    </section>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
