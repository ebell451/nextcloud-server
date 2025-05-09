<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\Integration\Lib;

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Tests\Integration\AbstractIntegrationTest;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_LDAP;
use OCA\User_LDAP\UserPluginManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../Bootstrap.php';

class IntegrationTestPaging extends AbstractIntegrationTest {
	/** @var UserMapping */
	protected $mapping;

	/** @var User_LDAP */
	protected $backend;

	/** @var int */
	protected $pagingSize = 2;

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../setup-scripts/createExplicitUsers.php');
		parent::init();

		$this->backend = new User_LDAP($this->access, Server::get(\OCP\Notification\IManager::class), Server::get(UserPluginManager::class), Server::get(LoggerInterface::class), Server::get(DeletedUsersIndex::class));
	}

	public function initConnection() {
		parent::initConnection();
		$this->connection->setConfiguration([
			'ldapPagingSize' => $this->pagingSize
		]);
	}

	/**
	 * fetch first three, afterwards all users
	 *
	 * @return bool
	 */
	protected function case1() {
		$filter = 'objectclass=inetorgperson';
		$attributes = ['cn', 'dn'];

		$result = $this->access->searchUsers($filter, $attributes, 4);
		// beware, under circumstances, the result  set can be larger then
		// the specified limit! In this case, if we specify a limit of 3,
		// the result will be 4, because the highest possible paging size
		// is 2 (as configured).
		// But also with more than one search base, the limit can be outpaced.
		if (count($result) !== 4) {
			return false;
		}

		$result = $this->access->searchUsers($filter, $attributes);
		if (count($result) !== 7) {
			return false;
		}

		return true;
	}
}

/** @var string $host */
/** @var int $port */
/** @var string $adn */
/** @var string $apwd */
/** @var string $bdn */
$test = new IntegrationTestPaging($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
