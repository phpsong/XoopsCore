<?php
namespace Xoops\Form;

require_once(dirname(__FILE__).'/../../../init_mini.php');

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-08-18 at 21:59:24.
 */
 
/**
 * PHPUnit special settings :
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */

class ElementTrayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ElementTray
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new ElementTray('Caption');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Xoops\Form\ElementTray::isRequired
     */
    public function testIsRequired()
    {
        $value = $this->object->isRequired();
        $this->assertFalse($value);
        
        $button = new Button('button_caption', 'button_name');
        $this->object->addElement($button, true);
        $value = $this->object->isRequired();
        $this->assertTrue($value);
        
        $value = $this->object->getRequired();
        $this->assertTrue(is_array($value));
        $this->assertInstanceOf('Xoops\Form\Button', $value[0]);
        
        $value = $this->object->getElements();
        $this->assertTrue(is_array($value));
        $this->assertInstanceOf('Xoops\Form\Button', $value[0]);
    }

    /**
     * @covers Xoops\Form\ElementTray::addElement
     */
    public function testAddElement()
    {
        // see testIsRequired
    }

    /**
     * @covers Xoops\Form\ElementTray::getRequired
     * @todo   Implement testGetRequired().
     */
    public function testGetRequired()
    {
        // see testIsRequired
    }

    /**
     * @covers Xoops\Form\ElementTray::getElements
     * @todo   Implement testGetElements().
     */
    public function testGetElements()
    {
        // see testIsRequired
    }

    /**
     * @covers Xoops\Form\ElementTray::getDelimiter
     * @todo   Implement testGetDelimiter().
     */
    public function testGetDelimiter()
    {
        $value = $this->object->getDelimiter();
        $this->assertSame('&nbsp;', $value);
        
        $value = $this->object->getDelimiter(true);
        $this->assertSame(' ', $value);
    }

    /**
     * @covers Xoops\Form\ElementTray::render
     * @todo   Implement testRender().
     */
    public function testRender()
    {
        $value = $this->object->render();
        $this->assertTrue(is_string($value));
    }
}