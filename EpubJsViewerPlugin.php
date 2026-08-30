<?php

/**
 * @file plugins/generic/epubJsViewer/EpubJsViewerPlugin.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com.br)
 * Inspired by the original Bibi EPUB Viewer plugin by Lepidus Tecnologia,
 * discontinued in 2025 because the Bibi reader was no longer maintained.
 * Reader replaced by epub.js (FuturePress), which is actively maintained.
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EpubJsViewerPlugin
 *
 * @brief Leitura embutida de EPUB com epub.js.
 *
 *        Porte do plugin de OJS/OPS para o OMP. As tres aplicacoes exibem
 *        o arquivo por caminhos diferentes:
 *
 *          OJS  ArticleHandler::view::galley   [$request, $issue, $galley, $submission]
 *          OPS  PreprintHandler::view::galley  [$request, $galley, $submission]
 *          OMP  CatalogBookHandler::view       [&$handler, &$submission,
 *                                               &$publicationFormat, &$submissionFile]
 *
 *        O OMP nao tem galley: o par formato de publicacao + arquivo faz o
 *        papel que no OJS cabe a um unico objeto ArticleGalley. Tambem nao
 *        existe galley de fasciculo, entao o caminho de issue nao se aplica.
 *
 *        Nenhum arquivo do core e alterado.
 */

namespace APP\plugins\generic\epubJsViewer;

use APP\core\Application;
use APP\template\TemplateManager;
use Exception;
use PKP\plugins\Hook;

class EpubJsViewerPlugin extends \PKP\plugins\GenericPlugin
{
    public const EPUB_MIME_TYPE = 'application/epub+zip';

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                switch (Application::get()->getName()) {
                    case 'ojs2':
                        Hook::add('ArticleHandler::view::galley', $this->submissionCallback(...), Hook::SEQUENCE_LAST);
                        Hook::add('IssueHandler::view::galley', $this->issueCallback(...), Hook::SEQUENCE_LAST);
                        break;
                    case 'ops':
                        Hook::add('PreprintHandler::view::galley', $this->submissionCallback(...), Hook::SEQUENCE_LAST);
                        break;
                    case 'omp':
                        Hook::add('CatalogBookHandler::view', $this->bookCallback(...), Hook::SEQUENCE_LAST);
                        break;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Nome estavel no registry e nas URLs do gerenciador de plugins.
     */
    public function getName()
    {
        return 'epubjsviewerplugin';
    }

    public function getDisplayName()
    {
        return __('plugins.generic.epubJsViewer.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.epubJsViewer.description');
    }

    /**
     * OMP: exibe um arquivo EPUB de um formato de publicacao no leitor.
     *
     * O hook e disparado por CatalogBookHandler::download() com $view = true,
     * ou seja, depois de o core validar formato disponivel e nao remoto,
     * publicacao publicada, arquivo pertencente ao formato, acesso aberto ou
     * compra paga e a restricao de acesso da editora. Devolver true faz o
     * core encerrar sem entregar o arquivo.
     *
     * @param string $hookName
     * @param array  $args     [&$handler, &$submission, &$publicationFormat, &$submissionFile]
     *
     * @return bool true quando o leitor foi exibido
     */
    public function bookCallback($hookName, $args)
    {
        $handler = $args[0] ?? null;
        $submission = $args[1] ?? null;
        $publicationFormat = $args[2] ?? null;
        $submissionFile = $args[3] ?? null;

        if (!$handler || !$submission || !$publicationFormat || !$submissionFile) {
            return false;
        }

        if (!$this->isEpub($submissionFile)) {
            return false;
        }

        $request = Application::get()->getRequest();
        $application = Application::get();

        // A publicacao pedida na URL: o handler ja resolveu qual versao e.
        $publication = $handler->publication ?? $submission->getCurrentPublication();
        if (!$publication) {
            return false;
        }

        $isLatest = (int) $publication->getId() === (int) $submission->getData('currentPublicationId');

        // Caminho comum das URLs do catalogo. Versoes antigas levam o
        // segmento version/{publicationId}, como no proprio core.
        $path = [$submission->getBestId()];
        if (!$isLatest) {
            $path[] = 'version';
            $path[] = $publication->getId();
        }

        $parentUrl = $request->url(null, 'catalog', 'book', $path);

        $filePath = array_merge($path, [$publicationFormat->getBestId(), $submissionFile->getBestId()]);
        $epubUrl = $request->url(null, 'catalog', 'download', $filePath);

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'displayTemplateResource' => $this->getTemplateResource('display.tpl'),
            'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
            'submission' => $submission,
            'publication' => $publication,
            'publicationFormat' => $publicationFormat,
            'submissionFile' => $submissionFile,
            'currentVersionString' => $application->getCurrentVersion()->getVersionString(false),
            'isLatestPublication' => $isLatest,
            'title' => $publication->getLocalizedFullTitle(),
            'epubUrl' => $epubUrl,
            'parentUrl' => $parentUrl,
            'galleyTitle' => __('submission.representationOfTitle', [
                'representation' => $publicationFormat->getLocalizedName(),
                'title' => $publication->getLocalizedFullTitle(),
            ]),
            'datePublished' => __('submission.outdatedVersion', [
                'datePublished' => $publication->getData('datePublished'),
                'urlRecentVersion' => $request->url(null, 'catalog', 'book', [$submission->getBestId()]),
            ]),
        ]);

