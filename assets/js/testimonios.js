/* ==========================================================================
   CITA COMPARENDO - testimonios.js
   Reproduce los audios de los testimonios: play/pause y solo uno a la vez.
   ========================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var botones = Array.prototype.slice.call(document.querySelectorAll('.testimonial-card__audio'));

        if (!botones.length) {
            return;
        }

        botones.forEach(function (boton) {
            var audio = document.getElementById(boton.getAttribute('aria-controls') || '');

            if (!audio) {
                return;
            }

            boton.addEventListener('click', function () {
                if (!audio.paused) {
                    audio.pause();
                    pintarEstado(boton, false);
                    return;
                }

                pausarOtros(boton);

                if (audio.ended) {
                    audio.currentTime = 0;
                }

                var reproduccion = audio.play();

                if (reproduccion && typeof reproduccion.then === 'function') {
                    reproduccion.then(function () {
                        if (!audio.paused) {
                            pintarEstado(boton, true);
                        }
                    }).catch(function () {
                        pintarEstado(boton, false);
                    });
                } else {
                    pintarEstado(boton, true);
                }
            });

            audio.addEventListener('ended', function () {
                audio.currentTime = 0;
                pintarEstado(boton, false);
            });
        });

        function pausarOtros(activo) {
            botones.forEach(function (boton) {
                if (boton === activo) {
                    return;
                }

                var audio = document.getElementById(boton.getAttribute('aria-controls') || '');

                if (audio && !audio.paused) {
                    audio.pause();
                }

                pintarEstado(boton, false);
            });
        }

        function pintarEstado(boton, activo) {
            boton.setAttribute('aria-pressed', activo ? 'true' : 'false');

            var icono = boton.querySelector('i');
            if (icono) {
                icono.classList.toggle('fa-pause', activo);
                icono.classList.toggle('fa-play', !activo);
            }

            var etiqueta = activo
                ? boton.getAttribute('data-label-pause')
                : boton.getAttribute('data-label-play');

            if (etiqueta) {
                boton.setAttribute('aria-label', etiqueta);
            }
        }
    });
})();
