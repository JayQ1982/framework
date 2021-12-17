<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\session;

class NoSqlSessionHandler extends AbstractSessionHandler
{
	protected function executePreStartActions(): void
	{
	}

	public function close(): bool
	{
		// TODO: Overwrite close() method.
		return true;
	}

	public function destroy($id): bool
	{
		// TODO: Overwrite destroy() method.
		return true;
	}

	public function gc($max_lifetime): false|int
	{
		// TODO: Overwrite gc() method.
		return true;
	}

	public function open($path, $name): bool
	{
		// TODO: Overwrite open() method.
		return true;
	}

	public function read($id): string
	{
		// TODO: Overwrite read() method.
		return '';
	}

	public function write($id, $data): bool
	{
		// TODO: Overwrite write() method.
		return true;
	}
}