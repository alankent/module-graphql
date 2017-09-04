<?php
/**
 * Created by PhpStorm.
 * User: akent
 * Date: 9/4/2017
 * Time: 4:25 PM
 */

namespace AlanKent\GraphQL\Types;


class StatusValue
{
    /** @var bool */
    private $success;

    /** @var string */
    private $message;

    public function __construct(bool $success, string $message) {
        $this->success = $success;
        $this->message = $message;
    }

    public function getSuccess():bool {
        return $this->success;
    }

    public function getMessage(): string {
        return $this->message;
    }
}
