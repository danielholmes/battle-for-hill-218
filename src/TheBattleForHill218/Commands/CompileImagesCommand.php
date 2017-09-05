<?php

namespace TheBattleForHill218\Commands;

use ImageOptimizer\OptimizerFactory;
use Intervention\Image\AbstractShape;
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

    const CARD_CORNER_RADIUS = 6;
    const CARD_WIDTH = 160;
    const CARD_HEIGHT = 230;

    /**
     * @var Image
     */
    private $cardMask;

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
        $start = microtime(true);

        $this->imageManager = new ImageManager(['driver' => 'imagick']);
        $tileRows = F\reduce_left(
            [
                new TileSpec(self::CARDS_DIRPATH . '/' . 'Hill.jpg', 'hill'),
                new TileSpec(self::CARDS_DIRPATH . '/' . 'Back.png', 'back'),
                new TileSpec(self::CARDS_DIRPATH . '/' . 'Red Solid.png'),
                new TileSpec(self::CARDS_DIRPATH . '/' . 'Red Dashed.png'),
                new TileSpec(self::CARDS_DIRPATH . '/' . 'White Dashed.png'),
                new TileSpec(self::CARDS_DIRPATH . '/' . 'Black Dashed.png')
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
        $fullImage = $this->appendIcons($image);
        $fullImagePath = __DIR__ . '/../../../' . self::OUTPUT_RELATIVE_PATHNAME;
        $fullImage->save($fullImagePath);
        $this->optimiseImage($fullImagePath);

        $cssContent = $this->createCss($tileRows, $fullImage);

        $took = microtime(true) - $start;
        $output->writeln($cssContent);
        $output->writeln(sprintf(
            'Image: %s [%sKB] done in %ss',
            self::OUTPUT_RELATIVE_PATHNAME,
            ceil(filesize($fullImagePath) / 1000),
            round($took, 1)
        ));
    }

    /**
     * @param string $path
     */
    private function optimiseImage($path)
    {
        $factory = new OptimizerFactory(['ignore_errors' => false]);
        $optimizer = $factory->get('pngquant');
        $optimizer->optimize($path);
    }

    /**
     * @param array $tileRows
     * @param Image $image
     * @return string
     */
    private function createCss(array $tileRows, Image $image)
    {
        return join(
            "\n\n",
            [
                $this->createSheetCss($tileRows, $image, 'Cards', 'card', 0.5),
                $this->createSheetCss($tileRows, $image, 'Battlefield Cards', 'battlefield-card > .card', 0.6),
                $this->createSheetCss($tileRows, $image, 'Tooltip Cards', 'tooltip-card', 1)
            ]
        );
    }

    /**
     * @param array $tileRows
     * @param Image $image
     * @param string $name
     * @param string $cssPrefix
     * @param float $ratio
     * @return string
     */
    private function createSheetCss(array $tileRows, Image $image, $name, $cssPrefix, $ratio)
    {
        $width = self::CARD_WIDTH * $ratio;
        $height = self::CARD_HEIGHT * $ratio;
        if ($width % 1 !== 0 || $height % 1 !== 0) {
            throw new \InvalidArgumentException("Invalid ratio {$ratio} - results in non int size {$width}x{$height}");
        }
        return join(
            "\n",
            array_merge(
                [
                    sprintf(
                        <<<CSS
/* %s */
.%s {
    width: %dpx;
    height: %dpx;
    background-image: url(img/cards.png);
    background-size: %dpx %dpx;
    background-position: %dpx %dpx;
    background-repeat: no-repeat;
}
CSS
                        ,
                        $name,
                        $cssPrefix,
                        $width,
                        $height,
                        (int) round($image->width() * $ratio),
                        (int) round($image->height() * $ratio),
                        $width,
                        $height
                    )
                ],
                F\map(
                    $tileRows,
                    function (array $tileRow, $row) use ($cssPrefix, $width, $height) {
                        $rowWithCss = F\filter($tileRow, function (TileSpec $spec) {
                            return $spec->includeInCss();
                        });
                        return join(
                            "\n",
                            F\map(
                                $rowWithCss,
                                function (TileSpec $tile, $col) use ($row, $cssPrefix, $width, $height) {
                                    return sprintf(
                                        <<<CSS
.%s.%s {
    background-position: %dpx %dpx;
}
CSS
                                        ,
                                        $cssPrefix,
                                        $tile->getCssName(),
                                        -1 * $col * $width,
                                        -1 * $row * $height
                                    );
                                }
                            )
                        );
                    }
                )
            )
        );
    }

    /**
     * @param Image $image
     * @return Image
     */
    private function appendIcons(Image $image)
    {
        $iconNames = [
            'Deck Icon.png',
            'Hand Icon.png',
            'Air Strike Green Icon.png',
            'Air Strike Blue Icon.png'
        ];
        $iconImages = F\map(
            $iconNames,
            function ($name) {
                return $this->imageManager->make(self::CARDS_DIRPATH . '/' . $name);
            }
        );
        $iconsImage = $this->combineImageRow($iconImages);
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
                $x = F\sum(
                    F\map(
                        array_slice($row, 0, $i),
                        function (Image $image) {
                            return $image->getWidth();
                        }
                    )
                );
                return $reduction->insert($image, 'top-left', $x);
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
        $totalHeight = F\sum(
            F\map(
                $imageRows,
                function (array $imageRow) {
                    return F\maximum(
                        F\map(
                            $imageRow,
                            function (Image $image) {
                                return $image->getHeight();
                            }
                        )
                    );
                }
            )
        );
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
     * @return Image
     */
    private function createCardMask()
    {
        if ($this->cardMask !== null) {
            return $this->cardMask;
        }

        $cardDiameter = 2 * self::CARD_CORNER_RADIUS;
        $styleCallback = function (AbstractShape $shape) {
            $shape->background(0xff00ff00);
        };
        $this->cardMask = $this->imageManager
            ->canvas(self::CARD_WIDTH, self::CARD_HEIGHT)
            ->rectangle(
                0,
                self::CARD_CORNER_RADIUS,
                self::CARD_WIDTH,
                self::CARD_HEIGHT - self::CARD_CORNER_RADIUS,
                $styleCallback
            )
            ->rectangle(
                self::CARD_CORNER_RADIUS,
                0,
                self::CARD_WIDTH - self::CARD_CORNER_RADIUS,
                self::CARD_HEIGHT,
                $styleCallback
            )
            ->circle(
                $cardDiameter,
                self::CARD_CORNER_RADIUS,
                self::CARD_CORNER_RADIUS,
                $styleCallback
            )
            ->circle(
                $cardDiameter,
                self::CARD_WIDTH - self::CARD_CORNER_RADIUS,
                self::CARD_CORNER_RADIUS,
                $styleCallback
            )
            ->circle(
                $cardDiameter,
                self::CARD_CORNER_RADIUS,
                self::CARD_HEIGHT - self::CARD_CORNER_RADIUS,
                $styleCallback
            )
            ->circle(
                $cardDiameter,
                self::CARD_WIDTH - self::CARD_CORNER_RADIUS,
                self::CARD_HEIGHT - self::CARD_CORNER_RADIUS,
                $styleCallback
            );
        return $this->cardMask;
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
                        return $image->resize(self::CARD_WIDTH, self::CARD_HEIGHT)
                            ->mask($this->createCardMask(), true);
                    }
                );
            }
        );
    }
}
