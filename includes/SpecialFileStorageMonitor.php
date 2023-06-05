<?php

namespace WikiForge\FileStorageMonitor;

use Aws\S3\S3Client;
use SpecialPage;

class SpecialFileStorageMonitor extends SpecialPage {
	public function __construct() {
		parent::__construct( 'FileStorageMonitor', 'monitor-file-storage' );
	}

	public function execute() {
		$this->setHeaders();
		$this->checkPermissions();

		$this->outputHeader();

		$fileStorageUsages = $this->retrieveFileStorageUsages();
		$this->outputResults( $fileStorageUsages );
	}

	/**
	 * Retrieve file storage usages for all wikis
	 *
	 * @return array
	 */
	private function retrieveFileStorageUsages(): array {
		$fileStorageUsages = [];
		$wikis = $this->getConfig()->get( 'LocalDatabases' );

		foreach ( $wikis as $wiki ) {
			$usage = $this->retrieveFileStorageUsageForWiki( $wiki );
			$fileStorageUsages[$wiki] = $usage;
		}

		return $fileStorageUsages;
	}

	/**
	 * Retrieve file storage usage for a specific wiki
	 *
	 * @param string $wiki
	 * @return float
	 */
	private function retrieveFileStorageUsageForWiki( string $wiki ): float {
		$bucketName = $this->getConfig()->get( 'FileStorageMonitorAWSBucketName' );
		$region = $this->getConfig()->get( 'FileStorageMonitorAWSRegion' );
		$key = $this->getConfig()->get( 'FileStorageMonitorAWSAccessKey' );
		$secret = $this->getConfig()->get( 'FileStorageMonitorAWSSecretKey' );

		$client = new S3Client( [
			'region' => $region,
			'version' => 'latest',
			'credentials' => [
				'key' => $key,
				'secret' => $secret,
			],
		] );

		$objects = $client->getIterator( 'ListObjects', [
			'Bucket' => $bucketName,
			'Prefix' => $wiki,
		] );

		$usage = 0;
		foreach ( $objects as $object ) {
			$usage += $object['Size'];
		}

		return round( $usage / 1024 / 1024 / 1024, 2 );
	}

	/**
	 * Display the results
	 *
	 * @param array $fileStorageUsages
	 */
	private function outputResults( array $fileStorageUsages ) {
		$this->getOutput()->addWikiTextAsInterface( 'File Storage Usages:' );

		foreach ( $fileStorageUsages as $wiki => $usage ) {
			$this->getOutput()->addWikiTextAsInterface( "$wiki: $usage GB" );
		}
	}

	/**
	 * @return string
	 */
	protected function getGroupName(): string {
		return 'wikimanage';
	}
}
