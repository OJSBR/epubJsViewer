{**
 * plugins/generic/epubJsViewer/templates/display.tpl
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Embedded reading of an EPUB file with epub.js.
 *
 * @hook Templates::Common::Footer::PageFooter []
 *}
<!DOCTYPE html>
<html lang="{$currentLocale|replace:"_":"-"|escape}" xml:lang="{$currentLocale|replace:"_":"-"|escape}">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	{* OMP has no article.pageTitle, so the title is used as it is. *}
	<title>
	{if $isTitleHtml}
		{$title|strip_tags|escape}
	{else}
		{$title|escape}
	{/if}
	</title>
	{load_header context="frontend" headers=$headers}
	{load_stylesheet context="frontend" stylesheets=$stylesheets}
	{load_script context="frontend" scripts=$scripts}
	<link rel="stylesheet" href="{$pluginUrl|escape}/styles/reader.css" type="text/css" />
</head>
<body class="pkp_page_{$requestedPage|escape} pkp_op_{$requestedOp|escape} epubjs_viewer">

	<header class="header_view">
		<a href="{$parentUrl|escape}" class="return">
			<span class="pkp_screen_reader">
				{* article.return and issue.return exist only in OJS; common.back exists in all three applications. *}
				{if $issue && !$submission}{translate key="issue.return"}
				{elseif $issue}{translate key="article.return"}
				{else}{translate key="common.back"}{/if}
			</span>
		</a>
		<span class="title">{$galleyTitle|escape}</span>
		<a href="{$epubUrl|escape}" class="download" download>
			<span class="label">{translate key="common.download"}</span>
		</a>
	</header>

	{if !$isLatestPublication}
		<div class="galley_view_outdated" role="status">{$datePublished}</div>
	{/if}

	<div id="epubjs_toolbar" class="epubjs_toolbar">
		<button type="button" id="epubjs_prev" class="epubjs_btn" aria-label="{"plugins.generic.epubJsViewer.previous"|translate|escape}" title="{"plugins.generic.epubJsViewer.previous"|translate|escape}">&#8249;</button>

		<select id="epubjs_toc" class="epubjs_toc" aria-label="{"plugins.generic.epubJsViewer.toc"|translate|escape}"></select>

		<span class="epubjs_group">
			<button type="button" id="epubjs_zoomout" class="epubjs_btn epubjs_zoom" aria-label="{"plugins.generic.epubJsViewer.zoomOut"|translate|escape}" title="{"plugins.generic.epubJsViewer.zoomOut"|translate|escape}">A&minus;</button>
			<span id="epubjs_zoomlevel" class="epubjs_zoomlevel" aria-live="polite">100%</span>
			<button type="button" id="epubjs_zoomin" class="epubjs_btn epubjs_zoom" aria-label="{"plugins.generic.epubJsViewer.zoomIn"|translate|escape}" title="{"plugins.generic.epubJsViewer.zoomIn"|translate|escape}">A+</button>
		</span>

		<button type="button" id="epubjs_mode" class="epubjs_btn" aria-label="{"plugins.generic.epubJsViewer.mode"|translate|escape}" title="{"plugins.generic.epubJsViewer.mode"|translate|escape}">&#9707;</button>

		<button type="button" id="epubjs_next" class="epubjs_btn" aria-label="{"plugins.generic.epubJsViewer.next"|translate|escape}" title="{"plugins.generic.epubJsViewer.next"|translate|escape}">&#8250;</button>
	</div>

	<div id="epubjs_area" class="epubjs_area">
		<div id="epubjs_reader" data-epub-url="{$epubUrl|escape}"></div>
		<noscript>
			<p class="epubjs_fallback">
				{translate key="plugins.generic.epubJsViewer.noScript"}
				<a href="{$epubUrl|escape}">{translate key="common.download"}</a>
			</p>
		</noscript>
	</div>

	<p id="epubjs_error" class="epubjs_fallback" hidden>
		{translate key="plugins.generic.epubJsViewer.loadError"}
		<a href="{$epubUrl|escape}">{translate key="common.download"}</a>
	</p>

	<script src="{$pluginUrl|escape}/lib/jszip/jszip.min.js"></script>
	<script src="{$pluginUrl|escape}/lib/epub.js/epub.min.js"></script>
	<script src="{$pluginUrl|escape}/js/reader.js"></script>
	{call_hook name="Templates::Common::Footer::PageFooter"}
</body>
</html>
