<?php
namespace eZ\UnconBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class EmptyFolderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'ez:uncon:empty_folder' )->setDefinition(
            array(
                new InputArgument( 'folder-location-id', InputArgument::REQUIRED, 'Location ID of a folder' )
            )
        );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $folderLocationId = $input->getArgument( 'folder-location-id' );

        $this->logInAsAdmin();

        $repository = $this->getContainer()->get( 'ezpublish.api.repository' );
        $locationService = $repository->getLocationService();
        try
        {
            $folderLocation = $locationService->loadLocation( $folderLocationId );
        }
        catch ( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e )
        {
            $output->writeln( $e->getMessage() );
        }

        $folderChildrenList = $locationService->loadLocationChildren( $folderLocation );

        if ( $folderChildrenList->totalCount == 0 )
        {
            $output->writeln( "Folder is empty" );
            return;
        }

        /** @var $location \eZ\Publish\API\Repository\Values\Content\Location */
        foreach ( $folderChildrenList->locations as $location )
        {
            $output->writeln( "Removing '" . $location->contentInfo->name . "'" );
            $locationService->deleteLocation( $location );
        }
    }

    protected function logInAsAdmin()
    {
        $repository = $this->getContainer()->get( 'ezpublish.api.repository' );
        $repository->setCurrentUser(
            $repository->getUserService()->loadUser( 14 )
        );
    }
}
