<?php

namespace Tleckie\Router\Test;


use PHPUnit\Framework\TestCase;
use Tleckie\Router\Item;

/**
 * Class ItemTest
 *
 * @package Tleckie\Router\Test
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class ItemTest extends TestCase
{
    /**
     * @test
     */
    public function closure(): void
    {

        $closure = static function ($id, $name) {
            return $id . $name;
        };

        foreach ([$closure, [new MyClass, 'action']] as $callable) {
            $item = new Item($closure, [0 => 25, 'id' => 25, 1 => 'Jhon', 'name' => 'Jhon']);

            static::assertEquals($closure, $item->callable());
            static::assertEquals([25, 'Jhon'], $item->params());
            static::assertEquals(sprintf('%s%s', 25, 'Jhon'), $item->call());
        }
    }
}

class MyClass
{
    public function action($id, $name)
    {
        return $id . $name;
    }
}