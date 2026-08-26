<?php

/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Erikwang2013\IndustrialProtocols\EtherNetIP\Tests\Unit;

use Erikwang2013\IndustrialProtocols\EtherNetIP\EtherNetIPConnector;
use Erikwang2013\IndustrialProtocols\EtherNetIP\EtherNetIPProtocol;
use PHPUnit\Framework\TestCase;

class EtherNetIPProtocolTest extends TestCase
{
    public function testMetadata(): void
    {
        $p = new EtherNetIPProtocol();

        $this->assertSame('ethernet-ip', $p->getName());
        $this->assertSame('1.0.0', $p->getVersion());
        $this->assertSame(['tcp'], $p->getSupportedVariants());
        $this->assertSame(44818, $p->getDefaultPort());
    }

    public function testCreateConnector(): void
    {
        $connector = (new EtherNetIPProtocol())->createConnector([
            'host' => '192.168.1.20', 'port' => 44818, 'timeout' => 3000,
        ]);

        $this->assertInstanceOf(EtherNetIPConnector::class, $connector);
    }

    public function testCreateConnectorAppliesDefaults(): void
    {
        $connector = (new EtherNetIPProtocol())->createConnector([]);

        $this->assertInstanceOf(EtherNetIPConnector::class, $connector);
        $this->assertFalse($connector->isConnected());
    }
}
