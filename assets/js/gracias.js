/* ==========================================================================
   CITA COMPARENDO - gracias.js
   Resumen del registro de una cita. Lee el token `citaConfirm` (o `cita`) de
   la URL, consulta `action=cita_detalles` y pinta el resultado sin innerHTML.
   ========================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var estado = document.getElementById('gr-estado');
        var resumen = document.getElementById('gr-resumen');
        var titulo = document.getElementById('gr-titulo');
        var imprimir = document.getElementById('gr-imprimir');

        if (!estado || !resumen) {
            return;
        }

        var config = window.CITA_GRACIAS || {};
        var i18n = config.i18n || {};
        var ajaxUrl = config.ajaxUrl || '';
        var cargando = false;

        if (imprimir) {
            imprimir.addEventListener('click', function () {
                window.print();
            });
        }

        var params = new URLSearchParams(window.location.search);
        var token = (params.get('citaConfirm') || params.get('cita') || '').trim();

        if (!token) {
            mostrarError(i18n.errorEnlace || 'Este enlace no incluye los datos de tu cita.');
            return;
        }

        cargar(token);

        /* --- Consulta al proxy ----------------------------------------- */
        function cargar(tokenActual) {
            if (!ajaxUrl) {
                mostrarError(i18n.errorServidor || 'El servicio de citas no está disponible.');
                return;
            }

            if (cargando) {
                return;
            }
            cargando = true;

            var cuerpo = new URLSearchParams();
            cuerpo.append('action', 'cita_detalles');
            cuerpo.append('citaConfirm', tokenActual);

            fetch(ajaxUrl, {
                method: 'POST',
                body: cuerpo,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json'
                }
            })
                .then(leerRespuesta)
                .then(function (respuesta) {
                    cargando = false;

                    if (respuesta.status === 429) {
                        mostrarError(i18n.errorLimite || 'Hiciste varias consultas seguidas. Espera un momento.');
                        return;
                    }

                    if (respuesta.status >= 500) {
                        mostrarError(i18n.errorServidor || 'El servicio de citas no está disponible.');
                        return;
                    }

                    if (!respuesta.ok) {
                        mostrarError(i18n.errorGeneral || 'No encontramos tu cita.');
                        return;
                    }

                    var payload = respuesta.datos;
                    var data = payload && payload.data ? payload.data : null;

                    if (!payload || payload.success !== true || !data) {
                        mostrarError(i18n.errorGeneral || 'No encontramos tu cita.');
                        return;
                    }

                    var citas = Array.isArray(data.citas) ? data.citas : [];

                    if (!citas.length) {
                        mostrarError(i18n.errorSinCitas || 'No encontramos citas asociadas a este enlace.');
                        return;
                    }

                    pintar(data, citas);
                })
                .catch(function () {
                    cargando = false;
                    mostrarError(i18n.errorRed || 'No pudimos consultar tu cita. Revisa tu conexión.');
                });
        }

        /**
         * Lee el cuerpo como JSON sin romperse con respuestas HTML.
         *
         * @param {Response} respuesta Respuesta de fetch.
         * @returns {Promise<{ok: boolean, status: number, datos: Object|null}>}
         */
        function leerRespuesta(respuesta) {
            return respuesta.text().then(function (texto) {
                var datos = null;

                try {
                    datos = JSON.parse(texto);
                } catch (error) {
                    datos = null;
                }

                return {
                    ok: respuesta.ok,
                    status: respuesta.status,
                    datos: datos
                };
            });
        }

        /* --- Pintado del resumen --------------------------------------- */
        function pintar(data, citas) {
            vaciar(resumen);
            vaciar(estado);
            estado.classList.remove('is-error');
            estado.hidden = true;
            resumen.hidden = false;

            resumen.appendChild(bloqueDatos(data.cliente || {}));
            resumen.appendChild(bloqueCitas(citas));

            enfocar(titulo);
        }

        function bloqueDatos(cliente) {
            var bloque = crear('section', 'gr-bloque');
            bloque.setAttribute('aria-labelledby', 'gr-datos-titulo');

            bloque.appendChild(cabecera('gr-datos-titulo', 'fa-address-card', i18n.datosTitulo || 'Tus datos'));

            var lista = crear('dl', 'gr-datos');

            lista.appendChild(dato(i18n.nombre || 'Nombre completo', texto(cliente.nombre_completo), true));
            lista.appendChild(dato(i18n.tipoDocumento || 'Tipo de documento', texto(cliente.tipo_doc)));
            lista.appendChild(dato(i18n.documento || 'Número de documento', texto(cliente.doc)));
            lista.appendChild(dato(i18n.telefono || 'Teléfono', texto(cliente.telefono)));
            lista.appendChild(dato(i18n.correo || 'Correo electrónico', texto(cliente.email)));

            bloque.appendChild(lista);

            return bloque;
        }

        function bloqueCitas(citas) {
            var varias = citas.length > 1;
            var bloque = crear('section', 'gr-bloque gr-bloque--citas');
            bloque.setAttribute('aria-labelledby', 'gr-citas-titulo');

            bloque.appendChild(cabecera(
                'gr-citas-titulo',
                'fa-calendar-check',
                varias ? (i18n.citasTitulo || 'Tus citas') : (i18n.citaTitulo || 'Tu cita')
            ));

            var lista = crear('div', 'gr-citas');

            citas.forEach(function (cita, indice) {
                lista.appendChild(tarjetaCita(cita, indice, varias));
            });

            bloque.appendChild(lista);

            return bloque;
        }

        function tarjetaCita(cita, indice, varias) {
            var datos = cita && typeof cita === 'object' ? cita : {};
            var card = crear('article', 'gr-cita');

            var cabeza = crear('div', 'gr-cita__cabecera');

            if (varias) {
                var numero = crear('span', 'gr-cita__num');
                numero.appendChild(crear('span', 'visually-hidden', (i18n.cita || 'Cita') + ' '));
                numero.appendChild(document.createTextNode(String(indice + 1)));
                cabeza.appendChild(numero);
            }

            var ident = crear('div', 'gr-cita__ident');
            var sede = crear('h3', 'gr-cita__sede', texto(datos.nombre_sede) || (i18n.sedePendiente || 'Sede por confirmar'));
            sede.id = 'gr-cita-sede-' + (indice + 1);
            card.setAttribute('aria-labelledby', sede.id);
            ident.appendChild(sede);

            var ref = referencia(datos.id_cita);
            if ('' !== ref) {
                ident.appendChild(crear('p', 'gr-cita__ref', ref));
            }
            cabeza.appendChild(ident);

            var icono = crear('span', 'gr-cita__icono');
            icono.setAttribute('aria-hidden', 'true');
            icono.appendChild(crear('i', 'fa-solid fa-calendar-day'));
            cabeza.appendChild(icono);

            card.appendChild(cabeza);

            var lista = crear('dl', 'gr-cita__datos');
            lista.appendChild(dato(i18n.fecha || 'Fecha', texto(datos.reserva_cita)));
            lista.appendChild(dato(i18n.horario || 'Franja horaria', texto(datos.rango_horario)));
            lista.appendChild(dato(i18n.direccion || 'Dirección', texto(datos.direccion_sede), true));

            card.appendChild(lista);

            return card;
        }

        function referencia(idCita) {
            var id = texto(idCita);

            if ('' === id) {
                return '';
            }

            var plantilla = i18n.refCita || 'Cita #%s';

            return plantilla.indexOf('%s') === -1
                ? plantilla + ' ' + id
                : plantilla.replace('%s', id);
        }

        /* --- Estados ---------------------------------------------------- */
        function mostrarError(mensaje) {
            vaciar(estado);
            estado.hidden = false;
            estado.classList.add('is-error');

            if (resumen) {
                resumen.hidden = true;
                vaciar(resumen);
            }

            var caja = crear('div', 'gr-aviso');
            var icono = crear('span', 'gr-aviso__icono');
            icono.setAttribute('aria-hidden', 'true');
            icono.appendChild(crear('i', 'fa-solid fa-triangle-exclamation'));

            var contenido = crear('div', 'gr-aviso__contenido');
            var tituloError = crear('h2', 'gr-aviso__titulo', i18n.errorTitulo || 'No encontramos tu cita');
            tituloError.setAttribute('tabindex', '-1');

            contenido.appendChild(tituloError);
            contenido.appendChild(crear('p', 'gr-aviso__texto', mensaje || i18n.errorGeneral || ''));

            caja.appendChild(icono);
            caja.appendChild(contenido);
            estado.appendChild(caja);

            enfocar(tituloError);
        }

        /* --- Utilidades ------------------------------------------------- */
        function cabecera(idTitulo, icono, etiqueta) {
            var fila = crear('div', 'gr-bloque__cabecera');
            var cajaIcono = crear('span', 'gr-bloque__icono');
            cajaIcono.setAttribute('aria-hidden', 'true');
            cajaIcono.appendChild(crear('i', 'fa-solid ' + icono));

            var heading = crear('h2', 'gr-bloque__titulo', etiqueta);
            heading.id = idTitulo;

            fila.appendChild(cajaIcono);
            fila.appendChild(heading);

            return fila;
        }

        function dato(etiqueta, valor, ancho) {
            var fila = crear('div', 'gr-dato' + (ancho ? ' gr-dato--ancho' : ''));
            fila.appendChild(crear('dt', '', etiqueta));
            fila.appendChild(crear('dd', '', '' !== valor ? valor : (i18n.noDisponible || 'No disponible')));

            return fila;
        }

        function crear(etiqueta, clase, textoNodo) {
            var nodo = document.createElement(etiqueta);

            if (clase) {
                nodo.className = clase;
            }
            if (typeof textoNodo === 'string' && '' !== textoNodo) {
                nodo.textContent = textoNodo;
            }

            return nodo;
        }

        function vaciar(nodo) {
            if (!nodo) {
                return;
            }
            while (nodo.firstChild) {
                nodo.removeChild(nodo.firstChild);
            }
        }

        function texto(valor) {
            if (typeof valor === 'string') {
                return valor.trim();
            }
            if (typeof valor === 'number' && isFinite(valor)) {
                return String(valor);
            }
            return '';
        }

        function enfocar(nodo) {
            if (!nodo || typeof nodo.focus !== 'function') {
                return;
            }

            window.requestAnimationFrame(function () {
                try {
                    nodo.focus({ preventScroll: false });
                } catch (error) {
                    nodo.focus();
                }
            });
        }
    });
})();
