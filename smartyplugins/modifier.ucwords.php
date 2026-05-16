<?php

namespace Bitweaver\Plugins;

function smarty_modifier_ucwords( ?string $string ): string {
	return ucwords( $string ?? '' );
}
