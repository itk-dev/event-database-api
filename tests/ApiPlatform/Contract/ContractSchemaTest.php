<?php

namespace App\Tests\ApiPlatform\Contract;

use App\Tests\ApiPlatform\AbstractApiTestCase;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Validate the deep payload of every resource — nested objects and their field
 * types — against a hand-authored JSON Schema (tests/schemas/contract.schema.json).
 *
 * This is the delta over CollectionContractTest, which only pins the top-level
 * member field set. Here a removed/renamed nested field (e.g. organizer.email)
 * or a changed type fails, while adding a field passes (the schema is
 * additive-tolerant). Together with the OpenAPI diff gate this is what protects
 * consumers through the API Platform upgrade — API Platform's self-generated
 * item/collection schema is near-empty (@id/@var/@context only), so it cannot.
 *
 * D6: the five "nested" resources return a hydra:Collection with a single
 * member on their item endpoint, so both collection and item members are
 * validated against the same definition; Tag/Vocabulary return true items.
 */
class ContractSchemaTest extends AbstractApiTestCase
{
    private const SCHEMA_URI = 'internal://contract.schema.json';
    private const SCHEMA_PATH = __DIR__.'/../../schemas/contract.schema.json';

    #[DataProvider('collectionProvider')]
    public function testCollectionMembersMatchSchema(string $path, string $definition): void
    {
        $data = $this->get([], $path)->toArray();
        self::assertNotEmpty($data['hydra:member'], $path.' needs at least one fixture member');

        foreach ($data['hydra:member'] as $i => $member) {
            $this->assertMatchesDefinition($member, $definition, $path.' member #'.$i);
        }
    }

    #[DataProvider('itemProvider')]
    public function testItemMatchesSchema(string $path, string $definition, bool $collectionWrapped): void
    {
        $data = $this->get([], $path)->toArray();

        if ($collectionWrapped) {
            self::assertArrayHasKey('hydra:member', $data, $path.' should return a collection wrapper (D6)');
            self::assertNotEmpty($data['hydra:member']);
            foreach ($data['hydra:member'] as $member) {
                $this->assertMatchesDefinition($member, $definition, $path.' item member');
            }

            return;
        }

        $this->assertMatchesDefinition($data, $definition, $path.' item');
    }

    public static function collectionProvider(): iterable
    {
        yield 'events' => ['/api/v2/events', 'event'];
        yield 'occurrences' => ['/api/v2/occurrences', 'occurrence'];
        yield 'daily_occurrences' => ['/api/v2/daily_occurrences', 'occurrence'];
        yield 'locations' => ['/api/v2/locations', 'location'];
        yield 'organizations' => ['/api/v2/organizations', 'organization'];
        yield 'tags' => ['/api/v2/tags', 'tag'];
        yield 'vocabularies' => ['/api/v2/vocabularies', 'vocabulary'];
    }

    public static function itemProvider(): iterable
    {
        // [path, definition, collectionWrapped]
        yield 'events' => ['/api/v2/events/7', 'event', true];
        yield 'occurrences' => ['/api/v2/occurrences/10', 'occurrence', true];
        yield 'daily_occurrences' => ['/api/v2/daily_occurrences/10', 'occurrence', true];
        yield 'locations' => ['/api/v2/locations/4', 'location', true];
        yield 'organizations' => ['/api/v2/organizations/9', 'organization', true];
        yield 'tags' => ['/api/v2/tags/aros', 'tag', false];
        yield 'vocabularies' => ['/api/v2/vocabularies/aarhusguiden', 'vocabulary', false];
    }

    private function assertMatchesDefinition(array $payload, string $definition, string $message): void
    {
        $storage = new SchemaStorage();
        $schema = json_decode((string) file_get_contents(self::SCHEMA_PATH), false, 512, JSON_THROW_ON_ERROR);
        $storage->addSchema(self::SCHEMA_URI, $schema);

        $validator = new Validator(new Factory($storage));

        // Re-decode as stdClass objects so json-schema distinguishes objects from lists.
        $data = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $validator->validate($data, (object) ['$ref' => self::SCHEMA_URI.'#/definitions/'.$definition]);

        self::assertTrue(
            $validator->isValid(),
            $message.' failed contract schema "'.$definition.'": '.self::formatErrors($validator->getErrors())
        );
    }

    /**
     * @param array<int, array{property?: string, message?: string}> $errors
     */
    private static function formatErrors(array $errors): string
    {
        return implode('; ', array_map(
            static fn (array $e) => trim(($e['property'] ?? '').' '.($e['message'] ?? '')),
            $errors
        ));
    }
}
