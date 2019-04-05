<?php

return [
	'Cache Path' => 'Cache Path',
	'The cache storage path (must be absolute)' => 'The cache storage path (must be absolute)',

	'Max Size' => 'Max Size',
	'The max size the stored cache can use. [How to format size](http://nginx.org/en/docs/syntax.html).' =>
		'The max size the stored cache can use. [How to format size](http://nginx.org/en/docs/syntax.html).',

	'Inactive' => 'Inactive',
	'The amount of time a file has to be inactive before the cache is automatically cleared. [How to format time]|(http://nginx.org/en/docs/syntax.html).' =>
		'The amount of time a file has to be inactive before the cache is automatically cleared. [How to format time]|(http://nginx.org/en/docs/syntax.html).',

	'Include Query String' => 'Include Query String',
	'Whether or not to include the query string when caching' => 'Whether or not to include the query string when caching',

	'Cache Duration' => 'Cache Duration',
	'How long to cache specific responses' => 'How long to cache specific responses',
	'Response Type' => 'Response Type',
	'Duration' => 'Duration',
	'Add Cache Duration' => 'Add Cache Duration',

	'Serve Stale on Error' => 'Serve Stale on Error',
	'Will serve stale content (if available) on a 50x response' => 'Will serve stale content (if available) on a 50x response',

	'Included URIs' => 'Included URIs',
	'URIs to include when caching' => 'URIs to include when caching',

	'Excluded URIs' => 'Excluded URIs',
	'URIs to exclude when caching' => 'URIs to exclude when caching',

	'Add URI' => 'Add URI',

	'Reload Command' => 'Reload Command',
	'The command to reload nginx (won\'t run if blank).' => 'The command to reload nginx (won\'t run if blank).',
];
