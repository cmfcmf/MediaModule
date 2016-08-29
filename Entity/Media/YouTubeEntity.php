<?php

/*
 * This file is part of the MediaModule for Zikula.
 *
 * (c) Christian Flach <hi@christianflach.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cmfcmf\Module\MediaModule\Entity\Media;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class YouTubeEntity extends UrlEntity
{
    public function setYouTubeId($youTubeId)
    {
        $this->extraData['youTubeId'] = $youTubeId;

        return $this;
    }

    public function getYouTubeId()
    {
        return isset($this->extraData['youTubeId']) ? $this->extraData['youTubeId'] : null;
    }

    public function setYouTubeType($youTubeType)
    {
        $this->extraData['youTubeType'] = $youTubeType;

        return $this;
    }

    public function getYouTubeType()
    {
        return isset($this->extraData['youTubeType']) ? $this->extraData['youTubeType'] : null;
    }

    public function setYouTubeThumbnailUrl($youTubeThumbnailUrl)
    {
        $this->extraData['youTubeThumbnailUrl'] = $youTubeThumbnailUrl;

        return $this;
    }

    public function getYouTubeThumbnailUrl()
    {
        return isset($this->extraData['youTubeThumbnailUrl']) ? $this->extraData['youTubeThumbnailUrl'] : null;
    }
}
