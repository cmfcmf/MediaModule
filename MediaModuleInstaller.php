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

namespace Cmfcmf\Module\MediaModule;

use Cmfcmf\Module\MediaModule\Entity\Collection\CollectionCategoryAssignmentEntity;
use Cmfcmf\Module\MediaModule\Entity\Collection\CollectionEntity;
use Cmfcmf\Module\MediaModule\Entity\Collection\Permission\AbstractPermissionEntity;
use Cmfcmf\Module\MediaModule\Entity\Collection\Permission\GroupPermissionEntity;
use Cmfcmf\Module\MediaModule\Entity\Collection\Permission\OwnerPermissionEntity;
use Cmfcmf\Module\MediaModule\Entity\Collection\Permission\Restriction\AbstractPermissionRestrictionEntity;
use Cmfcmf\Module\MediaModule\Entity\Collection\Permission\Restriction\PasswordPermissionRestrictionEntity;
use Cmfcmf\Module\MediaModule\Entity\Collection\Permission\UserPermissionEntity;
use Cmfcmf\Module\MediaModule\Entity\License\LicenseEntity;
use Cmfcmf\Module\MediaModule\Entity\Media\AbstractMediaEntity;
use Cmfcmf\Module\MediaModule\Entity\Media\MediaCategoryAssignmentEntity;
use Cmfcmf\Module\MediaModule\Entity\Watermark\AbstractWatermarkEntity;
use Cmfcmf\Module\MediaModule\Entity\Watermark\TextWatermarkEntity;
use Cmfcmf\Module\MediaModule\Security\CollectionPermission\CollectionPermissionSecurityTree;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zikula\Bundle\CoreBundle\Doctrine\Helper\SchemaHelper;
use Zikula\CategoriesModule\Entity\CategoryRegistryEntity;
use Zikula\ExtensionsModule\AbstractExtension;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\ExtensionsModule\Installer\AbstractExtensionInstaller;

