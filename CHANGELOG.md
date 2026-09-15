# Changelog

All notable changes to this plugin are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
version numbers follow the PKP four-part scheme used in `version.xml`.

## [Unreleased]

## [1.2.1.0] - 2026-09-15

### Changed
- The reader script moved from the page template to `js/reader.js`; the EPUB address is
  passed in a `data-epub-url` attribute. epub.js and JSZip moved to `lib/`.
- The reader page keeps the core `Templates::Common::Footer::PageFooter` hook, so analytics
  and usage plugins work on it as on the PDF reader.
- Every URL and every translation in an attribute of the template is escaped.
- Hook callbacks return `Hook::CONTINUE` / `Hook::ABORT`; nothing is registered while the
  site is under maintenance; the empty `settings.xml` is gone.
- Source comments, tests and locale file headers follow the house standard (English, standard
  copyright header).
- Tests: PHPUnit on `PKPTestCase` with the standard suite; Cypress runs in PKP's continuous
  integration on OJS, OMP and OPS; tests are no longer part of the release package.

## [1.2.0.4] - 2026-08-30

### Added
- Unit tests (`tests/EpubJsViewerPluginTest.php`): EPUB detection by mimetype and
  by file extension, rejection of every other format, and locale integrity —
  all 38 locales carry every key with no empty value, no legacy locale code is
  present, and every key used in a template exists in `locale/en`.
- Cypress specs (`cypress/tests/functional/EpubJsViewer.cy.js`): enabling the
  plugin, an EPUB opening in the reader instead of downloading, and paging, zoom
  and the way back. The specs discover the content instead of hard-coding an id,
  so the same file runs on OJS, OPS and OMP; they were executed against both an
  OJS 3.5 and an OMP 3.5 installation.
- A live demo link in the README.

### Changed
- The rule that decides whether the plugin takes over a file was extracted into
  `EpubJsViewerPlugin::isEpubFile()`, a pure static that takes the mimetype, the
  name and the path. Behaviour is unchanged; it is now coverable without booting
  the application.

## [1.2.0.3] - 2026-08-30

### Added
- OMP support. The reader now hooks `CatalogBookHandler::view` alongside the
  existing OJS and OPS hooks, so an EPUB publication format opens in the reader
  instead of downloading.

### Fixed
- The reader header was invisible on OMP. The OMP core styles
  `.header_viewable_file` and does not know `.header_view`, which this template
  uses, so the "back" link collapsed to zero width and disappeared. Styles were
  added scoped to `.pkp_page_catalog`, matching the OMP PDF reader, without
  touching the OJS and OPS appearance.

## [1.1.0.1] - 2026-08-29

### Added
- The 38-locale PKP standard set.

### Fixed
- Legacy locale codes (`fr_FR`, `pt_PT`, `nb`, `sr`, `zh_CN`) were replaced by
  the codes 3.5 actually loads. Under the old codes the locale never loaded and
  untranslated keys rendered as `##key##`.

## [1.1.0.0] - 2026-08-18

### Added
- First release: embedded EPUB galley reader for OJS 3.5, with table of contents,
  paging, zoom and a fallback download link. EPUB.js is vendored — no CDN.

[Unreleased]: https://github.com/OJSBR/epubJsViewer/compare/1.2.0.4...stable-3_5_0
[1.2.0.4]: https://github.com/OJSBR/epubJsViewer/releases/tag/1.2.0.4
[1.2.0.3]: https://github.com/OJSBR/epubJsViewer/releases/tag/1.2.0.3
[1.1.0.1]: https://github.com/OJSBR/epubJsViewer/releases/tag/1.1.0.1
[1.1.0.0]: https://github.com/OJSBR/epubJsViewer/releases/tag/1.1.0.0
