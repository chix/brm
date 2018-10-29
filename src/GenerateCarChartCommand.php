<?php

use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCarChartCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('generate:car-chart')
            ->addArgument('baseUrl', InputArgument::REQUIRED, 'base url with selected car type')
            ->addArgument('minYear', InputArgument::REQUIRED, 'min year of car')
            ->addOption('pages', 'p', InputOption::VALUE_REQUIRED, 'pages to search', '1')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parser = new Parser();
        $builder = new Builder();

        $pages = intval($input->getOption('pages'));
        if (!$pages) {
            $pages = 1;
        }
        $minYear = intval($input->getArgument('minYear'));
        $url = sprintf('%s?%d-&str=1-300', $input->getArgument('baseUrl'), $minYear);
        $urlParts = parse_url($url);
        $processedPages = 0;
        $cars = [];
        $builder->generateIndex();

        while ($processedPages < $pages && $url !== null) {
            $output->writeln(sprintf('Processing URL %s', $url));

            $dom = HtmlDomParser::file_get_html($url);
            if (!$dom) {
                $this->error($output, 'no dom');
            }
            $content = $dom->find('div.telo_webu', 0);
            if (!$content) {
                $this->error($output, 'no content');
            }

            foreach ($content->find('a[obal-zaznamu-browsu]') as $rowNode) {
                $adCar = $parser->parseCarRow($rowNode);
                if (!$adCar || !$adCar->motorVolume || !$adCar->mileage || !$adCar->price || !$adCar->year || !$adCar->fuelType || !$adCar->power) {
                    continue;
                }
                $cars[] = $adCar;
            }

            $url = $parser->parseNextPageUrl($content->find('div.strankovani', 0), 'tipcars');

            $processedPages++;
        }

        $title = iconv('windows-1250', 'UTF-8', trim($dom->find('title', 0)->innertext));
        $filename = 'tipcars-' . trim($urlParts['path'], '/\\') . '-' . date('Y-m-d') . '.html';
        $h = fopen(__DIR__ . '/../dist/' . $filename, 'w');
        fwrite($h, $builder->generateCarChart($cars, $title, $minYear));
        fclose($h);

        $h2 = fopen(__DIR__ . '/../dist/index.html', 'w');
        fwrite($h2, $builder->generateIndex());
        fclose($h2);

        $output->writeln("\nOutput written to files:");
        $output->writeln(realpath(__DIR__ . '/../dist/' . $filename));
        $output->writeln(realpath(__DIR__ . '/../dist/index.html'));
    }

    private function error(OutputInterface $output, $message)
    {
            $output->writeln(sprintf('<error>%s</error>', $message));
            exit;
    }
}
