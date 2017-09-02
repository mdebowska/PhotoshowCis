<?php
/**
 * Unique Login constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueLogin.
 *
 * @package Validator\Constraints
 */
class UniqueLogin extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = '"{{ user }}" is not unique Login.';

    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $elementId = null;

    /**
     * User repository.
     *
     * @var null|\Repository\UserRepository $repository
     */
    public $repository = null;
}