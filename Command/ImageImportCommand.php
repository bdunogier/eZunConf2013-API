<?php
namespace eZ\UnconBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption;

class ImageImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'ez:uncon:import_images' )->setDefinition(
            array(
                new InputArgument( 'source-path', InputArgument::REQUIRED, 'Path to import images from' ),
                new InputArgument( 'target-location-id', InputArgument::REQUIRED, 'Location ID images should be created under' )
            )
        );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $sourcePath = $input->getArgument( 'source-path' );
        if ( !file_exists( $sourcePath ) )
        {
            $output->writeln( "Source path '$sourcePath'  doesn\'t exist" );
            exit( 1 );
        }
        if ( !is_dir( $sourcePath ) )
        {
            $output->writeln( "Source path '$sourcePath' is not a directory" );
            exit( 1 );
        }

        // check if the target location exists

        /** @var $file SplFileInfo */
        foreach ( new \DirectoryIterator( $sourcePath ) as $file )
        {
            if ( !$file->isFile() || !in_array( $file->getExtension(), array( 'jpg', 'png', 'gif' ) ) )
                continue;

            $output->writeln( "Image: " . $file->getRealPath() );

            // create content &publish it !
        }
    }
}
