<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'id' => 'fee20daa2f2a45178c808f2f69b686d4',
    'mailTemplateTypeId' => '5dd637353d044752ae6a8c6e7f53430b',
    'media' => [
        [
            'position' => 1,
            'media' => [
                'uploadedAt' => '2020-11-06T07:54:21.173+00:00',
                'fileName' => 'waschmaschine_600x600',
                'mediaFolderId' => 'd4641502e1f34c5193d74d2e408bbed4',
                'hasFile' => false,
                'private' => false,
                'translations' => [
                    [
                        'languageId' => '5dd637353d044752ae6a8c6e7f53430b',
                        'title' => 'Nice title',
                        'alt' => 'Nice alt',
                    ],
                    [
                        'languageId' => '60d637353d044752ae6a8c6e7f53430b',
                        'title' => 'Another title',
                        'alt' => 'Another alt',
                    ],
                ],
                'tags' => [
                    [
                        'name' => 'Tag!',
                        'id' => 'cfdbb85022314529a1e4caa84829e1b8',
                    ],
                ],
                'id' => '84356a71233d4b3e9f417dcc8850c82f',
            ],
            'id' => 'eb5483a9c77c4919b5d110e8d745a1cc',
        ],
    ],
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
        [
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
