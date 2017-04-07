<?php

namespace CoreShop\Component\Order\Manager;

use CoreShop\Component\Order\Model\CartInterface;

interface CartManagerInterface
{
    /**
     * @param CartInterface $cart
     *
     * @return mixed
     */
    public function setCurrentCart(CartInterface $cart);

    /**
     * @return CartInterface
     */
    public function getCart();

    /**
     * @return bool
     */
    public function hasCart();

    /**
     * @param CartInterface $cart
     *
     * @return mixed
     */
    public function persistCart(CartInterface $cart);

    /**
     * @param $name
     * @param null $user
     * @param null $store
     * @param null $currency
     * @param bool $persist
     *
     * @return CartInterface
     */
    public function createCart($name, $user = null, $store = null, $currency = null, $persist = false);

    /**
     * @param $user
     *
     * @return CartInterface[]
     */
    public function getStoredCarts($user);

    /**
     * @param $customer
     * @param $name
     *
     * @return CartInterface|null
     */
    public function getByName($customer, $name);

    /**
     * @param int $id
     *
     * @return bool
     */
    public function deleteCart($id);
}