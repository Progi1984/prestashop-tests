<?php
namespace Console\App\Command;

use Console\App\Service\API\TestRail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
 
class TestRailExportModules extends Command
{
    /**
     * @var TestRail;
     */
    protected $testRailClient;

    const PROJECT_NAME = 'Modules';

    const OUTPUT_DIR = 'src/content/modules/';

    protected function configure()
    {
        $this->setName('testrail:export:modules')
            ->setDescription('TestRail : Export data')
            ->addOption(
                'url',
                null,
                InputOption::VALUE_OPTIONAL,
                '',
                getenv('TESTRAIL_URL') ?? null
            )
            ->addOption(
                'username',
                null,
                InputOption::VALUE_OPTIONAL,
                '',
                getenv('TESTRAIL_USERNAME') ?? null
            )
            ->addOption(
                'apikey',
                null,
                InputOption::VALUE_OPTIONAL,
                '',
                getenv('TESTRAIL_APIKEY') ?? null
            );   
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = time();

        // Init
        $this->testRailClient = new TestRail($input->getOption('url'));
        $this->testRailClient->set_user($input->getOption('username'));
        $this->testRailClient->set_password($input->getOption('apikey'));

        $projectId = $this->getProjectId();
        if (empty($projectId)) {
            return false;
        }
        $suites = $this->getSuites($projectId);

        foreach ($suites as $suiteId => $suiteName) {
            $cases = $this->getCases($projectId, $suiteId);
            $this->generatePages($suiteName, $cases);
        }
        
        $output->writeLn(['', 'Output generated in ' . (time() - $time) . 's.']);
    }

    private function getProjectId(): ?int
    {
        $projects = $this->testRailClient->send_get('get_projects');
        foreach ($projects as $value) {
            if ($value['name'] === self::PROJECT_NAME) {
                return $value['id'];
            }
        }
        return null;
    }

    private function getSuites(int $projectId): array
    {
        $data = $this->testRailClient->send_get('get_suites/' . $projectId);
        $suites = [];
        foreach ($data as $value) {
            $suites[$value['id']] = $value['name'];
        }
        return $suites;
    }

    private function getCases(int $projectId, int $idSuite): array
    {
        return $this->testRailClient->send_get('get_cases/' . $projectId . '&suite_id=' . $idSuite);
    }

    private function generatePages(string $moduleName, array $cases)
    {
        $dirName = self::OUTPUT_DIR . $moduleName;
        // Clean directory
        $this->delTree($dirName);
        // Check directory
        if (!is_dir($dirName)) {
            \mkdir($dirName);
        }

        // Check index.md
        $content = <<<EOT
---
title: $moduleName
menuTitle: $moduleName 
geekdocFlatSection: true
---

{{% children %}}
EOT;
        \file_put_contents($dirName . '/_index.md', $content);

        // Cases
        foreach ($cases as $case) {
            $custom_preconds = $case['custom_preconds'];
            $custom_preconds = str_replace("\r\n", '\\' . PHP_EOL, $custom_preconds);
            $custom_preconds = str_replace("\\" . PHP_EOL . '-', PHP_EOL . '-', $custom_preconds);
            $custom_preconds = trim($custom_preconds, " \\-" . PHP_EOL);

            $custom_steps = $case['custom_steps'];
            $custom_steps = str_replace(PHP_EOL, '\\' . PHP_EOL, $custom_steps);
            $custom_steps = str_replace("\\" . PHP_EOL . '-', PHP_EOL . '-', $custom_steps);
            $custom_steps = trim($custom_steps, " \\-" . PHP_EOL);

            $custom_expected = $case['custom_expected'];
            $custom_expected = str_replace(PHP_EOL, '\\' . PHP_EOL, $custom_expected);
            $custom_expected = str_replace("\\" . PHP_EOL . '-', PHP_EOL . '-', $custom_expected);
            $custom_expected = trim($custom_expected, " \\-" . PHP_EOL);

            $content = '---' . PHP_EOL
                . 'title: ' . $case['title'] . PHP_EOL
                . 'weight: ' .$case['display_order'] . PHP_EOL
                . '---' . PHP_EOL;
            if (!empty($custom_preconds)) {
            $content .= PHP_EOL
                . '## Preconditions' . PHP_EOL
                . PHP_EOL
                . $custom_preconds. PHP_EOL;
            }

            switch($case['template_id']) {
                case 1:
                    if (!empty($custom_steps)) {
                        $content .= '## Steps' . PHP_EOL . PHP_EOL . $custom_steps. PHP_EOL . PHP_EOL;
                    }
                    if (!empty($case['custom_expected'])) {
                        $content .= '## Expected result'. PHP_EOL . PHP_EOL . $custom_expected. PHP_EOL . PHP_EOL;
                    }
                break;
                case 2:
                    $case['custom_steps_separated'] = is_null($case['custom_steps_separated']) ? [] : $case['custom_steps_separated'];

                    if (!empty($case['custom_steps_separated'])) {
                        $content .= '## Steps' . PHP_EOL;
                        $content .= '| ' . 'Step Description'
                            . ' | ' . 'Expected result'
                            . ' |'  . PHP_EOL;

                        $content .= '| ----- | ----- |'  . PHP_EOL;
                        foreach ($case['custom_steps_separated'] as $step) {
                            $stepContent = $step['content'];
                            $stepContent = trim($stepContent);
                            $stepContent = str_replace("\r\n", '<br>', $stepContent);
                            $stepContent = str_replace(PHP_EOL, '<br>', $stepContent);
                            $stepContent = trim($stepContent, " \\-");

                            $stepExpected = $step['expected'];
                            $stepExpected = trim($stepExpected);
                            $stepExpected = str_replace("\r\n", '<br>', $stepExpected);
                            $stepExpected = str_replace(PHP_EOL, '<br>', $stepExpected);
                            $stepExpected = trim($stepExpected, " \\-");
                            
                            $content .= '| ' . $stepContent . ' | ' . $stepExpected . ' |'  . PHP_EOL;
                        }
                    }
                break;
                default:
                    throw new \Exception('Template not defined : ' . $case['template_id']);
            }
            \file_put_contents($dirName . '/' .$this->slugify($case['title']). '.md', $content);
        }
    }

    public function slugify(string $text): string
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        // trim
        $text = trim($text, '-');
        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        // lowercase
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }

    private function delTree(string $dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir); 
    }
}