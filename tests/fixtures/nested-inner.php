<?php
/** Test fixture rendered from inside nested-outer.php; prints the query vars it can see. */
printf(
    "inner: innerBlocks=%s className=%s title=%s\n",
    get_query_var('innerBlocks'),
    get_query_var('className'),
    get_query_var('title'),
);
