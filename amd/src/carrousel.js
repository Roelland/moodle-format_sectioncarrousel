// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Transform-based carousel navigation for the Section Carrousel format.
 *
 * Cards are moved via CSS transform so the <ul> can keep overflow:visible,
 * letting Bootstrap dropdown menus extend beyond the carousel boundary without
 * being clipped. The wrapper clips horizontal overflow via overflow-x:clip,
 * which (unlike overflow-x:hidden) does not coerce overflow-y to auto.
 *
 * @module    format_sectioncarrousel/carrousel
 * @copyright 2026 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/modal'], function(Modal) {
    'use strict';

    var initialized = false;

    /** Per-wrapper scroll state (index = current first-visible card index). */
    var state = new WeakMap();

    function getStep(container) {
        var card = container.querySelector('.carrousel-card-item');
        if (!card) {
            return 216;
        }
        var gap = parseFloat(window.getComputedStyle(container).gap) || 16;
        return card.offsetWidth + gap;
    }

    function getVisibleCount(container) {
        var card = container.querySelector('.carrousel-card-item');
        if (!card) {
            return 3;
        }
        var step = card.offsetWidth + (parseFloat(window.getComputedStyle(container).gap) || 16);
        var wrapperWidth = container.closest('.carrousel-nav-wrapper').clientWidth;
        return Math.max(1, Math.floor(wrapperWidth / step));
    }

    function getTotalCards(container) {
        return container.querySelectorAll('.carrousel-card-item').length;
    }

    function applyTransform(container, index) {
        container.style.transform = 'translateX(-' + (index * getStep(container)) + 'px)';
    }

    function showSubcourseModal(contentEl) {
        var titleEl   = contentEl.querySelector('.carrousel-modal-title');
        var bodyEl    = contentEl.querySelector('.carrousel-modal-body');
        var courseUrl = contentEl.getAttribute('data-courseurl');
        var gotoText  = contentEl.getAttribute('data-gototext');
        if (!titleEl || !bodyEl) {
            return;
        }
        var footer = courseUrl
            ? '<a href="' + courseUrl + '" class="btn btn-primary">' + gotoText + '</a>'
            : '';
        Modal.create({
            title: titleEl.textContent,
            body: bodyEl.innerHTML,
            footer: footer,
            isVerticallyCentered: true,
            scrollable: true,
            removeOnClose: true,
        }).then(function(modal) {
            modal.show();
            return modal;
        });
    }

    function init() {
        if (initialized) {
            return;
        }
        initialized = true;

        document.addEventListener('click', function(e) {
            // Carousel prev/next navigation.
            var btn = e.target.closest('.carrousel-prev-btn, .carrousel-next-btn');
            if (btn) {
                var wrapper = btn.closest('.carrousel-nav-wrapper');
                if (!wrapper) {
                    return;
                }
                var container = wrapper.querySelector('.carrousel-scroll-container');
                if (!container) {
                    return;
                }

                if (!state.has(wrapper)) {
                    state.set(wrapper, {index: 0});
                }
                var s = state.get(wrapper);

                var max = Math.max(0, getTotalCards(container) - getVisibleCount(container));

                if (btn.classList.contains('carrousel-prev-btn')) {
                    s.index = Math.max(0, s.index - 1);
                } else {
                    s.index = Math.min(max, s.index + 1);
                }

                applyTransform(container, s.index);
                return;
            }

            // Subcourse info modal.
            var infoBtn = e.target.closest('.carrousel-subcourse-info-btn');
            if (infoBtn) {
                var modalId = infoBtn.getAttribute('data-modal');
                var contentEl = modalId ? document.getElementById(modalId) : null;
                if (contentEl) {
                    showSubcourseModal(contentEl);
                }
            }

            // Restriction info modal (student only — teacher overlay is a plain <a> link).
            var restrictionBtn = e.target.closest('.carrousel-restriction-btn');
            if (restrictionBtn) {
                e.preventDefault();
                e.stopPropagation();
                var restrictionTitle = restrictionBtn.getAttribute('data-restriction-title');
                var restrictionBody  = restrictionBtn.getAttribute('data-restriction-body');
                if (restrictionBody) {
                    Modal.create({
                        title: restrictionTitle || '',
                        body: restrictionBody,
                        isVerticallyCentered: true,
                        scrollable: true,
                        removeOnClose: true,
                    }).then(function(modal) {
                        modal.show();
                        return modal;
                    });
                }
            }
        });
    }

    return {
        init: init
    };
});
