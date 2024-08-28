<?php

namespace modules\demos\console\controllers;

use CommerceGuys\Addressing\Subdivision\Subdivision;
use Craft;
use craft\base\ElementInterface;
use craft\commerce\db\Table;
use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\events\MailEvent;
use craft\commerce\models\OrderStatus;
use craft\commerce\models\Store;
use craft\commerce\Plugin;
use craft\commerce\records\Transaction;
use craft\commerce\services\Emails;
use craft\console\Controller;
use craft\elements\Address;
use craft\elements\db\ElementQuery;
use craft\elements\db\UserQuery;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\ElementException;
use craft\helpers\Console;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\queue\Queue;
use DateInterval;
use DateTime;
use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Illuminate\Support\Collection;
use Random\RandomException;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Form;
use yii\base\Event;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

class SeedController extends Controller
{
	public const FREEFORM_SUBMISSION_MIN = 1;

	public const FREEFORM_SUBMISSION_MAX = 10;

	public const FREEFORM_MESSAGE_CHARS_MIN = 5;

	public const FREEFORM_MESSAGE_CHARS_MAX = 10;

	/**
	 * Maximum number of carts to generate per day.
	 */
	public const CARTS_PER_DAY_MAX = 1;

	/**
	 * Maximum number of customers to generate.
	 */
	public const CUSTOMERS_MAX = 10;

	/**
	 * Minimum number of customers to generate.
	 */
	public const CUSTOMERS_MIN = 5;

	/**
	 * Maximum number of orders to generate per day.
	 */
	public const ORDERS_PER_DAY_MAX = 1;

	/**
	 * Maximum number of products per order/cart.
	 */
	public const PRODUCTS_PER_ORDER_MAX = 1;

	/**
	 * The start date of when orders should begin generating.
	 */
	public const START_DATE_INTERVAL = 'P40D';

	/**
	 * Maximum number of users to generate.
	 */
	public const USERS_MAX = 10;

	/**
	 * Minimum number of users to generate.
	 */
	public const USERS_MIN = 5;

	/**
	 * Percentage of customers to give VIP status.
	 */
	public const VIP_CUSTOMER_PERCENT = 10;

	/**
	 * Group handle for “Customers”.
	 */
	public const CUSTOMER_GROUP_HANDLE = 'customers';

	/**
	 * Group handle for “VIP Customers”.
	 */
	public const VIP_CUSTOMER_GROUP_HANDLE = 'vipCustomers';

	/**
	 * Minimum number of reviews per product.
	 */
	public const REVIEWS_PER_PRODUCT_MIN = 0;

	/**
	 * Maximum number of reviews per product.
	 */
	public const REVIEWS_PER_PRODUCT_MAX = 2;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	private array $_users = [];

	/**
	 * @var array<int, array<string, mixed>>
	 */
	private array $_guestCustomers = [];

	/**
	 * @var string[]
	 */
	private array $_countries = [];

	/**
	 * @var Product[]
	 */
	private array $_products = [];

	/**
	 * @var ?Collection<int, OrderStatus>
	 */
	private ?Collection $_orderStatuses = null;

	private ?DateTime $_startDate = null;

	private ?Store $_store = null;

	private FakerGenerator $_faker;

	#[\Override]
	public function init(): void
	{
		parent::init();

		if (! Craft::$app->isInstalled) {
			return;
		}

		// Don’t let order status emails send while this is running
		Event::on(
			Emails::class,
			Emails::EVENT_BEFORE_SEND_MAIL,
			function (MailEvent $event): void {
				$event->isValid = false;
			}
		);

		$startDate = new DateTime();
		$interval = new DateInterval(self::START_DATE_INTERVAL);
		$this->_startDate = $startDate->sub($interval);

		$this->_faker = Factory::create();
		$this->_store = Plugin::getInstance()?->getStores()->getCurrentStore();
	}

