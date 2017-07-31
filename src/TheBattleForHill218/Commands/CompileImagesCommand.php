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
    const CARDS_DIRPATH = 'resources/cards';
    const OUTPUT_RELATIVE_PATHNAME = 'img/cards.png';

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
        $tileRows = F\reduce_left(
            [
                new TileSpec(self::CARDS_DIRPATH . '/' . 'Hill.jpg', 'hill'),
                new TileSpec(self::CARDS_DIRPATH . '/' . 'Back.png', 'back'),
                new TileSpec(self::CARDS_DIRPATH . '/' . 'Red Solid.png', 'red-solid'),
                new TileSpec(self::CARDS_DIRPATH . '/' . 'Red Dashed.png', 'red-dashed'),
                new TileSpec(self::CARDS_DIRPATH . '/' . 'White Dashed.png', 'white-dashed')
            ],
            function (TileSpec $tile, $i, array $rowImages, array $reduction) {
                $numRows = count($reduction);
                $reduction[$i % $numRows][] = $tile;
                return $reduction;
            },
            $this->createTypePathRows()
        );
        $imageRows = $this->createImageRows($tileRows);
        $image = $this->combineImages($imageRows);
        $fullImage = $this->appendIcons($image, $imageRows);
        $fullImage->save(__DIR__ . '/../../../' . self::OUTPUT_RELATIVE_PATHNAME);
        $cssContent = $this->createCss($tileRows);

        $output->writeln($cssContent);
        $output->writeln(sprintf('Done: %s', self::OUTPUT_RELATIVE_PATHNAME));
    }

    /**
     * @param array $tileRows
     * @return string
     */
    private function createCss(array $tileRows)
    {
        return join(
            '',
            F\map(
                $tileRows,
                function (array $tileRow, $row) {
                    return join(
                        "\n",
                        F\map(
                            $tileRow,
                            function (TileSpec $tile, $col) use ($row) {
                                return sprintf(
                                    <<<CSS
.card.%s {
    background-position: %dpx %dpx;
}
CSS
                                    ,
                                    $tile->getCssName(),
                                    -1 * $col * self::CARD_WIDTH,
                                    -1 * $row * self::CARD_HEIGHT
                                );
                            }
                        )
                    );
                }
            )
        );
    }

    /**
     * @param Image $image
     * @return Image
     */
    private function appendIcons(Image $image)
    {
        $iconsImage = $this->imageManager->make(self::CARDS_DIRPATH . '/Deck Icon.png');
        $combined = $this->imageManager->canvas(
            max($image->getWidth(), $iconsImage->getWidth()),
            $image->getHeight() + $iconsImage->getHeight()
        );
        return $combined->insert($image, 'top-left', 0, 0)
            ->insert($iconsImage, 'top-left', 0, $image->getHeight());
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
        $colors = [
            new CardColorSpec('Blue', '04237b'),
            new CardColorSpec('Green', '3b550c')
        ];
        $types = [
            new CardTypeSpec('Artillery', 'artillery'),
            new CardTypeSpec('Heavy_Weapons', 'heavy-weapons'),
            new CardTypeSpec('Infantry', 'infantry'),
            new CardTypeSpec('Paratroopers', 'paratroopers'),
            new CardTypeSpec('Special_Forces', 'special-forces'),
            new CardTypeSpec('Tank', 'tank'),
            new CardTypeSpec('Air_Strike', 'air-strike')
        ];
        return F\map(
            $colors,
            function (CardColorSpec $color) use ($types) {
                return F\map(
                    $types,
                    function (CardTypeSpec $type) use ($color) {
                        $cardsDirpath = self::CARDS_DIRPATH;
                        return new TileSpec(
                            "{$cardsDirpath}/{$type->getFileName()}_{$color->getFileName()}.jpg",
                            "{$type->getCssName()}.color-{$color->getColor()}"
                        );
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
                    function (TileSpec $tile) {
                        $image = $this->imageManager->make($tile->getFileName());
                        if ($image->getWidth() < self::CARD_WIDTH || $image->getHeight() < self::CARD_HEIGHT) {
                            throw new \RuntimeException(sprintf(
                                'Card %s [%dx%d] smaller than target [%dx%d]',
                                $tile->getFileName(),
                                $image->getWidth(),
                                $image->getHeight(),
                                self::CARD_WIDTH,
                                self::CARD_HEIGHT
                            ));
                        }
                        return $image->resize(self::CARD_WIDTH, self::CARD_HEIGHT);
                    }
                );
            }
        );
    }
}
