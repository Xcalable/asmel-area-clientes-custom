// Resalta la opcion activa del menu de clientes segun la ruta actual.
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var currentPath = window.location.pathname.replace(/\/$/, '');
        var menuLinks = document.querySelectorAll('.ac-boton');

        if (!menuLinks.length) {
            return;
        }

        menuLinks.forEach(function(link) {
            if (!link || !link.href) {
                return;
            }

            var linkPath;
            try {
                linkPath = new URL(link.href).pathname.replace(/\/$/, '');
            } catch (error) {
                return;
            }

            link.classList.remove('active');

            if (linkPath === currentPath) {
                link.classList.add('active');
            }
        });
    });
})();

