<?php

namespace Cmfcmf\Module\MediaModule\Security\CollectionPermission;

use Cmfcmf\Module\MediaModule\Entity\Collection\Permission\AbstractPermissionEntity;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

interface CollectionPermissionInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param AbstractPermissionEntity $permissionEntity
     *
     * @return string
     */
    public function getTargets($permissionEntity);

    /**
     * @return string
     */
    public function getEntityClass();

    /**
     * @return string
     */
    public function getFormClass();

    /**
     * @param QueryBuilder &$qb
     * @param              $permissionAlias
     *
     * @return Expr|null
     */
    public function getApplicablePermissionsExpression(QueryBuilder &$qb, $permissionAlias);
}