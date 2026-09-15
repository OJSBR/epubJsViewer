/**
 * @file plugins/generic/epubJsViewer/js/reader.js
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The reader controls: paging, table of contents, zoom and reading mode. The
 * EPUB address comes from the data-epub-url attribute of #epubjs_reader, so
 * proxies that rewrite links in the page (EZproxy) rewrite it too. The zoom and
 * the mode are remembered between visits in the browser.
 */
(function () {
	'use strict';

	var area = document.getElementById('epubjs_reader');
	var error = document.getElementById('epubjs_error');
	if (!area) {
		return;
	}
	if (!window.ePub) {
		error.hidden = false;
		return;
	}

	var MODES = [
		{flow: 'paginated', spread: 'auto'}, // two pages when they fit
		{flow: 'paginated', spread: 'none'}, // always one page
		{flow: 'scrolled-doc', spread: 'none'} // continuous scrolling
	];
	var ZOOM_MIN = 70, ZOOM_MAX = 250, ZOOM_STEP = 10;
	var MODE_KEY = 'ojsbrEpubModo', ZOOM_KEY = 'ojsbrEpubZoom'; // kept from earlier releases, so readers keep their choice

	function read(key, fallback) {
		try {
			var value = parseInt(localStorage.getItem(key), 10);
			return isNaN(value) ? fallback : value;
		} catch (e) {
			return fallback;
		}
	}

	function write(key, value) {
		try {
			localStorage.setItem(key, String(value));
		} catch (e) {
			// Storage can be blocked; the reader works without it.
		}
	}

	var mode = Math.abs(read(MODE_KEY, 0)) % MODES.length;
	var zoom = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, read(ZOOM_KEY, 100)));

	var book = window.ePub(area.getAttribute('data-epub-url'), {openAs: 'epub'});
	var rendition;
	var zoomLevel = document.getElementById('epubjs_zoomlevel');
	var zoomIn = document.getElementById('epubjs_zoomin');
	var zoomOut = document.getElementById('epubjs_zoomout');

	function applyZoom() {
		if (rendition) {
			rendition.themes.fontSize(zoom + '%');
		}
		zoomLevel.textContent = zoom + '%';
		zoomOut.disabled = zoom <= ZOOM_MIN;
		zoomIn.disabled = zoom >= ZOOM_MAX;
	}

	function render(cfi) {
		if (rendition) {
			rendition.destroy();
		}
		rendition = book.renderTo(area, {
			width: '100%',
			height: '100%',
			flow: MODES[mode].flow,
			spread: MODES[mode].spread,
			// Scripts inside the book never run.
			allowScriptedContent: false
		});
		rendition.display(cfi || undefined);
		// The theme is lost when the rendition is rebuilt.
		applyZoom();
	}

	function currentCfi() {
		try {
			var location = rendition && rendition.currentLocation();
			return location && location.start ? location.start.cfi : null;
		} catch (e) {
			return null;
		}
	}

	render();
	book.ready.catch(function () {
		error.hidden = false;
	});

	document.getElementById('epubjs_prev').addEventListener('click', function () {
		rendition.prev();
	});
	document.getElementById('epubjs_next').addEventListener('click', function () {
		rendition.next();
	});

	document.getElementById('epubjs_mode').addEventListener('click', function () {
		var here = currentCfi();
		mode = (mode + 1) % MODES.length;
		write(MODE_KEY, mode);
		render(here);
	});

	zoomIn.addEventListener('click', function () {
		if (zoom < ZOOM_MAX) {
			zoom += ZOOM_STEP;
			write(ZOOM_KEY, zoom);
			applyZoom();
		}
	});
	zoomOut.addEventListener('click', function () {
		if (zoom > ZOOM_MIN) {
			zoom -= ZOOM_STEP;
			write(ZOOM_KEY, zoom);
			applyZoom();
		}
	});

	document.addEventListener('keyup', function (event) {
		if (event.target && /INPUT|SELECT|TEXTAREA/.test(event.target.tagName)) {
			return;
		}
		if (event.key === 'ArrowLeft') {
			rendition.prev();
		} else if (event.key === 'ArrowRight') {
			rendition.next();
		} else if (event.key === '+' || event.key === '=') {
			zoomIn.click();
		} else if (event.key === '-') {
			zoomOut.click();
		}
	});

	var toc = document.getElementById('epubjs_toc');
	book.loaded.navigation.then(function (navigation) {
		(navigation.toc || []).forEach(function (chapter) {
			var option = document.createElement('option');
			option.value = chapter.href;
			option.textContent = chapter.label ? chapter.label.trim() : chapter.href;
			toc.appendChild(option);
		});
		toc.addEventListener('change', function () {
			if (this.value) {
				rendition.display(this.value);
			}
		});
	});
})();
