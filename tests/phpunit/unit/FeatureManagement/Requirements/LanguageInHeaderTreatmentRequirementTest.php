<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace Vector\FeatureManagement\Tests;

use CentralIdLookup;
use HashConfig;
use User;
use Vector\Constants;
use Vector\FeatureManagement\Requirements\LanguageInHeaderTreatmentRequirement;
use WebRequest;

/**
 * @group Vector
 * @group FeatureManagement
 * @coversDefaultClass \Vector\FeatureManagement\Requirements\LanguageInHeaderTreatmentRequirement
 */
class LanguageInHeaderTreatmentRequirementTest extends \MediaWikiUnitTestCase {

	public function providerLanguageInHeaderTreatmentRequirement() {
		return [
			[
				// Is language enabled
				[
					'logged_in' => false,
					'logged_out' => false,
				],
				// is A-B test enabled
				false,
				// note 0 = anon user
				0,
				// use central id lookup?
				false,
				// `languageinheader` query param
				null,
				false,
				'If nothing enabled, nobody gets new treatment'
			],
			[
				// Is language enabled
				[
					'logged_in' => false,
					'logged_out' => true,
				],
				// is A-B test enabled
				false,
				// note 0 = anon user
				0,
				// use central id lookup?
				false,
				// `languageinheader` query param
				null,
				true,
				'Anon users should get new treatment if enabled when A/B test disabled'
			],
			[
				// Is language enabled
				[
					'logged_in' => true,
					'logged_out' => false,
				],
				// is A-B test enabled
				false,
				2,
				// use central id lookup?
				false,
				// `languageinheader` query param
				null,
				true,
				'Logged in users should get new treatment if enabled when A/B test disabled'
			],
			[
				// Is language enabled
				[
					'logged_in' => true,
					'logged_out' => false,
				],
				// is A-B test enabled
				false,
				1,
				// use central id lookup?
				false,
				// `languageinheader` query param
				null,
				true,
				'All odd logged in users should get new treatent when A/B test disabled'
			],
			[
				// Is language enabled
				[
					'logged_in' => false,
					'logged_out' => true,
				],
				// is A-B test enabled
				true,
				// note 0 = anon user
				0,
				// use central id lookup?
				false,
				// `languageinheader` query param
				null,
				true,
				// Ab test is only for logged in users
				'Anon users with a/b test enabled should see new treatment when config enabled'
			],
			[
				// Is language enabled
				[
					'logged_in' => false,
					'logged_out' => false,
				],
				// is A-B test enabled
				true,
				// note 0 = anon user
				0,
				// use central id lookup?
				false,
				// `languageinheader` query param
				null,
				false,
				// Ab test is only for logged in users
				'Anon users with a/b test enabled should see old treatment when config disabled'
			],
			[
				// Is language enabled
				[
					'logged_in' => true,
					'logged_out' => false,
				],
				// is A-B test enabled
				true,
				2,
				// use central id lookup?
				false,
				// `languageinheader` query param
				null,
				true,
				'Even logged in users get new treatment when A/B test enabled'
			],
			[
				// Is language enabled
				[
					'logged_in' => true,
					'logged_out' => false,
				],
				// is A-B test enabled
				true,
				1,
				// use central id lookup?
				false,
				// `languageinheader` query param
				null,
				false,
				'Odd logged in users do not get new treatment when A/B test enabled'
			],
			[
				// Is language enabled
				[
					'logged_in' => true,
					'logged_out' => false,
				],
				// is A-B test enabled
				true,
				2,
				// use central id lookup?
				true,
				// `languageinheader` query param
				null,
				true,
				'With CentralIdLookup, even logged in users get new treatment when A/B test enabled'
			],
			[
				// Is language enabled
				[
					'logged_in' => true,
					'logged_out' => false,
				],
				// is A-B test enabled
				true,
				1,
				// use central id lookup?
				true,
				// `languageinheader` query param
				null,
				false,
				'With CentralIdLookup, odd logged in users do not get new treatment when A/B test enabled'
			],
			[
				// Is language enabled
				[
					'logged_in' => true,
					'logged_out' => false,
				],
				// is A-B test enabled
				true,
				1,
				// use central id lookup?
				false,
				// `languageinheader` query param
				"1",
				true,
				'Odd logged in users get new treatment when A/B test enabled and query param set to "1"'
			],
			[
				// Is language enabled
				[
					'logged_in' => true,
					'logged_out' => false,
				],
				// is A-B test enabled
				true,
				1,
				// use central id lookup?
				false,
				// `languageinheader` query param
				"0",
				false,
				'Even logged in users get old treatment when A/B test enabled and query param set to "0"'
			],
			[
				// Is language enabled
				[
					'logged_in' => false,
					'logged_out' => false,
				],
				// is A-B test enabled
				false,
				1,
				// use central id lookup?
				false,
				// `languageinheader` query param
				"1",
				true,
				'Users get new treatment when query param set to "1" regardless of state of A/B test or config flags'
			],
			[
				// Is language enabled
				[
					'logged_in' => false,
					'logged_out' => false,
				],
				// is A-B test enabled
				false,
				1,
				// use central id lookup?
				false,
				// `languageinheader` query param
				"0",
				false,
				'Users get old treatment when query param set to "0" regardless of state of A/B test or config flags'
			],
		];
	}

	/**
	 * @covers ::isMet
	 * @dataProvider providerLanguageInHeaderTreatmentRequirement
	 * @param bool $configValue
	 * @param bool $abValue
	 * @param int $userId
	 * @param bool $useCentralIdLookup
	 * @param string|null $queryParam
	 * @param bool $expected
	 * @param string $msg
	 */
	public function testLanguageInHeaderTreatmentRequirement(
		$configValue, $abValue, $userId, $useCentralIdLookup, $queryParam, $expected, $msg
	) {
		$config = new HashConfig( [
			Constants::CONFIG_KEY_LANGUAGE_IN_HEADER => $configValue,
			Constants::CONFIG_LANGUAGE_IN_HEADER_TREATMENT_AB_TEST => $abValue,
		] );

		$user = $this->createMock( User::class );
		$user->method( 'isRegistered' )->willReturn( $userId !== 0 );
		$user->method( 'getID' )->willReturn( $userId );

		$request = $this->createMock( WebRequest::class );
		$request->method( 'getCheck' )->willReturn( $queryParam !== null );
		$request->method( 'getBool' )->willReturn( (bool)$queryParam );

		$centralIdLookup = $this->createMock( CentralIdLookup::class );
		$centralIdLookup->method( 'centralIdFromLocalUser' )->willReturn( $userId );

		$requirement = new LanguageInHeaderTreatmentRequirement(
			$config,
			$user,
			$request,
			$useCentralIdLookup ? $centralIdLookup : null
		);

		$this->assertSame( $expected, $requirement->isMet(), $msg );
	}
}
