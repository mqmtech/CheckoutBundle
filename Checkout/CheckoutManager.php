<?php

namespace MQM\CheckoutBundle\Checkout;



use MQM\ShoppingCartBundle\Model\ShoppingCartInterface;
use MQM\PricingBundle\Pricing\PricingManagerInterface;
use MQM\OrderBundle\Model\OrderFactoryInterface;
use MQM\TaxationBundle\Taxation\TaxationManagerInterface;

class CheckoutManager
{
    private $pricingManager;
    private $marketManager;
    private $orderFactory;
    
    public function __construct(PricingManagerInterface $pricingManager, TaxationManagerInterface $marketManager, OrderFactoryInterface $orderFactory)
    {
        $this->pricingManager = $pricingManager;
        $this->marketManager = $marketManager;
        $this->orderFactory = $orderFactory;
    }
    
    /**
     * Fill the shopping cart with the current market values and product discountRules
     *
     * @param ShoppingCart $shoppingCart
     * @return ShoppingCart 
     */
    public function checkout(ShoppingCartInterface $shoppingCart = null)
    {
        if ($shoppingCart == null) {
            return null;
        }
        
        $pricingManager = $this->pricingManager;
        $marketManager = $this->marketManager;
        $shoppingCartItems = $shoppingCart->getItems();
        if ($shoppingCartItems) {
            foreach ($shoppingCartItems as $item) {
                //Calculate price per unit and total (and check for discountRules)
                $price = $pricingManager->getProductPrice($item->getProduct());
                $item->setBasePrice($price->getValue());
                $priceValueForTotalQuantity = $price->getValue() * $item->getQuantity();
                $item->setTotalBasePrice($priceValueForTotalQuantity);
            }            
            $price = $this->getItemCollectionPrice($shoppingCartItems->toArray());
            $shoppingCart->setTotalProductsBasePrice($price->getValue());
            //Calculate totalBasePrice (products + shipment)
            $totalBasePrice = $price->getValue() ; // + shipment when it's needed
            $shoppingCart->setTotalBasePrice($totalBasePrice);        
            //Get Tax
            $tax = $marketManager->getTax();
            $shoppingCart->setTax($tax);
            //Get Tax Price
            $taxPrice =  $tax * $totalBasePrice;
            $shoppingCart->setTaxPrice($taxPrice);
            //Calculate totalPrice
            $totalPrice = $totalBasePrice + $taxPrice;
            $shoppingCart->setTotalPrice($totalPrice);
        }        
        $shoppingCart->setModifiedAt(new \DateTime('now'));
        
        return $shoppingCart;
    }
    
    /**
     * Convert a shoppingCart into an Order
     *
     * @param ShoppingCart $shoppingCart
     * @return Order 
     */
    public function shoppingCartToOrder(ShoppingCartInterface $shoppingCart)
    {
        if ($shoppingCart == null)
            return null;
        
        $orderFactory = $this->orderFactory;
        $order = $orderFactory->createOrder();
        $shoppingCartItems = $shoppingCart->getItems();
        foreach ($shoppingCartItems as $item) {
            $orderItem = $orderFactory->createOrderItem();
            $orderItem->setProduct($item->getProduct());
            $orderItem->setQuantity($item->getQuantity());
            $orderItem->setBasePrice($item->getBasePrice());
            $orderItem->setTotalBasePrice($item->getTotalBasePrice());
            
            //Add orderItem to Order
            $order->addItem($orderItem);
        }        
        $order->setTotalProductsBasePrice($shoppingCart->getTotalProductsBasePrice());
        $order->setTotalBasePrice($shoppingCart->getTotalBasePrice());
        $order->setTax($shoppingCart->getTax());
        $order->setTaxPrice($shoppingCart->getTaxPrice());
        $order->setTotalPrice($shoppingCart->getTotalPrice());
        $order->setModifiedAt(new \DateTime('now'));
        
        return $order;
    }
    
    /**
     *
     * @param array <OrderItem|ShoppingCartItem>
     * @return PriceInfo
     */
    private function getItemCollectionPrice(array $itemsCollection)
    {
        $price = $this->pricingManager->createPrice();
        $totalBasePrice = 0.0;
        foreach ($itemsCollection as $item) {
            $aTotalBasePrice = $item->getTotalBasePrice();            
            $totalBasePrice += $aTotalBasePrice;
        }        
        //TODO: Apply DiscountRules to Shopping Cart ?
        $price->setValue($totalBasePrice);
        
        return $price;
    }
}