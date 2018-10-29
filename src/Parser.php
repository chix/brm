<?php

use Sunra\PhpSimple\HtmlDomParser;

class Parser
{

    /**
     * @param simplehtmldom_1_5\simple_html_dom_node $node
     * @param string $site bazos|tipcars
     * @return string|null
     */
    public function parseNextPageUrl(simplehtmldom_1_5\simple_html_dom_node $node, $site = 'bazos')
    {
        if ($site === 'bazos') {
            $nextPageNode = $node->find('a', -1);
            if ($nextPageNode) {
                return $nextPageNode->href;
            }
        }
        if ($site === 'tipcars') {
            $nextPageIconNode = $node->find('i.icon-doprava', 0);
            if ($nextPageIconNode) {
                $nextPageNode = $nextPageIconNode->parent();
                if ($nextPageNode) {
                    return $nextPageNode->href;
                }
            }
        }
        return null;
    }

    /**
     * @param simplehtmldom_1_5\simple_html_dom_node $node
     * @return \AdRow|null
     */
    public function parseAdRow(simplehtmldom_1_5\simple_html_dom_node $node)
    {
        $headerNode = $node->find('tr', 0);
        $footerNode = $node->find('tr', 1);
        if (!$headerNode || !$footerNode) {
            return null;
        }

        $adRow = new AdRow();

        $titleNode = $headerNode->find('td span.nadpis a', 0);
        if ($titleNode) {
            $adRow->title = trim($titleNode->innertext);
        }

        $descriptionNode = $headerNode->find('td div.popis', 0);
        if ($descriptionNode) {
            $adRow->description = trim($descriptionNode->innertext);
        }

        $dateNode = $headerNode->find('td span.velikost10', 0);
        if ($dateNode) {
            $raw = trim($dateNode->innertext);
            $start = strpos($raw, '[') + 1;
            $end = strpos($raw, ']', $start);
            $rawDate = str_replace(' ', '', substr($raw, $start, $end - $start));
            list($day, $month, $year) = explode('.', $rawDate);
            $adRow->date = new DateTime();
            $adRow->date->setDate($year, $month, $day);
            $adRow->date->setTime(0, 0, 0);
        }

        $priceNode = $headerNode->find('td span.cena', 0);
        if ($priceNode) {
            $adRow->price = intval(str_replace([' ', 'Kč'], ['', ''], trim(strip_tags($priceNode->innertext))));
        }

        $locationNode = $headerNode->find('td', 2);
        if ($locationNode) {
            $parts = explode('<br>', trim($locationNode->innertext));
            if (count($parts) === 2) {
                $adRow->city = trim($parts[0]);
                $adRow->zipCode = trim($parts[1]);
            }
        }

        $scriptNode = $footerNode->find('script', 0);
        if ($scriptNode) {
            $script = $scriptNode->innertext;
            $start = strpos($script, "'") + 1;
            $end = strpos($script, "'", $start);
            $htmlString = substr($script, $start, $end - $start);

            $dom = HtmlDomParser::str_get_html($htmlString);
            if ($dom) {
                $userNode = $dom->find('a.akce', 2);
                if ($userNode) {
                    $adUser = new AdUser();
                    $urlParts = parse_url($userNode->href);
                    $queryParameters = [];
                    parse_str($urlParts['query'], $queryParameters);
                    $adUser->mailId = $queryParameters['mail'];
                    $adUser->name = htmlentities($queryParameters['jmeno']);
                    $adUser->url = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . '?mail=' . $adUser->mailId;
                    $adUser->city = $adRow->city;
                    $adUser->zipCode = $adRow->zipCode;
                    $adRow->user = $adUser;
                }
            }
        }
        
        return $adRow;
    }

    /**
     * @param simplehtmldom_1_5\simple_html_dom_node $node
     * @param \AdUser $adUser
     * @return \AdUser
     */
    public function parseAdUser(simplehtmldom_1_5\simple_html_dom_node $node, $adUser)
    {
        if (!$adUser) {
            $adUser = new AdUser();
        }

        if (stristr($node->innertext(), 'Uživatele zatím nikdo nehodnotil.')) {
            $adsNode = $node->find('table.listainzerat', 0);
            $reviewsNode = null;
        } else {
            $reviewsNode = $node->find('table.listainzerat', 0);
            $adsNode = $node->find('table.listainzerat', 1);
        }

        if ($reviewsNode) {
            $reviewCountNode = $reviewsNode->find('td', 0);
            if ($reviewCountNode) {
                $raw = $reviewCountNode->innertext;
                $start = strpos($raw, '(') + 1;
                $end = strpos($raw, ')', $start);
                $adUser->reviewCount = intval(substr($raw, $start, $end - $start));
            }
        }

        if ($adsNode) {
            $adCountNode = $adsNode->find('td', 0);
            if ($adCountNode) {
                $raw = $adCountNode->innertext;
                $start = strpos($raw, '(') + 1;
                $end = strpos($raw, ')', $start);
                $adUser->adCount = intval(substr($raw, $start, $end - $start));
            }
        }

        return $adUser;
    }

