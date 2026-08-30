<?php

/**
 * @file plugins/generic/epubJsViewer/tests/EpubJsViewerPluginTest.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com.br)
 * Distributed under the GNU GPL v3.
 *
 * @class EpubJsViewerPluginTest
 *
 * @brief Cobre a regra que decide se o plugin assume o arquivo e a integridade
 *        dos locales, que e onde este tipo de plugin quebra em silencio: no 3.5
 *        uma chave sem traducao vira ##chave## na tela em vez de cair no ingles.
 */

namespace APP\plugins\generic\epubJsViewer\tests;

use APP\plugins\generic\epubJsViewer\EpubJsViewerPlugin;
use PKP\tests\PKPTestCase;

class EpubJsViewerPluginTest extends PKPTestCase
{
    private const DIR = __DIR__ . '/..';

    /** O mimetype correto basta. */
    public function testReconheceEpubPeloMimetype(): void
    {
        $this->assertTrue(EpubJsViewerPlugin::isEpubFile('application/epub+zip', null, null));
    }

    /** Nem todo envio chega com o mimetype certo: a extensao tambem vale. */
    public function testReconheceEpubPelaExtensao(): void
    {
        $this->assertTrue(EpubJsViewerPlugin::isEpubFile('application/octet-stream', 'livro.epub', null));
        $this->assertTrue(EpubJsViewerPlugin::isEpubFile(null, null, '/files/1/a1b2c3.epub'));
        $this->assertTrue(EpubJsViewerPlugin::isEpubFile(null, 'LIVRO.EPUB', null), 'extensao maiuscula');
    }

    /** PDF, audio e o resto seguem pelo caminho normal do core. */
    public function testNaoAssumeOutrosTipos(): void
    {
        $casos = [
            ['application/pdf', 'livro.pdf', null],
            ['audio/mpeg', 'faixa.mp3', null],
            ['text/html', 'texto.html', null],
            [null, null, null],
            ['', '', ''],
            [null, 'sem-extensao', null],
        ];
        foreach ($casos as [$mime, $nome, $caminho]) {
            $this->assertFalse(
                EpubJsViewerPlugin::isEpubFile($mime, $nome, $caminho),
                'nao deveria assumir ' . var_export([$mime, $nome, $caminho], true)
            );
        }
    }

    /** Um nome que apenas contem "epub" nao e um EPUB. */
    public function testNaoConfundeNomeQueContemEpub(): void
    {
        $this->assertFalse(EpubJsViewerPlugin::isEpubFile(null, 'sobre-o-formato-epub.pdf', null));
        $this->assertFalse(EpubJsViewerPlugin::isEpubFile(null, 'epub.txt', null));
    }

    /** Todo locale precisa ter TODAS as chaves, sem valor vazio. */
    public function testTodosOsLocalesTemTodasAsChaves(): void
    {
        $chavesEn = array_keys($this->chavesDe(self::DIR . '/locale/en/locale.po'));
        $this->assertNotEmpty($chavesEn, 'locale/en sem chaves');
        foreach (glob(self::DIR . '/locale/*/locale.po') as $arquivo) {
            $locale = basename(dirname($arquivo));
            $chaves = $this->chavesDe($arquivo);
            $this->assertSame([], array_values(array_diff($chavesEn, array_keys($chaves))), "locale {$locale} sem chaves");
            foreach ($chaves as $chave => $valor) {
                $this->assertNotSame('', trim($valor), "locale {$locale}: chave {$chave} vazia");
            }
        }
    }

    /** Codigos legados nao existem no 3.5 e o locale nao carrega. */
    public function testNaoUsaCodigosDeLocaleLegados(): void
    {
        foreach (['fr_FR', 'pt_PT', 'nb', 'sr', 'zh_CN'] as $legado) {
            $this->assertDirectoryDoesNotExist(self::DIR . '/locale/' . $legado);
        }
    }

    /** Toda chave usada no template precisa existir no locale/en. */
    public function testChavesUsadasExistemNoIngles(): void
    {
        $chavesEn = array_keys($this->chavesDe(self::DIR . '/locale/en/locale.po'));
        $usadas = [];
        foreach (glob(self::DIR . '/templates/*.tpl') as $tpl) {
            preg_match_all('/key="(plugins\.generic\.epubJsViewer\.[a-zA-Z.]+)"/', file_get_contents($tpl), $m);
            $usadas = array_merge($usadas, $m[1]);
        }
        foreach (array_unique($usadas) as $chave) {
            $this->assertContains($chave, $chavesEn, "chave usada no template e ausente do locale/en: {$chave}");
        }
    }

    /**
     * Le o .po com o mesmo parser que o PKP usa em producao. Um regex caseiro
     * erra em msgstr multilinha e da falso negativo.
     *
     * @return array<string,string>
     */
    private function chavesDe(string $arquivo): array
    {
        $this->assertFileExists($arquivo);
        $out = [];
        foreach ((new \Gettext\Loader\PoLoader())->loadFile($arquivo) as $t) {
            if ($t->getOriginal() === '') {
                continue;
            }
            $out[$t->getOriginal()] = (string) $t->getTranslation();
        }
        return $out;
    }
}
