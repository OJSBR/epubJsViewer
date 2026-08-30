/**
 * @file cypress/tests/functional/EpubJsViewer.cy.js
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com.br)
 * Distributed under the GNU GPL v3.
 *
 * O mesmo plugin atende OJS, OPS e OMP, entao a suite descobre onde esta o
 * conteudo em vez de cravar rota: no OMP pelo catalogo, no OJS e no OPS pela
 * edicao/lista corrente. Roda contra o conjunto de dados de teste da PKP por
 * padrao; para apontar para outra instalacao:
 *   npx cypress run --spec 'plugins/generic/epubJsViewer/cypress/tests/functional/*.cy.js' \
 *     --env contextPath=minharevista,adminUsername=admin,adminPassword=senha
 */

describe('epubJsViewer plugin tests', function () {
	const contexto = Cypress.env('contextPath') || 'publicknowledge';
	const usuario = Cypress.env('adminUsername') || 'admin';
	const senha = Cypress.env('adminPassword') || 'admin';
	const ehOmp = Cypress.env('defaultGenre') === 'Book Manuscript';
	const LIMITE = 10;   // quantas paginas de conteudo abrir procurando um EPUB

	it('Enables the plugin', function () {
		cy.login(usuario, senha, contexto);

		abrirGradeDePlugins();

		// Idempotente de proposito: rodar a suite duas vezes nao pode DESLIGAR o
		// plugin que a primeira execucao ligou.
		cy.get('input[id^="select-cell-epubjsviewerplugin-enabled"]').as('ligar');
		cy.get('@ligar').then(($el) => {
			if (!$el.is(':checked')) {
				cy.get('@ligar').click();
				cy.get('div:contains(\'The plugin "EPUB.js Viewer" has been enabled.\')');
				cy.waitJQuery();
			}
		});
		cy.reload();
		abrirGradeDePlugins();
		cy.get('input[id^="select-cell-epubjsviewerplugin-enabled"]').should('be.checked');
	});

	it('Opens an EPUB in the reader instead of downloading it', function () {
		abrirConteudoComEpub(() => {
			linkEpub().click();

			cy.get('body').should('have.class', 'epubjs_viewer');
			cy.get('.epubjs_toolbar').should('be.visible');
			// O iframe do EPUB.js so aparece depois que o livro abriu de fato; e o
			// sinal mais barato de que o arquivo foi lido, nao so a pagina montada.
			cy.get('#epubjs_reader iframe', {timeout: 60000}).should('exist');
			// A mensagem de erro esta sempre no DOM, com o atributo hidden; quem a
			// revela e o catch do carregamento. Cobrar ausencia aqui e cobrar a
			// coisa errada — o que importa e ela nao ter sido revelada.
			cy.get('#epubjs_error').should('not.be.visible');
		});
	});

	it('Pages, changes zoom and offers a way back', function () {
		abrirConteudoComEpub(() => {
			linkEpub().click();
			cy.get('#epubjs_reader iframe', {timeout: 60000}).should('exist');

			cy.get('.epubjs_zoomlevel').invoke('text').then((inicio) => {
				cy.get('.epubjs_btn').contains('+').click();
				cy.get('.epubjs_zoomlevel').should('not.have.text', inicio);
				cy.get('.epubjs_btn').contains('−').click();
				cy.get('.epubjs_zoomlevel').should('have.text', inicio);
			});

			cy.get('.epubjs_toc option').should('have.length.at.least', 1);

			// O link de voltar ficou invisivel no OMP durante um tempo: o nucleo de la
			// estiliza .header_viewable_file e nao conhece .header_view, que e a classe
			// deste template, entao o link colapsava para largura zero. Visivel, nao
			// apenas presente, e o que este teste cobra.
			const cabecalho = ehOmp ? '.header_view' : '.header_view, .header_viewable_file';
			cy.get(`${cabecalho} a.return`).should('be.visible').click();
			cy.url().should('match', ehOmp ? /\/catalog\/book\// : /\/(article|preprint)\/view\//);
		});
	});

	/** Todo link cujo rotulo diz EPUB, em qualquer um dos tres aplicativos. */
	function linkEpub() {
		return cy.get('a').filter((_, a) => /epub/i.test(a.textContent)).first();
	}

	/**
	 * Leva ate a grade de plugins. Precisa ser chamado DE NOVO depois de cada
	 * reload: a pagina volta para a aba Appearance, e o conteudo das outras abas
	 * continua no DOM, apenas escondido — entao o seletor da grade ainda encontra
	 * o elemento e o clique nao faz nada, sem erro nenhum.
	 */
	function abrirGradeDePlugins() {
		cy.get('nav').contains('Settings').click();
		// Ensure submenu item click despite animation
		cy.get('nav').contains('Website').click({force: true});
		cy.get('button[id="plugins-button"]').click();
	}

	/**
	 * Abre a primeira pagina de conteudo que oferece um EPUB. Percorrer em vez de
	 * cravar um id mantem a suite util em qualquer base: o conjunto de dados da
	 * PKP muda de versao para versao, e aqui ele nem e o mesmo entre os aplicativos.
	 */
	function abrirConteudoComEpub(aoAchar) {
		const indice = ehOmp
			? `index.php/${contexto}/en/catalog`
			: `index.php/${contexto}/en/issue/current`;
		const padrao = ehOmp ? '/catalog/book/' : '/view/';

		cy.visit(indice);
		cy.get(`a[href*="${padrao}"]`).then(($as) => {
			const paginas = Cypress._.uniq([...$as].map((a) => a.getAttribute('href')));
			const teto = Math.min(paginas.length, LIMITE);
			if (paginas.length > LIMITE) {
				cy.log(`${paginas.length} paginas de conteudo; olhando so as ${LIMITE} primeiras`);
			}
			const tentar = (i) => {
				expect(i, `content with an EPUB among the first ${teto} items`).to.be.lessThan(teto);
				cy.visit(paginas[i]);
				cy.get('body').then(($b) => {
					const temEpub = [...$b.find('a')].some((a) => /epub/i.test(a.textContent));
					return temEpub ? aoAchar() : tentar(i + 1);
				});
			};
			tentar(0);
		});
	}
});
