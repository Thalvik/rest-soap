<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_order")
 */
class UserOrder
{
    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
    * @ORM\Column(type="decimal", length=50, nullable=true, options={"default":0.00})
    */
    private $orderAmount;

    /**
     * @ORM\Column(type="decimal", length=50, nullable=true, options={"default":0.00})
     */
    private $shippingAmount;

    /**
     * @ORM\Column(type="decimal", length=50, nullable=true, options={"default":0.00})
     */
    private $taxAmount;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="userOrders")
     **/
    private $user;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return UserOrder
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get orderAmount
     *
     * @return integer
     */
    public function getOrderAmount()
    {
        return $this->orderAmount;
    }

    /**
     * Set orderAmount
     *
     * @param integer $orderAmount
     *
     * @return UserOrder
     */
    public function setOrderAmount($orderAmount)
    {
        $this->orderAmount = $orderAmount;
        return $this;
    }

    /**
     * Get shippingAmount
     *
     * @return integer
     */
    public function getShippingAmount()
    {
        return $this->shippingAmount;
    }

    /**
     * Set shippingAmount
     *
     * @param integer $shippingAmount
     *
     * @return UserOrder
     */
    public function setShippingAmount($shippingAmount)
    {
        $this->shippingAmount = $shippingAmount;
        return $this;
    }

    /**
     * Get taxAmount
     *
     * @return integer
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    /**
     * Set taxAmount
     *
     * @param integer $taxAmount
     *
     * @return UserOrder
     */
    public function setTaxAmount($taxAmount)
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    /**
     * Set user
     *
     * @param User|null $user
     *
     * @return UserOrder
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
