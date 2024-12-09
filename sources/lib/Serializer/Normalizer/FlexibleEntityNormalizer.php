<?php

/*
 * This file is part of Pomm's SymfonyBidge package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\SymfonyBridge\Serializer\Normalizer;

use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FlexibleEntityNormalizer implements NormalizerInterface
{
    /** {@inheritdoc} */
    #[\Override]
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return $object->extract();
    }

    /** {@inheritdoc} */
    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FlexibleEntityInterface;
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [
            FlexibleEntityInterface::class => true,
        ];
    }
}
