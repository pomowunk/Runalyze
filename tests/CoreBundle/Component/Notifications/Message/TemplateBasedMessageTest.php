<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Notifications\Message;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\TemplateBasedMessage;

class TemplateBasedMessageTest extends TestCase
{
    public function testNonExistantTemplate()
    {
    	$this->expectException(\InvalidArgumentException::class);

        new TemplateBasedMessage('nonexistant.yml');
    }

    public function testSimpleTemplate()
    {
        $message = new TemplateBasedMessage(TESTS_ROOT.'/CoreBundle/DataFixtures/messages/test-message.yml');

        $this->assertTrue($message->hasLink());
        $this->assertEquals('foobar', $message->getText());
        $this->assertEquals('http://runalyze.com/', $message->getLink());
        $this->assertFalse($message->isLinkInternal());
    }
}
