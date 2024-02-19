<?php
declare(strict_types=1);

namespace App\Domain\GlobalMapping;

use JsonSerializable;

class GlobalMapping implements JsonSerializable {

    /**
     * @var string
     */
  private $oid;
  private $display_name;
  private $severity;
  private $pre_processing;
  private $type;
  private $parent_of;
  private $child_of;
  private $age_out;
  private $post_processing;

  public function __construct($array) {
    $this->oid = $array['oid'];
    $this->display_name = $array['display_name'];
    $this->severity = $array['severity'];
    $this->pre_processing = $array['pre_processing'];
    $this->type = $array['type'];
    $this->parent_of = $array['parent_of'];
    $this->child_of = $array['child_of'];
    $this->age_out = $array['age_out'];
    $this->post_processing = $array['post_processing'];
    }

    public function jsonSerialize() {
      return [
        'oid' => $this->oid,
        'display_name' => $this->display_name,
        'severity' => $this->severity,
        'pre_processing' => $this->pre_processing,
        'type' => $this->type,
        'parent_of' => $this->parent_of,
        'child_of' => $this->child_of,
        'age_out' => $this->age_out,
        'post_processing' => $this->post_processing,
        ];
    }
}
