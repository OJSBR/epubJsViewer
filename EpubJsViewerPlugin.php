<?php

/**
 * @file plugins/generic/epubJsViewer/EpubJsViewerPlugin.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EpubJsViewerPlugin
 *
 * @brief Embedded reading of EPUB files with epub.js.
 *
 * Based on the idea of the epubViewer plugin by Lepidus Tecnologia, discontinued
 * in 2025 because its Bibi reader was no longer maintained. The reader is
 * epub.js (FuturePress), which is.
 *
 * The three applications show a file through different handlers:
 *
 *   OJS  ArticleHandler::view::galley   [$request, $issue, $galley, $submission]
 *        IssueHandler::view::galley     [$request, $issue, $galley]
 *   OPS  PreprintHandler::view::galley  [$request, $galley, $submission]
 *   OMP  CatalogBookHandler::view       [$handler, $submission, $publicationFormat, $submissionFile]
 *
 * OMP has no galleys: a publication format plus a submission file play the part
 * of the OJS galley, and there are no issue galleys. No core file is replaced.
 */

namespace APP\plugins\generic\epubJsViewer;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class EpubJsViewerPlugin extends GenericPlugin
{
    public const EPUB_MIME_TYPE = 'application/epub+zip';

    /**
     * Register the plugin and, where it is enabled, the hook of the application.
     *
     * @param string $category
     * @param string $path
     * @param null|int $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!$success || Application::isUnderMaintenance() || !$this->getEnabled($mainContextId)) {
            return $success;
        }

        // Only reader-facing requests reach these handlers, and they always carry a context.
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

        return $success;
    }

    /**
     * Name shown in the plugins list.
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.epubJsViewer.displayName');
    }

    /**
     * Description shown in the plugins list.
     */
    public function getDescription(): string
    {
        return __('plugins.generic.epubJsViewer.description');
    }

    /**
     * Whether a file is an EPUB.
     *
     * The extension counts too: not every upload arrives with the right mimetype,
     * and OMP stores application/octet-stream for some of them.
     */
    public static function isEpubFile(?string $mimetype, ?string $name, ?string $path): bool
    {
        if ($mimetype === self::EPUB_MIME_TYPE) {
            return true;
        }
        foreach ([$name, $path] as $candidate) {
            if ($candidate && strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) === 'epub') {
                return true;
            }
        }

