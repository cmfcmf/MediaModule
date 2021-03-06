<?php

declare(strict_types=1);

/*
 * This file is part of the MediaModule for Zikula.
 *
 * (c) Christian Flach <hi@christianflach.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cmfcmf\Module\MediaModule\Controller;

use Cmfcmf\Module\MediaModule\Entity\License\LicenseEntity;
use Cmfcmf\Module\MediaModule\Form\License\LicenseType;
use Doctrine\ORM\OptimisticLockException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/licenses")
 */
class LicenseController extends AbstractController
{
    /**
     * @Route("/", methods={"GET"})
     * @Template("@CmfcmfMediaModule/License/index.html.twig")
     *
     * @return array
     */
    public function index()
    {
        if (!$this->securityManager->hasPermission('license', 'moderate')) {
            throw new AccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();
        /** @var LicenseEntity[] $entities */
        $entities = $em->getRepository('CmfcmfMediaModule:License\LicenseEntity')->findBy([], [
            'outdated' => 'ASC', 'id' => 'ASC'
        ]);

        return ['entities' => $entities];
    }

    /**
     * @Route("/new")
     * @Template("@CmfcmfMediaModule/License/edit.html.twig")
     *
     * @return array|RedirectResponse
     */
    public function create(Request $request)
    {
        if (!$this->securityManager->hasPermission('license', 'add')) {
            throw new AccessDeniedException();
        }

        $entity = new LicenseEntity(null);
        $form = $this->createForm(LicenseType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('status', $this->trans('License created!'));

            return $this->redirectToRoute('cmfcmfmediamodule_license_index');
        }

        return ['form' => $form->createView()];
    }

    /**
     * @Route("/edit/{id}")
     * @Template("@CmfcmfMediaModule/License/edit.html.twig")
     *
     * @return array
     */
    public function edit(Request $request, LicenseEntity $entity)
    {
        if (!$this->securityManager->hasPermission($entity, 'edit')) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(LicenseType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->merge($entity);
                $em->flush();

                $this->addFlash('status', $this->trans('License edited!'));

                return $this->redirectToRoute('cmfcmfmediamodule_license_index');
            } catch (OptimisticLockException $e) {
                $form->addError(new FormError($this->trans('Someone else edited the collection. Please either cancel editing or force reload the page.')));
            }
        }

        return ['form' => $form->createView()];
    }

    /**
     * @Route("/delete/{id}")
     * @Template("@CmfcmfMediaModule/License/delete.html.twig")
     *
     * @return array|RedirectResponse
     */
    public function delete(Request $request, LicenseEntity $entity)
    {
        if (!$this->securityManager->hasPermission($entity, 'delete')) {
            throw new AccessDeniedException();
        }

        if ($request->isMethod('GET')) {
            return ['entity' => $entity];
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        $this->addFlash('status', $this->trans('License deleted!'));

        return $this->redirectToRoute('cmfcmfmediamodule_license_index');
    }
}