class MediaModuleInstaller extends AbstractExtensionInstaller
{
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(
        AbstractExtension $extension,
        ManagerRegistry $managerRegistry,
        SchemaHelper $schemaTool,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        VariableApiInterface $variableApi,
        string $projectDir
    ) {
        parent::__construct($extension, $managerRegistry, $schemaTool, $requestStack, $translator, $variableApi);
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        $this->schemaTool->create(static::getEntities());

        $this->createLicenses();

        $temporaryUploadCollection = new CollectionEntity();
        $temporaryUploadCollection
            ->setTitle($this->trans('Temporary upload collection'))
            ->setSlug('tmp')
            ->setDescription($this->trans('This collection is needed as temporary storage for uploaded files. Do not edit or delete!'))
        ;
        $this->entityManager->persist($temporaryUploadCollection);

        // We need to create and flush the upload collection first, because it has to has the ID 1.
        $this->entityManager->flush();
        if (CollectionEntity::TEMPORARY_UPLOAD_COLLECTION_ID !== $temporaryUploadCollection->getId()) {
            throw new \Exception($this->trans('The id of the generated "temporary upload collection" must be %s%, but has a different value. This should not have happened. Please report this error.', ['%s%' => CollectionEntity::TEMPORARY_UPLOAD_COLLECTION_ID]));
        }

        $rootCollection = new CollectionEntity();
        $rootCollection
            ->setTitle($this->trans('Root collection'))
            ->setSlug($this->trans('root'))
            ->setDescription('The very top of the collection tree.');
        $this->entityManager->persist($rootCollection);

        $temporaryUploadCollection->setParent($rootCollection);
        $this->entityManager->merge($temporaryUploadCollection);

        $exampleCollection = new CollectionEntity();
        $exampleCollection
            ->setTitle($this->trans('Example collection'))
            ->setDescription($this->trans('Edit or delete this example collection'))
            ->setParent($rootCollection);
        $this->entityManager->persist($exampleCollection);

        $this->createPermissions($temporaryUploadCollection, $rootCollection);

        $this->setVar('descriptionEscapingStrategyForCollection', 'text');
        $this->setVar('descriptionEscapingStrategyForMedia', 'text');
        $this->setVar('defaultCollectionTemplate', 'cards');
        $this->setVar('defaultLicense', null);
        $this->setVar('slugEditable', true);
        $this->setVar('lastNewVersionCheck', 0);
        $this->setVar('newVersionAvailable', false);
        $this->setVar('enableMediaViewCounter', false);
        $this->setVar('enableCollectionViewCounter', false);

        $this->createCategoryRegistries();

        $this->createUploadDir();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade($oldversion): bool
    {
        switch ($oldversion) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case '1.0.0':
                $qb = $this->entityManager->createQueryBuilder();
                $qb->update('Cmfcmf\\Module\\MediaModule\\Entity\\Watermark\\TextWatermarkEntity', 'w')
                    ->set('w.font', $qb->expr()->literal('cmfcmfmediamodule:Indie_Flower'))
                    ->getQuery()
                    ->execute()
                ;
                // no break
            case '1.0.1':
            case '1.0.2':
            case '1.0.3':
            case '1.0.4':
            case '1.0.5':
            /** @noinspection PhpMissingBreakStatementInspection */
            // no break
            case '1.0.6':
                $this->schemaTool->create([
                    AbstractPermissionEntity::class,
                    GroupPermissionEntity::class,
                    OwnerPermissionEntity::class,
                    UserPermissionEntity::class,

                    AbstractPermissionRestrictionEntity::class,
                    PasswordPermissionRestrictionEntity::class
                ]);
                $this->schemaTool->update([
                    CollectionEntity::class
                ]);

                // Create root collection.
                $rootCollection = new CollectionEntity();
                $rootCollection
                    ->setTitle($this->trans('Root collection'))
                    ->setSlug($this->trans('root'))
                    ->setDescription('The very top of the collection tree.')
                ;
                $this->entityManager->persist($rootCollection);

                $allCollections = $this->entityManager
                    ->getRepository(CollectionEntity::class)
                    ->findAll();
                foreach ($allCollections as $collection) {
                    if (null === $collection->getParent() && null !== $collection->getId()) {
                        // Collection has no parent and isn't the to-be-created root collection.
                        $collection->setParent($rootCollection);
                        $this->entityManager->merge($collection);
                    }
                }
                $this->entityManager->flush();

                $this->createPermissions(
                    $this->entityManager->find(CollectionEntity::class, 1),
                    $rootCollection
                );
                // no break
            case '1.1.0':
            case '1.1.1':
            case '1.1.2':
                $this->schemaTool->update([
                    AbstractWatermarkEntity::class,
                    TextWatermarkEntity::class
                ]);
                $this->entityManager->getConnection()->executeUpdate("
                    UPDATE `cmfcmfmedia_watermarks`
                    SET `fontColor`='#000000ff', `backgroundColor`='#00000000'
                    WHERE `discr`='text'
                ");

                $this->schemaTool->update([
                    CollectionEntity::class,
                    CollectionCategoryAssignmentEntity::class,
                    AbstractMediaEntity::class,
                    MediaCategoryAssignmentEntity::class
                ]);

                $this->createCategoryRegistries();
                // no break
            case '1.2.0':
            case '1.2.1':
            case '1.2.2':
                // fields have changed: createdUserId becomes createdBy, updatedUserId becomes updatedBy
                $connection = $this->entityManager->getConnection();
                foreach (['collection', 'media', 'watermarks', 'permission'] as $tableName) {
                    $sql = '
                        ALTER TABLE `cmfcmfmedia_' . $tableName . '`
                        CHANGE `createdUserId` `createdBy` INT(11) DEFAULT NULL
                    ';
                    $stmt = $connection->prepare($sql);
                    $stmt->execute();

                    $sql = '
                        ALTER TABLE `cmfcmfmedia_' . $tableName . '`
                        CHANGE `updatedUserId` `updatedBy` INT(11) DEFAULT NULL
                    ';
                    $stmt = $connection->prepare($sql);
                    $stmt->execute();
                }
                // no break
            case '1.3.0':
            case '2.0.0':
                // @todo change datadir to public/uploads in media entities
                return true;
            default:
                return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
    {
        // @todo Also delete media files?
        $this->schemaTool->drop(static::getEntities());

        $this->delVars();

        // remove category registry entries
        $registries = $this->managerRegistry->getRepository('ZikulaCategoriesModule:CategoryRegistryEntity')->findBy(['modname' => 'CmfcmfMediaModule']);
        foreach ($registries as $registry) {
            $this->entityManager->remove($registry);
        }
        $this->entityManager->flush();

        return true;
    }

    /**
     * Returns a list of all entity classes.
     *
     * @return array
     *
     * @internal
     */
    public static function getEntities()
    {
        $prefix = 'Cmfcmf\\Module\\MediaModule\\Entity\\';
        $mediaPrefix = $prefix . 'Media\\';
        $watermarkPrefix = $prefix . 'Watermark\\';
        $hookObjectPrefix = $prefix . 'HookedObject\\';
        $permissionPrefix = $prefix . 'Collection\\Permission\\';

        return [
            $hookObjectPrefix . 'HookedObjectEntity',
            $hookObjectPrefix . 'HookedObjectMediaEntity',
            $hookObjectPrefix . 'HookedObjectCollectionEntity',

            $prefix . 'Collection\CollectionEntity',

            $prefix . 'License\LicenseEntity',

            $permissionPrefix . 'AbstractPermissionEntity',
            $permissionPrefix . 'UserPermissionEntity',
            $permissionPrefix . 'GroupPermissionEntity',
            $permissionPrefix . 'OwnerPermissionEntity',

            $permissionPrefix . 'Restriction\AbstractPermissionRestrictionEntity',
            $permissionPrefix . 'Restriction\PasswordPermissionRestrictionEntity',

            $mediaPrefix . 'AbstractMediaEntity',
            $mediaPrefix . 'AbstractFileEntity',
            $mediaPrefix . 'ImageEntity',
            $mediaPrefix . 'VideoEntity',
            $mediaPrefix . 'UrlEntity',

            $mediaPrefix . 'DeezerEntity',
            $mediaPrefix . 'SoundCloudEntity',
            $mediaPrefix . 'FlickrEntity',

            $watermarkPrefix . 'AbstractWatermarkEntity',
            $watermarkPrefix . 'ImageWatermarkEntity',
            $watermarkPrefix . 'TextWatermarkEntity',

            $prefix . 'Collection\CollectionCategoryAssignmentEntity',
            $prefix . 'Media\MediaCategoryAssignmentEntity'
        ];
    }

    /**
     * Creates a set of default licenses.
     */
    private function createLicenses()
    {
        $license = new LicenseEntity('all-rights-reserved');
        $license
            ->setTitle('All Rights Reserved')
            ->setEnabledForWeb(false)
            ->setEnabledForUpload(true)
        ;
        $this->entityManager->persist($license);

        $license = new LicenseEntity('no-rights-reserved');
        $license
            ->setTitle('No Rights Reserved')
            ->setEnabledForWeb(true)
            ->setEnabledForUpload(true)
        ;
        $this->entityManager->persist($license);

        $ccVersions = ['1.0', '2.0', '2.5', '3.0', '4.0'];
        $ccNames = [
            'CC-BY' => 'Creative Commons Attribution',
            'CC-BY-ND' => 'Creative Commons Attribution No Derivatives',
            'CC-BY-NC' => 'Creative Commons Attribution Non Commercial',
            'CC-BY-NC-ND' => 'Creative Commons Attribution Non Commercial No Derivatives',
            'CC-BY-NC-SA' => 'Creative Commons Attribution Non Commercial Share Alike',
            'CC-BY-SA' => 'Creative Commons Attribution Share Alike'
        ];

        foreach ($ccVersions as $version) {
            foreach ($ccNames as $id => $name) {
                if ('CC-BY-NC-ND' === $id && '1.0' === $version) {
                    // The license image somehow does not exist.
                    continue;
                }
                $urlId = mb_strtolower(mb_substr($id, 3));
                $license = new LicenseEntity("${id}-${version}");
                $license
                    ->setTitle("${name} ${version}")
                    ->setUrl("http://creativecommons.org/licenses/${urlId}/${version}/")
                    ->setImageUrl("https://i.creativecommons.org/l/${urlId}/${version}/80x15.png")
                    ->setEnabledForWeb(true)
                    ->setEnabledForUpload(true)
                    ->setOutdated('4.0' !== $version)
                ;
                $this->entityManager->persist($license);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * Creates the upload directory.
     */
    private function createUploadDir()
    {
        $uploadDirectory = $this->projectDir . '/public/cmfcmf-media-module/media';

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        $htaccess = <<<'TXT'
deny from all
<FilesMatch "(?i)\.(css|js|rss|png|gif|jpg|jpeg|psd|svg|txt|rtf|xml|pdf|sdt|odt|doc|docx|pps|ppt|pptx|xls|xlsx|mp3|wav|wma|avi|flv|mov|mp4|rm|vob|wmv|gz|rar|tar.gz|zip|ogg|webm)$">
order allow,deny
allow from all
</FilesMatch>
TXT;
        file_put_contents($uploadDirectory . '/.htaccess', $htaccess);
    }

    /**
     * Creates the basic permission scheme.
     */
    private function createPermissions(CollectionEntity $temporaryUploadCollection, CollectionEntity $rootCollection)
    {
        $temporaryUploadCollectionPermission = new GroupPermissionEntity();
        $temporaryUploadCollectionPermission->setCollection($temporaryUploadCollection)
            ->setDescription($this->trans('Disallow access to the temporary upload collection.'))
            ->setAppliedToSelf(true)
            ->setAppliedToSubCollections(true)
            ->setGoOn(false)
            ->setPermissionLevels([CollectionPermissionSecurityTree::PERM_LEVEL_NONE])
            ->setPosition(1)
            ->setLocked(true)
            ->setGroupIds([-1]);
        $this->entityManager->persist($temporaryUploadCollectionPermission);

        $adminPermission = new GroupPermissionEntity();
        $adminPermission->setCollection($rootCollection)
            ->setDescription($this->trans('Global admin permission'))
            ->setAppliedToSelf(true)
            ->setAppliedToSubCollections(true)
            ->setGoOn(false)
            ->setPermissionLevels([CollectionPermissionSecurityTree::PERM_LEVEL_CHANGE_PERMISSIONS])
            ->setPosition(2)
            ->setLocked(true)
            ->setGroupIds([2]); // Admin group
        $this->entityManager->persist($adminPermission);

        $ownerPermission = new OwnerPermissionEntity();
        $ownerPermission->setCollection($rootCollection)
            ->setDescription($this->trans('Allows owners to administrate their own collections.'))
            ->setAppliedToSelf(true)
            ->setAppliedToSubCollections(false)
            ->setGoOn(true)
            ->setPermissionLevels(
                [CollectionPermissionSecurityTree::PERM_LEVEL_ENHANCE_PERMISSIONS]
            )
            ->setPosition(3);
        $this->entityManager->persist($ownerPermission);

        $userPermission = new GroupPermissionEntity();
        $userPermission->setCollection($rootCollection)
            ->setDescription($this->trans('Allow view and download for everyone.'))
            ->setAppliedToSelf(true)
            ->setAppliedToSubCollections(true)
            ->setGoOn(false)
            ->setPermissionLevels(
                [CollectionPermissionSecurityTree::PERM_LEVEL_DOWNLOAD_SINGLE_MEDIUM]
            )
            ->setPosition(4)
            ->setGroupIds([-1]); // All groups
        $this->entityManager->persist($userPermission);

        $this->entityManager->flush();
    }

    private function createCategoryRegistries()
    {
        $categoryGlobal = $this->managerRegistry->getRepository('ZikulaCategoriesModule:CategoryEntity')->findOneBy(['name' => 'Global']);
        if (!$categoryGlobal) {
            return;
        }

        foreach (['AbstractMediaEntity', 'CollectionEntity'] as $entityName) {
            $registry = new CategoryRegistryEntity();
            $registry->setModname('CmfcmfMediaModule');
            $registry->setEntityname($entityName);
            $registry->setProperty('Main');
            $registry->setCategory($categoryGlobal);

            $this->entityManager->persist($registry);
        }
        $this->entityManager->flush();
    }
}
