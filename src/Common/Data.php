<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\Common;

use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\Validator\Validation;
use TheMarketer\ApiClient\Exception\ValidationException;

abstract class Data
{
    /**
     * Builds the DTO from `$data` and validates it.
     *
     * A key that is not a constructor parameter throws a {@see ValidationException} naming it
     * (`unknown field(s): foo, bar`) instead of being dropped, so a field the platform accepts but the DTO
     * lacks shows up in the caller's tests rather than going missing on the wire.
     */
    public static function validateAndCreate(array $data): static
    {
        $instance = static::construct($data);
        $instance->validate();

        return $instance;
    }

    /**
     * `new static(...$data)` with the unknown-key check and readable type errors; no validation.
     * DTOs that override {@see validateAndCreate()} build through this so they share the same rules.
     *
     * @throws ValidationException
     */
    protected static function construct(array $data): static
    {
        $params = array_map(
            fn(ReflectionParameter $p) => $p->getName(),
            (new ReflectionMethod(static::class, '__construct'))->getParameters(),
        );
        $unknown = array_diff(array_map('strval', array_keys($data)), $params);
        if ($unknown !== []) {
            throw new ValidationException('unknown field(s): ' . implode(', ', $unknown));
        }

        try {
            return new static(...$data);
        } catch (\TypeError $e) {
            throw new ValidationException(self::readableTypeError($e->getMessage()), 400, $e);
        }
    }

    /** `X::__construct(): Argument #1 ($number) must be of type int, array given, called in …` → `number: must be of type int, array given.` */
    private static function readableTypeError(string $message): string
    {
        if (preg_match('/\(\$(\w+)\) not passed/', $message, $m)) {
            return $m[1] . ': This value is required.';
        }
        if (preg_match('/\(\$(\w+)\) must be of type ([^,]+), (\S+) given/', $message, $m)) {
            return $m[1] . ': must be of type ' . $m[2] . ', ' . $m[3] . ' given.';
        }

        return $message;
    }

    protected function validate(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($this);
        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }

            throw new ValidationException(implode(', ', $messages));
        }
    }

    public function toArray(): array
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value instanceof self) {
                $result[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$key] = array_map(
                    fn($item) => $item instanceof self ? $item->toArray() : $item,
                    $value
                );
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}