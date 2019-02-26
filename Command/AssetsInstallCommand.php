<?php

namespace Harmony\Bundle\ExtensionBundle\Command;

use Harmony\Bundle\CoreBundle\Component\HttpKernel\AbstractKernel;
use Harmony\Sdk\Extension\ExtensionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class AssetsInstallCommand
 *
 * @package Harmony\Bundle\ExtensionBundle\Command
 */
class AssetsInstallCommand extends Command
{

    const METHOD_COPY             = 'copy';
    const METHOD_ABSOLUTE_SYMLINK = 'absolute symlink';
    const METHOD_RELATIVE_SYMLINK = 'relative symlink';

    protected static $defaultName = 'extension:assets:install';

    /** @var Filesystem $filesystem */
    protected $filesystem;

    /**
     * AssetsInstallCommand constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setDefinition([
            new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', null),
        ])
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the assets instead of copying it')
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Make relative symlinks')
            ->addOption('no-cleanup', null, InputOption::VALUE_NONE,
                'Do not remove the assets of the extensions that no longer exist')
            ->setDescription('Installs extensions web assets under a public directory')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command installs extension assets into a given
directory (e.g. the <comment>public</comment> directory).

  <info>php %command.full_name% public</info>

An "extensions" directory will be created inside the target directory and the
"Resources/public" directory of each extensions will be copied into it.

To create a symlink to each extension instead of copying its assets, use the
<info>--symlink</info> option (will fall back to hard copies when symbolic links aren't possible:

  <info>php %command.full_name% public --symlink</info>

To make symlink relative, add the <info>--relative</info> option:

  <info>php %command.full_name% public --symlink --relative</info>

EOT
            );
    }

    /**
     * Executes the current command.
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null null or 0 if everything went fine, or an error code
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var KernelInterface|AbstractKernel $kernel */
        $kernel    = $this->getApplication()->getKernel();
        $targetArg = rtrim($input->getArgument('target'), '/');

        if (!$targetArg) {
            $targetArg = $this->getPublicDirectory($kernel->getContainer());
        }

        if (!is_dir($targetArg)) {
            $targetArg = $kernel->getProjectDir() . '/' . $targetArg;

            if (!is_dir($targetArg)) {
                throw new InvalidArgumentException(sprintf('The target directory "%s" does not exist.',
                    $input->getArgument('target')));
            }
        }

        $extensionsDir = $targetArg . '/extensions/';

        $io = new SymfonyStyle($input, $output);
        $io->newLine();

        if ($input->getOption('relative')) {
            $expectedMethod = self::METHOD_RELATIVE_SYMLINK;
            $io->text('Trying to install assets as <info>relative symbolic links</info>.');
        } elseif ($input->getOption('symlink')) {
            $expectedMethod = self::METHOD_ABSOLUTE_SYMLINK;
            $io->text('Trying to install assets as <info>absolute symbolic links</info>.');
        } else {
            $expectedMethod = self::METHOD_COPY;
            $io->text('Installing assets as <info>hard copies</info>.');
        }

        $io->newLine();

        $rows     = [];
        $copyUsed = false;
        $exitCode = 0;
        /** @var ExtensionInterface $extension */
        foreach ($kernel->getExtensions() as $extension) {
            if (!is_dir($originDir = $extension->getPath() . '/Resources/public')) {
                continue;
            }

            $assetDir  = preg_replace('/extension$/', '', strtolower($extension->getName()));
            $targetDir = $extensionsDir . $assetDir;

            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $message = sprintf("%s\n-> %s", $extension->getName(), $targetDir);
            } else {
                $message = $extension->getName();
            }

