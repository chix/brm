<?php

use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSellerListCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('generate:seller-list')
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
        $baseUrl = 'https://auto.bazos.cz';
        $url = $baseUrl;
        $processedPages = 0;
        $users = [];
        $builder->generateIndex();

        while ($processedPages < $pages && $url !== null) {
            $output->writeln(sprintf('Processing URL %s', $url));

            $dom = HtmlDomParser::file_get_html($url);
            if (!$dom) {
                $this->error($output, 'no dom');
            }
            $content = $dom->find('div.sirka table', 0);
            if (!$content) {
                $this->error($output, 'no content');
            }

            foreach ($content->find('span.vypis table.inzeraty tbody') as $rowNode) {
                $adRow = $parser->parseAdRow($rowNode);
                if (!$adRow || !$adRow->user || !$adRow->user->mailId) {
                    continue;
                }
                $users[$adRow->user->mailId] = $adRow->user;
            }

            $nextPageUrl = $parser->parseNextPageUrl($content->find('div.strankovani', 0), 'bazos');
            $url = ($nextPageUrl !== null) ? $baseUrl . $nextPageUrl : null;

            $processedPages++;
        }

        foreach ($users as $mailId => $user) {
            $url = $user->url;
            $output->writeln(sprintf('Processing URL %s', $url));

            $dom = HtmlDomParser::file_get_html($url);
            if (!$dom) {
                $this->error($output, 'no user dom');
            }
            $content = $dom->find('div.sirka table', 0);
            if (!$content) {
                $this->error($output, 'no user content');
            }

            $users[$mailId] = $parser->parseAdUser($content, $user);
        }

        $filename = 'bazos-prodejci-'. date('Y-m-d') . '.html';
        $h = fopen(__DIR__ . '/../dist/' . $filename, 'w');
        fwrite($h, $builder->generateSellersList($users));
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
