<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'id' => '4e36dd2c6d3b4ab792c8f8dd90db462e',
    'mailTemplateTypeId' => '5dd637353d044752ae6a8c6e7f53430b',
    'systemDefault' => true,
    'senderName' => '{{ shopName }}',
    'description' => '',
    'subject' => 'Password recovery',
    'contentHtml' => '

        Hello {{ customerRecovery.customer.firstName }} {{ customerRecovery.customer.lastName }},

        You have requested a new password for your {{ shopName }} account.
        Click on the following link to reset your password:

        {{ resetUrl }}

        This link is valid for the next 2 hours.
        If you don\'t want to reset your password, ignore this email and no changes will be made.

        Yours sincerely
        Your {{ shopName }} team

',
    'contentPlain' => '        Hello {{ customerRecovery.customer.firstName }} {{ customerRecovery.customer.lastName }},

        You have requested a new password for your {{ shopName }} account.
        Click on the following link to reset your password:

        {{ resetUrl }}

        This link is valid for the next 2 hours.
        If you don\'t want to reset your password, ignore this email and no changes will be made.

        Yours sincerely
        Your {{ shopName }}-Team',
    'translations' => [
        [
            'senderName' => '{{ shopName }}',
            'description' => '',
            'subject' => 'Password recovery',
            'contentHtml' => '

        Hello {{ customerRecovery.customer.firstName }} {{ customerRecovery.customer.lastName }},

        You have requested a new password for your {{ shopName }} account.
        Click on the following link to reset your password:

        {{ resetUrl }}

        This link is valid for the next 2 hours.
        If you don\'t want to reset your password, ignore this email and no changes will be made.

        Yours sincerely
        Your {{ shopName }} team

',
            'contentPlain' => '        Hello {{ customerRecovery.customer.firstName }} {{ customerRecovery.customer.lastName }},

        You have requested a new password for your {{ shopName }} account.
        Click on the following link to reset your password:

        {{ resetUrl }}

        This link is valid for the next 2 hours.
        If you don\'t want to reset your password, ignore this email and no changes will be made.

        Yours sincerely
        Your {{ shopName }}-Team',
            'languageId' => '5dd637353d044752ae6a8c6e7f53430b',
        ],
    ],
];
