/* ==========================================================================
   CITA COMPARENDO - main.js
   Sin dependencias (Bootstrap se encarga de collapse, tabs y scrollspy).
   ========================================================================== */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.addEventListener('DOMContentLoaded', function () {
        headerShadow();
        closeMobileNav();
        initSedes();
        initSedeMap();
        reveals();
        counters();
    });

    /* ---------------------------------------------------------------------
       Utilidades
       --------------------------------------------------------------------- */
    function escapar(texto) {
        var div = document.createElement('div');
        div.textContent = texto === undefined || texto === null ? '' : texto;
        return div.innerHTML
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function coordenada(valor) {
        if (valor === undefined || valor === null || valor === '') {
            return null;
        }
        var numero = parseFloat(valor);
        return isFinite(numero) ? numero : null;
    }

    function distanciaKm(lat1, lng1, lat2, lng2) {
        var radio = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return radio * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    /* ---------------------------------------------------------------------
       Sombra del header al hacer scroll
       --------------------------------------------------------------------- */
    function headerShadow() {
        var header = document.querySelector('.site-header');
        if (!header) {
            return;
        }
        var toggle = function () {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        toggle();
        window.addEventListener('scroll', toggle, { passive: true });
    }

    /* ---------------------------------------------------------------------
       Cerrar el menú móvil al elegir una sección
       --------------------------------------------------------------------- */
    function closeMobileNav() {
        var collapse = document.getElementById('nav-principal');
        if (!collapse || typeof bootstrap === 'undefined') {
            return;
        }
        collapse.querySelectorAll('a[href^="#"]').forEach(function (link) {
            link.addEventListener('click', function () {
                if (collapse.classList.contains('show')) {
                    bootstrap.Collapse.getOrCreateInstance(collapse).hide();
                }
            });
        });
    }

    /* ---------------------------------------------------------------------
       Sedes: filtros por ciudad/zona, orden por cercanía y mapa
       --------------------------------------------------------------------- */
    function initSedes() {
        var seccion = document.getElementById('sedes');
        var lista = document.getElementById('sedes-lista');
        if (!seccion || !lista) {
            return;
        }

        var cards = Array.prototype.slice.call(lista.querySelectorAll('.sede-card'));
        if (!cards.length) {
            return;
        }

        var select = document.getElementById('filtro-ciudad');
        var botones = Array.prototype.slice.call(seccion.querySelectorAll('.filtro-zona'));
        var vacio = document.getElementById('sedes-vacio');
        var mensaje = document.getElementById('sedes-mensaje');
        var estado = {
            ciudad: 'todas',
            zona: 'todas',
            usuario: null
        };

        var mapa = crearMapaSedes();

        cards.forEach(function (card) {
            var badge = card.querySelector('.sede-card__dist');
            if (badge) {
                badge.textContent = '';
            }

            card.addEventListener('click', function (event) {
                if (event.target.closest('.sede-agendar')) {
                    return;
                }
                activarCard(card, true);
            });
        });

        // La card destacada del HTML (o la primera) arranca como activa.
        var inicial = cards.filter(function (card) {
            return card.classList.contains('is-active');
        })[0] || cards[0];
        activarCard(inicial, false);

        if (select) {
            select.addEventListener('change', function () {
                estado.ciudad = select.value || 'todas';
                if (estado.usuario && estado.zona === 'cercade') {
                    ordenarPorDistancia();
                }
                aplicarFiltro('ciudad');
            });
        }

        botones.forEach(function (boton) {
            boton.addEventListener('click', function () {
                var zona = boton.getAttribute('data-zona') || 'todas';

                if (zona === 'cercade') {
                    if (estado.zona === 'cercade' && estado.usuario) {
                        marcarBotones();
                        aplicarFiltro();
                        if (mapa) {
                            mapa.centrar(estado.usuario.lat, estado.usuario.lng, 13);
                        }
                        return;
                    }
                    pedirUbicacion();
                    return;
                }

                estado.zona = zona;
                marcarBotones();
                limpiarMensaje();
                aplicarFiltro();
            });
        });

        aplicarFiltro();

        /* --- Card activa ------------------------------------------------ */
        function activarCard(card, centrar) {
            if (!card) {
                return;
            }
            cards.forEach(function (otra) {
                otra.classList.remove('is-active');
            });
            card.classList.add('is-active');

            if (mapa) {
                mapa.activar(card, centrar);
            }
        }

        /* --- Filtros ---------------------------------------------------- */
        function aplicarFiltro(origen) {
            var visibles = [];

            cards.forEach(function (card) {
                var porCiudad = estado.ciudad === 'todas' ||
                    card.getAttribute('data-ciudad-slug') === estado.ciudad;
                var porZona = estado.zona === 'todas' ||
                    estado.zona === 'cercade' ||
                    card.getAttribute('data-zona') === estado.zona;
                var visible = porCiudad && porZona;
                var columna = card.closest('.sede-item');

                if (columna) {
                    columna.classList.toggle('d-none', !visible);
                }
                if (visible) {
                    visibles.push(card);
                }
            });

            if (vacio) {
                vacio.hidden = visibles.length > 0;
            }

            if (mapa) {
                mapa.visibles(visibles);

                if (origen === 'ciudad') {
                    centrarCiudad(visibles);
                }
                if (estado.zona !== 'cercade') {
                    mapa.quitarUsuario();
                }
            }
        }

        function centrarCiudad(visibles) {
            if (!mapa) {
                return;
            }

            if (estado.ciudad === 'todas' || !select) {
                mapa.centrar(mapa.centro.lat, mapa.centro.lng, mapa.centro.zoom);
                return;
            }

            var opcion = select.options[select.selectedIndex];
            if (!opcion) {
                return;
            }

            var lat = coordenada(opcion.getAttribute('data-lat'));
            var lng = coordenada(opcion.getAttribute('data-lng'));
            var zoom = parseInt(opcion.getAttribute('data-zoom'), 10) || 12;

            if (lat !== null && lng !== null) {
                mapa.centrar(lat, lng, zoom);
            } else if (visibles.length) {
                mapa.encuadrar(visibles);
            }
        }

        /* --- Cerca de mí ------------------------------------------------ */
        function pedirUbicacion() {
            if (!navigator.geolocation) {
                fallarUbicacion('Tu navegador no permite obtener tu ubicación.');
                return;
            }

            estado.zona = 'cercade';
            marcarBotones();
            mostrarMensaje('Buscando tu ubicación…');

            navigator.geolocation.getCurrentPosition(function (position) {
                estado.usuario = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                ordenarPorDistancia();
                aplicarFiltro();

                // La sede más cercana pasa a ser la activa (pin rojo) sin mover el mapa.
                var cercana = Array.prototype.slice.call(lista.querySelectorAll('.sede-card')).filter(function (card) {
                    var columna = card.closest('.sede-item');
                    return columna && !columna.classList.contains('d-none');
                })[0];
                if (cercana && cercana !== inicial) {
                    activarCard(cercana, false);
                }

                if (mapa) {
                    mapa.mostrarUsuario(estado.usuario.lat, estado.usuario.lng);
                    mapa.centrar(estado.usuario.lat, estado.usuario.lng, 13);
                }

                mostrarMensaje('Mostrando las sedes más cercanas a tu ubicación.');
            }, function () {
                fallarUbicacion('No pudimos obtener tu ubicación. Mostrando todas las sedes.');
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            });
        }

        function fallarUbicacion(texto) {
            estado.zona = 'todas';
            estado.usuario = null;
            marcarBotones();
            pintarDistancias(null);
            if (mapa) {
                mapa.quitarUsuario();
            }
            aplicarFiltro();
            mostrarMensaje(texto, true);
        }

        function ordenarPorDistancia() {
            cardDistancias().sort(function (a, b) {
                return a.km - b.km;
            }).forEach(function (item) {
                lista.appendChild(item.card.closest('.sede-item'));
            });

            pintarDistancias(estado.usuario);
        }

        function cardDistancias() {
            var listaDistancias = [];

            cards.forEach(function (card) {
                var lat = coordenada(card.getAttribute('data-lat'));
                var lng = coordenada(card.getAttribute('data-lng'));

                if (!estado.usuario || lat === null || lng === null) {
                    return;
                }

                listaDistancias.push({
                    card: card,
                    km: distanciaKm(estado.usuario.lat, estado.usuario.lng, lat, lng)
                });
            });

            return listaDistancias;
        }

        function pintarDistancias(usuario) {
            cards.forEach(function (card) {
                var badge = card.querySelector('.sede-card__dist');
                var lat = coordenada(card.getAttribute('data-lat'));
                var lng = coordenada(card.getAttribute('data-lng'));

                if (!badge) {
                    return;
                }
                if (!usuario || lat === null || lng === null) {
                    badge.textContent = '';
                    badge.hidden = true;
                    return;
                }

                var km = distanciaKm(usuario.lat, usuario.lng, lat, lng);
                var texto = km < 1
                    ? Math.round(km * 1000) + ' m'
                    : km.toFixed(1).replace('.', ',') + ' km';

                badge.textContent = 'A ' + texto;
                badge.hidden = false;
            });
        }

        /* --- Estados ---------------------------------------------------- */
        function marcarBotones() {
            botones.forEach(function (boton) {
                var activo = boton.getAttribute('data-zona') === estado.zona;
                boton.classList.toggle('is-active', activo);
                boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
            });
        }

        function mostrarMensaje(texto, esError) {
            if (!mensaje) {
                return;
            }
            mensaje.textContent = texto;
            mensaje.classList.toggle('is-error', Boolean(esError));
            mensaje.hidden = false;
        }

        function limpiarMensaje() {
            if (!mensaje) {
                return;
            }
            mensaje.textContent = '';
            mensaje.hidden = true;
            mensaje.classList.remove('is-error');
        }

        /* --- Leaflet ---------------------------------------------------- */
        function crearMapaSedes() {
            var contenedor = document.getElementById('mapa-sedes');
            if (!contenedor || typeof L === 'undefined') {
                return null;
            }

            var marcadores = [];
            var centro = {
                lat: coordenada(contenedor.getAttribute('data-lat')),
                lng: coordenada(contenedor.getAttribute('data-lng')),
                zoom: parseInt(contenedor.getAttribute('data-zoom'), 10) || 5
            };

            if (centro.lat === null || centro.lng === null) {
                centro.lat = 4.5709;
                centro.lng = -74.2973;
            }

            cards.forEach(function (card) {
                var lat = coordenada(card.getAttribute('data-lat'));
                var lng = coordenada(card.getAttribute('data-lng'));
                if (lat === null || lng === null) {
                    return;
                }
                marcadores.push({ card: card, lat: lat, lng: lng });
            });

            // Sin coordenadas no se crea el mapa: se deja el fallback visible.
            if (!marcadores.length) {
                return null;
            }

            var fallback = document.getElementById('mapa-fallback');
            if (fallback) {
                fallback.remove();
            }

            contenedor.classList.add('is-ready');

            var opciones = {
                scrollWheelZoom: false,
                zoomControl: true
            };

            var map = L.map(contenedor, opciones).setView([centro.lat, centro.lng], centro.zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            var tamano = [32, 46];
            var ancla = [16, 45];
            var azul = contenedor.getAttribute('data-pin-blue');
            var rojo = contenedor.getAttribute('data-pin-red');

            var iconoAzul = azul
                ? L.icon({ iconUrl: azul, iconSize: tamano, iconAnchor: ancla, popupAnchor: [0, -38] })
                : new L.Icon.Default();
            var iconoRojo = rojo
                ? L.icon({ iconUrl: rojo, iconSize: tamano, iconAnchor: ancla, popupAnchor: [0, -38] })
                : new L.Icon.Default();

            var capa = L.layerGroup().addTo(map);
            window.citaMap = map;
            var usuario = null;
            var activa = null;

            marcadores.forEach(function (item) {
                item.marker = L.marker([item.lat, item.lng], {
                    icon: iconoAzul,
                    title: item.card.getAttribute('data-nombre') || '',
                    alt: item.card.getAttribute('data-nombre') || ''
                });
                item.marker.bindPopup(popupDe(item.card), { maxWidth: 280 });
                item.marker.on('click', function () {
                    activarCard(item.card, false);
                });
            });

            // Reajusta el tamaño cuando cambia el viewport o la orientación.
            var temporizador = null;
            window.addEventListener('resize', function () {
                clearTimeout(temporizador);
                temporizador = setTimeout(function () {
                    map.invalidateSize();
                }, 200);
            });

            return {
                centro: centro,

                visibles: function (visibles) {
                    capa.clearLayers();
                    visibles.forEach(function (card) {
                        var item = marcadores.filter(function (m) {
                            return m.card === card;
                        })[0];
                        if (item) {
                            item.marker.addTo(capa);
                        }
                    });
                },

                activar: function (card, centrar) {
                    activa = card;

                    marcadores.forEach(function (item) {
                        item.marker.setIcon(item.card === activa ? iconoRojo : iconoAzul);
                        item.marker.setZIndexOffset(item.card === activa ? 1000 : 0);
                    });

                    var item = marcadores.filter(function (m) {
                        return m.card === card;
                    })[0];

                    if (!item) {
                        return;
                    }

                    if (!capa.hasLayer(item.marker)) {
                        item.marker.addTo(capa);
                    }

                    if (centrar) {
                        if (reduceMotion) {
                            map.setView([item.lat, item.lng], 15);
                        } else {
                            map.flyTo([item.lat, item.lng], 15, { duration: 0.6 });
                        }
                        item.marker.openPopup();
                    }
                },

                centrar: function (lat, lng, zoom) {
                    map.setView([lat, lng], zoom || 12);
                },

                encuadrar: function (visibles) {
                    var puntos = [];
                    visibles.forEach(function (card) {
                        var lat = coordenada(card.getAttribute('data-lat'));
                        var lng = coordenada(card.getAttribute('data-lng'));
                        if (lat !== null && lng !== null) {
                            puntos.push([lat, lng]);
                        }
                    });
                    if (puntos.length) {
                        map.fitBounds(puntos, { padding: [40, 40], maxZoom: 15 });
                    }
                },

                mostrarUsuario: function (lat, lng) {
                    if (!usuario) {
                        usuario = L.circleMarker([lat, lng], {
                            radius: 8,
                            weight: 3,
                            color: '#0F2E5A',
                            fillColor: '#4C9BFF',
                            fillOpacity: 1
                        }).addTo(map);
                        usuario.bindTooltip('Tu ubicación', { direction: 'top', offset: [0, -6] });
                    } else {
                        usuario.setLatLng([lat, lng]);
                    }
                    usuario.bringToFront();
                },

                quitarUsuario: function () {
                    if (usuario) {
                        map.removeLayer(usuario);
                        usuario = null;
                    }
                }
            };
        }

        function popupDe(card) {
            var nombre = card.getAttribute('data-nombre') || '';
            var direccion = card.getAttribute('data-direccion') || '';
            var horario = card.getAttribute('data-horario') || '';
            var lat = coordenada(card.getAttribute('data-lat'));
            var lng = coordenada(card.getAttribute('data-lng'));
            var html = '<strong>' + escapar(nombre) + '</strong>';

            if (direccion) {
                html += '<br>' + escapar(direccion);
            }
            if (horario) {
                html += '<br>' + escapar(horario);
            }
            if (lat !== null && lng !== null) {
                html += '<br><a class="map-popup__link" href="https://www.google.com/maps/dir/?api=1&destination=' +
                    lat + ',' + lng + '" target="_blank" rel="noopener">Cómo llegar</a>';
            }

            var url = card.getAttribute('data-url') || '';
            if (url) {
                html += '<br><a class="map-popup__link" href="' + escapar(url) + '">Ver ficha de la sede</a>';
            }

            return html;
        }
    }

    /* ---------------------------------------------------------------------
       Ficha de una sede (single-sede.php): mapa centrado en la sede
       --------------------------------------------------------------------- */
    function initSedeMap() {
        var contenedor = document.getElementById('mapa-sede');
        if (!contenedor || typeof L === 'undefined') {
            return;
        }

        var lat = coordenada(contenedor.getAttribute('data-lat'));
        var lng = coordenada(contenedor.getAttribute('data-lng'));
        if (lat === null || lng === null) {
            return;
        }

        var fallback = document.getElementById('mapa-sede-fallback');
        if (fallback) {
            fallback.remove();
        }

        var map = L.map(contenedor, {
            scrollWheelZoom: false,
            zoomControl: true
        }).setView([lat, lng], parseInt(contenedor.getAttribute('data-zoom'), 10) || 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var rojo = contenedor.getAttribute('data-pin-red');
        var icono = rojo
            ? L.icon({ iconUrl: rojo, iconSize: [32, 46], iconAnchor: [16, 45], popupAnchor: [0, -38] })
            : new L.Icon.Default();

        var nombre = contenedor.getAttribute('data-nombre') || '';
        var direccion = contenedor.getAttribute('data-direccion') || '';
        var popup = '<strong>' + escapar(nombre) + '</strong>';

        if (direccion) {
            popup += '<br>' + escapar(direccion);
        }

        L.marker([lat, lng], { icon: icono, title: nombre, alt: nombre })
            .addTo(map)
            .bindPopup(popup)
            .openPopup();

        window.citaSedeMap = map;
    }

    /* ---------------------------------------------------------------------
       Animaciones de entrada
       --------------------------------------------------------------------- */
    function reveals() {
        var elements = document.querySelectorAll('[data-reveal]');
        if (!elements.length) {
            return;
        }
        if (!('IntersectionObserver' in window) || reduceMotion) {
            elements.forEach(function (element) {
                element.classList.add('is-revealed');
            });
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

        elements.forEach(function (element) {
            observer.observe(element);
        });
    }

    /* ---------------------------------------------------------------------
       Contadores de las cifras
       --------------------------------------------------------------------- */
    function counters() {
        var elements = document.querySelectorAll('.js-count');
        if (!elements.length) {
            return;
        }

        var format = function (value, decimals) {
            var text = decimals ? value.toFixed(decimals) : String(Math.round(value));
            var parts = text.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return parts.join(',');
        };

        var animate = function (element) {
            var target = parseFloat(element.getAttribute('data-target') || '0');
            var prefix = element.getAttribute('data-prefix') || '';
            var suffix = element.getAttribute('data-suffix') || '';
            var decimals = parseInt(element.getAttribute('data-decimals') || '0', 10);

            if (reduceMotion) {
                element.textContent = prefix + format(target, decimals) + suffix;
                return;
            }

            var start = performance.now();
            var duration = 1400;

            var step = function (now) {
                var progress = Math.min((now - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                element.textContent = prefix + format(target * eased, decimals) + suffix;
                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            };
            requestAnimationFrame(step);
        };

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        animate(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.4 });
            elements.forEach(function (element) {
                observer.observe(element);
            });
        } else {
            elements.forEach(animate);
        }
    }
})();
