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

interface PasteMediaTypeInterface
{
    /**
     * Checks whether or not the media type matches the pasted text. Can return something in between 0 (does not match)
     * and 10.
     *
     * @param $pastedText
     *
     * @return int
     */
    public function matchesPaste($pastedText);

    /**
     * @param string $pastedText
     *
     * @return AbstractMediaEntity
     */
    public function getEntityFromPaste($pastedText);
}
