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

    /**
     * MutationType constructor.
     * @param \AlanKent\GraphQL\Types\TypeRegistry $typeRegistry
     */
    public function __construct(
        TypeRegistry $typeRegistry
    ) {
        $this->typeRegistry = $typeRegistry;

        $config = [
            'name' => 'Mutation',
            'description' => 'Mutation class for all mutation methods.',
            'fields' => [
                'hello' => [
                    'type' => Type::string(),
                    'description' => 'Returns a simple greeting (Hello World!) message.',
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
                        return $this->changePassword($context, $args['oldPassword'], $args['newPassword']);
                    },
                ],
            ],
        ];

        parent::__construct($config);
    }

    /**
     * Implementation of changePassword GraphQL request.
     */
    private function changePassword(Context $context, string $oldPassword, string $newPassword) {

        // TODO: Much of this was copied from EditPost.php. Feels redundant to copy all this code here.

        // By not using the class constructor, this lazily loads these interfaces when this specific method is called.
        // Adding them to the constructor will load up everything even when not needed.
        // (Another approach is to put this method in its own class so we can use a factory to create the class on demand.)
        /**@var ManagerInterface */
        $eventManager = $context->getServiceContract(ManagerInterface::class);
        /**@var Session */
        $session = $context->getServiceContract(Session::class);
        /** @var CustomerRepositoryInterface */
        $customerRepository = $context->getServiceContract(CustomerInterface::class);
        /** @var AuthenticationInterface */
        $authentication = $context->getServiceContract(AuthenticationInterface::class);
        /** @var AccountManagementInterface */
        $accountManagement = $context->getServiceContract(AccountManagementInterface::class);
        /** @var \Magento\Customer\Model\EmailNotificationInterface */
        $emailNotification = $context->getServiceContract(EmailNotificationInterface::class);

        try {

            if ($session->getCustomerId() == null) {
                return new StatusValue(false, __('You are not currently logged in.'));
            }

            /** @var CustomerInterface $currentCustomerDataObject */
            $currentCustomerDataObject = $customerRepository->getById($session->getCustomerId());

            // Throws exception if old password is not correct
            // TODO: Do we need this? changePassword() service contract takes old password as well...
            $authentication->authenticate($currentCustomerDataObject->getId(), $oldPassword);
            if ($newPassword === $oldPassword) {
                return new StatusValue(true, __('Password is unchanged.'));
            }

            // Call service contract to change password.
            $accountManagement->changePassword($currentCustomerDataObject->getEmail(), $oldPassword, $newPassword);

            // Send email that password was changed.
            $emailNotification->credentialsChanged(
                $currentCustomerDataObject,
                $currentCustomerDataObject->getEmail(),
                true
            );

            // Notify watchers that account was changed.
            // TODO: This was only a password change - is this event needed in this case?
            $eventManager->dispatch(
                'customer_account_edited',
                ['email' => $currentCustomerDataObject->getEmail()]
            );

        } catch (\Exception $exception) {
            return new StatusValue(false, $exception->getMessage());
        }

        return new StatusValue(true, __('Password changed successfully.'));
    }
}
