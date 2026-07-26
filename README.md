# EtherNet/IP 协议包 — 支持 ENIP 会话管理和 CIP Read Tag，端口 44818

> [English](README.en.md)

EtherNet/IP (Ethernet Industrial Protocol)，ENIP 会话注册 + CIP 标签读写，端口 44818。

## 安装

```bash
composer require erikwang2013/industrial-protocols-ethernetip
```

## 架构

EtherNetIPDriver（TCP）通过 ENIP RegisterSession 建立会话，CIP Read Tag Service 读取 PLC 标签数据。EtherNetIPFrame 实现 FrameInterface（ENIP header 24 字节）。

## 功能

ENIP 注册/注销会话、CIP Read Tag 服务、标签名寻址、批量标签读取

## 使用说明

```php
$conn = $kernel->getConnectionManager()->connect('eip-plc');
$result = $conn->read('MyTag');           // 单标签
$result = $conn->read(['Tag1','Tag2']);  // 批量
```

## 配置示例

```php
'devices' => [
    'eip-plc' => [
        'protocol' => 'ethernet-ip', 'variant' => 'tcp',
        'host' => '192.168.1.20', 'port' => 44818,
        'timeout' => 3000,
    ],
],
```

## 兼容框架

Laravel / Webman / Hyperf / ThinkPHP / Yii2 / Plain PHP

## 系统要求

- PHP >= 8.1
- Composer
- erikwang2013/industrial-protocols-kernel

## License

MIT — Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
