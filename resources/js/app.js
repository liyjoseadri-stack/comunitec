import './bootstrap';
import './confirmaciones';
import './carga';

const enfocarResumenErrores = () => {
    const resumen = document.getElementById('resumen-errores-validacion');

    if (!resumen) {
        return;
    }

    resumen.focus({ preventScroll: true });
    resumen.scrollIntoView({ behavior: 'instant', block: 'nearest' });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', enfocarResumenErrores, { once: true });
} else {
    enfocarResumenErrores();
}
