<?php

/*
 * This file is part of the MediaModule for Zikula.
 *
 * (c) Christian Flach <hi@christianflach.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cmfcmf\Module\MediaModule\MediaType;

use Cmfcmf\Module\MediaModule\Entity\Media\AbstractMediaEntity;
use Cmfcmf\Module\MediaModule\Entity\Media\MarkdownEntity;
use Cmfcmf\Module\MediaModule\Entity\Media\PlaintextEntity;
use Michelf\MarkdownExtra;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Markdown extends AbstractFileMediaType implements UploadableMediaTypeInterface
{
    /**
     * @var MarkdownExtra
     */
    private $markdownExtraParser;

    /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return $this->translator->trans('Markdown', [], $this->domain);
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'fa-align-right';
    }

    public function setMarkdownParser(MarkdownExtra $markdownExtraParser)
    {
        $this->markdownExtraParser = $markdownExtraParser;
    }

    public function renderFullpage(AbstractMediaEntity $entity)
    {
        /** @var MarkdownEntity $entity */
        $raw = file_get_contents($entity->getPath());
        $rendered = $this->markdownExtraParser->transform($raw);

        return $this->renderEngine->render('CmfcmfMediaModule:MediaType/Markdown:fullpage.html.twig', [
            'entity' => $entity,
            'rendered' => $rendered,
            'raw' => $raw
        ]);
    }

    public function getExtendedMetaInformation(AbstractMediaEntity $entity)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function canUpload(UploadedFile $file)
    {
        if ($file->getMimeType() == 'text/plain' && $file->getClientOriginalExtension() == 'md') {
            return 5;
        }

        return 0;
    }

    /**
     * Whether or not this media type supports uploading the file represented by the file info array.
     *
     * @param array $file
     *
     * @return int 10 if it perfectly matches, 0 if it can't upload.
     */
    public function canUploadArr(array $file)
    {
        if ($file['mimeType'] == 'text/plain' && pathinfo($file['name'], PATHINFO_EXTENSION) == 'md') {
            return 5;
        }

        return 0;
    }

    public function getThumbnail(AbstractMediaEntity $entity, $width, $height, $format = 'html', $mode = 'outbound', $optimize = true)
    {
        /** @var PlaintextEntity $entity */
        return $this->getIconThumbnailByFileExtension($entity, $width, $height, $format, $mode, $optimize, 'txt');
    }

    public function isEmbeddable()
    {
        return false;
    }
}
