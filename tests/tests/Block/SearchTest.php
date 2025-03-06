<?php

namespace Concrete\Tests\Block;

use BlockType;
use Concrete\Core\Attribute\Key\Category as AttributeCategory;
use Concrete\Block\Search\Controller;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Http\Request;
use Concrete\Core\Page\Page;
use Concrete\TestHelpers\Block\BlockTypeTestCase;

class SearchTest extends BlockTypeTestCase
{
    protected $btHandle = 'search';

    protected $requestData = [
        'lipsum' => [
            'title' => 'Lorem ipsum dolor sit amet',
            'buttonText' => 'Search',
        ],
    ];

    protected $expectedRecordData = [
        'lipsum' => [
            'title' => 'Lorem ipsum dolor sit amet',
            'buttonText' => 'Search',
            'baseSearchPath' => '',
            'search_all' => 0,
            'allow_user_options' => 0,
            'postTo_cID' => 0,
            'resultsURL' => '',
        ],
    ];

    /** @var Request */
    private $origRequest;

    public static function setUpBeforeClass():void
    {
        parent::setUpBeforeClass();

        AttributeCategory::add('collection');
    }

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->tables[] = 'btSearch';
        $this->tables[] = 'CollectionVersions';
        $this->tables[] = 'CollectionSearchIndexAttributes';
        $this->tables[] = 'Pages';
        $this->tables[] = 'PageTypes';
        $this->tables[] = 'PageSearchIndex';
        $this->tables[] = 'PermissionAccessEntityTypes';
        $this->tables[] = 'PermissionKeys';
        $this->tables[] = 'PermissionKeyCategories';
        $this->tables[] = 'PagePermissionAssignments';
        $this->tables[] = 'BlockPermissionAssignments';

        $this->metadatas[] = 'Concrete\Core\Entity\Attribute\Category';
        $this->metadatas[] = 'Concrete\Core\Entity\Attribute\Key\Key';
        $this->metadatas[] = 'Concrete\Core\Entity\Attribute\Value\Value';
        $this->metadatas[] = 'Concrete\Core\Entity\Attribute\Key\PageKey';
        $this->metadatas[] = 'Concrete\Core\Entity\Attribute\Value\PageValue';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->origRequest = Request::getInstance();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Request::setInstance($this->origRequest);
    }

    public function testSearchWithUnexpectedQuery(): void
    {
        // Tests only that the code does not cause any errors/exceptions
        $this->expectNotToPerformAssertions();

        $this->runSearch(['query' => ['foo' => 'bar']]);
    }

    public function testSearchWithUnexpectedSearchPath(): void
    {
        // Tests only that the code does not cause any errors/exceptions
        $this->expectNotToPerformAssertions();

        $this->runSearch([
            'query' => 'test',
            'search_paths' => [['foo' => 'bar']]
        ]);
    }

    public function testSearchWithMultipleSearchPaths(): void
    {
        // Tests only that the code does not cause any errors/exceptions
        $this->expectNotToPerformAssertions();

        $this->runSearch([
            'query' => 'test',
            'search_paths' => ['/foo', '/bar']
        ]);
    }

    private function runSearch($params): void
    {
        $request = new Request($params);
        $request->setCurrentPage(Page::getByID(1));
        Request::setInstance($request);

        $btc = $this->getBlockController();

        $btc->view();
    }

    private function getBlockController(): Controller
    {
        $bt = BlockType::installBlockType($this->btHandle);
        $btx = BlockType::getByID(1);
        $class = $btx->getBlockTypeClass();
        $btc = new $class();
        $btc->setApplication(Application::getFacadeApplication());

        return $btc;
    }
}
