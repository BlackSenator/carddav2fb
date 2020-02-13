<?php

namespace Andig;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SaveCommand extends Command
{
    use ConfigTrait;

    protected function configure()
    {
        $this->setName('save')
            ->setDescription('Download a phonebook from FRITZ!Box and save it as local VCF')
            ->addArgument('filename', InputArgument::REQUIRED, 'raw vcards file (VCF)')
            ->addOption('image', 'i', InputOption::VALUE_NONE, 'download images');

        $this->addConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadConfig($input);

        // we want to check for image download show stoppers as early as possible
        if ($input->getOption('image')) {
            $this->checkUploadImagePreconditions($this->config['fritzbox'], $this->config['phonebook']);
        }

        // download recent phonebook and save special attributes
        $savedAttributes = [];
        error_log("Downloading recent FRITZ!Box phonebook");
        $recentPhonebook = downloadPhonebook($this->config['fritzbox'], $this->config['phonebook']);
        if (count($savedAttributes = uploadAttributes($recentPhonebook, $this->config))) {
            error_log('Phone numbers with special attributes saved');
        } else {
            // no attributes are set in the FRITZ!Box or lost -> try to download them
            $savedAttributes = downloadAttributes($this->config['fritzbox']);   // try to get last saved attributes
        }

        //convert phonebook to vcf
        $vcf = phonebookToVCF($recentPhonebook, $input->getOption('image'), $this->config['fritzbox']);

        // save to file
        $filename = $input->getArgument('filename');
        if (file_put_contents($filename, $vcf) != false) {
            error_log(sprintf("Succesfully saved vCard(s) in %s", $filename));
        }

        return 0;
    }

    /**
     * checks if preconditions for upload images are OK
     *
     * @return            mixed     (true if all preconditions OK, error string otherwise)
     */
    private function checkUploadImagePreconditions($configFritz, $configPhonebook)
    {
        if (!function_exists("ftp_connect")) {
            throw new \Exception(
                <<<EOD
FTP functions not available in your PHP installation.
Image download not possible (remove -i switch).
Ensure PHP was installed with --enable-ftp
Ensure php.ini does not list ftp_* functions in 'disable_functions'
In shell run: php -r \"phpinfo();\" | grep -i FTP"
EOD
            );
        }
        if ($configFritz['user'] == 'dslf-conf') {
            throw new \Exception(
                <<<EOD
TR-064 default user dslf-conf has no permission for ftp access.
Image download not possible (remove -i switch).
EOD
            );
        }
    }
}
