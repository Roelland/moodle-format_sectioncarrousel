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
 * Scroll-snap carousel navigation for the Section Carrousel format.
 *
 * A single delegated click listener handles all .carrousel-nav-wrapper elements
 * on the page, including those injected later via AJAX section updates.
 *
 * @module    format_sectioncarrousel/carrousel
 * @copyright 2026 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    var initialized = false;

    /**
     * Attach a single delegated listener that handles all carousel prev/next buttons.
     */
    function init() {
        if (initialized) {
            return;
        }
        initialized = true;

        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.carrousel-prev-btn, .carrousel-next-btn');
            if (!btn) {
                return;
            }
            var wrapper = btn.closest('.carrousel-nav-wrapper');
            if (!wrapper) {
                return;
            }
            var container = wrapper.querySelector('.carrousel-scroll-container');
            if (!container) {
                return;
            }
            var card = container.querySelector('.carrousel-card-item');
            var gap = parseFloat(window.getComputedStyle(container).gap) || 16;
            var step = card ? (card.offsetWidth + gap) : 216;
            var direction = btn.classList.contains('carrousel-prev-btn') ? -1 : 1;
            container.scrollBy({left: direction * step, behavior: 'smooth'});
        });
    }

    return {
        init: init
    };
});
