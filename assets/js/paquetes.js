/* ==========================================================================
   CITA COMPARENDO - paquetes.js
   Selección de paquete, verificación de empresa y envío de la compra.
   ========================================================================== */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('compraPaqueteForm');
        if (!form) {
            return;
        }

        var config = window.CITA_PAQUETES || {};
        var i18n = config.i18n || {};
        var ajaxUrl = config.ajaxUrl || '';
        var redirect = config.redirect || '';

        var selectPaquete = document.getElementById('select-paquete');
        var tipoDocumento = document.getElementById('tipo-documento');
        var numeroDocumento = document.getElementById('numero-documento');
        var grupoEmpresa = document.getElementById('paquete-datos-empresa');
        var estado = document.getElementById('paquete-verificacion');
        var elegido = document.getElementById('paquete-elegido');
        var alerta = document.getElementById('paquete-alerta');
        var submit = document.getElementById('paquete-submit');
        var spinner = document.getElementById('paquete-spinner');
        var submitTexto = document.getElementById('paquete-submit-texto');
        var cards = Array.prototype.slice.call(document.querySelectorAll('.pack-card'));

        var verificacion = {
            token: 0,
            tipo: '',
            documento: '',
            enCurso: false
        };
        var temporizador = null;

        if (!selectPaquete || !tipoDocumento || !numeroDocumento || !grupoEmpresa) {
            return;
        }

        /* --- Selección de paquete -------------------------------------- */
        document.querySelectorAll('.pack-card__cta').forEach(function (boton) {
            boton.addEventListener('click', function () {
                selectPaquete.value = boton.getAttribute('data-paquete') || '';
                sincronizarPaquete();
                irAlFormulario();
            });
        });

        selectPaquete.addEventListener('change', sincronizarPaquete);
        sincronizarPaquete();

        function sincronizarPaquete() {
            var slug = selectPaquete.value;

            cards.forEach(function (card) {
                var activo = card.getAttribute('data-paquete') === slug;
                card.classList.toggle('is-selected', activo);

                var boton = card.querySelector('.pack-card__cta');
                if (boton) {
                    boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
                }
            });

            if (!elegido) {
                return;
            }

            if (!slug) {
                elegido.textContent = 'Aún no has elegido un paquete.';
                return;
            }

            var opcion = selectPaquete.options[selectPaquete.selectedIndex];
            var nombre = opcion ? opcion.textContent.split(' · ')[0].trim() : slug;
            elegido.textContent = 'Paquete seleccionado: ' + nombre;
        }

        function irAlFormulario() {
            var seccion = document.getElementById('comprar-paquete');
            if (!seccion) {
                return;
            }
            seccion.scrollIntoView({
                behavior: reduceMotion ? 'auto' : 'smooth',
                block: 'start'
            });
        }

        /* --- Verificación de la empresa -------------------------------- */
        tipoDocumento.addEventListener('change', function () {
            consultarEmpresa();
        });

        numeroDocumento.addEventListener('input', function () {
            clearTimeout(temporizador);
            temporizador = setTimeout(consultarEmpresa, 650);
        });

        numeroDocumento.addEventListener('blur', function () {
            clearTimeout(temporizador);
            consultarEmpresa();
        });

        function consultarEmpresa() {
            var tipo = tipoDocumento.value;
            var documento = numeroDocumento.value.trim();

            if (!tipo || documento.length < 4) {
                return;
            }

            if (tipo === verificacion.tipo && documento === verificacion.documento) {
                return;
            }

            var token = verificacion.token + 1;
            verificacion.token = token;
            verificacion.enCurso = true;
            verificacion.tipo = tipo;
            verificacion.documento = documento;

            if (estado) {
                estado.className = 'paquete-form__estado is-loading';
                estado.textContent = i18n.verificando || 'Consultando empresa…';
            }

            var datos = new FormData();
            datos.append('action', 'verificar_empresa');
            datos.append('nonceContactForm', nonce());
            datos.append('tipo-documento', tipo);
            datos.append('numero-documento', documento);

            fetch(ajaxUrl, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin'
            })
                .then(leerRespuesta)
                .then(function (respuesta) {
                    if (token !== verificacion.token) {
                        return;
                    }
                    verificacion.enCurso = false;

                    var empresa = respuesta && (respuesta.Data ||
                        (respuesta.data && respuesta.data.empresa));

                    if (respuesta && respuesta.success === true && empresa && typeof empresa === 'object') {
                        rellenarEmpresa(empresa);
                        revelarEmpresa();
                        mensajeEstado(i18n.empresaOk || 'Empresa encontrada.', 'ok');
                        return;
                    }

                    // Sin empresa en el CRM: se permite registrarla a mano.
                    revelarEmpresa();
                    var aviso = (respuesta && (respuesta.error || (respuesta.data && respuesta.data.message))) || '';
                    if (aviso) {
                        aviso += ' ' + 'Completa los datos de tu empresa para continuar.';
                    } else {
                        aviso = i18n.empresaNueva || 'Completa los datos de tu empresa para continuar.';
                    }
                    mensajeEstado(aviso, 'aviso');
                })
                .catch(function () {
                    if (token !== verificacion.token) {
                        return;
                    }
                    verificacion.enCurso = false;
                    revelarEmpresa();
                    mensajeEstado(
                        'No pudimos verificar la empresa. Puedes completar los datos manualmente.',
                        'error'
                    );
                });
        }

        function rellenarEmpresa(empresa) {
            asignar('nombre-empresa', empresa.Nombre || empresa.nombre || empresa.nombre_empresa);
            asignar('Telefono-empresa', empresa.telefono_empresa || empresa.Telefono || empresa.telefono);
            asignar('email-empresa', empresa.correo || empresa.correo_empresa || empresa.Correo || empresa.email);
            asignar('descripcion', empresa.Descripcion || empresa.descripcion);
        }

        function asignar(nombre, valor) {
            if (!valor) {
                return;
            }
            var campo = form.elements[nombre];
            if (campo) {
                campo.value = String(valor);
            }
        }

        function revelarEmpresa() {
            grupoEmpresa.hidden = false;
        }

        function mensajeEstado(texto, tipo) {
            if (!estado) {
                return;
            }
            estado.className = 'paquete-form__estado' + (tipo ? ' is-' + tipo : '');
            estado.textContent = texto;
        }

        /* --- Envío de la compra ---------------------------------------- */
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            ocultarAlerta();
            limpiarCamposInvalidos();

            if (!selectPaquete.value) {
                mostrarAlerta('Selecciona un paquete para continuar.', 'error');
                selectPaquete.focus();
                return;
            }

            if (grupoEmpresa.hidden) {
                revelarEmpresa();
                mostrarAlerta(i18n.verificaPrimero || 'Completa los datos de tu empresa para continuar.', 'aviso');
                var primero = grupoEmpresa.querySelector('input, select, textarea');
                if (primero) {
                    primero.focus();
                }
                return;
            }

            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                mostrarAlerta('Revisa los campos marcados para continuar.', 'error');
                var invalido = form.querySelector(':invalid');
                if (invalido) {
                    invalido.focus();
                }
                return;
            }

            var datos = new FormData(form);
            datos.set('action', 'SendFormToDatabaseAndAPI');
            datos.set('nonceContactForm', nonce());

            ocupado(true);

            fetch(ajaxUrl, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin'
            })
                .then(leerRespuesta)
                .then(function (respuesta) {
                    var payload = respuesta && respuesta.data ? respuesta.data : {};
                    var exito = respuesta && (respuesta.success === true || respuesta.validate === true);

                    if (!exito) {
                        ocupado(false);
                        mostrarAlerta(
                            payload.message || (respuesta && respuesta.error) ||
                            'No pudimos procesar tu solicitud. Inténtalo de nuevo.',
                            'error'
                        );

                        if (payload.fields && typeof payload.fields === 'object') {
                            marcarCampos(payload.fields);
                            revelarEmpresa();
                        }
                        return;
                    }

                    if (payload.wompi_redirect) {
                        window.location.assign(payload.wompi_redirect);
                        return;
                    }

                    if (payload.redirect || redirect) {
                        window.location.assign(destinoGracias(payload, selectPaquete.value));
                        return;
                    }

                    ocupado(false);
                    mostrarAlerta(
                        payload.message || 'Recibimos tu solicitud. Te contactaremos pronto.',
                        'ok'
                    );
                })
                .catch(function () {
                    ocupado(false);
                    mostrarAlerta(i18n.errorRed || 'No pudimos enviar tu solicitud. Inténtalo de nuevo.', 'error');
                });
        });

        function destinoGracias(payload, slug) {
            var base = payload.redirect || redirect || '/gracias-compra-paquete/';
            var nombre = nombrePaquete(slug);

            try {
                var url = new URL(base, window.location.origin);
                if (slug) {
                    url.searchParams.set('paquete', slug);
                }
                if (nombre) {
                    url.searchParams.set('paquete-nombre', nombre);
                }
                if (payload.message) {
                    url.searchParams.set('mensaje', String(payload.message).slice(0, 180));
                }
                return url.toString();
            } catch (error) {
                return base;
            }
        }

        function nombrePaquete(slug) {
            var card = document.querySelector('.pack-card[data-paquete="' + slug + '"] .pack-card__nombre');
            return card ? card.textContent.trim() : '';
        }

        function ocupado(activo) {
            submit.disabled = activo;
            submit.setAttribute('aria-busy', activo ? 'true' : 'false');

            if (spinner) {
                spinner.hidden = !activo;
            }
            if (submitTexto) {
                submitTexto.textContent = activo
                    ? (i18n.enviando || 'Enviando…')
                    : (i18n.comprar || 'Comprar paquete');
            }
        }

        /* --- Mensajes y campos inválidos ------------------------------- */
        function mostrarAlerta(texto, tipo) {
            if (!alerta) {
                return;
            }
            alerta.textContent = texto;
            alerta.className = 'paquete-alerta is-' + (tipo || 'error');
            alerta.hidden = false;
        }

        function ocultarAlerta() {
            if (!alerta) {
                return;
            }
            alerta.textContent = '';
            alerta.hidden = true;
            alerta.className = 'paquete-alerta';
        }

        function marcarCampos(errores) {
            Object.keys(errores).forEach(function (nombre) {
                var campo = form.elements[nombre];
                var ayuda = form.querySelector('[data-error="' + nombre + '"]');

                if (campo) {
                    campo.classList.add('is-invalid');
                    campo.setAttribute('aria-invalid', 'true');
                }
                if (ayuda) {
                    ayuda.textContent = errores[nombre];
                }
            });
        }

        function limpiarCamposInvalidos() {
            form.querySelectorAll('.is-invalid').forEach(function (campo) {
                campo.classList.remove('is-invalid');
                campo.removeAttribute('aria-invalid');
            });
            form.querySelectorAll('[data-error]').forEach(function (ayuda) {
                ayuda.textContent = '';
            });
        }

        form.addEventListener('input', function (event) {
            var campo = event.target;
            if (!campo || !campo.classList || !campo.classList.contains('is-invalid')) {
                return;
            }
            campo.classList.remove('is-invalid');
            campo.removeAttribute('aria-invalid');

            var ayuda = form.querySelector('[data-error="' + campo.name + '"]');
            if (ayuda) {
                ayuda.textContent = '';
            }
        });

        /* --- Utilidades ------------------------------------------------- */
        function nonce() {
            var campo = form.elements.nonceContactForm;
            return campo ? campo.value : '';
        }

        function leerRespuesta(response) {
            return response.text().then(function (texto) {
                var datos = null;
                try {
                    datos = texto ? JSON.parse(texto) : null;
                } catch (error) {
                    datos = null;
                }

                if (!datos && !response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return datos || {};
            });
        }
    });
})();
