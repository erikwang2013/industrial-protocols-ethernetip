<?php

/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Erikwang2013\IndustrialProtocols\EtherNetIP\Tests\Unit;

use Erikwang2013\IndustrialProtocols\Connection\ConnectionState;
use Erikwang2013\IndustrialProtocols\EtherNetIP\EtherNetIPConnector;
use PHPUnit\Framework\TestCase;

/**
 * Connector behavior that needs no network.
 */
class EtherNetIPConnectorTest extends TestCase
{
    public function testHealthClosedBeforeConnect(): void
    {
        $connector = new EtherNetIPConnector(['host' => '127.0.0.1', 'port' => 44818]);

        $health = $connector->getHealth();
        $this->assertSame(ConnectionState::CLOSED, $health->state);
        $this->assertFalse($connector->isConnected());
    }

    public function testReadBeforeConnectThrows(): void
    {
        $connector = new EtherNetIPConnector(['host' => '127.0.0.1', 'port' => 44818]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not connected');
        $connector->read('MyTag');
    }

    public function testWriteReturnsEmptyResult(): void
    {
        $connector = new EtherNetIPConnector(['host' => '127.0.0.1', 'port' => 44818]);

        $this->assertSame([], $connector->write('MyTag', [1]));
    }

    public function testConnectRefusedThrows(): void
    {
        // Bind an ephemeral port, release it, then connect — ECONNREFUSED
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $name = stream_socket_get_name($server, false);
        $port = (int) substr($name, strrpos($name, ':') + 1);
        fclose($server);

        $connector = new EtherNetIPConnector([
            'host' => '127.0.0.1', 'port' => $port, 'timeout' => 10000,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('EIP connect failed');
        $connector->connect();
    }
}
