<?php
declare(strict_types = 1);

class Hour extends DateTimeImmutable {
	const DEFAULT_FORMAT = 'Y-m-d H:i:s';
	
	private $string         = null;
	public $hour            = null;
	public static $timezone = null;	
	public function __construct(string $string) {
		$this->string     = $string;
		$this->hourNumber = intval(substr($this->string, 11, 2));
		if (is_null(self::$timezone)) {
			self::$timezone = new DateTimeZone('UTC'); 
		}
		parent::__construct($string, self::$timezone);
	}
	
	public static function now(): Hour {
		$now       = new DateTime();
		$nowString = $now->format(self::DEFAULT_FORMAT);
		$hour      = new static($nowString);
		return $hour;
	}

	public function previousHour(): Hour {
		$format     = 'Y-m-d H:00:00';
		$hourString = $this->format($format);
		$hour       = new static($hourString);
		return $hour;
	}
	
	public function nextHour(): Hour {
		$hour           = $this->previousHour();
		$nextHour       = $hour->add(new DateInterval('PT1H'));
		$nextHourString = $nextHour->format(self::DEFAULT_FORMAT);
		$nextHourObject = new static($nextHourString);
		return $nextHourObject;
	}
	
	public function equalHours($hour): bool {
		$equals = (
			substr_compare($this->string, $hour->string, 0, 13) === 0
		);
		return $equals;
	}
	
	public function differenceInSeconds(Hour $datetime): int {
		$difference          = $this->diff($datetime);
		$differenceInSeconds =
			$difference->h * 3600 + // 60 * 60
			$difference->i * 60   +
			$difference->s
		;
		return $differenceInSeconds;
	}
	
	public function dayNumber(): string {
		$format    = 'N';
		$dayNumber = $this->format($format);
		return $dayNumber;
	}
	
	public function __toString(): string {
		return $this->string;
	}
}

class HourPeriod {
	public $hour    = null;
	public $seconds = null;
	public function __construct(Hour $hour, int $seconds) {
		$this->hour    = $hour;
		$this->seconds = $seconds;
	}

	const TOTAL_NUMBER_OF_SECONDS = 60 * 60;	
	public static function createFullHour(Hour $hour) {
		$seconds    = self::TOTAL_NUMBER_OF_SECONDS;
		$hourPeriod = new static($hour, $seconds);
		return $hourPeriod;
	}
	
	public static function createEmptyHour(Hour $hour) {
		$seconds    = 0;
		$hourPeriod = new static($hour, $seconds);
		return $hourPeriod;
	}
	
	public static function createByDifference(
		Hour $hour, Hour $start, Hour $end
	) {
		$difference = $start->differenceInSeconds($end);
		$hourPeriod = new static($hour, $difference);
		return $hourPeriod;
	}
	
	public function merge(HourPeriod $hourPeriod) {
		$this->seconds += $hourPeriod->seconds;
		return $this;
	}
}

class HourPeriodList extends ArrayObject {
	public function seconds() {
		$seconds = 0;
		$count   = $this->count();
		if ($count > 0) {
			$total   = $this->total(); 
			$divisor = $count * HourPeriod::TOTAL_NUMBER_OF_SECONDS;
			$seconds = $total / $divisor;
		}
		return $seconds;		
	}
	
	public function total(): int {
		$seconds = 0;
		foreach($this as $hourPeriod) {
			$seconds += $hourPeriod->seconds;
		}
		return $seconds;
	}
}

class AverageList extends ArrayObject {
	public function average(): float {
		$average = array_sum((array) $this) / count($this);
		return $average;
	}
}

class HourPeriodGroup {
	private $dictionary = null;
	private $days       = null;
	private $hours      = null;
	public function __construct(string $interval) {
		$this->dictionary = array();
		$this->interval($interval);
	}
	
	private function interval(string $interval): void {
		$now            = Hour::now();
		$intervalObject = DateInterval::createFromDateString($interval);
		$timestamp      = $now->sub($intervalObject)->nextHour();		
		$rows           = Database::rowsFromDatebase($timestamp);
		$this->processRows($rows);
	}	

	public function processRows(array $rows): void {
		$previousRow = null;
		foreach($rows as $row) {
			if (!is_null($previousRow)) {
				$this->processStartEnd(
					$previousRow->created,
					$row->created        ,
					$previousRow->state
				);
			}
			$previousRow = $row;
		}
	}	
	private function processStartEnd(
		string $startTimestamp, string $endTimestamp, string $stateString
	): void {
		$start = new Hour($startTimestamp);
		$end   = new Hour($endTimestamp  );
		$state = intval($stateString);
		if ($start->equalHours($end)) {
			$startHour = $start->previousHour();			
			$this->addHourPeriod($startHour, $state, $start, $end);
		} else {
			$this->processBeyondMidnight($start, $end, $state);
		}
	}
	private function processBeyondMidnight(
		Hour $start, Hour $end, int $state
	): void {
		$startHour     = $start->previousHour();	
		$startNextHour = $start->nextHour();
		$this->addHourPeriod($startHour, $state, $start, $startNextHour);
		
		$endHour = $end->previousHour();				
		$hour    = $startNextHour;
		while($hour < $endHour) {
			$this->addHourPeriod($hour, $state);
			$hour = $hour->nextHour();
		}
		
		$this->addHourPeriod($endHour, $state, $end, $endHour);
	}

