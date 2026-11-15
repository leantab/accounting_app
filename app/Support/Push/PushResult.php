<?php

namespace App\Support\Push;

enum PushResult
{
    case Sent;

    /** The device no longer exists; its token should be forgotten. */
    case InvalidToken;

    /** A temporary problem; worth trying again. */
    case Failed;
}
