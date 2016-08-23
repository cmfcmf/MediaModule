<?php

namespace Cmfcmf\Module\MediaModule\Importer;

use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

interface ImporterInterface
{
    /**
     * The unique importer id. Must be unique across all bundles. It is recommended to prefix it with the bundle name.
     *
     * @return string
     */
    public function getId();

    /**
     * The title of the importer.
     *
     * @return string
     */
    public function getTitle();

    /**
     * The description of the importer.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Checks whether the importer can be used. True if it can, an error message otherwise.
     *
     * @return bool|string
     */
    public function checkRequirements();

    /**
     * @return FormTypeInterface
     */
    public function getSettingsForm();

    public function import($formData, FlashBagInterface $flashBag);
}
