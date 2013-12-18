<?php

namespace Pitpit\Component\MongoFilesystem\Tests;

class MongoGridTestHelper
{
    static $fs;

    public static function getGridFS()
    {
        if (!self::$fs) {
            $server = isset($_SERVER['MONGO_SERVER']) ? $_SERVER['MONGO_SERVER'] : null;
            $name = isset($_SERVER['MONGO_DB']) ? $_SERVER['MONGO_DB'] : 'mongo-filesystem-test';
            $mongo = new \MongoClient($server);
            $db = new \MongoDB($mongo, $name);
            self::$fs = new \MongoGridFS($db, 'fs');
        }

        return self::$fs;
    }
}
