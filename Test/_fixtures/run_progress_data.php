<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SwagMigrationAssistant\Migration\Run\EntityProgress;
use SwagMigrationAssistant\Migration\Run\RunProgress;

// Categories & Products
$categoryEntity = new EntityProgress();
$categoryEntity->setEntityName('category');
$categoryEntity->setCurrentCount(0);
$categoryEntity->setTotal(8);

$productEntity = new EntityProgress();
$productEntity->setEntityName('product');
$productEntity->setCurrentCount(0);
$productEntity->setTotal(37);

$categoriesProducts = new RunProgress();
$categoriesProducts->setId('products');
$categoriesProducts->setCurrentCount(0);
$categoriesProducts->setTotal(45);
$categoriesProducts->setEntities([
    $categoryEntity,
    $productEntity,
]);

// Customers & Orders
$customerEntity = new EntityProgress();
$customerEntity->setEntityName('customer');
$customerEntity->setCurrentCount(0);
$customerEntity->setTotal(3);

$orderEntity = new EntityProgress();
$orderEntity->setEntityName('order');
$orderEntity->setCurrentCount(0);
$orderEntity->setTotal(2);

$customersOrders = new RunProgress();
$customersOrders->setId('customersOrders');
$customersOrders->setCurrentCount(0);
$customersOrders->setTotal(5);
$customersOrders->setEntities([
    $customerEntity,
    $orderEntity,
]);

// Media
$mediaEntity = new EntityProgress();
$mediaEntity->setEntityName('media');
$mediaEntity->setCurrentCount(0);
$mediaEntity->setTotal(23);

$media = new RunProgress();
$media->setId('media');
$media->setCurrentCount(0);
$media->setTotal(23);
$media->setEntities([
    $mediaEntity,
]);

// Media file process
$mediaEntity = new EntityProgress();
$mediaEntity->setEntityName('media');
$mediaEntity->setCurrentCount(0);
$mediaEntity->setTotal(23);

$media = new RunProgress();
$media->setId('processMediaFiles');
$media->setCurrentCount(0);
$media->setTotal(23);
$media->setEntities([
    $mediaEntity,
]);

return [
    $categoriesProducts,
    $customersOrders,
    $media,
];
