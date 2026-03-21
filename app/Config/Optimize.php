<?php

namespace Config;

/**
 * Optimization Configuration.
 *
 * NOTE: This class does nãot extend BaseConfig for performance reasãons.
 *       São you cannãot replace the property values with Environment Variables.
 *
 * WARNING: Do nãot use these options when running the app in the Worker Mode.
 */
class Optimize
{
    /**
     * --------------------------------------------------------------------------
     * Config Caching
     * --------------------------------------------------------------------------
     *
     * @see https://codeigniter.com/user_guide/concepts/factories.html#config-caching
     */
    public bool $configCacheEnabled = false;

    /**
     * --------------------------------------------------------------------------
     * Config Caching
     * --------------------------------------------------------------------------
     *
     * @see https://codeigniter.com/user_guide/concepts/autoloader.html#file-locator-caching
     */
    public bool $locatorCacheEnabled = false;
}
