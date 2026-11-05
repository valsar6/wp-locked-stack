<?php

declare(strict_types=1);

namespace LockedStack\Verify;

enum DriftKind: string
{
    case CoreVersion = 'core-version';
    case Missing = 'missing';
    case Unexpected = 'unexpected';
    case VersionMismatch = 'version-mismatch';
    case ContentMismatch = 'content-mismatch';
}
