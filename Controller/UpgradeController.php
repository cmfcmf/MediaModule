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

use Cmfcmf\Module\MediaModule\Exception\UpgradeFailedException;
use Cmfcmf\Module\MediaModule\Exception\UpgradeNotRequiredException;
use Cmfcmf\Module\MediaModule\Upgrade\ModuleUpgrader;
use Cmfcmf\Module\MediaModule\Upgrade\VersionChecker;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\ThemeModule\Engine\Annotation\Theme;

class UpgradeController extends AbstractController
{
    /**
     * @Route("/settings/upgrade")
     * @Template("@CmfcmfMediaModule/Upgrade/doUpgrade.html.twig")
     * @Theme("admin")
     *
     * @return array|RedirectResponse
     */
    public function doUpgrade(ModuleUpgrader $moduleUpgrader)
    {
        if (!$this->securityManager->hasPermission('settings', 'admin')) {
            throw new AccessDeniedException();
        }

        $hasPermission = $this->hasPermission('ZikulaExtensionsModule::', '::', ACCESS_ADMIN);

        return [
            'steps' => $moduleUpgrader->getUpgradeSteps(),
            'hasPermission' => $hasPermission
        ];
    }

    /**
     * @Route("/settings/upgrade/ajax/{step}", options={"expose" = true})
     *
     * @param $step
     *
     * @return JsonResponse
     */
    public function ajax(
        ModuleUpgrader $moduleUpgrader,
        VersionChecker $versionChecker,
        $step
    ) {
        if (!$this->securityManager->hasPermission('settings', 'admin') || !$this->securityManager->hasPermissionRaw('ZikulaExtensionsModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        set_time_limit(60);

        try {
            $upgradeDone = $moduleUpgrader->upgrade($step, $versionChecker);
            if ($upgradeDone) {
                $this->resetUpgradeMessage();
            }

            return $this->json([
                'proceed' => true,
                'message' => null,
                'done' => $upgradeDone
            ]);
        } catch (UpgradeNotRequiredException $e) {
            $this->resetUpgradeMessage();

            return $this->json([
                'proceed' => false,
                'message' => $e->getMessage(),
                'done' => false
            ]);
        } catch (UpgradeFailedException $e) {
            return $this->json([
                'proceed' => false,
                'message' => $e->getMessage(),
                'done' => false
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'proceed' => false,
                'message' => $this->trans(
                    'Something unexpected happened. Please report this problem and give the following information: %s',
                    ['%s' => (string) $e],
                    'cmfcmfmediamodule'
                ),
                'done' => false
            ]);
        }
    }

    private function resetUpgradeMessage()
    {
        $this->setVar('newVersionAvailable', false);
        $this->setVar('lastNewVersionCheck', 0);
    }
}
