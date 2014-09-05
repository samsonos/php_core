<?php
//[PHPCOMPRESSOR(remove,start)]
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 05.09.14 at 12:27
 */
 namespace samson\core;

 /**
 * Class to build system execution profile to find weak places
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 */
class Benchmark 
{
    /** Collection of performance benchmarks for analyzing */
    protected $data = array();

    /**
     * Store current benchmark
     * @param string $function  Function name
     * @param array  $args      Function arguments
     * @param string $class     Function class
     */
    public function store($function = __FUNCTION__, $args = array(), $class = __CLASS__)
    {
        $this->data[] = array(
            microtime(true)-__SAMSON_T_STARTED__, 	// Time elapsed from start
            $class.'::'.$function, 		            // Function class::name
            $args, 									// Function arguments
            memory_get_usage(true) 					// Memory
        );
    }

    /**
     * Output to current system output stream benchmark data and total results
     *
     * @param string  $output   Current system output stream
     * @param array   $vars     Associative collection of view data
     * @param iModule $module   Pointer to current module
     */
    public function show(&$output, $vars = null, iModule $module = null)
    {
        // TODO: this is not correct!!!
        if (!in_array($module->id(), array('compressor', 'deploy'))) {
            $output .= '<!-- Total time elapsed:'.round( microtime(TRUE) - __SAMSON_T_STARTED__, 3 ).'s -->';
            if( function_exists('db')) $output .= '<!-- '.db()->profiler().' -->';
            $output .= '<!-- Memory used: '.round(memory_get_peak_usage(true)/1000000,1).' МБ -->';
            $output .= '<!-- Benchmark table: -->';

            $l = 0;
            $m = 0;
            foreach ($this->data as $data) {
                // Generate params string
                $params = array();
                if (is_array( $data[2] )) {
                    foreach ($data[2] as $value) {
                        if (is_string($value)) {
                            $params[] = '"'.(strlen($value) > 10 ? substr($value, 0, 10).'..':$value).'"';
                        }
                    }
                }
                $params = implode( ',', $params );

                $started 		= sprintf( '%5ss', number_format( round($data[0],4), 4 ));
                $elapsed 		= sprintf( ' | %5ss', number_format( round($data[0] - $l,4), 4 ));
                $mem 			= sprintf( ' | %7s МБ',number_format($data[3]/1000000,4));
                $mem_elapsed 	= sprintf( ' | %7s МБ',number_format(($data[3]-$m)/1000000,4));

                $output .= '<!-- '.$started.''.$elapsed.$mem.$mem_elapsed.' | '.$data[1].'('.$params.') -->';

                // Save previous TS
                $l = $data[0];
                $m = $data[3];
            }
        }
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Subscribe to core after render event to show benchmark
        Event::subscribe('core.rendered', array($this, 'show'));
    }

    // Singleton pattern
    /** @var Benchmark Single object instance  */
    private static $instance;

    /** @return Benchmark Get object instance */
    public static function instance()
    {
        return isset(self::$instance) ? self::$instance : (self::$instance = new Benchmark());
    }
}
//[PHPCOMPRESSOR(remove,end)]
 