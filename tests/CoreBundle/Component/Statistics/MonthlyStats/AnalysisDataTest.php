<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Statistics\MonthlyStats;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisData;
use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisSelection;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Selection\Selection;

class AnalysisDataTest extends TestCase
{
    /**
     * @param array $results
     * @return AnalysisData
     */
    protected function getAnalysisDataMockWithEmptySelections(array $results = [])
    {
        return new AnalysisData(
            new Selection([]),
            new AnalysisSelection(),
            $this->getTrainingRepositoryMock($results),
            new Account()
        );
    }

    /**
     * @param array $results
     * @return TrainingRepository
     */
    protected function getTrainingRepositoryMock(array $results = [])
    {
        $repository = $this->getMockBuilder(TrainingRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('getMonthlyStatsFor')
            ->willReturn($results);

        /** @var TrainingRepository $repository */
        return $repository;
    }

    public function testEmptyAnalysisData()
    {
        $data = $this->getAnalysisDataMockWithEmptySelections();

        $this->assertTrue($data->isEmpty());
        $this->assertEqualsWithDelta(0.0, $data->getRawValue(1970, 1), 1e-6);
        $this->assertNotEquals(0, $data->getMaximum());
    }

    public function testYearRange()
    {
        $data = $this->getAnalysisDataMockWithEmptySelections([
            ['year' => 2006, 'month' => 7, 'value' => 42.0],
            ['year' => 2009, 'month' => 1, 'value' => 3.14]
        ]);

        $this->assertFalse($data->isEmpty());
        $this->assertEquals([2009, 2008, 2007, 2006], $data->getYears());
        $this->assertEqualsWithDelta(42.0, $data->getRawValue(2006, 7), 1e-6);
        $this->assertEqualsWithDelta(0.0, $data->getRawValue(2007, 7), 1e-6);
        $this->assertEqualsWithDelta(3.14, $data->getRawValue(2009, 1), 1e-6);
        $this->assertEqualsWithDelta(42.0, $data->getMaximum(), 1e-6);
    }
}
