<?php
namespace Pilight;

/**
 * Class AbstractCommand
 * @package Pilight
 */
abstract class AbstractCommand extends AbstractClient
{
    protected $action = '';

    /**
     * @param $callback
     * @param $message
     * @return mixed
     */
    protected function callback($callback, $message)
    {
        return call_user_func($callback ? $callback : [$this, 'evaluate'], $message);
    }

    /**
     * @param array $message
     * @return array
     */
    protected function evaluate(array $message)
    {
        return $message;
    }

    /**
     * @param array $command
     * @param null $callback
     * @return bool|mixed
     */
    public function execute(array $command, $callback = null)
    {
        $query = json_encode(array_merge(['action' => $this->action], $command, ['media' => 'all']));

        if (false === fwrite($this->getSocket(), $query, 1024)) {
            return false;
        }

        $buffer = '';
        $response = ['status' => 'failed'];
        $callCount = 0;

        do {
            $buffer .= fgets($this->getSocket(), 1024);
            $length = strlen($buffer);

            if ($length > 2 && 10 === ord($buffer[$length - 1]) && 10 === ord($buffer[$length - 2])) {
                $response = json_decode(trim(substr($buffer, 0, -2)), true);
                break;
            }
        } while (!feof($this->getSocket()) || $callCount++ < 100);

        if ($response === ['status' => 'failed']) {
            return false;
        }

        return $this->callback($callback, $response);
    }
}