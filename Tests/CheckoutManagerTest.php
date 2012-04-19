<?php

namespace MQM\CheckoutBundle\Test\Checkout;

use MQM\CheckoutBundle\Checkout\CheckoutInterface;
use MQM\CheckoutBundle\Model\CheckoutManagerInterface;
use Symfony\Bundle\FrameworkBundle\Tests\Functional\AppKernel;

class CheckoutManagerTest extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{   
    protected $_container;
    
    /**
     * @var CheckoutManagerInterface
     */
    private $checkoutManager;


    public function __construct()
    {
        parent::__construct();
        
        $client = static::createClient();
        $container = $client->getContainer();
        $this->_container = $container;  
    }
    
    protected function setUp()
    {
        $this->checkoutManager = $this->get('mqm_checkout.checkout_manager');
    }

    protected function tearDown()
    {
    }

    protected function get($service)
    {
        return $this->_container->get($service);
    }
    
    public function testGetAssertManager()
    {
        $this->assertNotNull($this->checkoutManager);
    }

}
