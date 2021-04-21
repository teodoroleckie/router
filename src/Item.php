<?php

namespace Tleckie\Router;

use Closure;

/**
 * Class Item
 *
 * @package Tleckie\Router
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class Item
{
    /** @var Closure|callable */
    private $closure;

    /** @var array */
    private array $params;

    /**
     * Item constructor.
     *
     * @param Closure|callable $callable
     * @param array            $params
     */
    public function __construct(Closure|callable $callable, array $params)
    {
        $this->closure = $callable;
        $this->params = $this->clearParams($params);
    }

    /**
     * @param array $params
     * @return array
     */
    private function clearParams(array $params): array
    {
        $returnParams = [];
        foreach ($params as $paramKey => $paramValue) {
            if (is_numeric($paramKey)) {
                $returnParams[$paramKey] = $paramValue;
            }
        }

        return $returnParams;
    }

    /**
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * @return Closure|callable
     */
    public function callable(): Closure|callable
    {
        return $this->closure;
    }

    /**
     * @return mixed
     */
    public function call(): mixed
    {
        return call_user_func_array($this->closure, $this->params);
    }
}