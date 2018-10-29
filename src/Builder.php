<?php

use Nette\Utils\Json;
use Symfony\Component\Finder\Finder;

class Builder
{
    /**
     * @return string
     */
    public function generateIndex()
    {
        $template = file_get_contents(__DIR__ . '/index.html.tpl');

        $finder = new Finder();

        $tableBodyHtml = '';
        foreach ($finder->files()->in(__DIR__ . '/../dist')->name('*.html')->sort(function ($a, $b) {
            return ($b->getMTime() - $a->getMTime());
        }) as $htmlFile) {
            $filename = $htmlFile->getFilename();
            if ($filename === 'index.html') {
                continue;
            }
            $tableBodyHtml .= sprintf(
                '<tr><td class="text-center"><h3><a href="%s">%s</a></h3></td></tr>',
                '/' . $filename,
                str_replace(['tipcars-', 'bazos-'], ['', ''], $filename)
            );
        }

        $html = str_replace('___TBODY___', $tableBodyHtml, $template);

        return $html;
    }

    /**
     * @param AdUser[] $users
     * @return string
     */
    public function generateSellersList($users)
    {
        $template = file_get_contents(__DIR__ . '/seller-list.html.tpl');

        uasort($users, function ($a, $b) {
            return $b->reviewCount - $a->reviewCount;
        });

        $tableBodyHtml = '';
        foreach ($users as $user) {
            $tableBodyHtml .= sprintf(
                '<tr><td><a href="%s" target="_blank">%s</a></td><td>%s</td><td>%d</td><td>%d</td></tr>',
                $user->url,
                $user->name,
                $user->city,
                $user->adCount,
                $user->reviewCount
            );
        }

        $html = str_replace('___TBODY___', $tableBodyHtml, $template);

        return $html;
    }

    /**
     * @param AdCar[] $cars
     * @param string $title
     * @param integer $minYear
     * @return string
     */
    public function generateCarChart($cars, $title, $minYear)
    {
        $template = file_get_contents(__DIR__ . '/car-chart.html.tpl');

        $colors = [
            'FF0000', '00FF00', '0000FF', 'FFFF00', 'FF00FF', '00FFFF', '000000',
            '800000', '008000', '000080', '808000', '800080', '008080', '808080',
            'C00000', '00C000', '0000C0', 'C0C000', 'C000C0', '00C0C0', 'C0C0C0',
            '400000', '004000', '000040', '404000', '400040', '004040', '404040',
            '200000', '002000', '000020', '202000', '200020', '002020', '202020',
            '600000', '006000', '000060', '606000', '600060', '006060', '606060',
            'A00000', '00A000', '0000A0', 'A0A000', 'A000A0', '00A0A0', 'A0A0A0',
            'E00000', '00E000', '0000E0', 'E0E000', 'E000E0', '00E0E0', 'E0E0E0',
        ];
        /*
        $colors = [
            'rgba(77, 201, 246, 0.5)',
            'rgba(246, 112, 25, 0.5)',
            'rgba(245, 55, 148, 0.5)',
            'rgba(83, 123, 196, 0.5)',
            'rgba(172, 194, 54, 0.5)',
            'rgba(22, 106, 143, 0.5)',
            'rgba(0, 169, 80, 0.5)',
            'rgba(88, 89, 91, 0.5)',
            'rgba(133, 73, 186, 0.5)',
            'rgba(67, 122, 55, 0.5)'
        ];
        */

        uasort($cars, function ($a, $b) {
            return $a->motorVolume - $b->motorVolume;
        });

        $jsonData = [];
        foreach ($cars as $car) {
            $motorVolume = ceil($car->motorVolume / 100) * 100;
            $motor = sprintf('%.1f, %s, %s', $motorVolume / 1000, $car->power, $car->fuelType);
            if (!isset($jsonData[$motor])) {
                $jsonData[$motor] = new \stdClass();
                $jsonData[$motor]->label = $motor;
                $jsonData[$motor]->data = [];
            }
            $data = new \stdClass();
            $data->x = $car->price;
            $data->y = $car->mileage;
            $data->r = ($car->year - $minYear + 1) * 2;
            $data->title = sprintf('%s (%s, %s)', $car->title, $car->year, $car->bodyType);
            $data->year = $car->year;
            $data->fuelType = $car->fuelType;
            $data->color = $car->colorRgb;
            $data->url = 'https://www.tipcars.com'.$car->url;
            $jsonData[$motor]->data[] = $data;
        }

        $normalizedJsonData = array_values($jsonData);
        $i = 0;
        foreach ($normalizedJsonData as $dataset) {
            $count = count($dataset->data);
            if ($count <= 1) {
                $dataset->backgroundColor = 'rgba(255, 255, 255, 0.5)';
                //$dataset->hidden = true;
            } else {
                $dataset->backgroundColor = $this->hex2rgba($colors[$i++], 0.5);
            }
            $dataset->label .= sprintf(' (%d)', $count);
        }

        $html = str_replace(['___TITLE___', '___DATA___'], [$title, Json::encode($normalizedJsonData)], $template);

        return $html;
    }

    function hex2rgba($color, $opacity = false) {
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        if (strlen($color) == 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return 'rgb(0,0,0)';
        }

        $rgb = array_map('hexdec', $hex);    

        if ($opacity) {
            if (abs($opacity) > 1) {
                $opacity = 1.0;
            }

            $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(",", $rgb) . ')';
        }    
        return $output;
    }
}
