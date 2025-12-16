<?php

declare(strict_types=1);

namespace Laminas\I18n\Validator;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final readonly class ConfigProvider
{
    /**
     * @return array{
     *     validators: ServiceManagerConfiguration,
     * }
     */
    public function __invoke(): array
    {
        return [
            'validators' => [
                'factories' => [
                    Alnum::class       => Factory\AlnumFactory::class,
                    Alpha::class       => Factory\AlphaFactory::class,
                    CountryCode::class => InvokableFactory::class,
                    DateTime::class    => Factory\DateTimeFactory::class,
                    IsFloat::class     => Factory\IsFloatFactory::class,
                    IsInt::class       => Factory\IsIntFactory::class,
                    PostCode::class    => Factory\PostCodeFactory::class,
                ],
                'aliases'   => [
                    'alnum'       => Alnum::class,
                    'Alnum'       => Alnum::class,
                    'alpha'       => Alpha::class,
                    'Alpha'       => Alpha::class,
                    'countryCode' => CountryCode::class,
                    'CountryCode' => CountryCode::class,
                    'datetime'    => DateTime::class,
                    'dateTime'    => DateTime::class,
                    'DateTime'    => DateTime::class,
                    'float'       => IsFloat::class,
                    'Float'       => IsFloat::class,
                    'int'         => IsInt::class,
                    'Int'         => IsInt::class,
                    'isfloat'     => IsFloat::class,
                    'isFloat'     => IsFloat::class,
                    'IsFloat'     => IsFloat::class,
                    'isint'       => IsInt::class,
                    'isInt'       => IsInt::class,
                    'IsInt'       => IsInt::class,
                    'postcode'    => PostCode::class,
                    'postCode'    => PostCode::class,
                    'PostCode'    => PostCode::class,
                ],
            ],
        ];
    }
}