    /**
     * @param simplehtmldom_1_5\simple_html_dom_node $node
     * @return \AdCar|null
     */
    public function parseCarRow(simplehtmldom_1_5\simple_html_dom_node $node)
    {
        $adCar = new AdCar();

        $adCar->title = iconv('UTF-8','UTF-8//IGNORE', trim($node->title));
        $adCar->url = trim($node->href);

        $priceRaw = trim(strip_tags($node->find('div.koncova_cena', 0)->innertext));
        $adCar->price = intval(str_replace([' ', 'Kč'], ['', ''], $priceRaw));

        $adCar->year = intval(trim($node->find('div.rok_vyroby', 0)->innertext));

        $mileageRaw = trim($node->find('div.najeto', 0)->innertext);
        $adCar->mileage = intval(str_replace([' ', 'tkm', 'km'], ['', '000', ''], $mileageRaw));

        $colorNode = $node->find('div.barva', 0);
        if ($colorNode) {
            $adCar->color = iconv('UTF-8','UTF-8//IGNORE', trim($colorNode->title));
            $styleRaw = $colorNode->style;
            if ($styleRaw) {
                $start = strpos($styleRaw, '(') + 1;
                $end = strpos($styleRaw, ')', $start);
                $colorRgbParts = explode(',', substr($styleRaw, $start, $end - $start));
                if (count($colorRgbParts) === 3) {
                    $rgb = new ColorRGB();
                    $rgb->r = intval($colorRgbParts[0]);
                    $rgb->g = intval($colorRgbParts[1]);
                    $rgb->b = intval($colorRgbParts[2]);
                    $adCar->colorRgb = $rgb;

                }
            }
        }

        $bodyTypeRaw = trim(strip_tags($node->find('div.karoserie', 0)->innertext));
        $bodyTypeParts = explode('/', $bodyTypeRaw);
        if (count($bodyTypeParts) === 2) {
            $adCar->bodyType = iconv('windows-1250', 'UTF-8', trim($bodyTypeParts[0]));
            $adCar->doorCount = intval(trim($bodyTypeParts[1]));
        } elseif (count($bodyTypeParts) === 1) {
            $adCar->bodyType = iconv('windows-1250', 'UTF-8', trim($bodyTypeParts[0]));
	}

        $engineRaw = trim($node->find('div.motor', 0)->innertext);
        $engineParts = explode(',', $engineRaw);

        if (count($engineParts) === 3) {
            $adCar->fuelType = iconv('windows-1250', 'UTF-8', trim($engineParts[0]));
            $adCar->motorVolume = intval(str_replace([' ', 'ccm'], ['', ''], trim($engineParts[1])));
            $adCar->power = iconv('windows-1250', 'UTF-8', trim($engineParts[2]));
        }

        return $adCar;
    }
}

class AdRow
{
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $description;
    /**
     * @var \DateTime
     */
    public $date;
    /**
     * @var integer
     */
    public $price;
    /**
     * @var string
     */
    public $city;
    /**
     * @var string
     */
    public $zipCode;
    /**
     * @var AdUser
     */
    public $user;
}

class AdUser
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $city;
    /**
     * @var string
     */
    public $zipCode;
    /**
     * @var integer
     */
    public $mailId;
    /**
     * @var string
     */
    public $url;
    /**
     * @var integer
     */
    public $reviewCount;
    /**
     * @var integer
     */
    public $adCount;
}

class AdCar
{
    /**
     * @var string
     */
    public $title;
    /**
     * @var integer
     */
    public $year;
    /**
     * @var integer
     */
    public $mileage;
    /**
     * @var integer
     */
    public $price;
    /**
     * @var string
     */
    public $bodyType;
    /**
     * @var string
     */
    public $fuelType;
    /**
     * @var integer
     */
    public $motorVolume;
    /**
     * @var string
     */
    public $power;
    /**
     * @var integer
     */
    public $doorCount;
    /**
     * @var string
     */
    public $color;
    /**
     * @var ColorRGB
     */
    public $colorRgb;
    /**
     * @var string
     */
    public $url;
}

class ColorRGB
{
    /**
     * @var integer
     */
    public $r;
    /**
     * @var integer
     */
    public $g;
    /**
     * @var integer
     */
    public $b;
}
