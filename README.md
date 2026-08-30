# EPUB.js Viewer — OJS / OPS / OMP plugin

> **Based on the original `epubViewer` plugin** by **[Lepidus Tecnologia](https://github.com/lepidus)**,
> which served Brazilian journals for years and was **discontinued in 2025** because the
> **[Bibi](https://github.com/satorumurmur/bibi)** reader by **Satoru Matsushima** was no longer
> maintained. The idea and the OJS integration are theirs.
> Reading engine replaced by **[epub.js](https://github.com/futurepress/epub.js)** by
> **[FuturePress](https://github.com/futurepress)**, which is actively maintained.
> Developed and maintained by **[OJSBR](https://ojsbr.com)** — full details in the
> [Credits & acknowledgements](#credits--acknowledgements) section.

[![OJS](https://img.shields.io/badge/OJS-3.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-1.2.0.4-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS 3.5](https://github.com/OJSBR/epubJsViewer/releases/download/1.2.0.4/epubJsViewer-1.2.0.4.tar.gz) — or browse all [Releases](../../releases).

**▶️ Live demo:** [Editora UEMG — book with an EPUB format](https://ebooks.editora.uemg.br/editora/pt_BR/catalog/book/1)

## Why this plugin exists

The `epubViewer` plugin embedded EPUB galleys in OJS using the Bibi reader. In 2025 Lepidus added a
deprecation notice: Bibi had not been updated for years, so the plugin would no longer be maintained.
It never received an OJS 3.5 release.

That left journals in a bad spot. Several of our clients had **EPUB galleys already published** and
had **upgraded to OJS 3.5** — their readers suddenly had no embedded reader at all, only a download
link. Porting the old plugin would not solve it, because the problem was the reading engine, not the
integration.

So we rebuilt it around **epub.js**, an actively maintained reader, keeping the same idea and the
same place in the OJS workflow. We are **making it public for the whole community**, because any
journal running OJS 3.5 with EPUB galleys has exactly the same problem.

In the future we intend to submit it to the **official OJS Plugin Gallery**, should PKP find it
useful for the wider community.

## Compatibility & branches

| Application | Version | Branch | Release |
|---|---|---|---|
| OJS / OPS / OMP | 3.5.x | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 1.2.0.4 |

Requires PHP 8.2+.

### OMP support (since 1.2.0.0)

OMP has no galleys: a publication format plus a submission file play the part that a single
`ArticleGalley` plays in OJS, and there are no issue galleys at all. The plugin registers a
different hook per application, and no core file is touched:

| Application | Hook |
|---|---|
| OJS | `ArticleHandler::view::galley`, `IssueHandler::view::galley` |
| OPS | `PreprintHandler::view::galley` |
| OMP | `CatalogBookHandler::view` |

On OMP the hook fires only after the core has validated that the publication format is
available and not remote, the publication is published, the file belongs to that format, and
access is open or paid — no authorization rule is reimplemented by the plugin.

### Known limitation: single-file EPUBs

epub.js renders one EPUB section per iframe. An EPUB that packs the whole book into one or two
huge XHTML sections — Project Gutenberg does this — is laid out in paginated flow as a single
column strip tens of thousands of pixels wide, which the browser cannot paint; with
`spread: 'auto'` it can freeze the tab. Measured on a 211 KB section: a 99,792 px wide view,
blank. The cover section of the same file, at 377 bytes, renders correctly.

EPUBs with one XHTML per chapter — what publishing tools normally produce — are unaffected.

## What it does

Displays **EPUB galleys** inside an embedded reader, instead of forcing the reader to download the
file. Works for **article galleys** and **issue galleys** in OJS, and for **preprint galleys** in OPS.

The reader offers:

- **Page navigation** with buttons and keyboard arrows (← →)
- **Table of contents** read from the EPUB itself, as a jump-to selector
- **Text zoom** from 70% to 250% — in EPUB the right way to zoom is to grow the *text*, which
  reflows and stays sharp, not to scale pixels
- **Reading mode** toggle: two pages side by side, a single page, or continuous scroll
- **Remembered preferences** — zoom and reading mode persist across articles and visits
- **Graceful degradation** — a download link is always visible, and shown as a fallback when
  JavaScript is off or the file cannot be rendered

## No CDN

**epub.js 0.3.93** and **JSZip 3.10.1** ship inside `js/`. No request ever leaves for a third-party
service: the reader works on closed networks and does not expose your readers to any third party.
EPUB content is rendered with `allowScriptedContent: false`, so scripts embedded in the EPUB do not run.

## Installation

Dashboard → Settings → Website → Plugins → Upload A New Plugin, or unpack into `plugins/generic/`
and enable it under Installed Plugins. No configuration is required.

## Tests

PHPUnit, in the PKP `ApplicationPlugins` suite:

```bash
cd lib/pkp/tests
php ../lib/vendor/bin/phpunit --no-coverage -c phpunit.xml \
  /absolute/path/to/plugins/generic/epubJsViewer/tests
```

7 tests / 484 assertions covering EPUB detection by mimetype and by file extension on
either the display name or the stored path (the extension has a vote because not every
upload arrives with the right mimetype), the rejection of every other format, and locale
integrity: every locale carrying every key with no empty value, no legacy locale codes,
and every key used in a template present in `locale/en`, since in 3.5 a missing key
renders as `##key##` instead of falling back to English.

Cypress, from the installation root:

```bash
npx cypress run \
  --config specPattern='plugins/generic/epubJsViewer/cypress/tests/functional/*.cy.js' \
  --env contextPath=mypress,adminUsername=admin,adminPassword=secret
```

3 specs: enabling the plugin, an EPUB opening in the reader instead of downloading, and
paging, zoom and the way back. The specs discover the content instead of hard-coding an
id, so the same file runs on OJS, OPS and OMP; they were executed against both an OJS 3.5
and an OMP 3.5 installation. The "way back" assertion demands the link be **visible**, not
merely present: on OMP it was once in the DOM at zero width, because the OMP core styles
`.header_viewable_file` and does not know the `.header_view` this template uses.

## Credits & acknowledgements

- **[Lepidus Tecnologia](https://github.com/lepidus)** — authors of the original `epubViewer` plugin
  for OJS/OMP, the idea this one continues.
- **[Satoru Matsushima](https://github.com/satorumurmur)** — author of **Bibi**, the reader used by
  the original plugin.
- **[FuturePress](https://github.com/futurepress)** — authors of **epub.js**, the reading engine
  used here (BSD-2-Clause).
- **[Stuk](https://github.com/Stuk/jszip)** — authors of **JSZip** (MIT).
- **[PKP](https://pkp.sfu.ca)** — Open Journal Systems.

## License

GNU GPL v3 — see [LICENSE](LICENSE). epub.js is BSD-2-Clause; JSZip is MIT.

---

## 🇧🇷 Português

> **Baseado no plugin `epubViewer` original** da **[Lepidus Tecnologia](https://github.com/lepidus)**,
> que serviu as revistas brasileiras por anos e foi **descontinuado em 2025** porque o leitor
> **[Bibi](https://github.com/satorumurmur/bibi)**, de **Satoru Matsushima**, deixou de ser atualizado.
> A ideia e a integração com o OJS são deles. O motor de leitura foi substituído pelo
> **[epub.js](https://github.com/futurepress/epub.js)**, da **[FuturePress](https://github.com/futurepress)**,
> que segue mantido. Desenvolvido e mantido pela **[OJSBR](https://ojsbr.com)**.


**▶️ Demonstração:** [Editora UEMG — livro com formato EPUB](https://ebooks.editora.uemg.br/editora/pt_BR/catalog/book/1)

### Por que este plugin existe

O `epubViewer` exibia composições EPUB dentro do OJS usando o leitor Bibi. Em 2025 a Lepidus incluiu
um aviso de descontinuação: o Bibi estava havia anos sem atualização, e por isso o plugin deixaria de
ser mantido. Ele nunca ganhou versão para o OJS 3.5.

Isso deixou revistas numa situação ruim. Vários dos nossos clientes **já tinham composições EPUB
publicadas** e **haviam atualizado para o OJS 3.5** — de uma hora para outra, seus leitores ficaram
sem leitor embutido, apenas com o link de download. Portar o plugin antigo não resolveria, porque o
problema estava no motor de leitura, não na integração.

Então reconstruímos o plugin em torno do **epub.js**, um leitor ativamente mantido, preservando a
mesma ideia e o mesmo lugar no fluxo do OJS. Estamos **tornando-o público para toda a comunidade**,
porque qualquer revista em OJS 3.5 com composições EPUB enfrenta exatamente o mesmo problema.

Futuramente, pretendemos submetê-lo à **Galeria oficial de plugins do OJS**, caso a PKP tenha
interesse para a comunidade em geral.

### Compatibilidade e branches

| Aplicação | Versão | Branch | Release |
|---|---|---|---|
| OJS / OPS / OMP | 3.5.x | [`stable-3_5_0`](../../tree/stable-3_5_0) *(padrão)* | 1.2.0.4 |

Requer PHP 8.2+.

#### Suporte a OMP (a partir da 1.2.0.0)

O OMP não tem galley: o par formato de publicação + arquivo faz o papel que no OJS cabe a um
único `ArticleGalley`, e não existe galley de fascículo. O plugin registra um hook diferente
por aplicação, sem tocar em nenhum arquivo do core:

| Aplicação | Hook |
|---|---|
| OJS | `ArticleHandler::view::galley`, `IssueHandler::view::galley` |
| OPS | `PreprintHandler::view::galley` |
| OMP | `CatalogBookHandler::view` |

No OMP o hook só é alcançado depois de o core validar formato disponível e não remoto,
publicação publicada, arquivo pertencente ao formato e acesso aberto ou compra paga — nenhuma
regra de autorização é reimplementada pelo plugin.

#### Limitação conhecida: EPUB de arquivo único

O epub.js renderiza uma seção do EPUB por iframe. Um EPUB que empacota o livro inteiro em uma
ou duas seções XHTML enormes — como faz o Project Gutenberg — é montado, em fluxo paginado,
como uma tira de colunas de dezenas de milhares de pixels, que o navegador não consegue pintar;
com `spread: 'auto'` chega a travar a aba. Medido numa seção de 211 KB: view de 99.792 px,
em branco. A seção de capa do mesmo arquivo, com 377 bytes, renderiza corretamente.

EPUBs com um XHTML por capítulo — o que as ferramentas de editoração normalmente produzem —
não são afetados.

### O que faz

Exibe **composições EPUB** em um leitor embutido, em vez de obrigar o leitor a baixar o arquivo.
Funciona para composições de **artigo** e de **edição** no OJS, e de **preprint** no OPS.

O leitor oferece:

- **Navegação por página**, com botões e setas do teclado (← →)
- **Sumário** extraído do próprio EPUB, como seletor de salto
- **Zoom de texto** de 70% a 250% — em EPUB o zoom correto é aumentar o *texto*, que reflui e
  permanece nítido, e não escalar pixels
- **Modo de leitura** alternável: duas páginas lado a lado, uma página, ou rolagem contínua
- **Preferências lembradas** — zoom e modo persistem entre artigos e visitas
- **Degradação graciosa** — o link de download fica sempre visível, e aparece como alternativa
  quando o JavaScript está desligado ou o arquivo não pode ser renderizado

### Sem CDN

O **epub.js 0.3.93** e o **JSZip 3.10.1** estão embutidos em `js/`. Nenhuma requisição sai para
serviço de terceiro: o leitor funciona em rede fechada e não expõe o acesso dos seus leitores a
ninguém. O conteúdo do EPUB é renderizado com `allowScriptedContent: false`, de modo que scripts
embutidos no arquivo não são executados.

### Instalação

Painel → Configurações → Website → Plugins → Enviar um novo plugin, ou descompacte em
`plugins/generic/` e habilite em Plugins Instalados. Não requer configuração.

### Testes

PHPUnit, na suíte `ApplicationPlugins` da PKP:

```bash
cd lib/pkp/tests
php ../lib/vendor/bin/phpunit --no-coverage -c phpunit.xml \
  /caminho/absoluto/plugins/generic/epubJsViewer/tests
```

7 testes / 484 asserções cobrindo a detecção de EPUB pelo mimetype e pela extensão (a
extensão tem voz porque nem todo envio chega com o mimetype certo), a recusa de todos os
outros formatos e a integridade dos 38 locales.

Cypress, a partir da raiz da instalação:

```bash
npx cypress run \
  --config specPattern='plugins/generic/epubJsViewer/cypress/tests/functional/*.cy.js' \
  --env contextPath=minhaeditora,adminUsername=admin,adminPassword=senha
```

3 specs: ligar o plugin, um EPUB abrir no leitor em vez de baixar, e a paginação, o zoom e
o link de voltar. Os specs descobrem o conteúdo em vez de cravar id, então o mesmo arquivo
roda em OJS, OPS e OMP — e foram executados numa instalação OJS 3.5 e numa OMP 3.5.

### Créditos e agradecimentos

- **[Lepidus Tecnologia](https://github.com/lepidus)** — autores do `epubViewer` original para
  OJS/OMP, a ideia que este plugin dá continuidade.
- **[Satoru Matsushima](https://github.com/satorumurmur)** — autor do **Bibi**, leitor usado pelo
  plugin original.
- **[FuturePress](https://github.com/futurepress)** — autores do **epub.js**, motor de leitura aqui
  utilizado (BSD-2-Clause).
- **[Stuk](https://github.com/Stuk/jszip)** — autores do **JSZip** (MIT).
- **[PKP](https://pkp.sfu.ca)** — Open Journal Systems.

### Licença

GNU GPL v3 — veja [LICENSE](LICENSE). O epub.js é BSD-2-Clause; o JSZip, MIT.
