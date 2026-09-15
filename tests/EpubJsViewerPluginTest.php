<?php

/**
 * @file plugins/generic/epubJsViewer/tests/EpubJsViewerPluginTest.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EpubJsViewerPluginTest
 *
 * @brief The rule that decides whether the plugin takes over a file, and the
 *        reader page.
 */

namespace APP\plugins\generic\epubJsViewer\tests;

use APP\plugins\generic\epubJsViewer\EpubJsViewerPlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\plugins\Hook;
use PKP\tests\PKPTestCase;

#[CoversClass(EpubJsViewerPlugin::class)]
class EpubJsViewerPluginTest extends PKPTestCase
{
    private function template(): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/templates/display.tpl');
    }

    public function testAnEpubIsRecognisedByItsMimetype(): void
    {
        $this->assertTrue(EpubJsViewerPlugin::isEpubFile('application/epub+zip', null, null));
    }

    public function testAnEpubIsRecognisedByItsExtension(): void
    {
        // Not every upload arrives with the right mimetype.
        $this->assertTrue(EpubJsViewerPlugin::isEpubFile('application/octet-stream', 'book.epub', null));
        $this->assertTrue(EpubJsViewerPlugin::isEpubFile(null, null, '/files/1/a1b2c3.epub'));
        $this->assertTrue(EpubJsViewerPlugin::isEpubFile(null, 'BOOK.EPUB', null), 'upper-case extension');
    }

    public function testOtherFilesAreLeftToTheCore(): void
    {
        foreach ([
            ['application/pdf', 'book.pdf', null],
            ['audio/mpeg', 'track.mp3', null],
            ['text/html', 'text.html', null],
            [null, null, null],
            ['', '', ''],
            [null, 'no-extension', null],
            [null, 'about-the-epub-format.pdf', null],
            [null, 'epub.txt', null],
        ] as [$mimetype, $name, $path]) {
            $this->assertFalse(EpubJsViewerPlugin::isEpubFile($mimetype, $name, $path), 'took over ' . var_export([$mimetype, $name, $path], true));
        }
    }

    public function testAGalleyThatIsNotAnEpubIsLeftToTheCore(): void
    {
        $galley = new class () {
            public function getFileType()
            {
                return 'application/pdf';
            }
        };

        $this->assertSame(Hook::CONTINUE, (new EpubJsViewerPlugin())->issueCallback('IssueHandler::view::galley', [null, null, $galley]));
    }

    public function testTheReaderPageKeepsTheCoreFooterHook(): void
    {
        // Analytics and usage plugins print their code through this hook, as on the PDF reader.
        $this->assertStringContainsString('{call_hook name="Templates::Common::Footer::PageFooter"}', $this->template());
    }

    public function testTheReaderScriptIsAFileAndBookScriptsNeverRun(): void
    {
        $this->assertSame(0, preg_match('/<script(?![^>]*\bsrc=)[^>]*>/', $this->template()), 'Inline script in the reader page.');
        $this->assertStringContainsString('allowScriptedContent: false', (string) file_get_contents(dirname(__DIR__) . '/js/reader.js'));
    }

    public function testEveryUrlInTheTemplateIsEscaped(): void
    {
        preg_match_all('/\{\$(pluginUrl|epubUrl|parentUrl)[^}]*\}/', $this->template(), $matches);

        $this->assertNotEmpty($matches[0]);
        foreach ($matches[0] as $output) {
            $this->assertStringContainsString('|escape', $output, "{$output} is not escaped.");
        }
    }
}
