<?php

declare(strict_types=1);

use MoonShine\Laravel\Contracts\Fields\HasAsyncSearchContract;
use MoonShine\Laravel\Contracts\Fields\HasRelatedValuesContact;
use MoonShine\Laravel\Fields\Relationships\ModelRelationField;
use MoonShine\Laravel\Fields\Relationships\MorphTo;
use MoonShine\Support\DTOs\Select\Option;
use MoonShine\Support\DTOs\Select\OptionProperty;
use MoonShine\Support\DTOs\Select\Options;
use MoonShine\Tests\Fixtures\Models\Category;
use MoonShine\Tests\Fixtures\Models\ImageModel;
use MoonShine\Tests\Fixtures\Models\Item;
use MoonShine\Tests\Fixtures\Resources\TestImageResource;
use MoonShine\UI\Contracts\DefaultValueTypes\CanBeObject;
use MoonShine\UI\Contracts\HasDefaultValueContract;

uses()->group('model-relation-fields');
uses()->group('morph-field');

beforeEach(function (): void {
    $this->item = Item::factory()->createOne();
    $this->image = ImageModel::create([
        'imageable_id' => $this->item->getKey(),
        'imageable_type' => Item::class,
    ]);

    $this->field = MorphTo::make('Imageable', resource: TestImageResource::class)
        ->fillData($this->item)
        ->types([
            Item::class => 'name',
            Category::class => 'name',
        ]);
});

describe('common field methods', function () {
    it('ModelRelationField is parent', function (): void {
        expect($this->field)
            ->toBeInstanceOf(ModelRelationField::class);
    });

    it('type', function (): void {
        expect($this->field->getAttributes()->get('type'))
            ->toBeEmpty();
    });

    it('correct interfaces', function (): void {
        expect($this->field)
            ->toBeInstanceOf(HasAsyncSearchContract::class)
            ->toBeInstanceOf(HasRelatedValuesContact::class)
            ->toBeInstanceOf(HasDefaultValueContract::class)
            ->toBeInstanceOf(CanBeObject::class);
    });
});

describe('unique field methods', function () {
    it('async search', function (): void {
        expect($this->field->asyncSearch('name'))
            ->isAsyncSearch()
            ->toBeTrue()
            ->getAsyncSearchColumn()
            ->toBe('name');
    });

    it('types', function (): void {
        expect($this->field)
            ->getSearchColumn(Item::class)
            ->toBe('name')
            ->and($this->field->getTypes()->toArray())
            ->toBe((new Options([
                Item::class => new Option('Item', Item::class, selected: true, properties: new OptionProperty(null)),
                Category::class => 'Category',
            ]))->toArray())
            ->and($this->field)
            ->getMorphType()
            ->toBe('imageable_type')
            ->getMorphKey()
            ->toBe('imageable_id');
    });
});

describe('basic methods', function () {
    it('change preview', function () {
        expect($this->field->changePreview(static fn () => 'changed'))
            ->preview()
            ->toBe('changed');
    });

    it('formatted value', function () {
        $field = MorphTo::make('Imageable', formatted: static fn () => ['changed'], resource: TestImageResource::class)
            ->fillData($this->item);

        expect($field->toFormattedValue())
            ->toBe(['changed']);
    });

    it('applies', function () {
        expect()
            ->applies($this->field);
    });
});
