<?php

namespace Tests\Integration\Schema;

use Tests\DBTestCase;
use Tests\Utils\Models\User;
use Nuwave\Lighthouse\Execution\Utils\GlobalId;

class NodeInterfaceTest extends DBTestCase
{
    /**
     * @var mixed[]
     */
    protected $testTuples = [
        1 => [
            'id' => 1,
            'name' => 'foobar',
        ],
        2 => [
            'id' => 2,
            'name' => 'barbaz',
        ],
    ];

    /**
     * @test
     */
    public function itCanResolveNodes(): void
    {
        $this->schema = '
        type User @node(resolver: "Tests\\\Integration\\\Schema\\\NodeInterfaceTest@resolveNode") {
            name: String!
        }
        '.$this->placeholderQuery();

        $firstGlobalId = GlobalId::encode('User', $this->testTuples[1]['id']);
        $secondGlobalId = GlobalId::encode('User', $this->testTuples[2]['id']);

        $this->query('
        {
            first: node(id: "'.$firstGlobalId.'") {
                id
                ...on User {
                    name
                }
            }
            second: node(id: "'.$secondGlobalId.'") {
                id
                ...on User {
                    name
                }
            }
        }
        ')->assertExactJson([
            'data' => [
                'first' => [
                    'id' => $firstGlobalId,
                    'name' => $this->testTuples[1]['name'],
                ],
                'second' => [
                    'id' => $secondGlobalId,
                    'name' => $this->testTuples[2]['name'],
                ],
            ]
        ]);
    }

    /**
     * @param  int  $id
     * @return mixed[]
     */
    public function resolveNode(int $id): array
    {
        return $this->testTuples[$id];
    }

    /**
     * @test
     */
    public function itCanResolveModelsNodes(): void
    {
        $this->schema = '
        type User @model {
            name: String!
        }
        '.$this->placeholderQuery();

        $user = factory(User::class)->create(
            ['name' => 'Sepp']
        );
        $globalId = GlobalId::encode('User', $user->getKey());

        $this->query('
        {
            node(id: "'.$globalId.'") {
                id
                ...on User {
                    name
                }
            }
        }
        ')->assertExactJson([
            'data' => [
                'node' => [
                    'id' => $globalId,
                    'name' => 'Sepp',
                ],
            ]
        ]);
    }
}
