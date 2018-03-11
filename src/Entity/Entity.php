<?php

namespace HotNano\Entity;

use Carbon\Carbon;

class Entity
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $image;

    /**
     * Owner Address
     *
     * @var string|null
     */
    private $ownerAddress;

    /**
     * @var Carbon|null
     */
    private $ownedSince;

    /**
     * Amount of Nanos payed by the current Owner.
     *
     * @var float
     */
    private $currentPrice;

    /**
     * Amount of Nanos to pay to become the new Owner.
     *
     * @var float
     */
    private $targetPrice;

    /**
     * @var Carbon|null
     */
    private $updatedAt;

    public function __construct()
    {
        $this->currentPrice = 0.0;
        $this->targetPrice = 0.01; // Start price.
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @param null|string $image
     */
    public function setImage(?string $image)
    {
        $this->image = $image;
    }

    /**
     * @return null|string
     */
    public function getOwnerAddress(): ?string
    {
        return $this->ownerAddress;
    }

    /**
     * @param null|string $ownerAddress
     */
    public function setOwnerAddress(?string $ownerAddress)
    {
        $this->ownerAddress = $ownerAddress;
    }

    /**
     * @return Carbon|null
     */
    public function getOwnedSince(): ?Carbon
    {
        return $this->ownedSince;
    }

    /**
     * @param Carbon|null $ownedSince
     */
    public function setOwnedSince(?Carbon $ownedSince)
    {
        $this->ownedSince = $ownedSince;
    }

    /**
     * @return float
     */
    public function getCurrentPrice(): float
    {
        return $this->currentPrice;
    }

    /**
     * @param float $currentPrice
     */
    public function setCurrentPrice(float $currentPrice): void
    {
        $this->currentPrice = $currentPrice;
    }

    /**
     * @return float
     */
    public function getTargetPrice(): float
    {
        return $this->targetPrice;
    }

    /**
     * @param float $targetPrice
     */
    public function setTargetPrice(float $targetPrice): void
    {
        $this->targetPrice = $targetPrice;
    }

    /**
     * @return Carbon|null
     */
    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    /**
     * @param Carbon|null $updatedAt
     */
    public function setUpdatedAt(?Carbon $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function toArray(): array
    {
        if ($this->getOwnedSince()) {
            $ownerSince = $this->getOwnedSince()->format('c');
        } else {
            $ownerSince = null;
        }

        if ($this->getUpdatedAt()) {
            $updatedAt = $this->getUpdatedAt()->format('c');
        } else {
            $updatedAt = null;
        }

        return [
            'name' => $this->getName(),
            'image' => $this->getImage(),

            'owner_address' => $this->getOwnerAddress(),
            'owner_since' => $ownerSince,

            'current_price' => $this->getCurrentPrice(),
            'target_price' => $this->getTargetPrice(),

            'updated_at' => $updatedAt,
        ];
    }

    public static function loadFromArray(array $a): Entity
    {
        $e = new Entity();

        $mappings = [
            [
                'name' => 'name',
                'setter' => 'setName',
            ],
            [
                'name' => 'image',
                'setter' => 'setImage',
            ],

            [
                'name' => 'owner_address',
                'setter' => 'setOwnerAddress',
            ],
            [
                'name' => 'owner_since',
                'converter_fn' => function ($val) {
                    return Carbon::parse($val);
                },
                'setter' => 'setOwnerSince',
            ],

            [
                'name' => 'current_price',
                'converter_fn' => function ($val) {
                    return floatval($val);
                },
                'setter' => 'setCurrentPrice',
            ],
            [
                'name' => 'target_price',
                'converter_fn' => function ($val) {
                    return floatval($val);
                },
                'setter' => 'setTargetPrice',
            ],

            [
                'name' => 'updated_at',
                'converter_fn' => function ($val) {
                    return Carbon::parse($val);
                },
                'setter' => 'setUpdatedAt',
            ],
        ];

        $reflectionClass = new \ReflectionClass($e);

        foreach ($mappings as $mapping) {
            if (!array_key_exists($mapping['name'], $a) || !$a[$mapping['name']]) {
                continue;
            }

            if (array_key_exists('converter_fn', $mapping)) {
                $converterFn = $mapping['converter_fn'];
                $val = $converterFn($a[$mapping['name']]);
            } else {
                $val = $a[$mapping['name']];
            }

            $reflectionMethod = $reflectionClass->getMethod($mapping['setter']);
            $reflectionMethod->invoke($e, $val);
        }

        return $e;
    }
}
