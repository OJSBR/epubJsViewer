/**
 * @file cypress/tests/functional/EpubJsViewer.cy.js
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Functional tests on OJS, OPS and OMP.
 *
 * Parameters (--env): contextPath, adminUser, adminPassword (captcha on login
 * must be off for the run). The defaults match the data set of PKP's
 * continuous integration, and the first test enables the plugin when it is off.
 *
 * The reader tests also need epubPage: the path, from the site root, of an
 * article, preprint or book page that offers an EPUB (for example
 * index.php/journal/article/view/12). They are skipped without it: the data set
 * of PKP's continuous integration has no EPUB.
 */

describe('EPUB.js Viewer plugin', function() {
	const contextPath = Cypress.env('contextPath') || 'publicknowledge';
	const adminUser = Cypress.env('adminUser') || 'admin';
	const adminPassword = Cypress.env('adminPassword') || 'admin';
	const epubPage = Cypress.env('epubPage');

	// ---- OJSBR spec helpers (padrão v2): work on OJS/OMP 3.3, 3.4 and 3.5 and in PKP's CI ----

	const pageUrl = (path) => '/index.php/' + contextPath + (path ? '/' + path : '');

	// Same as PKP's cy.waitJQuery(), which the support files of OJS 3.3 test sites may lack.
	// The Plugins tab can keep requests open for a while (the plugin gallery), hence the timeout.
	const waitJQuery = () => cy.window().its('jQuery.active', {timeout: 60000}).should('eq', 0);

	// Requests carry the browser's User-Agent: OJS 3.3 drops a session whose agent changes.
	const request = (options) => cy.window({log: false}).then((win) => cy.request(Object.assign(
		typeof options === 'string' ? {url: options} : options,
		{headers: Object.assign({'User-Agent': win.navigator.userAgent}, (typeof options === 'string' ? {} : options.headers) || {})}
	)));

	// Signs in through requests (the login page can re-render while it is typed into), then
	// falls back to the form when the session did not stick (OJS 3.3 cookie handling).
	const login = (username, password) => {
		cy.clearCookies();
		request(pageUrl('login')).then((response) => {
			const token = /name="csrfToken" value="([^"]+)"/.exec(response.body)[1];
			// The form posts to the URL with the language: a redirect would turn the POST into a GET.
			const action = /<form[^>]*id="login"[^>]*action="([^"]+)"/.exec(response.body)[1];
			request({method: 'POST', url: action, form: true, body: {csrfToken: token, username: username, password: password}, log: false});
		});
		cy.visit(pageUrl('submissions') + '?reload=' + Date.now());
		cy.get('body').then(($body) => {
			if ($body.find('form#login').length) {
				cy.get('form#login input[name="username"]').type(username, {delay: 0});
				cy.get('form#login input[name="password"]').type(password, {delay: 0, log: false});
				cy.get('form#login').submit();
				cy.get('form#login', {timeout: 30000}).should('not.exist');
			}
		});
	};

	// REST API calls made from the page itself, so they carry the browser's own session.
	const api = (path, options = {}) => cy.window({log: false}).then((win) => cy.wrap(
		win.fetch(path, Object.assign({credentials: 'same-origin'}, options)).then((response) => {
			if (!response.ok) {
				return response.text().then((text) => {
					throw new Error(path + ' answered ' + response.status + ': ' + text.slice(0, 300));
				});
			}
			return response.json();
		}),
		{log: false, timeout: 30000}
	));

	// The website settings page on its Plugins tab (a new query string forces a load). Load it
	// once per test: loading it again while its plugin gallery request is pending stalls the
	// web server of PKP's CI; API calls and settings modals work on the page already open.
	const openPluginsTab = () => {
		cy.visit(pageUrl('management/settings/website') + '?reload=' + Date.now() + '#plugins');
		cy.get('button[id="plugins-button"]', {timeout: 60000}).click();
		cy.get('button[id="plugins-button"]').should('have.attr', 'aria-selected', 'true');
		waitJQuery();
	};

	// Enables the plugin in the grid when it is off (never turns it off).
	const enablePlugin = (rowName) => {
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]', {timeout: 30000}).then(($checkbox) => {
			if (!$checkbox.is(':checked')) {
				cy.wrap($checkbox).click();
				waitJQuery();
			}
		});
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]').should('be.checked');
	};

	// Opens the settings modal from the grid, without reloading the page: a reload right
	// after saving can stall the web server of PKP's CI. The form is fetched each time.
	const openPluginSettings = (rowName, formSelector) => {
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]', {timeout: 30000}).then(($link) => {
			if (!$link.is(':visible')) {
				cy.get('tr[id$="-row-' + rowName + '"] a.show_extras').first().click();
			}
		});
		// The grid may still be animating the extras row: the link is clicked once it exists.
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]').first().click({force: true});
		waitJQuery();
		cy.window().should((win) => {
			expect(win.jQuery(formSelector).data('pkp.handler')).to.exist;
		});
	};

	// ---- end of helpers ----

	// The first link whose label says EPUB, in any of the three applications.
	const epubLink = () => cy.get('a').filter((i, a) => /epub/i.test(a.textContent)).first();

	const openReader = () => {
		cy.visit('/' + epubPage.replace(/^\//, ''));
		epubLink().click();
		cy.get('body').should('have.class', 'epubjs_viewer');
		// The epub.js iframe only appears once the book has actually been opened.
		cy.get('#epubjs_reader iframe', {timeout: 60000}).should('exist');
	};

	it('Enables the plugin', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		enablePlugin('epubjsviewerplugin');
	});

	(epubPage ? it : it.skip)('Opens an EPUB in the reader instead of downloading it', function() {
		openReader();
		cy.get('.epubjs_toolbar').should('be.visible');
		// The error message is always in the page with the hidden attribute; only a failed load reveals it.
		cy.get('#epubjs_error').should('not.be.visible');
	});

	(epubPage ? it : it.skip)('Pages, changes the zoom and offers a way back', function() {
		openReader();

		cy.get('#epubjs_zoomlevel').invoke('text').then((start) => {
			cy.get('#epubjs_zoomin').click();
			cy.get('#epubjs_zoomlevel').should('not.have.text', start);
			cy.get('#epubjs_zoomout').click();
			cy.get('#epubjs_zoomlevel').should('have.text', start);
		});

		cy.get('#epubjs_toc option').should('have.length.at.least', 1);

		// On OMP the back link once collapsed to zero width: visible, not only present, is what counts.
		cy.get('.header_view a.return').should('be.visible').click();
		cy.url().should('match', /\/(article|preprint)\/view\/|\/catalog\/book\//);
	});
});
