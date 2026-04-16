<?php
if (!function_exists('getRolActual')) {
    require_once __DIR__ . '/auth.php';
}
$rol = getRolActual();
?>
    </main>

    <footer class="footer">
        <div class="footer-inner">
            <p class="footer-left">Hecho por: Gabriel</p>
            <p class="footer-right">Su rol es: <?php echo ucfirst($rol); ?></p>
        </div>
    </footer>

<script src="/js/main.js"></script>
</body>
</html>
