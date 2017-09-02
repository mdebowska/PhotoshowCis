<?php
/**
 * Unique Tag constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueTag.
 *
 * @package Validator\Constraints
 */
class UniqueTag extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = '{{ tag }} is not unique Tag.';

    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $elementId = null;

    /**
     * Tag repository.
     *
     * @var null|\Repository\TagRepository $repository
     */
    public $repository = null;
}