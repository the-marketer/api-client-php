<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\Common;

use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\Validator\Validation;
use TheMarketer\ApiClient\Exception\ValidationException;

abstract class Data
{
    public static function validateAndCreate(array $data): static
    {
        $params = array_map(
            fn(ReflectionParameter $p) => $p->getName(),
            (new ReflectionMethod(static::class, '__construct'))->getParameters(),
        );
        try {
            $instance = new static(...array_intersect_key($data, array_flip($params)));
        } catch (\TypeError $e) {
            throw new ValidationException(self::readableTypeError($e->getMessage()), 400, $e);
        }
        $instance->validate();

        return $instance;
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