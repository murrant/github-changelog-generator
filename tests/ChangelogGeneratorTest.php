<?php

namespace ins0\GitHub;

use PHPUnit_Framework_TestCase;

class ChangelogGeneratorTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage No releases found for this repository
	 */
	public function testThrowsExceptionIfNoReleasesAreFound()
	{
		$mockRepository = $this->getMockRepository();
		
		$mockRepository->method('getReleases')->willReturn([]);

		$changelogGenerator = new ChangelogGenerator($mockRepository);
		$changelog = $changelogGenerator->generate();
	}

	public function testThatAReleaseWithNoIssuesGeneratesAnEmptyChangelog()
	{
		$mockRepository = $this->getMockRepositoryWithIssues([]);
		$changelogGenerator = new ChangelogGenerator($mockRepository);

		$this->assertEquals(
			$this->loadFile('output/release-with-no-sections.md'),
			$changelogGenerator->generate()
		);
	}

	public function testIssuesAreOnlyPinnedToReleaseTags()
	{
		$mockRepository = $this->getMockRepositoryWithIssues($this->loadFixtureData('issues'));
		$changelogGenerator = new ChangelogGenerator($mockRepository);

		$this->assertEquals(
			$this->loadFile('output/release-with-all-sections.md'),
			$changelogGenerator->generate()
		);
	}

	public function testGeneratesAChangedSectionUnderRelease()
	{
		// Only test issues 1 and 2 (tagged with 'enhancement')
		$issues = $this->loadFixtureData('issues');
		$mockRepository = $this->getMockRepositoryWithIssues([$issues[3], $issues[4]]);
		$changelogGenerator = new ChangelogGenerator($mockRepository);

		$this->assertEquals(
			$this->loadFile('output/release-with-changed-only-section.md'),
			$changelogGenerator->generate()
		);
	}

	public function testGeneratesAnAddedSectionUnderRelease()
	{
		// Only test issue 3 (tagged with 'feature')
		$mockRepository = $this->getMockRepositoryWithIssues([$this->loadFixtureData('issues')[2]]);
		$changelogGenerator = new ChangelogGenerator($mockRepository);

		$this->assertEquals(
			$this->loadFile('output/release-with-added-only-section.md'),
			$changelogGenerator->generate()
		);
	}

	public function testGeneratesAPullRequestsSectionUnderRelease()
	{
		// Only test issue 4 (no tags, marked as a 'pull request')
		$mockRepository = $this->getMockRepositoryWithIssues([$this->loadFixtureData('issues')[1]]);
		$changelogGenerator = new ChangelogGenerator($mockRepository);

		$this->assertEquals(
			$this->loadFile('output/release-with-pull-requests-only-section.md'),
			$changelogGenerator->generate()
		);
	}

	public function testGeneratesAFixedSectionUnderRelease()
	{
		// Only test issue 5 (tagged with 'bug')
		$mockRepository = $this->getMockRepositoryWithIssues([$this->loadFixtureData('issues')[0]]);
		$changelogGenerator = new ChangelogGenerator($mockRepository);

		$this->assertEquals(
			$this->loadFile('output/release-with-fixed-only-section.md'),
			$changelogGenerator->generate()
		);
	}

	public function testCanChooseCustomLabelForChangedSection()
	{
		$issues = $this->loadFixtureData('issues-with-custom-labels');
		$issueMappings = [ChangelogGenerator::LABEL_TYPE_CHANGED => ['CustomEnhancementLabel']];
		$mockRepository = $this->getMockRepositoryWithIssues([$issues[2], $issues[3]]);
		$changelogGenerator = new ChangelogGenerator($mockRepository, $issueMappings);

		$this->assertEquals(
			$this->loadFile('output/release-with-changed-only-section.md'),
			$changelogGenerator->generate()
		);
	}

	public function testCanChooseCustomLabelForAddedSection()
	{
		$issues = $this->loadFixtureData('issues-with-custom-labels');
		$issueMappings = [ChangelogGenerator::LABEL_TYPE_ADDED => ['CustomFeatureLabel']];
		$mockRepository = $this->getMockRepositoryWithIssues([$issues[1]]);
		$changelogGenerator = new ChangelogGenerator($mockRepository, $issueMappings);

		$this->assertEquals(
			$this->loadFile('output/release-with-added-only-section.md'),
			$changelogGenerator->generate()
		);
	}

	public function testCanChooseCustomLabelForFixedSection()
	{
		$issues = $this->loadFixtureData('issues-with-custom-labels');
		$issueMappings = [ChangelogGenerator::LABEL_TYPE_FIXED => ['CustomBugLabel']];
		$mockRepository = $this->getMockRepositoryWithIssues([$issues[0]]);
		$changelogGenerator = new ChangelogGenerator($mockRepository, $issueMappings);

		$this->assertEquals(
			$this->loadFile('output/release-with-fixed-only-section.md'),
			$changelogGenerator->generate()
		);
	}

	private function getMockRepositoryWithIssues(array $issues)
	{
		$mockRepository = $this->getMockRepository();

		$mockRepository->method('getReleases')->willReturn($this->loadFixtureData('releases'));
		$mockRepository->method('getIssues')->willReturn($issues);
		$mockRepository->method('getIssueEvents')->will($this->returnValueMap([
	    	[1, $this->loadFixtureData('issue1-events')],
            [2, $this->loadFixtureData('issue2-events')],
            [3, $this->loadFixtureData('issue3-events')],
            [4, $this->loadFixtureData('issue4-events')],
            [5, $this->loadFixtureData('issue5-events')]
        ]));

        return $mockRepository;
	}

	private function getMockRepository()
	{
		return $this->getMockBuilder('ins0\\GitHub\\Repository')
			->disableOriginalConstructor()->getMock();
	}

	private function loadFixtureData($fixture)
	{
		return json_decode($this->loadFile("fixtures/{$fixture}.json"));
	}

	private function loadFile($file)
	{
		return file_get_contents(__DIR__ . "/{$file}");
	}
}
