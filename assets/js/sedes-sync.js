/**
 * Admin: sincronizar sedes desde el CRM.
 *
 * Solo se encola en las pantallas del CPT `sede`.
 */
(function () {
    'use strict';

    var config = window.CITA_SEDES_SYNC || null;
    if (!config) {
        return;
    }

    var botonSync = document.getElementById('cita-sync-sedes');
    var botonProbar = document.getElementById('cita-probar-crm');
    var salida = document.getElementById('cita-sync-salida');

    if (!salida) {
        return;
    }

    function escribir(mensaje) {
        salida.textContent = mensaje;
    }

    function resumir(respuesta, textoOk) {
        var lineas = [];
        var datos = respuesta && respuesta.data ? respuesta.data : {};

        if (respuesta && respuesta.success) {
            lineas.push((datos.message || textoOk || config.strings.listo));
        } else {
            lineas.push('✖ ' + (datos.message || (respuesta && respuesta.error) || config.strings.error));
        }

        if (datos.stats) {
            var stats = datos.stats;
            lineas.push('');
            lineas.push('Creadas:       ' + (stats.created || 0));
            lineas.push('Actualizadas:  ' + (stats.updated || 0));
            lineas.push('Reactivadas:   ' + (stats.reactivated || 0));
            lineas.push('Desactivadas:  ' + (stats.deactivated || 0));
            lineas.push('En borrador:   ' + (stats.borradores || 0));
            lineas.push('Omitidas:      ' + (stats.omitidas || 0));

            if (stats.errors && stats.errors.length) {
                lineas.push('');
                lineas.push('Errores (' + stats.errors.length + '):');
                stats.errors.forEach(function (error) {
                    lineas.push(' - ' + error);
                });
            }
        }

        if (datos.ubicaciones !== undefined) {
            lineas.push('');
            lineas.push('Ubicaciones: ' + datos.ubicaciones);
            lineas.push('Sedes:       ' + datos.sedes);
        }

        escribir(lineas.join('\n'));
    }

    function llamar(accion, nonce, boton, texto, ok) {
        var cuerpo = new FormData();
        cuerpo.append('action', accion);
        cuerpo.append('nonce', nonce);

        boton.disabled = true;
        escribir(texto);

        return fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: cuerpo
        })
            .then(function (respuesta) {
                return respuesta.json().catch(function () {
                    return {
                        success: false,
                        data: { message: 'Respuesta no válida del servidor (HTTP ' + respuesta.status + ').' }
                    };
                });
            })
            .then(function (json) {
                resumir(json, ok);
            })
            .catch(function (error) {
                escribir('✖ ' + (error && error.message ? error.message : config.strings.error));
            })
            .then(function () {
                boton.disabled = false;
            });
    }

    if (botonSync) {
        botonSync.addEventListener('click', function () {
            if (!window.confirm(config.strings.confirmar)) {
                return;
            }

            llamar('sync_sedes', config.nonce, botonSync, config.strings.sincronizando);
        });
    }

    if (botonProbar) {
        botonProbar.addEventListener('click', function () {
            llamar('cita_probar_crm', config.nonceProbar, botonProbar, config.strings.probando);
        });
    }
})();
