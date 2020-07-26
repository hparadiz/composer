<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Command;

use Composer\Factory;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Henry Paradiz <henry.paradiz@gmail.com>
 */
class SystemCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('system')
            ->setDescription('Allows running commands in the system php dependencies dir (/usr/lib{64}/php/).')
            ->setDefinition(array(
                new InputArgument('command-name', InputArgument::REQUIRED, ''),
                new InputArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, ''),
            ))
            ->setHelp(
                <<<EOT
Use this command as a wrapper to run other Composer commands
within the system context of COMPOSER_HOME.

You can use this to install libraries that can be used by any PHP script
on the system.
EOT
            )
        ;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        // extract real command name
        $tokens = preg_split('{\s+}', $input->__toString());
        $args = array();
        foreach ($tokens as $token) {
            if ($token && $token[0] !== '-') {
                $args[] = $token;
                if (count($args) >= 2) {
                    break;
                }
            }
        }

        // show help for this command if no command was found
        if (count($args) < 2) {
            return parent::run($input, $output);
        }

        // change to system dir
        $config = Factory::createConfig();
        $home = $config->get('system-home');

        if (!is_dir($home)) {
            $fs = new Filesystem();
            $fs->ensureDirectoryExists($home);
            if (!is_dir($home)) {
                throw new \RuntimeException('Could not create home directory');
            }
        }

        try {
            chdir($home);
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not switch to home directory "'.$home.'"', 0, $e);
        }
        $this->getIO()->writeError('<info>Changed current directory to '.$home.'</info>');

        // create new input without "system" command prefix
        $input = new StringInput(preg_replace('{\bs(?:y(?:s(?:t(?:e(?:m)?)?)?)?)?\b}', '', $input->__toString(), 1));
        $this->getApplication()->resetComposer();

        return $this->getApplication()->run($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    public function isProxyCommand()
    {
        return true;
    }
}
