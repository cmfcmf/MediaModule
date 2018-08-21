<?php

/*
 * This file is part of the MediaModule for Zikula.
 *
 * (c) Christian Flach <hi@christianflach.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cmfcmf\Module\MediaModule\Imagine\Cache;

use Liip\ImagineBundle\Imagine\Cache\SignerInterface;

/**
 * Temporary dummy signer until https://github.com/liip/LiipImagineBundle/issues/837 has been resolved.
 */
class DummySigner implements SignerInterface
{
    /**
     * @var string
     */
    protected $secret;

    /**
     * @param string $secret
     */
    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @inheritDoc
     */
    public function sign($path, array $runtimeConfig = null)
    {
        if ($runtimeConfig) {
            array_walk_recursive($runtimeConfig, function (&$value) {
                $value = (string) $value;
            });
        }

        return substr(preg_replace('/[^a-zA-Z0-9-_]/', '', base64_encode(hash_hmac('sha256', ltrim($path, '/').(null === $runtimeConfig ?: serialize($runtimeConfig)), $this->secret, true))), 0, 8);
    }

    /**
     * @inheritDoc
     */
    public function check($hash, $path, array $runtimeConfig = null)
    {
        return true;//$hash === $this->sign($path, $runtimeConfig);
    }
}