        return false;
    }

    /**
     * OMP: show an EPUB file of a publication format in the reader.
     *
     * The hook is called by CatalogBookHandler::download() with $view = true, after
     * the core has checked that the format is available and not remote, that the
     * publication is published, that the file belongs to the format and that access
     * is open or paid for. No authorization rule is repeated here.
     *
     * @param string $hookName
     * @param array $args [$handler, $submission, $publicationFormat, $submissionFile]
     */
    public function bookCallback($hookName, $args): bool
    {
        [$handler, $submission, $publicationFormat, $submissionFile] = array_pad($args, 4, null);
        if (!$handler || !$submission || !$publicationFormat || !$submissionFile) {
            return Hook::CONTINUE;
        }
        if (!self::isEpubFile($submissionFile->getData('mimetype'), $submissionFile->getLocalizedData('name'), $submissionFile->getData('path'))) {
            return Hook::CONTINUE;
        }

        // The publication of the URL: the handler has already resolved the version.
        $publication = $handler->publication ?? $submission->getCurrentPublication();
        if (!$publication) {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $isLatest = (int) $publication->getId() === (int) $submission->getData('currentPublicationId');

        // Older versions carry the version/{publicationId} segment, as in the core.
        $path = [$submission->getBestId()];
        if (!$isLatest) {
            array_push($path, 'version', $publication->getId());
        }

        $this->display($request, [
            'submission' => $submission,
            'publication' => $publication,
            'publicationFormat' => $publicationFormat,
            'submissionFile' => $submissionFile,
            'isLatestPublication' => $isLatest,
            'title' => $publication->getLocalizedFullTitle(),
            'isTitleHtml' => false,
            'epubUrl' => $request->url(null, 'catalog', 'download', array_merge($path, [$publicationFormat->getBestId(), $submissionFile->getBestId()])),
            'parentUrl' => $request->url(null, 'catalog', 'book', $path),
            'galleyTitle' => __('submission.representationOfTitle', [
                'representation' => $publicationFormat->getLocalizedName(),
                'title' => $publication->getLocalizedFullTitle(),
            ]),
            'datePublished' => __('submission.outdatedVersion', [
                'datePublished' => $publication->getData('datePublished'),
                'urlRecentVersion' => $request->url(null, 'catalog', 'book', [$submission->getBestId()]),
            ]),
        ]);

        return Hook::ABORT;
    }

    /**
     * OJS and OPS: show an EPUB galley of a submission in the reader.
     *
     * @param string $hookName
     * @param array $args OJS [$request, $issue, $galley, $submission]; OPS [$request, $galley, $submission]
     */
    public function submissionCallback($hookName, $args): bool
    {
        $request = $args[0];
        if (Application::get()->getName() === 'ojs2') {
            [, $issue, $galley, $submission] = $args;
            $submissionNoun = 'article';
        } else {
            [, $galley, $submission] = $args;
            $issue = null;
            $submissionNoun = 'preprint';
        }

        if (!$galley || $galley->getFileType() !== self::EPUB_MIME_TYPE) {
            return Hook::CONTINUE;
        }

        $galleyPublication = null;
        foreach ($submission->getData('publications') as $publication) {
            if ($publication->getId() === $galley->getData('publicationId')) {
                $galleyPublication = $publication;
                break;
            }
        }
        if (!$galleyPublication) {
            return Hook::CONTINUE;
        }

        $parentUrl = $request->url(null, $submissionNoun, 'view', [$submission->getBestId()]);

        $this->display($request, [
            'galleyFile' => $galley->getFile(),
            'issue' => $issue,
            'submission' => $submission,
            'submissionNoun' => $submissionNoun,
            'bestId' => $galleyPublication->getData('urlPath') ?? $submission->getId(),
            'galley' => $galley,
            'galleyPublication' => $galleyPublication,
            'isLatestPublication' => $submission->getData('currentPublicationId') === $galley->getData('publicationId'),
            'title' => $galleyPublication->getLocalizedTitle(null, 'html'),
            'isTitleHtml' => true,
            'epubUrl' => $request->url(null, $submissionNoun, 'download', [$submission->getBestId(), $galley->getBestGalleyId(), $galley->getFile()->getId()]),
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

        return Hook::ABORT;
    }

    /**
     * OJS: show an EPUB issue galley in the reader.
     *
     * @param string $hookName
     * @param array $args [$request, $issue, $galley]
     */
    public function issueCallback($hookName, $args): bool
    {
        [$request, $issue, $galley] = $args;
        if (!$galley || $galley->getFileType() !== self::EPUB_MIME_TYPE) {
            return Hook::CONTINUE;
        }

        $this->display($request, [
            'issue' => $issue,
            'galley' => $galley,
            'galleyFile' => $galley->getFile(),
            'isLatestPublication' => true,
            'title' => $issue->getLocalizedTitle(),
            'isTitleHtml' => false,
            'epubUrl' => $request->url(null, 'issue', 'download', [$issue->getBestIssueId(), $galley->getBestGalleyId()]),
            'parentUrl' => $request->url(null, 'issue', 'view', [$issue->getBestIssueId()]),
            'galleyTitle' => $issue->getLocalizedTitle(),
        ]);

        return Hook::ABORT;
    }

    /**
     * Render the reader page.
     */
    private function display($request, array $variables): void
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign(array_merge([
            'displayTemplateResource' => $this->getTemplateResource('display.tpl'),
            'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
            'currentVersionString' => Application::get()->getCurrentVersion()->getVersionString(false),
        ], $variables));
        $templateMgr->display($this->getTemplateResource('display.tpl'));
    }
}