	const STATE_CLOSED = 0;
	const STATE_OPEN   = 1;
	private function addHourPeriod(
		Hour $hour        , int  $state,
		Hour $start = null, Hour $end   = null
	): void {
		$hourPeriod = (($state === self::STATE_CLOSED)
			? HourPeriod::createEmptyHour($hour)
			: (is_null($start)
				? HourPeriod::createFullHour($hour)
				: HourPeriod::createByDifference($hour, $start, $end)
			)
		);
		$hash = (string) $hour;
		if (array_key_exists($hash, $this->dictionary)) {
			$hourPeriod = $hourPeriod->merge($this->dictionary[$hash]);
		}
		$this->dictionary[$hash] = $hourPeriod;
	}
	
	private function days(): array {
		$days = array_fill(1, 7, array_fill(0, 24, null));
		foreach($this->dictionary as $hourPeriod) {
			$dayNumber  = $hourPeriod->hour->dayNumber();
			$hourNumber = $hourPeriod->hour->hourNumber;
			if (!isset($days[$dayNumber][$hourNumber])) {
				$days[$dayNumber][$hourNumber] = new HourPeriodList();
			}
			$days[$dayNumber][$hourNumber][] = $hourPeriod;
		}
		return $days;
	}
	
	private $statistics       = null;
	private $totalPercentages = null;
	private $hourPercentages  = null;
	private $dayList          = null;
	public function statistics(): array {
		$this->statistics       = array();
		$this->totalPercentages = new AverageList();
		$this->hourPercentages  = array();	
		
		$days = $this->days();
		foreach($days as $dayNumber => $hours) {
			$this->dayList = new AverageList();
			foreach($hours as $hourNumber => $hourPeriodList) {
				$this->seconds($hourNumber, $hourPeriodList);
			}
			$this->dayListAverage($dayNumber);
		}
		
		$this->hoursAverage();
		$this->round();		
		return $this->statistics;
	}
	private function seconds(int $hourNumber, HourPeriodList $hourPeriodList) {
		$seconds = $hourPeriodList->seconds();
		$this->dayList[$hourNumber] = $seconds;
		$this->totalPercentages[]   = $seconds;
		if (!isset($this->hourPercentages[$hourNumber])) {
			$this->hourPercentages[$hourNumber] = new AverageList();
		}		
		$this->hourPercentages[$hourNumber][] = $seconds;
	}
	private function dayListAverage($dayNumber) {
		$this->statistics[$dayNumber]   = (array) $this->dayList;
		$this->statistics[$dayNumber][] = $this->dayList->average();
	}
	private function hoursAverage() {
		$hoursAverage = array();
		foreach($this->hourPercentages as $hourNumber => $percentages) {
			$hoursAverage[$hourNumber] = $percentages->average();
		}
		$hoursAverage[]     = $this->totalPercentages->average();
		$this->statistics[] = $hoursAverage;		
	}
	private function round() {
		array_walk_recursive($this->statistics, function(&$value) {
			$value = round($value, 4);
		});	
	}
	
	private function json(): void {
		$statistics = $this->statistics();
		$jsonString = json_encode($statistics, JSON_FORCE_OBJECT);
		header('Content-Type: application/json');		
		echo($jsonString);
	}

	public static function handleRequest(): void {	
		$intervalList = array(
			'1week'  => '1 week'  ,
			'1month' => '1 month' ,
			'3month' => '3 months',
			'1year'  => '1 year'  ,
			'2year'  => '2 years' ,
		);
		$intersect = array_intersect(
			array_keys($intervalList), array_keys($_GET)
		);
		$intervalKey = reset($intersect);
		if ($intervalKey !== false) {
			$interval = $intervalList[$intervalKey];		
			$group    = new static($interval);
			$group->json();
		}
	}
}

class Database extends PDO {
	private function __construct() {
		include_once($_SERVER['DOCUMENT_ROOT'] . '/../spaceAPI_config.php');
		$dsn =
			'mysql:dbname=' . $spaceApi_db_dbname . 
			';host='        . $spaceApi_db_servername
		;
		parent::__construct(
			$dsn, $spaceApi_db_username, $spaceApi_db_password
		);
	}

	public static function rowsFromDatebase(Hour $timestamp): array {
		$connection = new static();
		$sql = "
			select * from (
				select created, state
				from statechanges
				where created = (
					select max(created)
					from statechanges
					where created < ?
				)
				union all
				select created, state
				from statechanges
				where created >= ?
				union all
				select now(), null
			) as `table`
			order by created
		";
		$bindings = array($timestamp, $timestamp);
		$statement = $connection->prepare($sql);
		$statement->execute($bindings);
		$result = $statement->fetchAll(PDO::FETCH_CLASS);
		if (count($result) > 0) {
			if ($result[0]->created < $timestamp) {
				$result[0]->created = (string) $timestamp;
			}
		}
		return $result;
	}
}

HourPeriodGroup::handleRequest();
