<?php

namespace Bitweaver\Plugins;

function smarty_modifier_implode( array $array, string $glue = '' ): string {
	return implode( $glue, $array );
}
