<?php
if (!function_exists('getRolActual')) {
    require_once __DIR__ . '/auth.php';
}

$rol = ucfirst(getRolActual());
?>
    </main>

    <footer class="footer">
        <div class="footer-inner">
            <p class="footer-left">Hecho por: Gabriel</p>
            <p class="footer-right">Rol actual: <?php echo htmlspecialchars($rol); ?></p>
        </div>
    </footer>

<script src="/js/main.js"></script>
</body>
</html>
