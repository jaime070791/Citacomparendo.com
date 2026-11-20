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
        sedesFilter();
        reveals();
        counters();
        initMap();
    });

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
       Filtro de sedes por zona
       --------------------------------------------------------------------- */
    function sedesFilter() {
        var tabs = document.querySelectorAll('.sedes__tabs .nav-link');
        var items = document.querySelectorAll('.sede-item');
        if (!tabs.length || !items.length) {
            return;
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (item) {
                    item.classList.remove('active');
                    item.setAttribute('aria-pressed', 'false');
                });
                tab.classList.add('active');
                tab.setAttribute('aria-pressed', 'true');

                var zona = tab.getAttribute('data-zona');
                items.forEach(function (item) {
                    var visible = zona === 'medellin' || zona === 'todas' || zona === 'cercade' || item.getAttribute('data-zona') === zona;
                    item.classList.toggle('d-none', !visible);
                });

                if (zona === 'cercade') {
                    centerOnUser();
                }
            });
        });
    }

    function centerOnUser() {
        if (!navigator.geolocation || !window.citaMap) {
            return;
        }
        navigator.geolocation.getCurrentPosition(function (position) {
            window.citaMap.setView([position.coords.latitude, position.coords.longitude], 13);
        }, function () { /* el usuario rechazó el permiso: no hacemos nada */ });
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

    /* ---------------------------------------------------------------------
       Mapa de sedes (Leaflet + OpenStreetMap)
       --------------------------------------------------------------------- */
    function initMap() {
        var container = document.getElementById('mapa-sedes');
        if (!container || typeof L === 'undefined' || !window.CITA_MAP) {
            return;
        }

        var data = window.CITA_MAP;
        var fallback = document.getElementById('mapa-fallback');
        if (fallback) {
            fallback.remove();
        }

        var map = L.map(container, {
            scrollWheelZoom: false,
            zoomControl: true
        }).setView(data.center, data.zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var size = [32, 46];
        var anchor = [16, 45];
        var blueIcon = L.icon({ iconUrl: data.pinBlue, iconSize: size, iconAnchor: anchor, popupAnchor: [0, -38] });
        var redIcon = L.icon({ iconUrl: data.pinRed, iconSize: size, iconAnchor: anchor, popupAnchor: [0, -38] });

        var escape = function (text) {
            var div = document.createElement('div');
            div.textContent = text === undefined || text === null ? '' : text;
            return div.innerHTML;
        };

        data.pins.forEach(function (pin) {
            var popup = '<strong>' + escape(pin.nombre) + '</strong><br>' +
                escape(pin.direccion) + '<br>' + escape(pin.horario) + '<br>' +
                '<a href="' + escape(pin.url) + '" target="_blank" rel="noopener">Agendar por WhatsApp</a>';

            L.marker([pin.lat, pin.lng], { icon: pin.activa ? redIcon : blueIcon })
                .addTo(map)
                .bindPopup(popup);
        });

        window.citaMap = map;
    }
})();
