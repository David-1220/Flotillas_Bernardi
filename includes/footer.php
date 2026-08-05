<footer>
        Bernardi &copy; <?= date('Y') ?> &mdash; Desarrollado con Apache, PHP &amp; MySQL
    </footer>

    <!-- Script del menú de hamburguesa responsivo -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const navbarMenu = document.getElementById('navbarMenu');

        if (hamburgerBtn && navbarMenu) {
            hamburgerBtn.addEventListener('click', function () {
                navbarMenu.classList.toggle('active');
            });
        }
    });
    </script>

</body>
</html>
