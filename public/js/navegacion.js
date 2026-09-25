(() => {
    const boton = document.querySelector('#boton-menu');
    const menu = document.querySelector('#navegacion-lateral');
    const fondo = document.querySelector('.fondo-menu');

    if (!boton || !menu || !fondo) {
        return;
    }

    const cerrar = () => {
        document.body.classList.remove('menu-abierto');
        boton.setAttribute('aria-expanded', 'false');
        boton.setAttribute('aria-label', 'Abrir menú de navegación');
    };

    const abrir = () => {
        document.body.classList.add('menu-abierto');
        boton.setAttribute('aria-expanded', 'true');
        boton.setAttribute('aria-label', 'Cerrar menú de navegación');
    };

    boton.addEventListener('click', () => {
        if (document.body.classList.contains('menu-abierto')) {
            cerrar();
            return;
        }

        abrir();
    });

    fondo.addEventListener('click', cerrar);
    menu.addEventListener('click', (evento) => {
        if (evento.target.closest('a')) {
            cerrar();
        }
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') {
            cerrar();
        }
    });
    window.matchMedia('(min-width: 64.001rem)').addEventListener('change', cerrar);
})();
