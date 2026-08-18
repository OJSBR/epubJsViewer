{**
 * plugins/generic/epubJsViewer/templates/display.tpl
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com.br)
 * Distributed under the GNU GPL v3.
 *
 * Embedded viewing of an EPUB galley using epub.js.
 *}
<!DOCTYPE html>
<html lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>
	{if $isTitleHtml}
		{translate key="article.pageTitle" title=$title|strip_tags|escape}
	{else}
		{translate key="article.pageTitle" title=$title|escape}
	{/if}
	</title>
	{load_header context="frontend" headers=$headers}
	{load_stylesheet context="frontend" stylesheets=$stylesheets}
	{load_script context="frontend" scripts=$scripts}
	<link rel="stylesheet" href="{$pluginUrl}/styles/reader.css" type="text/css" />
</head>
<body class="pkp_page_{$requestedPage|escape} pkp_op_{$requestedOp|escape} epubjs_viewer">

	<header class="header_view">
		<a href="{$parentUrl}" class="return">
			<span class="pkp_screen_reader">
				{if $issue && !$submission}{translate key="issue.return"}{else}{translate key="article.return"}{/if}
			</span>
		</a>
		<span class="title">{$galleyTitle|escape}</span>
		<a href="{$epubUrl}" class="download" download>
			<span class="label">{translate key="common.download"}</span>
		</a>
	</header>

	{if !$isLatestPublication}
		<div class="galley_view_outdated" role="status">{$datePublished}</div>
	{/if}

	<div id="epubjs_toolbar" class="epubjs_toolbar">
		<button type="button" id="epubjs_prev" class="epubjs_btn" aria-label="{translate key="plugins.generic.epubJsViewer.previous"}" title="{translate key="plugins.generic.epubJsViewer.previous"}">&#8249;</button>

		<select id="epubjs_toc" class="epubjs_toc" aria-label="{translate key="plugins.generic.epubJsViewer.toc"}"></select>

		<span class="epubjs_group">
			<button type="button" id="epubjs_zoomout" class="epubjs_btn epubjs_zoom" aria-label="{translate key="plugins.generic.epubJsViewer.zoomOut"}" title="{translate key="plugins.generic.epubJsViewer.zoomOut"}">A&minus;</button>
			<span id="epubjs_zoomlevel" class="epubjs_zoomlevel" aria-live="polite">100%</span>
			<button type="button" id="epubjs_zoomin" class="epubjs_btn epubjs_zoom" aria-label="{translate key="plugins.generic.epubJsViewer.zoomIn"}" title="{translate key="plugins.generic.epubJsViewer.zoomIn"}">A+</button>
		</span>

		<button type="button" id="epubjs_mode" class="epubjs_btn" aria-label="{translate key="plugins.generic.epubJsViewer.mode"}" title="{translate key="plugins.generic.epubJsViewer.mode"}">&#9707;</button>

		<button type="button" id="epubjs_next" class="epubjs_btn" aria-label="{translate key="plugins.generic.epubJsViewer.next"}" title="{translate key="plugins.generic.epubJsViewer.next"}">&#8250;</button>
	</div>

	<div id="epubjs_area" class="epubjs_area">
		<div id="epubjs_reader"></div>
		<noscript>
			<p class="epubjs_fallback">
				{translate key="plugins.generic.epubJsViewer.noScript"}
				<a href="{$epubUrl}">{translate key="common.download"}</a>
			</p>
		</noscript>
	</div>

	<p id="epubjs_error" class="epubjs_fallback" hidden>
		{translate key="plugins.generic.epubJsViewer.loadError"}
		<a href="{$epubUrl}">{translate key="common.download"}</a>
	</p>

	<script src="{$pluginUrl}/js/jszip.min.js"></script>
	<script src="{$pluginUrl}/js/epub.min.js"></script>
	<script>
		(function () {
			var url  = {$epubUrl|json_encode};
			var area = document.getElementById('epubjs_reader');
			var erro = document.getElementById('epubjs_error');
			if (!window.ePub) { erro.hidden = false; return; }

			// --- preferencias do leitor, lembradas entre visitas ---
			var MODOS = [
				{ flow: 'paginated',   spread: 'auto' },  // duas paginas quando couber
				{ flow: 'paginated',   spread: 'none' },  // sempre uma pagina
				{ flow: 'scrolled-doc', spread: 'none' }  // rolagem continua
			];
			var ZOOM_MIN = 70, ZOOM_MAX = 250, ZOOM_PASSO = 10;

			function ler(chave, padrao) {
				try { var v = parseInt(localStorage.getItem(chave), 10); return isNaN(v) ? padrao : v; }
				catch (e) { return padrao; }
			}
			function gravar(chave, valor) {
				try { localStorage.setItem(chave, String(valor)); } catch (e) {}
			}

			var iModo = ler('ojsbrEpubModo', 0) % MODOS.length;
			var zoom  = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, ler('ojsbrEpubZoom', 100)));

			var book = ePub(url, { openAs: 'epub' });
			var rendition;
			var rotulo = document.getElementById('epubjs_zoomlevel');

			function aplicarZoom() {
				if (rendition) { rendition.themes.fontSize(zoom + '%'); }
				rotulo.textContent = zoom + '%';
				document.getElementById('epubjs_zoomout').disabled = (zoom <= ZOOM_MIN);
				document.getElementById('epubjs_zoomin').disabled  = (zoom >= ZOOM_MAX);
			}

			function montar(cfi) {
				if (rendition) { rendition.destroy(); }
				rendition = book.renderTo(area, {
					width: '100%',
					height: '100%',
					flow: MODOS[iModo].flow,
					spread: MODOS[iModo].spread,
					allowScriptedContent: false
				});
				rendition.display(cfi || undefined);
				aplicarZoom();          // o tema se perde ao remontar; reaplica sempre
				return rendition;
			}

			function ondeEstou() {
				try {
					var loc = rendition && rendition.currentLocation();
					return (loc && loc.start) ? loc.start.cfi : null;
				} catch (e) { return null; }
			}

			montar();
			book.ready.catch(function () { erro.hidden = false; });

			// --- controles ---
			document.getElementById('epubjs_prev').addEventListener('click', function () { rendition.prev(); });
			document.getElementById('epubjs_next').addEventListener('click', function () { rendition.next(); });

			document.getElementById('epubjs_mode').addEventListener('click', function () {
				var aqui = ondeEstou();
				iModo = (iModo + 1) % MODOS.length;
				gravar('ojsbrEpubModo', iModo);
				montar(aqui);
			});

			document.getElementById('epubjs_zoomin').addEventListener('click', function () {
				if (zoom >= ZOOM_MAX) { return; }
				zoom += ZOOM_PASSO; gravar('ojsbrEpubZoom', zoom); aplicarZoom();
			});
			document.getElementById('epubjs_zoomout').addEventListener('click', function () {
				if (zoom <= ZOOM_MIN) { return; }
				zoom -= ZOOM_PASSO; gravar('ojsbrEpubZoom', zoom); aplicarZoom();
			});

			document.addEventListener('keyup', function (e) {
				if (e.target && /INPUT|SELECT|TEXTAREA/.test(e.target.tagName)) { return; }
				if (e.key === 'ArrowLeft')  { rendition.prev(); }
				if (e.key === 'ArrowRight') { rendition.next(); }
				if (e.key === '+' || e.key === '=') { document.getElementById('epubjs_zoomin').click(); }
				if (e.key === '-') { document.getElementById('epubjs_zoomout').click(); }
			});

			var sel = document.getElementById('epubjs_toc');
			book.loaded.navigation.then(function (nav) {
				(nav.toc || []).forEach(function (c) {
					var o = document.createElement('option');
					o.value = c.href;
					o.textContent = c.label ? c.label.trim() : c.href;
					sel.appendChild(o);
				});
				sel.addEventListener('change', function () { if (this.value) { rendition.display(this.value); } });
			});
		})();
	</script>
</body>
</html>
