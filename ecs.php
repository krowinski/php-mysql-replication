<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ReturnToYieldFromFixer;
use PhpCsFixer\Fixer\ArrayNotation\YieldFromArrayToYieldsFixer;
use PhpCsFixer\Fixer\Casing\NativeTypeDeclarationCasingFixer;
use PhpCsFixer\Fixer\FunctionNotation\PhpdocToParamTypeFixer;
use PhpCsFixer\Fixer\FunctionNotation\PhpdocToPropertyTypeFixer;
use PhpCsFixer\Fixer\FunctionNotation\PhpdocToReturnTypeFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAddMissingParamAnnotationFixer;
use PhpCsFixer\Fixer\Whitespace\TypeDeclarationSpacesFixer;
use PhpCsFixerCustomFixers\Fixer\NoLeadingSlashInGlobalNamespaceFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocNoSuperfluousParamFixer;
use PhpCsFixerCustomFixers\Fixer\PromotedConstructorPropertyFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->parallel();
    $ecsConfig->sets([
        SetList::PSR_12,
        SetList::CLEAN_CODE,
        SetList::STRICT,
        SetList::ARRAY,
        SetList::PHPUNIT,
        SetList::DOCTRINE_ANNOTATIONS,
        SetList::COMMENTS,
        SetList::SYMPLIFY,
        SetList::CONTROL_STRUCTURES,
    ]);

    $ecsConfig->rules([
        NativeTypeDeclarationCasingFixer::class,
        ReturnToYieldFromFixer::class,
        TypeDeclarationSpacesFixer::class,
        YieldFromArrayToYieldsFixer::class,
        PhpdocToPropertyTypeFixer::class,
        PhpdocToParamTypeFixer::class,
        PhpdocToReturnTypeFixer::class,
        PromotedConstructorPropertyFixer::class,
        NoLeadingSlashInGlobalNamespaceFixer::class,
        PhpdocNoSuperfluousParamFixer::class,
        PhpdocAddMissingParamAnnotationFixer::class,
    ]);

    $ecsConfig->fileExtensions(['php']);
    $ecsConfig->cacheDirectory('.cache/ecs');
    $ecsConfig->paths([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/example',]);
};
