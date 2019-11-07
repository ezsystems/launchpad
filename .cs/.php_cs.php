<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()->in('src');//->in('tests');

return PhpCsFixer\Config::create()
                        ->setRules(
                            [
                                '@Symfony' => true,
                                'binary_operator_spaces' => [
                                    'align_equals' => false,
                                    'align_double_arrow' => false,
                                ],
                                'array_syntax' => ['syntax' => 'short'],
                                'pre_increment' => false,
                                'ordered_imports' => true,
                                'phpdoc_order' => true,
                                'linebreak_after_opening_tag' => true,
                                'phpdoc_no_package' => false,
                                'phpdoc_inline_tag' => false,
                                'cast_spaces' => false,
                                'no_superfluous_phpdoc_tags' => true,
                            ]
                        )
                        ->setFinder($finder);
