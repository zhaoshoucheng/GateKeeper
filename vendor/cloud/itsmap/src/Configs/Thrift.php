<?php

namespace Didi\Cloud\ItsMap\Configs;

class Thrift
{
    // 用来保存已经尝试过的addressIndex
    private static $retryAddressIndex = [];

    private static $configs = [
        ENV::DEVELOPMENT => [
            'inhert' => [
                'host' => '10.94.105.126',
                'port' => "3306",
                'read_timeout' => 30,
                'write_timeout' => 30,
            ],
            'inhert_to' => [
                'address' => [
                    [
                        'host' => '100.90.165.26',
                        'port' => '50000',
                    ],
                    [
                        'host' => '100.90.204.12',
                        'port' => '50000',
                    ]
                ],
                'read_timeout' => 60,
                'write_timeout' => 60,
                'class' => '\DidiRoadNet\InheritServiceClient',
                'transport' => 'Thrift\Transport\TFramedTransport',
            ],
            'caculator' => [
                'host' => '10.93.94.36',
                'port' => "8383",
                'read_timeout' => 30,
                'write_timeout' => 30,
                'class' => '\StsData\CalculatorServiceClient',
                'transport' => 'Thrift\Transport\TBufferedTransport',
            ],
        ],
        ENV::DEBUG => [
            'inhert' => [
                'address' => [
                    [
                        'host' => '100.90.165.26',
                        'port' => '50005',
                    ],
                    [
                        'host' => '100.90.165.26',
                        'port' => '50000',
                    ]
                ],
                'read_timeout' => 30,
                'write_timeout' => 30,
                'class' => '\DidiRoadNet\InheritServiceClient',
                'transport' => 'Thrift\Transport\TFramedTransport',
            ],
            'inhert_to' => [
                'address' => [
                    [
                        'host' => '100.90.165.26',
                        'port' => '50100',
                    ],
                    [
                        'host' => '100.90.204.12',
                        'port' => '50100',
                    ]
                ],
                'read_timeout' => 60,
                'write_timeout' => 60,
                'class' => '\DidiRoadNet\InheritServiceClient',
                'transport' => 'Thrift\Transport\TFramedTransport',
            ],
            'shmdata' => [
                'host' => '100.90.165.26',
                'port' => "50001",
                'read_timeout' => 30,
                'write_timeout' => 30,
                'class' => '\DidiRoadNet\ShmDataServiceClient',
                'transport' => 'Thrift\Transport\TFramedTransport',
            ],
            'caculator' => [
                'host' => '100.90.164.31',
                'port' => "8383",
                'read_timeout' => 30,
                'write_timeout' => 30,
                'class' => '\StsData\CalculatorServiceClient',
                'transport' => 'Thrift\Transport\TBufferedTransport',
            ],
        ],
        ENV::ONLINE => [
            // TODO:
        ],
    ];

    /*
     * 获取某个db配置
     */
    public static function get($connection)
    {
        $config = self::$configs[ENV::$current][$connection];

        // 没有多个地址，只能获取单个
        if (empty($config['address'])) {
            return $config;
        }

        $addresses = $config['address'];

        $all = range(0, count($addresses) - 1);
        $used = [];
        if (!empty(self::$retryAddressIndex[$connection])) {
            $used = self::$retryAddressIndex[$connection];
        }
        $ok = array_values(array_diff($all, $used));
        if ($ok) {
            $rand = rand(0, count($ok) - 1);
            $index = $ok[$rand];
        } else {
            $index = $used[0];
        }

        $address = $addresses[$index];
        if (empty(self::$retryAddressIndex[$connection])) {
            self::$retryAddressIndex[$connection] = [$index];
        } else {
            self::$retryAddressIndex[$connection][] = $index;
        }

        return array_merge($address, $config);
    }
}