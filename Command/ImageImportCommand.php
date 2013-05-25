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

        $repository = $this->getContainer()->get( 'ezpublish.api.repository' );
        $repository->setCurrentUser( $repository->getUserService()->loadUser( 14 ) );

        $targetLocationId = $input->getArgument( 'target-location-id' );
        $locationService = $this->getContainer()->get( 'ezpublish.api.repository' )->getLocationService();

        try
        {
            $locationService->loadLocation( $targetLocationId );
        }
        catch ( \eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e )
        {
            $output->writeln( $e->getMessage() );
            exit( 1 );
        }
        catch ( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e )
        {
            $output->writeln( $e->getMessage() );
            exit( 1 );
        }

        $imageContentType = $this->getContainer()->get( 'ezpublish.api.repository' )->getContentTypeService()->loadContentTypeByIdentifier( 'image' );

        $contentService = $this->getContainer()->get( 'ezpublish.api.repository' )->getContentService();

        $locationCreateStruct = $locationService->newLocationCreateStruct( $targetLocationId );

        /** @var $file SplFileInfo */
        foreach ( new \DirectoryIterator( $sourcePath ) as $file )
        {
            if ( !$file->isFile() || !in_array( $file->getExtension(), array( 'jpg', 'png', 'gif' ) ) )
                continue;

            $imageCreateStruct = $contentService->newContentCreateStruct(
                $imageContentType, 'eng-GB'
            );

            $nameString = $file->getBasename( '.' . $file->getExtension() );
            $imageCreateStruct->setField( 'name', $nameString );
            $imageCreateStruct->setField( 'image', new \eZ\Publish\Core\FieldType\Image\Value(
                array(
                    'path' => $file->getRealPath(),
                    'fileSize' => $file->getSize(),
                    'fileName' =>$file->getBasename(),
                    'alternativeText' => $nameString
                )
            ) );

            $imageContent = $contentService->createContent( $imageCreateStruct, array( clone $locationCreateStruct ) );
            $contentService->publishVersion( $imageContent->versionInfo );
            $output->writeln( "published image $nameString with id #" . $imageContent->id );
        }
    }
}
