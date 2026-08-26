<?php

/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Erikwang2013\IndustrialProtocols\EtherNetIP\Tests\Simulation;

use Erikwang2013\IndustrialProtocols\Connection\ConnectionState;
use Erikwang2013\IndustrialProtocols\EtherNetIP\EtherNetIPConnector;
use PHPUnit\Framework\TestCase;

/**
 * Full connector lifecycle against a fake EtherNet/IP server: session
 * registration, tag read and session unregistration.
 */
class EtherNetIPConnectorSimulationTest extends TestCase
{
    public function testConnectorLifecycle(): void
    {
        $proc = proc_open([PHP_BINARY, '-r', <<<'STUB'
            $server = stream_socket_server('tcp://127.0.0.1:15100');
            echo "READY\n";
            flush();
            $client = @stream_socket_accept($server, 5);
            $session = 0;
            while ($client && !feof($client)) {
                $header = fread($client, 24);
                if (strlen($header) < 24) break;
                $cmd = unpack('v', substr($header, 0, 2))[1];
                $len = unpack('v', substr($header, 2, 2))[1];
                if ($len > 24) fread($client, $len - 24);
                if ($cmd === 0x0065) $session = 12345; // register session
                $replySession = ($cmd === 0x0065) ? $session : unpack('V', substr($header, 4, 4))[1];
                fwrite($client, pack('v', $cmd) . pack('v', 28) . pack('V', $replySession)
                    . pack('V', 0) . pack('Q', 0) . pack('V', 0) . pack('V', 0));
                usleep(100000); // hold open briefly; immediate close races the parent read
            }
            if ($client) fclose($client);
            fclose($server);
STUB, ], [1 => ['pipe', 'w']], $pipes);

        fgets($pipes[1]);

        $connector = new EtherNetIPConnector([
            'host' => '127.0.0.1', 'port' => 15100, 'timeout' => 10000,
        ]);
        $connector->connect();
        $this->assertTrue($connector->isConnected());

        $health = $connector->getHealth();
        $this->assertSame(ConnectionState::HEALTHY, $health->state);

        // Tag read echoes back the session handle assigned at registration
        $result = $connector->read('MyTag');
        $this->assertSame(12345, $result['MyTag']['session_handle']);
        $this->assertSame(0x0070, $result['MyTag']['command']);

        // Read with multiple addresses issues one request per address
        $result = $connector->read(['A', 'B']);
        $this->assertSame(12345, $result['A']['session_handle']);
        $this->assertSame(12345, $result['B']['session_handle']);

        $connector->disconnect();
        $this->assertFalse($connector->isConnected());

        proc_close($proc);
    }
}
