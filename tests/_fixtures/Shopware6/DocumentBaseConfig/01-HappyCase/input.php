<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'name' => 'Testtype',
    'filenamePrefix' => 'abc',
    'filenameSuffix' => 'def',
    'documentNumber' => '',
    'global' => false,
    'config' => [
        'pageOrientation' => 'portrait',
        'pageSize' => 'letter',
        'displayHeader' => true,
        'displayFooter' => true,
        'displayLineItems' => true,
        'displayPageCount' => true,
        'displayPrices' => true,
        'displayLineItemPosition' => true,
        'itemsPerPage' => '5',
        'displayCompanyAddress' => true,
        'companyAddress' => 'a',
        'companyName' => 'b',
        'companyEmail' => 'c@d.ef',
        'companyUrl' => 'g',
        'taxNumber' => 'h',
        'taxOffice' => 'i',
        'vatId' => 'j',
        'bankName' => 'k',
        'bankIban' => 'l',
        'bankBic' => 'm',
        'placeOfJurisdiction' => 'n',
        'placeOfFulfillment' => 'o',
        'executiveDirector' => 'p',
    ],
    'salesChannels' => [
        [
            'id' => '3e447eb526c44d6a8d5397581ba2eb31',
            'salesChannelId' => '98432def39fc4624b33213a56b8c944d',
            'documentType' => [
                'name' => 'Delivery note',
                'technicalName' => 'delivery_note',
                'id' => '3292943c32e5499f9a54cc5ff1a16abe',
            ],
        ],
        [
            'id' => 'a62d1774ec7c412cae65f4a5a0cee9cc',
            'salesChannelId' => '3f05f1f6514d43f7ae11a669c7557d1c',
            'documentType' => [
                'name' => 'Delivery note',
                'technicalName' => 'delivery_note',
                'id' => '3292943c32e5499f9a54cc5ff1a16abe',
            ],
        ],
    ],
    'documentType' => [
        'name' => 'Delivery note',
        'technicalName' => 'delivery_note',
        'id' => '3292943c32e5499f9a54cc5ff1a16abe',
    ],
    'logo' => [
        'uploadedAt' => '2020-11-17T08:35:16.027+00:00',
        'fileName' => 'demostore-logo',
        'url' => 'http://nextsupport.local/media/74/58/cd/1605193590/722be997c723e7ec4e8cc6975f5d2ae1.jpg',
        'fileSize' => 100,
        'mediaFolderId' => '6da8ff8a64144e06b9cd2cb3567b5b98',
        'private' => false,
        'id' => '4db51565a90544faab4399fed2c46468',
    ],
    'id' => '285c94d513214fbdafd2b2ba84d630e4',
];
