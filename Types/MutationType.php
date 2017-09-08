<?php

namespace AlanKent\GraphQL\Types;

use AlanKent\GraphQL\App\Context;
use Braintree\Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\ManagerInterface;

/**
 * Entry point for all mutation methods of GraphQL endpoint.
 */
class MutationType extends ObjectType
{
    /** @var TypeRegistry */
    private $typeRegistry;

    /**@var ManagerInterface */
    private $eventManager;

    /**@var Session */
    private $session;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var AuthenticationInterface */
    private $authentication;

    /** @var AccountManagementInterface */
    private $accountManagement;

    /** @var \Magento\Customer\Model\EmailNotificationInterface */
    private $emailNotification;

    /**
     * MutationType constructor.
     * @param \AlanKent\GraphQL\Types\TypeRegistry $typeRegistry
     * @param Session $session
     * @param ManagerInterface $eventManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param AuthenticationInterface $authentication
     * @param AccountManagementInterface $accountManagement
     * @param EmailNotificationInterface $emailNotification
     */
    public function __construct(
        TypeRegistry $typeRegistry,
        Session $session,
        ManagerInterface $eventManager,
        CustomerRepositoryInterface $customerRepository,
        AuthenticationInterface $authentication,
        AccountManagementInterface $accountManagement,
        EmailNotificationInterface $emailNotification
    ) {
        $this->typeRegistry = $typeRegistry;
        $this->session = $session;
        $this->eventManager = $eventManager;
        $this->customerRepository = $customerRepository;
        $this->authentication = $authentication;
        $this->accountManagement = $accountManagement;
        $this->emailNotification = $emailNotification;

        $config = [
            'name' => 'Mutation',
            'description' => 'Mutation class for all mutation methods.',
            'fields' => [
                'hello' => [
                    'type' => Type::string(),
                    'description' => 'Returns a simple greeting (Hellow World!) message.',
                    'resolve' => function() {
                        return 'Your graphql-php endpoint is ready now! Use GraphiQL to browse API';
                    }
                ],
                'placeOrder' => [
                    'type' => $this->typeRegistry->makeOutputType('Order'), // TODO: Should be "Order!"
                    'description' => 'Place an order.',
                    'args' => [
                        'order' => [
                            'type' => $this->typeRegistry->makeInputType('Order!'),
                            'description' => 'The order to be placed.'
                        ]
                    ],
                    'resolve' => function($val, $args, $context, ResolveInfo $info) {
                        return null; // TODO
                    }
                ],
                'requestPasswordReset' => [
                    'type' => StatusType::singleton(),
                    'description' => 'Request a password reset',
                    'args' => [
                        'email' => [
                            'type' => Type::string(),
                            'description' => 'Email address of the customer to request the password reset for.'
                        ]
                    ],
                    'resolve' => function($val, $args, $context, ResolveInfo $info) {
                        /** @var Context $context */
                        /** @var AccountManagementInterface $ami */
                        $ami = $context->getServiceContract(AccountManagementInterface::class);
                        //$ami->TODO - not sure which method to call!
                        return new StatusValue(false, 'Reset method not implemented yet.');
                    }
                ],
                'changePassword' => [
                    'type' => StatusType::singleton(),
                    'description' => 'Request a password reset',
                    'args' => [
                        'oldPassword' => [
                            'type' => Type::nonNull(Type::string()),
                            'description' => "Current user's old password.",
                        ],
                        'newPassword' => [
                            'type' => Type::nonNull(Type::string()),
                            'description' => "New password to change password to.",
                        ],
                    ],
                    'resolve' => function($val, $args, $context, ResolveInfo $info) {
                        return $this->changePassword($args['oldPassword'], $args['newPassword']);
                    },
                ],
            ],
        ];

        parent::__construct($config);
    }

    /**
     * Implementation of changePassword GraphQL request.
     */
    private function changePassword(string $oldPassword, string $newPassword) {

        // TODO: Much of this was copied from EditPost.php. Feels redundant to copy all this code here.

        try {

            if ($this->session->getCustomerId() == null) {
                return new StatusValue(false, __('You are not currently logged in.'));
            }

            /** @var CustomerInterface $currentCustomerDataObject */
            $currentCustomerDataObject = $this->customerRepository->getById($this->session->getCustomerId());

            // Throws exception if old password is not correct
            // TODO: Do we need this? changePassword() service contract takes old password as well...
            $this->authentication->authenticate($currentCustomerDataObject->getId(), $oldPassword);
            if ($newPassword === $oldPassword) {
                return new StatusValue(true, __('Password is unchanged.'));
            }

            // Call service contract to change password.
            $this->accountManagement->changePassword($currentCustomerDataObject->getEmail(), $oldPassword, $newPassword);

            // Send email that password was changed.
            $this->emailNotification->credentialsChanged(
                $currentCustomerDataObject,
                $currentCustomerDataObject->getEmail(),
                true
            );

            // Notify watchers that account was changed.
            // TODO: This was only a password change - is this event needed in this case?
            $this->eventManager->dispatch(
                'customer_account_edited',
                ['email' => $currentCustomerDataObject->getEmail()]
            );

        } catch (\Exception $exception) {
            return new StatusValue(false, $exception->getMessage());
        }

        return new StatusValue(true, __('Password changed successfully.'));
    }
}
