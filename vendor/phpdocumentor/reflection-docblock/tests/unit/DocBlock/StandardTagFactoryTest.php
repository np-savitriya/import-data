<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link http://phpdoc.org
 */

namespace phpDocumentor\Reflection\DocBlock;

use Mockery as m;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter\PassthroughFormatter;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\See;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \phpDocumentor\Reflection\DocBlock\StandardTagFactory
 * @covers ::<private>
 */
class StandardTagFactoryTest extends TestCase
{
    /**
     * Call Mockery::close after each test.
     */
    public function tearDown() : void
    {
        m::close();
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     * @uses \phpDocumentor\Reflection\DocBlock\Tags\Generic
     * @uses \phpDocumentor\Reflection\DocBlock\Tags\BaseTag
     * @uses \phpDocumentor\Reflection\DocBlock\Description
     *
     * @covers ::__construct
     * @covers ::create
     */
    public function testCreatingAGenericTag() : void
    {
        $expectedTagName         = 'unknown-tag';
        $expectedDescriptionText = 'This is a description';
        $expectedDescription     = new Description($expectedDescriptionText);
        $context                 = new Context('');

        $descriptionFactory = m::mock(DescriptionFactory::class);
        $descriptionFactory
            ->shouldReceive('create')
            ->once()
            ->with($expectedDescriptionText, $context)
            ->andReturn($expectedDescription);

        $tagFactory = new StandardTagFactory(m::mock(FqsenResolver::class));
        $tagFactory->addService($descriptionFactory, DescriptionFactory::class);

        /** @var Generic $tag */
        $tag = $tagFactory->create('@' . $expectedTagName . ' This is a description', $context);

        $this->assertInstanceOf(Generic::class, $tag);
        $this->assertSame($expectedTagName, $tag->getName());
        $this->assertSame($expectedDescription, $tag->getDescription());
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     * @uses \phpDocumentor\Reflection\DocBlock\Tags\Author
     * @uses \phpDocumentor\Reflection\DocBlock\Tags\BaseTag
     *
     * @covers ::__construct
     * @covers ::create
     */
    public function testCreatingASpecificTag() : void
    {
        $context    = new Context('');
        $tagFactory = new StandardTagFactory(m::mock(FqsenResolver::class));

        /** @var Author $tag */
        $tag = $tagFactory->create('@author Mike van Riel <me@mikevanriel.com>', $context);

        $this->assertInstanceOf(Author::class, $tag);
        $this->assertSame('author', $tag->getName());
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     * @uses \phpDocumentor\Reflection\DocBlock\Tags\See
     * @uses \phpDocumentor\Reflection\DocBlock\Tags\BaseTag
     * @uses \phpDocumentor\Reflection\Fqsen
     * @uses \phpDocumentor\Reflection\DocBlock\Tags\Reference\Fqsen
     *
     * @covers ::__construct
     * @covers ::create
     */
    public function testAnEmptyContextIsCreatedIfNoneIsProvided() : void
    {
        $fqsen              = '\Tag';
        $resolver           = m::mock(FqsenResolver::class)
            ->shouldReceive('resolve')
            ->with('Tag', m::type(Context::class))
            ->andReturn(new Fqsen($fqsen))
            ->getMock();
        $descriptionFactory = m::mock(DescriptionFactory::class);
        $descriptionFactory->shouldIgnoreMissing();

        $tagFactory = new StandardTagFactory($resolver);
        $tagFactory->addService($descriptionFactory, DescriptionFactory::class);

        /** @var See $tag */
        $tag = $tagFactory->create('@see Tag');

        $this->assertInstanceOf(See::class, $tag);
        $this->assertSame($fqsen, (string) $tag->getReference());
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     * @uses \phpDocumentor\Reflection\DocBlock\Tags\Author
     * @uses \phpDocumentor\Reflection\DocBlock\Tags\BaseTag
     *
     * @covers ::__construct
     * @covers ::create
     */
    public function testPassingYourOwnSetOfTagHandlers() : void
    {
        $context    = new Context('');
        $tagFactory = new StandardTagFactory(m::mock(FqsenResolver::class), ['user' => Author::class]);

        /** @var Author $tag */
        $tag = $tagFactory->create('@user Mike van Riel <me@mikevanriel.com>', $context);

        $this->assertInstanceOf(Author::class, $tag);
        $this->assertSame('author', $tag->getName());
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::__construct
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     *
     * @covers ::create
     */
    public function testExceptionIsThrownIfProvidedTagIsNotWellformed() : void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'The tag "@user[myuser" does not seem to be wellformed, please check it for errors'
        );
        $tagFactory = new StandardTagFactory(m::mock(FqsenResolver::class));
        $tagFactory->create('@user[myuser');
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     *
     * @covers ::__construct
     * @covers ::addParameter
     */
    public function testAddParameterToServiceLocator() : void
    {
        $resolver   = m::mock(FqsenResolver::class);
        $tagFactory = new StandardTagFactory($resolver);
        $tagFactory->addParameter('myParam', 'myValue');

        $this->assertAttributeSame(
            [FqsenResolver::class => $resolver, 'myParam' => 'myValue'],
            'serviceLocator',
            $tagFactory
        );
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::__construct
     *
     * @covers ::addService
     */
    public function testAddServiceToServiceLocator() : void
    {
        $service = new PassthroughFormatter();

        $resolver   = m::mock(FqsenResolver::class);
        $tagFactory = new StandardTagFactory($resolver);
        $tagFactory->addService($service);

        $this->assertAttributeSame(
            [FqsenResolver::class => $resolver, PassthroughFormatter::class => $service],
            'serviceLocator',
            $tagFactory
        );
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::__construct
     *
     * @covers ::addService
     */
    public function testInjectConcreteServiceForInterfaceToServiceLocator() : void
    {
        $interfaceName = Formatter::class;
        $service       = new PassthroughFormatter();

        $resolver   = m::mock(FqsenResolver::class);
        $tagFactory = new StandardTagFactory($resolver);
        $tagFactory->addService($service, $interfaceName);

        $this->assertAttributeSame(
            [FqsenResolver::class => $resolver, $interfaceName => $service],
            'serviceLocator',
            $tagFactory
        );
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::__construct
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::create
     * @uses \phpDocumentor\Reflection\DocBlock\Tags\Author
     *
     * @covers ::registerTagHandler
     */
    public function testRegisteringAHandlerForANewTag() : void
    {
        $resolver   = m::mock(FqsenResolver::class);
        $tagFactory = new StandardTagFactory($resolver);

        $tagFactory->registerTagHandler('my-tag', Author::class);

        // Assert by trying to create one
        $tag = $tagFactory->create('@my-tag Mike van Riel <me@mikevanriel.com>');
        $this->assertInstanceOf(Author::class, $tag);
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::__construct
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     *
     * @covers ::registerTagHandler
     */
    public function testHandlerRegistrationFailsIfProvidedTagNameIsNamespaceButNotFullyQualified() : void
    {
        $this->expectException('InvalidArgumentException');
        $resolver   = m::mock(FqsenResolver::class);
        $tagFactory = new StandardTagFactory($resolver);
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        $tagFactory->registerTagHandler(\Name\Spaced\Tag::class, Author::class);
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::__construct
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     *
     * @covers ::registerTagHandler
     */
    public function testHandlerRegistrationFailsIfProvidedHandlerIsEmpty() : void
    {
        $this->expectException('InvalidArgumentException');
        $resolver   = m::mock(FqsenResolver::class);
        $tagFactory = new StandardTagFactory($resolver);
        $tagFactory->registerTagHandler('my-tag', '');
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::__construct
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     *
     * @covers ::registerTagHandler
     */
    public function testHandlerRegistrationFailsIfProvidedHandlerIsNotAnExistingClassName() : void
    {
        $this->expectException('InvalidArgumentException');
        $resolver   = m::mock(FqsenResolver::class);
        $tagFactory = new StandardTagFactory($resolver);
        $tagFactory->registerTagHandler('my-tag', 'IDoNotExist');
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::__construct
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     *
     * @covers ::registerTagHandler
     */
    public function testHandlerRegistrationFailsIfProvidedHandlerDoesNotImplementTheTagInterface() : void
    {
        $this->expectException('InvalidArgumentException');
        $resolver   = m::mock(FqsenResolver::class);
        $tagFactory = new StandardTagFactory($resolver);
        $tagFactory->registerTagHandler('my-tag', 'stdClass');
    }

    /**
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::__construct
     * @uses \phpDocumentor\Reflection\DocBlock\StandardTagFactory::addService
     * @uses \phpDocumentor\Reflection\Docblock\Description
     * @uses \phpDocumentor\Reflection\Docblock\Tags\Return_
     * @uses \phpDocumentor\Reflection\Docblock\Tags\BaseTag
     *
     * @covers ::create
     */
    public function testReturnTagIsMappedCorrectly() : void
    {
        $context = new Context('');

        $descriptionFactory = m::mock(DescriptionFactory::class);
        $descriptionFactory
            ->shouldReceive('create')
            ->once()
            ->with('', $context)
            ->andReturn(new Description(''));

        $typeResolver = new TypeResolver();

        $tagFactory = new StandardTagFactory(m::mock(FqsenResolver::class));
        $tagFactory->addService($descriptionFactory, DescriptionFactory::class);
        $tagFactory->addService($typeResolver, TypeResolver::class);

        /** @var Return_ $tag */
        $tag = $tagFactory->create('@return mixed', $context);

        $this->assertInstanceOf(Return_::class, $tag);
        $this->assertSame('return', $tag->getName());
    }
}