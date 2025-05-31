<?php

// src/Events.php

class Events {
    private static array $handlers = [];

    /**
     * Registers a handler for a specific event.
     *
     * @param string $event_name The name of the event.
     * @param callable $handler The callback function to execute.
     * @param int $priority The priority of the handler (lower numbers execute earlier).
     */
    public static function register(string $event_name, callable $handler, int $priority = 10): void {
        self::$handlers[$event_name][$priority][] = $handler;
        // Sort handlers by priority after adding
        if (isset(self::$handlers[$event_name])) {
            ksort(self::$handlers[$event_name], SORT_NUMERIC);
        }
    }

    /**
     * Dispatches an event, calling all registered handlers.
     *
     * Handlers can modify arguments if they accept them by reference.
     * If a handler returns false (strict comparison), subsequent handlers for that event are skipped.
     *
     * @param string $event_name The name of the event to dispatch.
     * @param array $args Arguments to pass to the handlers. For multiple arguments that need modification,
     *                    it's often best to pass an object or an array that handlers can modify.
     *                    If direct modification of passed args is needed, they must be passed by reference
     *                    to the handlers, and this dispatch method would need to handle that.
     *                    For simplicity, we'll pass args by value, and if modification is needed,
     *                    handlers can return values. This example is simplified and doesn't aggregate return values.
     * @return array|null Returns an array of results from handlers, or null if no handlers.
     *                    More sophisticated logic might be needed based on how results are used.
     *                    The original Perl event system often modified variables in the caller's scope or via references.
     */
    public static function dispatch(string $event_name, array &$args = []): ?array {
        if (!defined('ENABLE_EVENT_HANDLERS') || !ENABLE_EVENT_HANDLERS) {
            return null;
        }

        if (!isset(self::$handlers[$event_name])) {
            return null;
        }

        $results = [];
        foreach (self::$handlers[$event_name] as $priority_level) {
            foreach ($priority_level as $handler) {
                // To allow handlers to modify args directly, call_user_func_array would need references.
                // $result = call_user_func_array($handler, &$args); // This requires careful handling of references in args

                // Simpler: pass args by value, handler can return something if needed.
                // For Wakaba's style, often arguments are passed as a list and handlers might return a modified list.
                // Or specific arguments are modified by reference.
                // This basic dispatcher calls handlers and collects return values.
                // If a handler needs to stop propagation, it can return false.

                // Example: Pass individual arguments from the $args array
                $result = $handler(...$args);

                if ($result === false) { // Strict false to stop propagation
                    break 2; // Break out of both foreach loops
                }
                $results[] = $result; // Collect results (could be modified args or status flags)
            }
        }
        return $results;
    }

    /**
     * A more advanced dispatch method that allows handlers to modify arguments by reference.
     * The primary argument to be modified should be the first one.
     *
     * @param string $event_name The name of the event.
     * @param mixed $modifiable_arg The primary argument that handlers can modify (passed by reference).
     * @param array $other_args Additional arguments.
     * @return bool Returns true if event was handled, false if a handler stopped propagation.
     */
    public static function dispatchWithModifiableArg(string $event_name, &$modifiable_arg, array $other_args = []): bool {
        if (!defined('ENABLE_EVENT_HANDLERS') || !ENABLE_EVENT_HANDLERS) {
            return true; // No handlers, so proceed as normal
        }

        if (!isset(self::$handlers[$event_name])) {
            return true; // No handlers for this event
        }

        $all_args = array_merge([&$modifiable_arg], $other_args);

        foreach (self::$handlers[$event_name] as $priority_level) {
            foreach ($priority_level as $handler) {
                // Use call_user_func_array to pass arguments by reference correctly
                // Note: The handler function signature must also accept the argument by reference.
                // Example handler: function my_event_handler(&$arg1, $arg2) { ... }
                $return_value = call_user_func_array($handler, $all_args);

                if ($return_value === false) { // Strict false to stop propagation
                    return false;
                }
            }
        }
        return true;
    }
}

// Example of how to register an event (would be in an event_setup.php or similar)
/*
if (defined('ENABLE_EVENT_HANDLERS') && ENABLE_EVENT_HANDLERS) {
    Events::register('before_post_display', function(&$post_data) {
        $post_data['comment'] .= "<p>Event handler modified this!</p>";
        // return true; // Continue propagation
    });
}
*/
