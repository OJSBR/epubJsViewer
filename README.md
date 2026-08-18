# EPUB.js Viewer — OJS plugin

> **Based on the original `epubViewer` plugin** by **[Lepidus Tecnologia](https://github.com/lepidus)**,
> which served Brazilian journals for years and was **discontinued in 2025** because the
> **[Bibi](https://github.com/satorumurmur/bibi)** reader by **Satoru Matsushima** was no longer
> maintained. The idea and the OJS integration are theirs.
> Reading engine replaced by **[epub.js](https://github.com/futurepress/epub.js)** by
> **[FuturePress](https://github.com/futurepress)**, which is actively maintained.
> Developed and maintained by **[OJSBR](https://ojsbr.com)** — full details in the
> [Credits & acknowledgements](#credits--acknowledgements) section.

[![OJS](https://img.shields.io/badge/OJS-3.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS 3.5](https://github.com/OJSBR/epubJsViewer/releases/download/1.1.0.0/epubJsViewer-1.1.0.0.tar.gz) — or browse all [Releases](../../releases).

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

| OJS/OPS | Branch | Release |
|---|---|---|
| 3.5.x | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 1.1.0.0 |

Requires PHP 8.2+.

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

| OJS/OPS | Branch | Release |
|---|---|---|
| 3.5.x | [`stable-3_5_0`](../../tree/stable-3_5_0) *(padrão)* | 1.1.0.0 |

Requer PHP 8.2+.

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
