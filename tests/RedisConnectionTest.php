<?php

namespace yiiunit\extensions\redis;

/**
 * @group redis
 */
class ConnectionTest extends TestCase
{
    /**
     * test connection to redis and selection of db
     */
    public function testConnect()
    {
        $db = $this->getConnection(false);
        $database = $db->database;
        $db->open();
        $this->assertEquals('+PONG', $db->ping());
        $db->set('YIITESTKEY', 'YIITESTVALUE');
        $db->close();

        $db = $this->getConnection(false);
        $db->database = $database;
        $db->open();
        $this->assertEquals('YIITESTVALUE', $db->get('YIITESTKEY'));
        $db->close();

        $db = $this->getConnection(false);
        $db->database = 1;
        $db->open();
        $this->assertFalse($db->get('YIITESTKEY'));
        $db->close();
    }

    public function keyValueData()
    {
        return [
            [123],
            [-123],
            [0],
            ['test'],
            ["test\r\ntest"],
            [''],
        ];
    }

    /**
     * @dataProvider keyValueData
     */
    public function testStoreGet($data)
    {
        $db = $this->getConnection(true);

        $db->set('hi', $data);
        $this->assertEquals($data, $db->get('hi'));
    }

    /**
     * https://github.com/yiisoft/yii2/issues/4745
     */
    public function testReturnType()
    {
        $redis = $this->getConnection();
        $redis->executeCommand('SET',['key1','val1']);
        $redis->executeCommand('HMSET', ['hash1', ['hk3' => 'hv3', 'hk4' => 'hv4']]);
        $redis->executeCommand('RPUSH',['newlist2','tgtgt','tgtt','44',11]);
        $redis->executeCommand('SADD',['newset2','segtggttval','sv1','sv2','sv3']);
        $redis->executeCommand('ZADD',['newz2',2,'ss',3,'pfpf']);
        $allKeys = $redis->executeCommand('KEYS',['*']);
        sort($allKeys);
        $this->assertEquals(['hash1', 'key1', 'newlist2', 'newset2', 'newz2'], $allKeys);
        $expected = [
            'hash1' => \Redis::REDIS_HASH,
            'key1' => \Redis::REDIS_STRING,
            'newlist2' => \Redis::REDIS_LIST,
            'newset2' => \Redis::REDIS_SET,
            'newz2' => \Redis::REDIS_ZSET,
        ];
        foreach($allKeys as $key) {
            $this->assertEquals($expected[$key], $redis->executeCommand('TYPE',[$key]));
        }
    }
}
