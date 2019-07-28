<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EasyExtendsBundle\Generator;

use Sonata\EasyExtendsBundle\Bundle\BundleMetadata;
use Symfony\Component\Console\Output\OutputInterface;

class OrmGenerator implements GeneratorInterface
{
    /**
     * @var string
     */
    protected $entityTemplate;

    /**
     * @var string
     */
    protected $entityRepositoryTemplate;

    public function __construct()
    {
        $this->entityTemplate = (string) file_get_contents(__DIR__.'/../Resources/skeleton/orm/entity.mustache');
        $this->entityRepositoryTemplate = (string) file_get_contents(__DIR__.'/../Resources/skeleton/orm/repository.mustache');
    }

    /**
     * {@inheritdoc}
     */
    public function generate(OutputInterface $output, BundleMetadata $bundleMetadata): void
    {
        $this->generateMappingEntityFiles($output, $bundleMetadata);
        $this->generateEntityFiles($output, $bundleMetadata);
        $this->generateEntityRepositoryFiles($output, $bundleMetadata);
    }

    public function generateMappingEntityFiles(OutputInterface $output, BundleMetadata $bundleMetadata): void
    {
        $output->writeln(' - Copy entity files');

        $files = $bundleMetadata->getOrmMetadata()->getEntityMappingFiles();
        foreach ($files as $file) {
            // copy mapping definition
            $fileName = substr($file->getFileName(), 0, strrpos($file->getFileName(), '.'));

            $dest_file = sprintf('%s/%s', $bundleMetadata->getOrmMetadata()->getExtendedMappingEntityDirectory(), $fileName);
            $src_file = sprintf('%s/%s', $bundleMetadata->getOrmMetadata()->getMappingEntityDirectory(), $file->getFileName());

            if (is_file($dest_file)) {
                $output->writeln(sprintf('   ~ <info>%s</info>', $fileName));
            } else {
                $output->writeln(sprintf('   + <info>%s</info>', $fileName));

                $mappingEntityTemplate = file_get_contents($src_file);

                $string = Mustache::replace($mappingEntityTemplate, [
                    'namespace' => $bundleMetadata->getExtendedNamespace(),
                ]);

                file_put_contents($dest_file, $string);
            }
        }
    }

    public function generateEntityFiles(OutputInterface $output, BundleMetadata $bundleMetadata): void
    {
        $output->writeln(' - Generating entity files');

        $names = $bundleMetadata->getOrmMetadata()->getEntityNames();

        foreach ($names as $name) {
            $extendedName = $name;

            $dest_file = sprintf('%s/%s.php', $bundleMetadata->getOrmMetadata()->getExtendedEntityDirectory(), $name);
            $src_file = sprintf('%s/%s.php', $bundleMetadata->getOrmMetadata()->getEntityDirectory(), $extendedName);

            if (!is_file($src_file)) {
                $extendedName = 'Base'.$name;
                $src_file = sprintf('%s/%s.php', $bundleMetadata->getOrmMetadata()->getEntityDirectory(), $extendedName);

                if (!is_file($src_file)) {
                    $output->writeln(sprintf('   ! <info>%s</info>', $extendedName));

                    continue;
                }
            }

            if (is_file($dest_file)) {
                $output->writeln(sprintf('   ~ <info>%s</info>', $name));
            } else {
                $output->writeln(sprintf('   + <info>%s</info>', $name));

                $string = Mustache::replace($this->getEntityTemplate(), [
                    'extended_namespace' => $bundleMetadata->getExtendedNamespace(),
                    'name' => $name !== $extendedName ? $extendedName : $name,
                    'class' => $name,
                    'extended_name' => $name === $extendedName ? 'Base'.$name : $extendedName,
                    'namespace' => $bundleMetadata->getNamespace(),
                ]);

                file_put_contents($dest_file, $string);
            }
        }
    }

    public function generateEntityRepositoryFiles(OutputInterface $output, BundleMetadata $bundleMetadata): void
    {
        $output->writeln(' - Generating entity repository files');

        $names = $bundleMetadata->getOrmMetadata()->getEntityNames();

        foreach ($names as $name) {
            $dest_file = sprintf('%s/%sRepository.php', $bundleMetadata->getOrmMetadata()->getExtendedEntityDirectory(), $name);
            $src_file = sprintf('%s/Base%sRepository.php', $bundleMetadata->getOrmMetadata()->getEntityDirectory(), $name);

            if (!is_file($src_file)) {
                $output->writeln(sprintf('   ! <info>%sRepository</info>', $name));

                continue;
            }

            if (is_file($dest_file)) {
                $output->writeln(sprintf('   ~ <info>%sRepository</info>', $name));
            } else {
                $output->writeln(sprintf('   + <info>%sRepository</info>', $name));

                $string = Mustache::replace($this->getEntityRepositoryTemplate(), [
                    'extended_namespace' => $bundleMetadata->getExtendedNamespace(),
                    'name' => $name,
                    'namespace' => $bundleMetadata->getNamespace(),
                ]);

                file_put_contents($dest_file, $string);
            }
        }
    }

    public function getEntityTemplate(): string
    {
        return $this->entityTemplate;
    }

    public function getEntityRepositoryTemplate(): string
    {
        return $this->entityRepositoryTemplate;
    }
}
