<?php declare(strict_types=1);

namespace Crehler\BaseLinkerShopsApi\Component\BaseLinker\StoreApiStruct;

/**
 * Trait AssignArrayTrait
 */
trait AssignArrayTrait
{
    /**
     * @param array $options
     *
     * @return $this
     */
    public function assign(array $options)
    {
        foreach ($options as $key => $value) {
            if ($key === 'id' && method_exists($this, 'setId')) {
                $this->setId($value);
                continue;
            }

            try {
                $this->$key = $value;
            } catch (\Exception $error) {
                // nth
            }
        }

        return $this;

    }


}
