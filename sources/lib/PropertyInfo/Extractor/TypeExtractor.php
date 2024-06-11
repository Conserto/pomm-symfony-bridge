<?php

/*
 * This file is part of Pomm's SymfonyBidge package.
 *
 * (c) 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\SymfonyBridge\PropertyInfo\Extractor;

use PommProject\Foundation\Converter\ConverterClient;
use PommProject\Foundation\Converter\ConverterPooler;
use PommProject\Foundation\Exception\FoundationException;
use PommProject\Foundation\Pomm;
use PommProject\ModelManager\Exception\ModelException;
use PommProject\ModelManager\Session;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Extract data using pomm.
 *
 * @package PommSymfonyBridge
 * @copyright 2015 Grégoire HUBERT
 * @author Nicolas Joseph
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class TypeExtractor implements PropertyTypeExtractorInterface
{
    public function __construct(private readonly Pomm $pomm)
    {
    }

    /**
     * !! For symfony 7.1 support !!
     * @throws FoundationException|ModelException
     * @see PropertyTypeExtractorInterface
     */
    public function getType(string $class, string $property, array $context = array()): ?Type
    {
        return $this->doGetType($class, $property, $context);
    }

    /**
     * @throws FoundationException|ModelException
     * @see PropertyTypeExtractorInterface
     */
    public function getTypes(string $class, string $property, array $context = array()): array
    {
        return array_filter([
            $this->doGetType($class, $property, $context)
        ]);
    }

    /**
     * @throws ModelException
     * @throws FoundationException
     */
    private function doGetType(string $class, string $property, array $context = array()): ?Type
    {
        if (isset($context['session:name'])) {
            /** @var Session $session */
            $session = $this->pomm->getSession($context['session:name']);
        } else {
            /** @var Session $session */
            $session = $this->pomm->getDefaultSession();
        }

        $modelName = $context['model:name'] ?? "{$class}Model";

        if (!class_exists($modelName)) {
            return null;
        }

        $sqlType = $this->getSqlType($session, $modelName, $property);
        $pommType = $this->getPommType($session, $sqlType);

        return $this->createPropertyType($pommType);
    }

    /**
     * Get the sql type of $property
     *
     * @throws FoundationException
     * @throws ModelException
     */
    private function getSqlType(Session $session, string $modelName, string $property): string
    {
        $model = $session->getModel($modelName);
        $structure = $model->getStructure();

        return $structure->getTypeFor($property);
    }

    /**
     * Get the corresponding php type of a $sql_type type
     *
     * @throws FoundationException
     */
    private function getPommType(Session $session, string $sql_type): string
    {
        /** @var ConverterPooler $converterPooler */
        $converterPooler =  $session->getPoolerForType('converter');

        $pommTypes = $converterPooler->getConverterHolder()->getTypesWithConverterName();

        if (!isset($pommTypes[$sql_type])) {
            throw new \RuntimeException("Invalid $sql_type");
        }

        return $pommTypes[$sql_type];
    }

    /** Create a new Type for the $pommType type */
    private function createPropertyType(string $pommType): Type
    {
        $class = null;

        $type = match ($pommType) {
            'JSON', 'Array' => Type::BUILTIN_TYPE_ARRAY,
            'Binary', 'String' => Type::BUILTIN_TYPE_STRING,
            'Boolean' => Type::BUILTIN_TYPE_BOOL,
            'Number' => Type::BUILTIN_TYPE_INT,
            default => Type::BUILTIN_TYPE_OBJECT,
        };

        return new Type($type, false, $class);
    }
}
