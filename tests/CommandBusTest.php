<?php

namespace Telegram\Bot\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Tests\Mocks\Mocker;
use Telegram\Bot\Commands\CommandBus;
use Telegram\Bot\Tests\Mocks\MockCommand;
use Telegram\Bot\Tests\Mocks\MockCommandTwo;

class CommandBusTest extends TestCase
{
    /**
     * @var CommandBus
     */
    protected $commandBus;

    public function setUp(): void
    {
        $this->commandBus = new CommandBus(Mocker::createApi()->reveal());
    }

    /** @test */
    public function it_adds_a_command_and_checks_commands_can_be_returned()
    {
        $this->commandBus->addCommand(MockCommand::class);
        $commands = $this->commandBus->getCommands();
        $this->assertInstanceOf(MockCommand::class, $commands['mycommand']);
        $this->assertCount(1, $commands);
    }

    /** @test */
    public function it_adds_multiple_commands()
    {
        $this->commandBus->addCommands([MockCommand::class, MockCommandTwo::class]);
        $commands = $this->commandBus->getCommands();

        $this->assertInstanceOf(MockCommand::class, $commands['mycommand']);
        $this->assertInstanceOf(MockCommandTwo::class, $commands['mycommand2']);
    }

    /** @test */
    public function it_removes_a_command()
    {
        $this->commandBus->addCommands([MockCommand::class, MockCommandTwo::class]);
        $this->commandBus->removeCommand('mycommand');

        $commands = $this->commandBus->getCommands();

        $this->assertCount(1, $commands);
        $this->assertArrayNotHasKey('mycommand', $commands);
        $this->assertInstanceOf(MockCommandTwo::class, $commands['mycommand2']);
    }

    /** @test */
    public function it_removes_multiple_commands()
    {
        $this->commandBus->addCommands([MockCommand::class, MockCommandTwo::class]);
        $this->commandBus->removeCommands(['mycommand', 'mycommand2']);

        $commands = $this->commandBus->getCommands();

        $this->assertCount(0, $commands);
        $this->assertArrayNotHasKey('mycommand', $commands);
        $this->assertArrayNotHasKey('mycommand2', $commands);
    }

    /**
     * @test
     */
    public function it_checks_a_supplied_command_object_is_of_the_correct_type()
    {
        $this->expectException(TelegramSDKException::class);

        $this->commandBus->addCommand(new \stdClass());
    }

    /**
     * @test
     */
    public function it_throws_exception_if_supplied_command_class_does_not_exist()
    {
        $this->expectException(TelegramSDKException::class);
        $this->commandBus->addCommand('nonexistclass');
    }


    /**
     * @test
     */
    public function it_throws_exception_if_message_is_only_blank_text()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->commandBus->parseCommand('');
    }

    /** @test */
    public function it_parses_the_commandText_correctly()
    {
        $result = $this->commandBus->parseCommand('/userCommand@botname arg1 arg2');

        $this->assertEquals('userCommand', $result[1]);
        $this->assertEquals('botname', $result[2]);
        $this->assertEquals('arg1 arg2', $result[3]);
    }

    /** @test */
    public function it_parses_the_commandText_correctly_2()
    {
        $result = $this->commandBus->parseCommand('/userCommand arg1 arg2');

        $this->assertEquals('userCommand', $result[1]);
        $this->assertEmpty($result[2]);
        $this->assertEquals('arg1 arg2', $result[3]);
    }

    /** @test */
    public function it_parses_the_commandText_correctly_3()
    {
        $result = $this->commandBus->parseCommand('sometext first /userCommand arg1 arg2');

        $this->assertEmpty($result);
    }

    /** @test */
    public function it_returns_the_result_from_the_handle_method_on_the_command()
    {
        $command = new MockCommand();
        $this->commandBus->addCommand($command);

        $res = $this->commandBus->execute('mycommand', '', Mocker::createUpdateResponse());

        $this->assertEquals('mycommand handled', $res);
    }

    /** @test */
    public function it_handles_a_command_and_returns_the_update_object_correctly()
    {
        $command = Mocker::createMockCommand('mycommand');
        $this->commandBus->addCommand($command->reveal());

        $result = $this->commandBus->handler('/mycommand', Mocker::createUpdateObject()->reveal());

        $this->assertInstanceOf(Update::class, $result);
    }
}
