<?php
namespace eZ\UnconBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreatePhoneNumberContentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'ezuncon:create_phone_number' );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        /** @var $repository \eZ\Publish\API\Repository\Repository */
        $repository = $this->getContainer()->get( 'ezpublish.api.repository' );
        $repository->setCurrentUser( $repository->getUserService()->loadUser( 14 ) );

        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier( 'phonenumber' ),
            'eng-GB'
        );

        $contentCreateStruct->setField( 'phonenumber', 'abcd' );
        print_r( $contentService->createContent( $contentCreateStruct ) );
    }
}
