/**
 * Sliders placed with [slider:slug]: previous and next buttons, dots, and an
 * optional timer (data-seconds) that pauses while the pointer or keyboard focus
 * is on the slider. Without this script, the first slide is shown.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function setUp(slider) {
        if (slider.dataset.ready) {
            return;
        }

        slider.dataset.ready = 'true';

        var track = slider.querySelector('.slider-track');
        var slides = Array.prototype.slice.call(track.children);
        var dots = slider.querySelectorAll('.slider-dots button');
        var count = slides.length;
        var isSliding = slider.classList.contains('animation-slide');
        var current = 0;
        var isMoving = false;
        var timer = null;

        if (count < 2) {
            return;
        }

        // In the slide animation, a copy of the last slide sits before the first and a
        // copy of the first after the last, so the slider can carry on past either end
        // and then jump, unseen, back to the real slide.
        if (isSliding) {
            var firstCopy = slides[0].cloneNode(true);
            var lastCopy = slides[count - 1].cloneNode(true);

            [firstCopy, lastCopy].forEach(function (copy) {
                copy.classList.remove('is-active');
                copy.setAttribute('aria-hidden', 'true');
            });

            track.appendChild(firstCopy);
            track.insertBefore(lastCopy, slides[0]);
            moveTrack(1, false);

            track.addEventListener('transitionend', function (event) {
                if (event.target === track) {
                    settle();
                }
            });
        }

        slider.classList.add('is-ready');

        function moveTrack(position, animate) {
            track.style.transition = animate && ! reduceMotion ? '' : 'none';
            track.style.transform = 'translateX(' + (-position * 100) + '%)';
        }

        function settle() {
            isMoving = false;
            moveTrack(current + 1, false);
        }

        function show(index) {
            if (isMoving) {
                return;
            }

            var next = (index + count) % count;

            slides[current].classList.remove('is-active');
            slides[next].classList.add('is-active');
            dots[current].removeAttribute('aria-current');
            dots[next].setAttribute('aria-current', 'true');

            if (isSliding) {
                isMoving = ! reduceMotion;
                moveTrack(index + 1, true);
            }

            current = next;

            if (isSliding && reduceMotion) {
                settle();
            }
        }

        function startTimer() {
            var seconds = parseFloat(slider.dataset.seconds);

            stopTimer();

            if (seconds > 0 && ! reduceMotion) {
                timer = window.setInterval(function () {
                    show(current + 1);
                }, seconds * 1000);
            }
        }

        function stopTimer() {
            window.clearInterval(timer);
        }

        slider.querySelector('.slider-prev').addEventListener('click', function () {
            show(current - 1);
        });

        slider.querySelector('.slider-next').addEventListener('click', function () {
            show(current + 1);
        });

        dots.forEach(function (dot, index) {
            dot.addEventListener('click', function () {
                show(index);
            });
        });

        slider.addEventListener('mouseenter', stopTimer);
        slider.addEventListener('mouseleave', startTimer);
        slider.addEventListener('focusin', stopTimer);
        slider.addEventListener('focusout', startTimer);

        startTimer();
    }

    document.querySelectorAll('.slider').forEach(setUp);
})();
