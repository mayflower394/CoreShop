<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2019 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Bundle\CoreBundle\Validator\Constraints;

use CoreShop\Bundle\CoreBundle\Validator\QuantityValidatorService;
use CoreShop\Bundle\OrderBundle\DTO\AddToCartInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\OrderItemInterface;
use CoreShop\Component\Core\Model\ProductInterface;
use CoreShop\Component\Inventory\Model\StockableInterface;
use CoreShop\Component\Order\Cart\CartItemResolver;
use CoreShop\Component\Order\Model\PurchasableInterface;
use CoreShop\Component\StorageList\StorageListItemResolverInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

class AddToCartMinimumQuantityValidator extends ConstraintValidator
{
    /**
     * @var QuantityValidatorService
     */
    private $quantityValidatorService;

    /**
     * @var StorageListItemResolverInterface
     */
    protected $cartItemResolver;

    /**
     * @param QuantityValidatorService         $quantityValidatorService
     * @param StorageListItemResolverInterface $cartItemResolver
     */
    public function __construct(
        QuantityValidatorService $quantityValidatorService,
        StorageListItemResolverInterface $cartItemResolver = null
    )
    {
        $this->quantityValidatorService = $quantityValidatorService;

        if (null === $cartItemResolver) {
            @trigger_error(
                'Not passing a StorageListItemResolverInterface as second argument is deprecated since 2.1.1 and will be removed with 3.0.0',
                E_USER_DEPRECATED
            );

            $this->cartItemResolver = new CartItemResolver();
        }
        else {
            $this->cartItemResolver = $cartItemResolver;
        }
    }

    /**
     * @param mixed      $addToCartDto
     * @param Constraint $constraint
     */
    public function validate($addToCartDto, Constraint $constraint): void
    {
        Assert::isInstanceOf($addToCartDto, AddToCartInterface::class);
        Assert::isInstanceOf($constraint, AddToCartMinimumQuantity::class);

        /**
         * @var PurchasableInterface $purchasable
         */
        $purchasable = $addToCartDto->getCartItem()->getProduct();

        if (!$purchasable instanceof StockableInterface) {
            return;
        }

        if (!$purchasable instanceof ProductInterface) {
            return;
        }

        /**
         * @var OrderInterface     $cart
         * @var OrderItemInterface $cartItem
         */
        $cartItem = $addToCartDto->getCartItem();
        $cart = $addToCartDto->getCart();

        $quantity = $cartItem->getDefaultUnitQuantity() + $this->getExistingCartItemQuantityFromCart($cart, $cartItem);
        $minLimit = $purchasable->getMinimumQuantityToOrder();

        if ($this->quantityValidatorService->isLowerThenMinLimit($minLimit, $quantity)) {
            $this->context->addViolation(
                $constraint->messageBelowMinimum,
                [
                    '%stockable%' => $purchasable->getInventoryName(),
                    '%limit%' => $minLimit,
                ]
            );
        }
    }

    private function getExistingCartItemQuantityFromCart(OrderInterface $cart, OrderItemInterface $cartItem): int
    {
        $product = $cartItem->getProduct();
        $quantity = 0;

        /**
         * @var OrderItemInterface $item
         */
        foreach ($cart->getItems() as $item) {
            if (!$product && $this->cartItemResolver->equals($item, $cartItem)) {
                return $item->getDefaultUnitQuantity();
            }

            if ($item->getProduct() instanceof $product && $item->getProduct()->getId() === $product->getId()) {
                $quantity += $item->getDefaultUnitQuantity();
            }
        }

        return $quantity;
    }
}
