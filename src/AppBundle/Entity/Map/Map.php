<?php

namespace AppBundle\Entity\Map;

use Doctrine\ORM\Mapping as ORM;

/**
 * Map
 *
 * @ORM\Table(name="map")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Map\MapRepository")
 */
class Map
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="width", type="integer")
     */
    private $width;

    /**
     * @var int
     *
     * @ORM\Column(name="height", type="integer")
     */
    private $height;

    /**
     * @var string
     *
     * @ORM\Column(name="tiles", type="text")
     */
    private $tiles;


    public function __construct($tiles = null)
    {
        $this->setTiles($tiles);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Map
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set width
     *
     * @param integer $width
     *
     * @return Map
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     *
     * @return Map
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set tiles
     *
     * @param string $tiles
     *
     * @return Map
     */
    public function setTiles($tiles, $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-')
    {
        list($width, $tiles) = explode('/', $tiles . '/');
        if (empty($tiles)) {
            $tiles = $width;
            $width = null;
        }
        $tiles = explode(';', $tiles);
        $tilesCount = count($tiles);
        if (!is_null($width)) {
            $this->setWidth($width = intval($width));
            $this->setHeight($height = ceil($tilesCount / $width));
            $shortSide = min($width, $height);
            $orderedTiles = [];
            for ($y = 0; $y < $width; $y++) {
                for ($x = 0; $x < min($shortSide, $y + 1); $x++) {
                    $orderedTiles[] = $tiles[$width * ($x + 1) + $x - $y - 1];
                }
            }
            for ($y = 0; $y < $height - 1; $y++) {
                for ($x = 0; $x < min($shortSide, $height - $y - 1); $x++) {
                    $orderedTiles[] = $tiles[$width * ($y + $x + 1) + $x];
                }
            }
            $tiles = $orderedTiles;
        }
        $rotates = ['', 'v', 'h', 'r'];
        $biggerId = 0;
        foreach ($tiles as &$tile) {
            $rotate = strtolower(substr($tile, 0, 1));
            if (intval($rotate) === 0) {
                $tile = substr($tile, 1);
            } else {
                $rotate = '';
            }
            $id = intval($tile);
            if ($id > $biggerId) {
                $biggerId = $id;
            }
            $tile = array(
                'id' => $id,
                'rotate' => $rotate,
            );
        }
        $output = '';
        $buffer = 0;
        $bufferLength = 0;
        $alphabetBits = floor(log(strlen($alphabet), 2));
        $recordBits = function ($bits, $length) use (&$buffer, &$output, &$bufferLength, $alphabet, $alphabetBits) {
            $buffer |= ($bits << $bufferLength);
            $bufferLength += $length;
            while ($bufferLength >= $alphabetBits) {
                $output .= substr($alphabet, $buffer & ((1 << $alphabetBits) - 1), 1);
                $buffer >>= $alphabetBits;
                $bufferLength -= $alphabetBits;
            }
        };
        $idBits = ceil(log($biggerId, 2));
        $rotateBits = ceil(log(count($rotates), 2));
        $recordBits(0, 2);
        $recordBits($rotateBits, 6);
        $recordBits($idBits, 8);
        foreach ($tiles as &$tile) {
            $recordBits(array_search(strtolower($tile['rotate']), $rotates), $rotateBits);
            $recordBits($tile['id'], $idBits);
        }
        if ($bufferLength) {
            $recordBits(0, $alphabetBits - $bufferLength);
        }
        $this->tiles = $output;

        return $this;
    }

    /**
     * Get tiles
     *
     * @return string
     */
    public function getTiles()
    {
        return $this->tiles;
    }
}

