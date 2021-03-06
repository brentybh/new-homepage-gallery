<?php

namespace App;

use App\Model\Date;
use App\Model\Image;
use App\Model\Record;
use App\Repository\RepositoryInterface;
use BohanYang\BingWallpaper\Client;
use BohanYang\BingWallpaper\CurrentTime;
use BohanYang\BingWallpaper\Market;
use BohanYang\BingWallpaper\RequestParams;
use Psr\Log\LoggerInterface;

use function array_diff;
use function array_keys;

class Collector
{
    /** @var RepositoryInterface */
    private $repository;

    /** @var Client */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(RepositoryInterface $repository, Client $client, LoggerInterface $logger)
    {
        $this->repository = $repository;
        $this->client = $client;
        $this->logger = $logger;
    }

    private const MARKETS = [
        'en-US',
        'pt-BR',
        'en-CA',
        'fr-CA',
        'en-GB',
        'fr-FR',
        'de-DE',
        'en-IN',
        'zh-CN',
        'ja-JP',
    ];

    /** @return RequestParams[] */
    private function makeRequestParams() : array
    {
        $markets = [];

        foreach (self::MARKETS as $market) {
            $markets[$market] = new Market($market);
        }

        $now = new CurrentTime();
        $markets = $now->getMarketsHaveBecomeTheLaterDate($markets);
        $date = $now->getTheLaterDate();
        $requests = array_keys($markets);
        $this->logger->debug('Date ' . $date->format('Y/n/j'));
        $this->logger->debug('Available', $requests);
        $existing = $this->repository->findMarketsHaveRecordOn(Date::createFromLocal($date), $requests);
        $this->logger->debug('Existing', $existing);
        $requests = array_diff($requests, $existing);
        $this->logger->debug('Requests', $requests);
        foreach ($requests as $i => $market) {
            $requests[$i] = RequestParams::create($markets[$market], $date);
        }

        return $requests;
    }

    public function collect() : void
    {
        $requests = $this->makeRequestParams();

        if ($requests === []) {
            return;
        }

        $this->logger->notice('Start collect');

        foreach ($this->client->batch($requests) as $result) {
            $this->saveResult($result);
        }
    }

    public function collectOne(string $market, int $offset)
    {
        $market = new Market($market);
        $request = RequestParams::createFromOffset($market, $offset);
        $result = $this->client->fetch($request);
        $this->saveResult($result);

        return $result;
    }

    private function saveResult(array $result) : void
    {
        $image = new Image($result['image']);
        unset($result['image']);
        $result['date'] = Date::createFromLocal($result['date']);
        $record = new Record($result);
        $this->repository->save($record, $image);
    }
}
