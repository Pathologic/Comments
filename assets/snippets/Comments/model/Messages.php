<?php namespace Comments\Traits;

/**
 * Trait Messages
 * @package Comments\Traits
 */
trait Messages
{
    protected $messages = array();
    /**
     * @param array $messages
     * @return $this
     */
    public function addMessages (array $messages = array())
    {
        if (!empty($messages)) {
            foreach ($messages as $message) {
                if (is_scalar($message)) {
                    $this->messages[] = $message;
                }
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getMessages ()
    {
        return $this->messages;
    }

    /**
     *
     */
    public function resetMessages ()
    {
        $this->messages = [];

        return $this;
    }
}