	/**
	 * Seeds all data necessary for a working demo
	 */
	public function actionIndex(): int
	{
		$this->stdout('Beginning seed ... ' . PHP_EOL . PHP_EOL);
		// $this->runAction('freeform-data', ['contact']);
		$this->runAction('refresh-articles');
		$this->runAction('commerce-data');
		$this->_cleanup();
		$this->stdout('Seed complete.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
		return ExitCode::OK;
	}

	public function actionClean(): int
	{
		$submissions = Submission::find();
		$submissions->isSpam(null);
		$this->deleteElements($submissions, 'submissions');
		$this->runAction('delete-commerce-data');
		return ExitCode::OK;
	}

	/**
	 * Seeds Freeform with submission data
	 *
	 * @param string $formHandle Freeform form handle
	 */
	public function actionFreeformData(string $formHandle): int
	{
		$this->stdout('Seeding Freeform data ... ' . PHP_EOL);

		$freeform = Freeform::getInstance();
		$form = $freeform->forms->getFormByHandle($formHandle)?->getForm();
		$submissionCount = $this->_faker->numberBetween(self::FREEFORM_SUBMISSION_MIN, self::FREEFORM_SUBMISSION_MAX);
		$errorCount = 0;

		for ($i = 1; $i <= $submissionCount; $i++) {
			try {
				$submission = $this->_createFormSubmission($form);
				$this->stdout("    - [{$i}/{$submissionCount}] Creating submission {$submission->title} ... ");

				if ($this->_saveFormSubmission($submission)) {
					$this->stdout('done' . PHP_EOL, Console::FG_GREEN);
				} else {
					$this->stderr('failed: ' . implode(', ', $submission->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
					$errorCount++;
				}
			} catch (\Throwable $e) {
				$this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
				$errorCount++;
			}
		}

		$this->stdout('Done seeding Freeform data.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
		return $errorCount !== 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
	}

	public function actionRefreshArticles(): int
	{
		$this->stdout('Refreshing articles ... ');
		$entries = Entry::find()->section('articles');

		foreach ($entries->all() as $entry) {
			$entry->postDate = $this->_faker->dateTimeInInterval('-1 months', '-5 days');
			Craft::$app->elements->saveElement(element: $entry);
		}

		$this->stdout('done' . PHP_EOL, Console::FG_GREEN);

		return ExitCode::OK;
	}

	public function actionCommerceData(): int
	{
		$this->stdout('Seeding Commerce data ... ' . PHP_EOL . PHP_EOL);
		$this->_createUsers();
		$this->_createGuestCustomers();
		$this->_createOrders();
		$this->_createReviews();
		$this->stdout('Done seeding Commerce data.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);

		return ExitCode::OK;
	}

	public function actionDeleteCommerceData(): int
	{
		$this->deleteElements(Order::find(), 'orders');
		$this->deleteElements(User::find()->status(User::STATUS_INACTIVE), 'customers');
		$this->deleteElements(Entry::find()->section('reviews'), 'reviews');
		$this->deleteElements(User::find()->group(self::CUSTOMER_GROUP_HANDLE), 'customers');
		$this->deleteElements(User::find()->group(self::VIP_CUSTOMER_GROUP_HANDLE), 'customers');

		Plugin::getInstance()?->getCarts()->purgeIncompleteCarts();

		return ExitCode::OK;
	}

	private function _cleanup(): void
	{
		$this->stdout('Running queue ... ' . PHP_EOL);
		/** @var Queue $queue */
		$queue = Craft::$app->getQueue();
		$queue->run();
		$this->stdout('Queue finished.' . PHP_EOL, BaseConsole::FG_GREEN);

		$this->stdout('Clearing data cache ... ');
		Craft::$app->getCache()?->flush();
		$this->stdout('done' . PHP_EOL, BaseConsole::FG_GREEN);

		$compiledClassesPath = Craft::$app->getPath()->getCompiledClassesPath();

		$this->stdout('Clearing compiled classes ... ');
		FileHelper::removeDirectory($compiledClassesPath);
		$this->stdout('done' . PHP_EOL, BaseConsole::FG_GREEN);

		$this->stdout('Setting system status to online ... ');
		Craft::$app->projectConfig->set('system.live', true, null, false);
		$this->stdout('done' . PHP_EOL, BaseConsole::FG_GREEN);
	}

	/**
	 * @param ElementQuery<int, ElementInterface> $query
	 */
	private function deleteElements(ElementQuery $query, string $label = 'elements'): void
	{
		$count = $query->count();
		$errorCount = 0;
		$this->stdout("Deleting {$label} ..." . PHP_EOL);

		foreach ($query->all() as $element) {
			$i = isset($i) ? $i + 1 : 1;
			$this->stdout("    - [{$i}/{$count}] Deleting element {$element->title} ... ");
			try {
				$success = Craft::$app->getElements()->deleteElement($element, true);
				if ($success) {
					$this->stdout('done' . PHP_EOL, Console::FG_GREEN);
				} else {
					$this->stderr('failed: ' . implode(', ', $element->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
					$errorCount++;
				}
			} catch (\Throwable $e) {
				$this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
				$errorCount++;
			}
		}

		$message = "Done deleting {$label}.";
		if ($errorCount !== 0) {
			$message .= " ({$errorCount} errors)";
		}

		$this->stdout($message . PHP_EOL . PHP_EOL, Console::FG_GREEN);
	}

	private function _createFormSubmission(Form $form): Submission
	{
		/** @var Submission $submission */
		$submission = Freeform::getInstance()->submissions->createSubmissionFromForm($form);
		$submission->dateCreated = $this->_faker->dateTimeThisMonth();
		$submission->dateUpdated = $submission->dateCreated;

		// Reparse the title with the fake date
		$submission->title = Craft::$app->view->renderString(
			$form->getSubmissionTitleFormat(),
			$form->getLayout()->getFieldsByHandle() + [
				'dateCreated' => $submission->dateCreated,
				'form' => $form,
			]
		);

		$submission->setFormFieldValues([
			'email' => $this->_faker->email,
			'firstName' => $this->_faker->firstName,
			'lastName' => $this->_faker->lastName,
			'message' => $this->_faker->realTextBetween(self::FREEFORM_MESSAGE_CHARS_MIN, self::FREEFORM_MESSAGE_CHARS_MAX),
		]);

		return $submission;
	}

	private function _saveFormSubmission(Submission $submission): bool
	{
		if (! Craft::$app->getElements()->saveElement($submission)) {
			return false;
		}

		// Update submissions table to match date, so element index will sort properly
		$dateCreatedDb = Db::prepareDateForDb($submission->dateCreated);

		Craft::$app->db->createCommand()
			->update($submission::TABLE, [
				'dateCreated' => $dateCreatedDb,
				'dateUpdated' => $dateCreatedDb,
			], [
				'id' => $submission->id,
			])
			->execute();

		return true;
	}

	/**
	 * Create demo users.
	 *
	 * @throws ElementException
	 * @throws \Throwable
	 * @throws \craft\errors\ElementNotFoundException
	 * @throws \yii\base\Exception
	 * @throws \yii\base\InvalidConfigException
	 */
	private function _createUsers(): void
	{
		$customerGroup = Craft::$app->getUserGroups()->getGroupByHandle(self::CUSTOMER_GROUP_HANDLE);
		$vipCustomerGroup = Craft::$app->getUserGroups()->getGroupByHandle(self::VIP_CUSTOMER_GROUP_HANDLE);

		$this->stdout('Creating users ... ' . PHP_EOL);
		$numUsers = random_int(self::USERS_MIN, self::USERS_MAX);
		for ($i = 1; $i <= $numUsers; $i++) {
			$firstName = $this->_faker->firstName();
			$lastName = $this->_faker->lastName;
			$email = $this->_faker->unique()->email;
			// Assign everybody to “Customers” group
			$groups = array_filter([$customerGroup]);

			// Should we also add this user to the “VIP Customers” group?
			if ($vipCustomerGroup && $i <= (ceil($numUsers * (self::VIP_CUSTOMER_PERCENT / 100)))) {
				$groups[] = $vipCustomerGroup;
			}

			$groupIds = array_map(static fn ($group) => $group->id, $groups);

			$attributes = [
				'type' => User::class,
				'email' => $email,
				'username' => $email,
				'firstName' => $firstName,
				'lastName' => $lastName,
				'fullName' => $firstName . ' ' . $lastName,
			];
			$this->stdout("    - [{$i}/{$numUsers}] Creating user " . $attributes['fullName'] . ' ... ');

			/** @var User $user */
			$user = Craft::$app->getElements()->createElement($attributes);
			/** @var int $userId */
			$userId = $user->id;

			if (! Craft::$app->getElements()->saveElement($user)) {
				// If a user cannot be saved, simply skip over it and carry on.
				continue;
			}

			$user->setGroups($groups);
			Craft::$app->getUsers()->assignUserToGroups($userId, $groupIds);

			$addresses = [];
			for ($j = 0; $j < random_int(1, 3); $j++) {
				$addresses[] = $this->_createAddress($firstName, $lastName, $user);
			}

			Craft::$app->getUsers()->activateUser($user);

			$this->_users[] = $user->toArray() + [
				'addresses' => $addresses,
			];
			$this->stdout('done' . PHP_EOL, Console::FG_GREEN);
		}

		$this->stdout('Done creating users' . PHP_EOL, Console::FG_GREEN);
	}

	/**
	 * Create guest customer data.
	 *
	 * @throws \yii\base\Exception
	 * @throws \yii\base\InvalidConfigException
	 * @throws RandomException
	 */
	private function _createGuestCustomers(): void
	{
		$this->stdout('Creating customers...' . PHP_EOL);
		$numCustomers = random_int(self::CUSTOMERS_MIN, self::CUSTOMERS_MAX);
		for ($i = 0; $i <= $numCustomers; $i++) {
			$customer = Craft::$app->getUsers()->ensureUserByEmail($this->_faker->email);

			$customer->firstName = $this->_faker->firstName();
			$customer->lastName = $this->_faker->lastName;

			Craft::$app->getElements()->saveElement($customer, false);

			$this->stdout("    - [{$i}/{$numCustomers}] Creating guest customer " . $customer->firstName . ' ' . $customer->lastName . ' ... ');
			$addresses = [];
			for ($j = 0; $j <= random_int(1, 3); $j++) {
				$address = $this->_createAddress($customer->firstName, $customer->lastName, $customer);

				$addresses[] = $address;
			}

			$this->_guestCustomers[] = $customer->toArray() + [
				'addresses' => $addresses,
			];
			$this->stdout('done' . PHP_EOL, BaseConsole::FG_GREEN);
		}

		$this->stdout('Done creating customers' . PHP_EOL, BaseConsole::FG_GREEN);
	}

	/**
	 * Create and save address data.
	 *
	 * @throws \yii\base\Exception
	 * @throws \yii\base\InvalidConfigException
	 * @throws \Exception
	 */
	private function _createAddress(string $firstName, string $lastName, User $customer): Address
	{
		$country = $this->_getRandomCountry();

		/** @var Address $address */
		$address = Craft::createObject([
			'class' => Address::class,
			'attributes' => [
				'primaryOwnerId' => $customer->id,
				'firstName' => $firstName,
				'lastName' => $lastName,
				'addressLine1' => $this->_faker->streetAddress,
				'locality' => $this->_faker->city,
				'postalCode' => $this->_faker->postcode,
				'countryCode' => $country,
			],
		]);

		$addressFormat = Craft::$app->getAddresses()->getAddressFormatRepository()->get($country);

		if ($addressFormat->getAdministrativeAreaType() !== null && ($subdivision = $this->_getRandomStateFromCountry($country)) instanceof \CommerceGuys\Addressing\Subdivision\Subdivision) {
			$address->administrativeArea = $subdivision->getCode();
		}

		Craft::$app->getElements()->saveElement($address, false);

		return $address;
	}

	/**
	 * @return string Country code
	 * @throws \Exception
	 */
	private function _getRandomCountry(): string
	{
		if ($this->_countries === []) {
			$this->_countries = $this->_store?->getSettings()->getCountries() ?? [];
		}

		/** @var string $country */
		$country = $this->_faker->randomElement($this->_countries);

		return $country;
	}

	/**
	 * @throws \Exception
	 */
	private function _getRandomStateFromCountry(string $country): ?Subdivision
	{
		$subdivisions = Craft::$app->getAddresses()->getSubdivisionRepository()->getAll([$country]);

		if (empty($subdivisions)) {
			return null;
		}

		/** @var string $subdivision */
		$subdivision = $this->_faker->randomElement($subdivisions);

		return $subdivision;
	}

	/**
	 * Create demo orders and carts.
	 *
	 * @throws \Exception
	 */
	private function _createOrders(): void
	{
		$this->stdout('Creating orders...' . PHP_EOL);
		$date = new DateTime();
		while ($date->format('Y-m-d') >= $this->_startDate?->format('Y-m-d')) {
			// Carts
			$this->stdout('    - [' . $date->format('Y-m-d') . '] Creating carts ... ');
			for ($i = 1; $i <= random_int(1, self::CARTS_PER_DAY_MAX); $i++) {
				$date = $this->_setTime($date);
				$this->_createOrderElement($date, false);
			}

			$this->stdout('done' . PHP_EOL, Console::FG_GREEN);

			// Orders
			$this->stdout('    - [' . $date->format('Y-m-d') . '] Creating orders ... ');
			for ($j = 1; $j <= random_int(1, self::ORDERS_PER_DAY_MAX); $j++) {
				$date = $this->_setTime($date);
				$this->_createOrderElement($date);
			}

			$date->sub(new DateInterval('P1D'));
			$this->stdout('done' . PHP_EOL, Console::FG_GREEN);
		}

		$this->stdout('Done creating orders' . PHP_EOL, Console::FG_GREEN);
	}

	/**
	 * Set random time on a DateTime object.
	 *
	 * @throws \Exception
	 */
	private function _setTime(DateTime $date): DateTime
	{
		if (DateTimeHelper::isToday($date)) {
			$date->setTime(random_int(0, (int) $date->format('G')), random_int(0, (int) $date->format('i')), 0);
		} else {
			$date->setTime(random_int(0, 23), random_int(0, 59), random_int(0, 59));
		}

		return $date;
	}

	/**
	 * Return a random customer from those imported.
	 *
	 * @return array<string, mixed>
	 */
	private function _getRandomCustomer(bool $isUser): array
	{
		/** @var array<string, mixed> $customer */
		$customer = $this->_faker->randomElement($isUser ? $this->_users : $this->_guestCustomers);

		return $customer;
	}

	/**
	 * Return a random address from an imported customer.
	 *
	 * @param array<string, mixed> $customer
	 */
	private function _getRandomAddressFromCustomer(array $customer): Address
	{
		/** @var Address $address */
		$address = $this->_faker->randomElement($customer['addresses']);

		return $address;
	}

	/**
	 * Return a random product.
	 */
	private function _getRandomProduct(): Product
	{
		if ($this->_products === []) {
			/** @var ProductQuery $productQuery */
			$productQuery = Craft::$app->getElements()->createElementQuery(Product::class);
			$this->_products = $productQuery->all();
		}

		/** @var Product $product */
		$product = $this->_faker->randomElement($this->_products);

		return $product;
	}

	/**
	 * Return a random order status.
	 */
	private function _getRandomOrderStatus(): OrderStatus
	{
		if (! $this->_orderStatuses instanceof \Illuminate\Support\Collection) {
			$this->_orderStatuses = Plugin::getInstance()?->getOrderStatuses()->getAllOrderStatuses();
		}

		/* @phpstan-ignore-next-line   */
		return $this->_orderStatuses->random();
	}

	/**
	 * Create and save an order element.
	 *
	 * @throws \Throwable
	 * @throws \craft\commerce\errors\OrderStatusException
	 * @throws \craft\errors\ElementNotFoundException
	 * @throws \yii\base\Exception
	 * @throws \yii\base\InvalidConfigException
	 */
	private function _createOrderElement(DateTime $date, bool $isCompleted = true): void
	{
		$customer = $this->_getRandomCustomer((bool) random_int(0, 1));

		$attributes = [
			'dateUpdated' => $date,
			'dateCreated' => $date,
		];

		/** @var Order $order */
		$order = Craft::createObject([
			'class' => Order::class,
			'attributes' => $attributes,
		]);


		$order->setCustomerId($customer['id']);

		$order->number = Plugin::getInstance()->getCarts()->generateCartNumber();

		Craft::$app->getElements()->saveElement($order);

		/** @var Address $billingAddress */
		$billingAddress = Craft::$app->getElements()->duplicateElement($this->_getRandomAddressFromCustomer($customer), [
			'primaryOwnerId' => $order->id,
			'title' => Craft::t('commerce', 'Billing Address'),
		]);
		/** @var Address $shippingAddress */
		$shippingAddress = Craft::$app->getElements()->duplicateElement($this->_getRandomAddressFromCustomer($customer), [
			'primaryOwnerId' => $order->id,
			'title' => Craft::t('commerce', 'Shipping Address'),
		]);

		$order->setBillingAddress($billingAddress);
		$order->setShippingAddress($shippingAddress);

		$lineItems = [];
		$numProducts = random_int(1, self::PRODUCTS_PER_ORDER_MAX);
		for ($i = 1; $i <= $numProducts; $i++) {
			$product = $this->_getRandomProduct();
			// Weight the qty in favour of 1 item
			$qty = random_int(0, 9) < 8 ? 1 : 2;
			$lineItems[] = Plugin::getInstance()->getLineItems()->createLineItem($order, $product->getDefaultVariant()->id, [], $qty);
		}

		$order->setLineItems($lineItems);

		Craft::$app->getElements()->saveElement($order);

		if ($isCompleted) {
			// Get everything completed before messing with the order status
			$order->markAsComplete();

			$order->orderStatusId = $this->_getRandomOrderStatus()->id;
			$order->dateOrdered = $date;

			Craft::$app->getDb()->createCommand()
				->update(Table::ORDERS, [
					'orderStatusId' => $order->orderStatusId,
					'dateOrdered' => Db::prepareDateForDb($date),
				], [
					'id' => $order->id,
				])
				->execute();

			$this->_createTransactionForOrder($order);
		}
	}

	/**
	 * Create and save a transaction for and order element.
	 *
	 * @throws \craft\commerce\errors\TransactionException
	 */
	private function _createTransactionForOrder(Order $order): void
	{
		if ($order->isCompleted) {
			$transaction = Plugin::getInstance()->getTransactions()->createTransaction($order, null);
			$transaction->type = Transaction::TYPE_PURCHASE;
			$transaction->status = Transaction::STATUS_SUCCESS;

			Plugin::getInstance()->getTransactions()->saveTransaction($transaction);
		}
	}

	/**
	 * Create product review data
	 *
	 * @throws \Throwable
	 * @throws \craft\errors\ElementNotFoundException
	 * @throws \yii\base\Exception
	 * @throws \yii\base\InvalidConfigException
	 */
	private function _createReviews(): void
	{
		$reviewsSection = Craft::$app->getEntries()->getSectionByHandle('reviews');
		/** @var UserQuery $authorQuery */
		$authorQuery = Craft::$app->getElements()->createElementQuery(User::class);
		/** @var User|null $author */
		$author = $authorQuery->admin(true)->orderBy('id ASC')->one();

		if (! $reviewsSection || ! $author) {
			return;
		}

		$startDateInterval = new DateInterval(self::START_DATE_INTERVAL);

		$this->stdout('Creating reviews ... ' . PHP_EOL);
		$index = 1;
		$numProducts = count($this->_products);
		foreach ($this->_products as $_product) {
			$numReviews = random_int(self::REVIEWS_PER_PRODUCT_MIN, self::REVIEWS_PER_PRODUCT_MAX);
			$this->stdout("    - [{$index}/{$numProducts}] Creating {$numReviews} reviews for " . $_product->title . ' ... ');
			for ($i = 0; $i <= $numReviews; $i++) {
				$reviewDate = new DateTime();
				$reviewDate->sub(new DateInterval('P' . random_int(0, $startDateInterval->days) . 'D'));
				$reviewDate = $this->_setTime($reviewDate);

				/** @var Entry $review */
				$review = Craft::createObject([
					'class' => Entry::class,
				]);

				$review->authorId = $author->id;
				$review->postDate = $reviewDate;
				$review->sectionId = $reviewsSection->id;
				$review->typeId = $reviewsSection->getEntryTypes()[0]->id;
				$review->title = $this->_faker->randomLetter . '. ' . $this->_faker->lastName;

				$paragraphs = $this->_faker->paragraphs(random_int(0, 3));
				$stars = $this->_faker->optional(0.2, 5)->numberBetween(1, 5);
				$review->setFieldValues([
					'body' => '<p>' . implode('</p><p>', $paragraphs) . '</p>',
					'product' => [$_product->id],
					'stars' => (string) $stars,
				]);

				Craft::$app->getElements()->saveElement($review);
			}

			$this->stdout('done' . PHP_EOL, Console::FG_GREEN);
			$index++;
		}

		$this->stdout('Done creating reviews' . PHP_EOL, Console::FG_GREEN);
	}
}
