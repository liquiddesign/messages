<?php

declare(strict_types=1);

namespace Messages\DB;

use StORM\Entity;

/**
 * @table{"name":"messages_email"}
 */
class Email extends Entity
{
	/**
	 * @column
	 */
	public string $email;
	
	/**
	 * @column{"type":"timestamp"}
	 */
	public string $created;
}
