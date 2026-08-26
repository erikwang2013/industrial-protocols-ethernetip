<?php

/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Erikwang2013\IndustrialProtocols\EtherNetIP\Tests\Unit;

use Erikwang2013\IndustrialProtocols\EtherNetIP\Frame\EtherNetIPFrame;
use PHPUnit\Framework\TestCase;

class EtherNetIPFrameTest extends TestCase
{
    public function testRegisterSession(): void
    {
        $frame = EtherNetIPFrame::registerSession();
        $bytes = $frame->toBytes();
        $this->assertGreaterThanOrEqual(24, strlen($bytes));
        $cmd = unpack('v', substr($bytes, 0, 2))[1];
        $this->assertSame(0x0065, $cmd);
    }

    public function testReadTag(): void
    {
        $frame = EtherNetIPFrame::readTag(1, 'MyTag');
        $bytes = $frame->toBytes();
        $cmd = unpack('v', substr($bytes, 0, 2))[1];
        $this->assertSame(0x0070, $cmd);
    }

    public function testFrameRoundTrip(): void
    {
        $original = EtherNetIPFrame::registerSession();
        $parsed = EtherNetIPFrame::fromBytes($original->toBytes());
        $this->assertIsArray($parsed->getData());
    }

    public function testRegisterSessionExactBytes(): void
    {
        $bytes = EtherNetIPFrame::registerSession()->toBytes();

        // Command 0x0065, length 32 (24-byte header + 8-byte payload)
        $this->assertSame(0x0065, unpack('v', substr($bytes, 0, 2))[1]);
        $this->assertSame(32, unpack('v', substr($bytes, 2, 2))[1]);
        $this->assertSame(0, unpack('V', substr($bytes, 4, 4))[1]); // session 0
        // Payload starts at byte 24 (status/options), data at byte 28
        $this->assertSame(1, ord($bytes[28]));
        $this->assertSame(0, ord($bytes[29]));
    }

    public function testUnregisterSession(): void
    {
        $bytes = EtherNetIPFrame::unregisterSession(0x1234)->toBytes();

        $this->assertSame(0x0066, unpack('v', substr($bytes, 0, 2))[1]);
        $this->assertSame(0x1234, unpack('V', substr($bytes, 4, 4))[1]);
    }

    public function testReadTagCarriesSessionHandleAndCipData(): void
    {
        $bytes = EtherNetIPFrame::readTag(0x1234, 'Tag1')->toBytes();

        $this->assertSame(0x0070, unpack('v', substr($bytes, 0, 2))[1]);
        $this->assertSame(0x1234, unpack('V', substr($bytes, 4, 4))[1]);
        // CIP Read Tag service code 0x4C at data offset 0 (byte 28)
        $this->assertSame(0x4C, ord($bytes[28]));
        $this->assertSame('Tag1', substr($bytes, 28 + 7, 4));
    }

    public function testFromBytesRejectsShortFrame(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('frame too short');
        EtherNetIPFrame::fromBytes(str_repeat("\0", 23));
    }

    public function testFromBytesRoundTripPreservesCommandAndSession(): void
    {
        $original = EtherNetIPFrame::readTag(7, 'MyTag');
        $parsed = EtherNetIPFrame::fromBytes($original->toBytes());

        $data = $parsed->getData();
        $this->assertSame(0x0070, $data['command']);
        $this->assertSame(7, $data['session_handle']);
        $this->assertArrayHasKey('payload', $data);
    }

    public function testGetDataOmitsPayloadKeyWhenEmpty(): void
    {
        // Unregister has no payload data
        $data = EtherNetIPFrame::unregisterSession(1)->getData();
        $this->assertArrayNotHasKey('payload', $data);
        $this->assertSame(1, $data['session_handle']);
    }
}