        $templateMgr->display($this->getTemplateResource('display.tpl'));
        return true;
    }

    /**
     * O OMP guarda o mimetype no arquivo, nao no formato de publicacao.
     * Alguns envios chegam como application/octet-stream, entao a extensao
     * decide quando o mimetype registrado nao ajuda.
     */
    private function isEpub($submissionFile): bool
    {
        return self::isEpubFile(
            $submissionFile->getData('mimetype'),
            $submissionFile->getLocalizedData('name'),
            $submissionFile->getData('path')
        );
    }

    /**
     * Decide se o arquivo e um EPUB.
     *
     * Puro de proposito: separado do objeto de arquivo, e a regra que decide se
     * o plugin assume ou nao a renderizacao, e da para cobrir por teste sem subir
     * aplicacao. A extensao tem voz porque nem todo envio chega com o mimetype
     * correto — o OMP grava application/octet-stream em alguns casos.
     */
    public static function isEpubFile(?string $mimetype, ?string $name, ?string $path): bool
    {
        if ($mimetype === self::EPUB_MIME_TYPE) {
            return true;
        }
        foreach ([$name, $path] as $nome) {
            if ($nome && strtolower(pathinfo($nome, PATHINFO_EXTENSION)) === 'epub') {
                return true;
            }
        }
        return false;
    }

    /**
     * Present an EPUB article galley inside the epub.js reader.
     */
    public function submissionCallback($hookName, $args)
    {
        $request = &$args[0];
        $application = Application::get();

        switch ($application->getName()) {
            case 'ojs2':
                $issue = &$args[1];
                $galley = &$args[2];
                $submission = &$args[3];
                $submissionNoun = 'article';
                break;
            case 'ops':
                $galley = &$args[1];
                $submission = &$args[2];
                $submissionNoun = 'preprint';
                $issue = null;
                break;
            default:
                throw new Exception('Unknown application!');
        }

        if (!$galley || $galley->getFileType() !== self::EPUB_MIME_TYPE) {
            return false;
        }

        $galleyPublication = null;
        foreach ($submission->getData('publications') as $publication) {
            if ($publication->getId() === $galley->getData('publicationId')) {
                $galleyPublication = $publication;
                break;
            }
        }
        if (!$galleyPublication) {
            return false;
        }

        $templateMgr = TemplateManager::getManager($request);

        $epubUrl = $request->url(
            null,
            $submissionNoun,
            'download',
            [$submission->getBestId(), $galley->getBestGalleyId(), $galley->getFile()->getId()]
        );
        $parentUrl = $request->url(null, $submissionNoun, 'view', [$submission->getBestId()]);

        $templateMgr->assign([
            'displayTemplateResource' => $this->getTemplateResource('display.tpl'),
            'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
            'galleyFile' => $galley->getFile(),
            'issue' => $issue,
            'submission' => $submission,
            'submissionNoun' => $submissionNoun,
            'bestId' => $galleyPublication->getData('urlPath') ?? $submission->getId(),
            'galley' => $galley,
            'galleyPublication' => $galleyPublication,
            'currentVersionString' => $application->getCurrentVersion()->getVersionString(false),
            'isLatestPublication' => $submission->getData('currentPublicationId') === $galley->getData('publicationId'),
            'title' => $galleyPublication->getLocalizedTitle(null, 'html'),
            'isTitleHtml' => true,
            'epubUrl' => $epubUrl,
            'parentUrl' => $parentUrl,
            'galleyTitle' => __('submission.representationOfTitle', [
                'representation' => $galley->getLabel(),
                'title' => $galleyPublication->getLocalizedFullTitle(),
            ]),
            'datePublished' => __('submission.outdatedVersion', [
                'datePublished' => $galleyPublication->getData('datePublished'),
                'urlRecentVersion' => $parentUrl,
            ]),
        ]);

        $templateMgr->display($this->getTemplateResource('display.tpl'));
        return true;
    }

    /**
     * Present an EPUB issue galley inside the epub.js reader.
     */
    public function issueCallback($hookName, $args)
    {
        $request = &$args[0];
        $issue = &$args[1];
        $galley = &$args[2];

        if (!$galley || $galley->getFileType() !== self::EPUB_MIME_TYPE) {
            return false;
        }

        $templateMgr = TemplateManager::getManager($request);
        $parentUrl = $request->url(null, 'issue', 'view', [$issue->getBestIssueId()]);

        $templateMgr->assign([
            'displayTemplateResource' => $this->getTemplateResource('display.tpl'),
            'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
            'issue' => $issue,
            'galley' => $galley,
            'galleyFile' => $galley->getFile(),
            'isLatestPublication' => true,
            'title' => $issue->getLocalizedTitle(),
            'isTitleHtml' => false,
            'epubUrl' => $request->url(null, 'issue', 'download', [$issue->getBestIssueId(), $galley->getBestGalleyId()]),
            'parentUrl' => $parentUrl,
            'galleyTitle' => $issue->getLocalizedTitle(),
        ]);

        $templateMgr->display($this->getTemplateResource('display.tpl'));
        return true;
    }
}