            try {
                $this->filesystem->remove($targetDir);

                if (self::METHOD_RELATIVE_SYMLINK === $expectedMethod) {
                    $method = $this->relativeSymlinkWithFallback($originDir, $targetDir);
                } elseif (self::METHOD_ABSOLUTE_SYMLINK === $expectedMethod) {
                    $method = $this->absoluteSymlinkWithFallback($originDir, $targetDir);
                } else {
                    $method = $this->hardCopy($originDir, $targetDir);
                }

                if (self::METHOD_COPY === $method) {
                    $copyUsed = true;
                }

                if ($method === $expectedMethod) {
                    $rows[] = [
                        sprintf('<fg=green;options=bold>%s</>',
                            '\\' === \DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" /* HEAVY CHECK MARK (U+2714) */),
                        $message,
                        $method
                    ];
                } else {
                    $rows[] = [
                        sprintf('<fg=yellow;options=bold>%s</>', '\\' === \DIRECTORY_SEPARATOR ? 'WARNING' : '!'),
                        $message,
                        $method
                    ];
                }
            }
            catch (\Exception $e) {
                $exitCode = 1;
                $rows[]   = [
                    sprintf('<fg=red;options=bold>%s</>',
                        '\\' === \DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98" /* HEAVY BALLOT X (U+2718) */),
                    $message,
                    $e->getMessage()
                ];
            }
        }

        if ($rows) {
            $io->table(['', 'Extension', 'Method / Error'], $rows);
        }

        if (0 !== $exitCode) {
            $io->error('Some errors occurred while installing assets.');
        } else {
            if ($copyUsed) {
                $io->note('Some assets were installed via copy. If you make changes to these assets you have to run this command again.');
            }
            $io->success($rows ? 'All assets were successfully installed.' :
                'No assets were provided by any extension.');
        }

        return $exitCode;
    }

    /**
     * Try to create relative symlink.
     * Falling back to absolute symlink and finally hard copy.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function relativeSymlinkWithFallback(string $originDir, string $targetDir): string
    {
        try {
            $this->symlink($originDir, $targetDir, true);
            $method = self::METHOD_RELATIVE_SYMLINK;
        }
        catch (IOException $e) {
            $method = $this->absoluteSymlinkWithFallback($originDir, $targetDir);
        }

        return $method;
    }

    /**
     * Try to create absolute symlink.
     * Falling back to hard copy.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function absoluteSymlinkWithFallback(string $originDir, string $targetDir): string
    {
        try {
            $this->symlink($originDir, $targetDir);
            $method = self::METHOD_ABSOLUTE_SYMLINK;
        }
        catch (IOException $e) {
            // fall back to copy
            $method = $this->hardCopy($originDir, $targetDir);
        }

        return $method;
    }

    /**
     * Creates symbolic link.
     *
     * @param string $originDir
     * @param string $targetDir
     * @param bool   $relative
     */
    private function symlink(string $originDir, string $targetDir, bool $relative = false)
    {
        if ($relative) {
            $this->filesystem->mkdir(\dirname($targetDir));
            $originDir = $this->filesystem->makePathRelative($originDir, realpath(\dirname($targetDir)));
        }
        $this->filesystem->symlink($originDir, $targetDir);
        if (!file_exists($targetDir)) {
            throw new IOException(sprintf('Symbolic link "%s" was created but appears to be broken.', $targetDir), 0,
                null, $targetDir);
        }
    }

    /**
     * Copies origin to target.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function hardCopy(string $originDir, string $targetDir): string
    {
        $this->filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));

        return self::METHOD_COPY;
    }

    private function getPublicDirectory(ContainerInterface $container)
    {
        $defaultPublicDir = 'public';

        if (!$container->hasParameter('kernel.project_dir')) {
            return $defaultPublicDir;
        }

        $composerFilePath = $container->getParameter('kernel.project_dir') . '/composer.json';

        if (!file_exists($composerFilePath)) {
            return $defaultPublicDir;
        }

        $composerConfig = json_decode(file_get_contents($composerFilePath), true);

        if (isset($composerConfig['extra']['public-dir'])) {
            return $composerConfig['extra']['public-dir'];
        }

        return $defaultPublicDir;
    }
}