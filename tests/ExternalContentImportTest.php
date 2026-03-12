<?php

namespace NZTA\ExternalContentApi\Tests;

use NZTA\ContentApi\ImportExport\ExternalContentImport;
use NZTA\ContentApi\Model\ExternalContent;
use NZTA\ContentApi\Model\ExternalContentApplication;
use NZTA\ContentApi\Model\ExternalContentArea;
use NZTA\ContentApi\Model\ExternalContentPage;
use NZTA\ContentApi\Model\ExternalContentType;
use SilverStripe\Dev\SapphireTest;

class ExternalContentImportTest extends SapphireTest
{
    protected static $fixture_file = 'Fixtures/ExternalContentImportTest.yml';

    private const CHECK_UPDATED_EXTERNALID = 'TestApp|TestApp-Area|TA-2001';

    private const IMPORT_FIXTURE = __DIR__ . '/Fixtures/Import.csv';

    public function testImport()
    {
        // TestApp Index page is pre-loaded via fixture to assert data is not duplicated.
        $this->assertCount(17, ExternalContent::get(), 'Unexpected number of Content items');
        $this->assertCount(1, ExternalContentApplication::get(), 'There should be only one Application');
        $this->assertCount(1, ExternalContentArea::get(), 'There should be only one Area');
        $this->assertCount(1, ExternalContentPage::get(), 'Only the Index page should be defined');
        $this->assertCount(2, ExternalContentType::get(), 'There are rich text and plain text Types only');

        $this->assertContains('Initial', $this->objFromFixture(ExternalContent::class, '1')->Content);

        $importer = new ExternalContentImport(ExternalContent::class);
        $importer->load(self::IMPORT_FIXTURE);

        // TestApp Index page is pre-loaded via fixture to assert data is not duplicated.
        $this->assertCount(51, ExternalContent::get(), 'Existing records should not be duplicated');
        $this->assertCount(2, ExternalContentApplication::get(), 'There should be only 2 applications');
        $this->assertCount(2, ExternalContentArea::get(), 'There should be only 2 areas');
        $this->assertCount(
            3,
            ExternalContentPage::get(),
            '"Index" page should not be reused between applications - it should be created once per application'
        );
        $this->assertCount(2, ExternalContentType::get(), 'Types are not created for rows missing a type entry');
        $this->assertCount(0, ExternalContentType::get()->filter('Name', [null, '']), 'Empty types do not exist');

        $this->assertContains(
            'Initial',
            ExternalContent::get()->find('ExternalID', self::CHECK_UPDATED_EXTERNALID)->Content,
            'The import should not update existing records'
        );
    }

    public function testImportWithDeleteFirst()
    {
        // $originalRecord a.k.a $this->objFromFixture(ExternalContent::class, '1')
        $originalRecord = ExternalContent::get()->find('ExternalID', self::CHECK_UPDATED_EXTERNALID);
        $importer = new ExternalContentImport(ExternalContent::class);
        $importer->deleteExistingRecords = true;
        $importer->load(self::IMPORT_FIXTURE);
        $updatedContent = ExternalContent::get()->find('ExternalID', self::CHECK_UPDATED_EXTERNALID);
        $this->assertNotEquals(
            $originalRecord->ID,
            $updatedContent->ID,
            'After deletion and recreation the same ExternalID should have a new ID for the DataObject'
        );
        $this->assertNotContains(
            'Initial',
            $updatedContent->Content,
            'The import should update existing records via deleting them first'
        );
    }

    public function testImportWithDeleteDeletesOnlyFromTheApplicationBeingImported()
    {
        $newApplication = new ExternalContentApplication(['Name' => 'ThirdApp']);
        $newApplicationId = $newApplication->write();
        $newArea = new ExternalContentArea(['Name' => 'ThirdApp-Area3', 'ApplicationID' => $newApplicationId]);
        $newAreaId = $newArea->write();
        $newPage = new ExternalContentPage(['Name' => 'Three', 'AreaID' => $newAreaId]);
        $newPageId = $newPage->write();
        $type = $this->objFromFixture(ExternalContentType::class, 'plaintext');
        $newContent = new ExternalContent([
            'ExternalID' => 'ThirdApp-hello',
            'TypeID' => $type->ID,
            'Content' => 'Hello!'
        ]);
        $newContentId = $newContent->write();
        $newContent->Pages()->add($newPage);

        $supplementaryContent = new ExternalContent([
            'ExternalID' => 'ShouldBeRemoved',
            'TypeID' => $type->ID,
            'Content' => 'This should get deleted'
        ]);

        $importer = new ExternalContentImport(ExternalContent::class);
        $importer->deleteExistingRecords = true;
        $importer->load(self::IMPORT_FIXTURE);

        $this->assertNull(ExternalContent::get()->find('ExternalID', 'ShouldBeRemoved'));

        $thirdApp = ExternalContentApplication::get()->find('Name', 'ThirdApp');
        $this->assertNotNull($thirdApp);
        $thirdAppAreas = $thirdApp->Areas();
        $this->assertCount(1, $thirdAppAreas);
        $thirdAppPages = $thirdAppAreas->first()->Pages();
        $this->assertCount(1, $thirdAppPages);
        $thirdAppContents = $thirdAppPages->first()->Contents();
        $this->assertCount(1, $thirdAppContents);
        $thirdAppContentItem = $thirdAppContents->first();
        $this->assertSame('ThirdApp-hello', $thirdAppContentItem->ExternalID);
        $this->assertSame('Hello!', $thirdAppContentItem->Content);
    }
}
