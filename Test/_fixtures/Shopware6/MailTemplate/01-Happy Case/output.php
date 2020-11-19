<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'id' => 'fee20daa2f2a45178c808f2f69b686d4',
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
            'mailTemplateId' => 'fee20daa2f2a45178c808f2f69b686d4',
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
        [
            'mailTemplateId' => 'fee20daa2f2a45178c808f2f69b686d4',
            'senderName' => '{{ shopName }}',
            'description' => '',
            'subject' => 'Password Wiederherstellung',
            'contentHtml' => '

        Hallo {{ customerRecovery.customer.firstName }} {{ customerRecovery.customer.lastName }},

        Sie haben ein neues Passwort für Ihren {{ shopName }}-Account angefordert.
        Klicken Sie auf folgenden Link, um Ihr Passwort zurückzusetzen:

        {{ resetUrl }}

        Dieser Link ist für die nächsten 2 Stunden gültig.
        Falls Sie Ihr Passwort nicht zurücksetzen möchten, ignorieren Sie diese E-Mail - es wird dann keine Änderung vorgenommen.

        Mit freundlichen Grüßen
        Ihr {{ shopName }}-Team

',
            'contentPlain' => '        Hallo {{ customerRecovery.customer.firstName }} {{ customerRecovery.customer.lastName }},

        Sie haben ein neues Passwort für Ihren {{ shopName }}-Account angefordert.
        Klicken Sie auf folgenden Link, um Ihr Passwort zurückzusetzen:

        {{ resetUrl }}

        Dieser Link ist für die nächsten 2 Stunden gültig.
        Falls Sie Ihr Passwort nicht zurücksetzen möchten, ignorieren Sie diese E-Mail - es wird dann keine Änderung vorgenommen.

        Mit freundlichen Grüßen
        Ihr {{ shopName }}-Team',
            'languageId' => '60d637353d044752ae6a8c6e7f53430b',
        ],
    ],
];
