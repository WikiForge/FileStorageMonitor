<?php

namespace WikiForge\FileStorageMonitor\Specials;

use Aws\S3\S3Client;
use SpecialPage;

class SpecialFileStorageMonitor extends SpecialPage {
	public function __construct() {
		parent::__construct( 'FileStorageMonitor', 'monitor-file-storage' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $sub
	 */
	public function execute( $sub ) {
		$this->setHeaders();
		$this->outputHeader();

		$fileStorageUsages = $this->retrieveFileStorageUsages();

		$this->outputResults( $fileStorageUsages );
	}

	/**
	 * Retrieve file storage usages for all wikis
	 *
	 * @return array
	 */
	private function retrieveFileStorageUsages() {
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
	 * @return int
	 */
	private function retrieveFileStorageUsageForWiki( $wiki ) {
		$bucketName = $this->getConfig()->get( 'FileStorageMonitorAWSBucketName' );
		$prefix = $this->getConfig()->get( 'FileStorageMonitorAWSPrefix' );
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

		$objects = $client->listObjects( [
			'Bucket' => $bucketName,
			'Prefix' => $prefix,
			'ObjectAttributes' => ['ObjectSize'],
		] );

		$usage = 0;
		foreach ( $objects as $object ) {
var_dump( $object );
			$usage += $object['ContentLength'];
		}

		return $usage;
	}

	/**
	 * Display the results
	 *
	 * @param array $fileStorageUsages
	 */
	private function outputResults( $fileStorageUsages ) {
		$this->getOutput()->addWikiTextAsInterface( 'File Storage Usages:' );

		foreach ( $fileStorageUsages as $wiki => $usage ) {
			$this->getOutput()->addWikiTextAsInterface( "$wiki: $usage bytes" );
		}
	}
}
