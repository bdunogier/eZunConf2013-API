<?php
namespace eZ\UnconBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use eZ\Publish\API\Repository\Values\Content\LocationCreateStruct;

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
        $sourcePath = realpath( $input->getArgument( 'source-path' ) );
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

        $this->logInAsAdmin();

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

        $this->createAndPublishStructure(
            new \RecursiveDirectoryIterator( $sourcePath, \FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS ),
            $locationService->loadLocation( $targetLocationId ),
            $output
        );
    }

    public function createAndPublishStructure( \RecursiveDirectoryIterator $iterator, \eZ\Publish\API\Repository\Values\Content\Location $parentLocation, OutputInterface $output )
    {
        /** @var $file \SplFileInfo */
        foreach ( $iterator as $file )
        {
            if ( $iterator->hasChildren() )
            {
                $output->writeln( "Entering folder " . $file->getRealPath() );

                // Create the folder
                $folder = $this->createAndPublishFolder( $file->getBasename(), $parentLocation->id );
                $output->writeln( "Created folder '" . $folder->contentInfo->name . "'" );

                // Dive in
                $this->createAndPublishStructure( $iterator->getChildren(), $folder, $output );
            }

            if ( !in_array( strtolower( $file->getExtension() ), array( 'jpg', 'gif', 'png' ) ) )
                continue;

            $image = $this->createAndPublishImage( $file, $parentLocation->id );
            $output->writeln( "Created image '" . $image->contentInfo->name . "'" );
        }
    }

    /**
     * @param string $file
     * @param int $parentLocationId
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    protected function createAndPublishImage( $file, $parentLocationId )
    {
        $locationCreateStruct = $this->getRepository()->getLocationService()->newLocationCreateStruct( $parentLocationId );
        $imageCreateStruct = $this->getContentService()->newContentCreateStruct(
            $this->getContentType( 'image' ), 'eng-GB'
        );

        $nameString = $file->getBasename( '.' . $file->getExtension() );
        $imageCreateStruct->setField( 'name', $nameString );
        $imageCreateStruct->setField(
            'image',
            new \eZ\Publish\Core\FieldType\Image\Value(
                array(
                    'path' => $file->getRealPath(),
                    'fileSize' => $file->getSize(),
                    'fileName' => $file->getBasename(),
                    'alternativeText' => $nameString
                )
            )
        );

        $imageDraft = $this->getContentService()->createContent( $imageCreateStruct, array( $locationCreateStruct ) );
        $imageContent = $this->getContentService()->publishVersion( $imageDraft->versionInfo );

        return $imageContent;
    }

    /**
     * Creates and publishes a folder content
     * @param string $name
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    protected function createAndPublishFolder( $name, $parentLocationId )
    {
        $locationService = $this->getRepository()->getLocationService();

        $locationCreateStruct = $locationService->newLocationCreateStruct( $parentLocationId );
        $imageCreateStruct = $this->getContentService()->newContentCreateStruct(
            $this->getContentType( 'folder' ), 'eng-GB'
        );

        $imageCreateStruct->setField( 'name', $name );
        $imageDraft = $this->getContentService()->createContent( $imageCreateStruct, array( $locationCreateStruct ) );
        $imageContent = $this->getContentService()->publishVersion( $imageDraft->versionInfo );

        return $locationService->loadLocation( $imageContent->contentInfo->mainLocationId );
    }

    /**
     * @return \eZ\Publish\API\Repository\Repository
     */
    protected function getRepository()
    {
        if ( !isset( $this->repository ) )
        {
            $this->repository = $this->getContainer()->get( 'ezpublish.api.repository' );
        }
        return $this->repository;
    }

    /**
     * @return \eZ\Publish\API\Repository\ContentService
     */
    protected function getContentService()
    {
        if ( !isset( $this->contentService ) )
        {
            $this->contentService = $this->getRepository()->getContentService();
        }
        return $this->contentService;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentType
     */
    protected function getContentType( $contentTypeIdentifier )
    {
        return $this->imageContentType = $this->getRepository()
            ->getContentTypeService()
            ->loadContentTypeByIdentifier( $contentTypeIdentifier );
    }

    protected function logInAsAdmin()
    {
        $this->getRepository()->setCurrentUser( $this->getRepository()->getUserService()->loadUser( 14 ) );
    }

    /** @var \eZ\Publish\API\Repository\Repository */
    protected $repository;

    /** @var \eZ\Publish\API\Repository\ContentService */
    protected $contentService;

    /** @var \eZ\Publish\API\Repository\Values\ContentType\ContentType */
    protected $imageContentType;
}
