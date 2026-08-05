<footer>
        Bernardi &copy; <?= date('Y') ?> &mdash; Desarrollado con Apache, PHP &amp; MySQL
    </footer>

    <!-- Script del menú de hamburguesa responsivo -->
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const headerNav = document.getElementById('headerNav');
        
            if (hamburgerBtn && headerNav) {
                hamburgerBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    headerNav.classList.toggle('active');
                });
        
                // Cerrar menú si el usuario toca fuera de él
                document.addEventListener('click', function (e) {
                    if (!headerNav.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                        headerNav.classList.remove('active');
                    }
                });
            }
        });
        </script>

</body>
</html>
