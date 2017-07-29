<?php

namespace TheBattleForHill218\Commands;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Functional as F;

class CompileImagesCommand extends Command
{
    const CARD_WIDTH = 80;
    const CARD_HEIGHT = 112;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('compile-images')
            ->setDescription('Compiles images into tilesheet');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->imageManager = new ImageManager(['driver' => 'gd']);
        $pathRows = $this->createTypePathRows();
        // TODO: Add merge highlights, etc
        $imageRows = $this->createImageRows($pathRows);
        $image = $this->combineImages($imageRows);
        $image->save(__DIR__ . '/../../../build/cards.png');

        $output->writeln("Done");
    }

    /**
     * @param array $imageRows
     * @return Image
     */
    private function combineImages(array $imageRows)
    {
        $rowImages = F\map(
            $imageRows,
            function (array $imageRow) {
                return $this->combineImageRow($imageRow);
            }
        );
        return F\reduce_left(
            $rowImages,
            function (Image $image, $i, array $rowImages, Image $reduction) {
                return $reduction->insert($image, 'top-left', 0, $i * self::CARD_HEIGHT);
            },
            $this->createCanvas($imageRows)
        );
    }

    /**
     * @param array $row
     * @return Image
     */
    private function combineImageRow(array $row)
    {
        return F\reduce_left(
            $row,
            function (Image $image, $i, array $row, Image $reduction) {
                return $reduction->insert($image, 'top-left', $i * self::CARD_WIDTH);
            },
            $this->createCanvas([$row])
        );
    }

    /**
     * @param array $imageRows
     * @return Image
     */
    private function createCanvas(array $imageRows)
    {
        $totalWidth = self::CARD_WIDTH * F\maximum(
            F\map(
                $imageRows,
                function (array $imageRow) {
                        return count($imageRow);
                }
            )
        );
        $totalHeight = self::CARD_HEIGHT * count($imageRows);
        return $this->imageManager->canvas($totalWidth, $totalHeight);
    }

    /**
     * @return array
     */
    private function createTypePathRows()
    {
        $cardsDirpath = __DIR__ . '/../../../resources/cards';
        $colors = ['Blue', 'Green'];
        $types = ['Artillery', 'Heavy_Weapons', 'Infantry', 'Paratroopers', 'Special_Forces', 'Tank', 'Air_Strike'];
        return F\map(
            $colors,
            function ($color) use ($types, $cardsDirpath) {
                return F\map(
                    $types,
                    function ($type) use ($color, $cardsDirpath) {
                        return "{$cardsDirpath}/{$type}_{$color}.jpg";
                    }
                );
            }
        );
    }

    /**
     * @param array $pathRows
     * @return array
     */
    private function createImageRows(array $pathRows)
    {
        return F\map(
            $pathRows,
            function (array $row) {
                return F\map(
                    $row,
                    function ($path) {
                        return $this->imageManager->make($path)
                            ->resize(self::CARD_WIDTH, self::CARD_HEIGHT);
                    }
                );
            }
        );
    }
}
