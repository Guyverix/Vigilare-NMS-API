<?php
declare(strict_types=1);

namespace App\Domain\GlobalMapping;

// Following my verion of CRUD: Create, Retrieve, Update, Delete

interface GlobalMappingRepository {
    public function createGlobalMappingHost($array);
    public function createGlobalMappingHostGroup($array);
    public function createGlobalMappingHostAttribute($array);
    public function createGlobalMappingTrap($array);
    public function createGlobalMappingPoller($array);
    public function createGlobalMappingTemplate();             // Not even going to think aobut this one right now

    public function viewGlobalMappingHost();
    public function viewGlobalMappingHostGroup();
    public function viewGlobalMappingHostAttribute();
    public function viewGlobalMappingTrap();
    public function viewGlobalMappingPoller();
    public function viewGlobalMappingTemplate();               // Not even going to think aobut this one right now

    public function findGlobalMappingHost($array);
    public function findGlobalMappingHostGroup($array);
    public function findGlobalMappingHostAttribute($array);
    public function findGlobalMappingTrap($array);
    public function findGlobalMappingPoller($array);
    public function findGlobalMappingTemplate();               // Not even going to think aobut this one right now

    public function updateGlobalMappingHost($array);
    public function updateGlobalMappingHostGroup($array);
    public function updateGlobalMappingHostAttribute($array);
    public function updateGlobalMappingTrap($array);
    public function updateGlobalMappingPoller($array);
    public function updateGlobalMappingTemplate();             // Not even going to think aobut this one right now

    public function deleteGlobalMappingHost($array);
    public function deleteGlobalMappingHostGroup($array);
    public function deleteGlobalMappingHostAttribute($array);
    public function deleteGlobalMappingTrap($array);
    public function deleteGlobalMappingPoller($array);
    public function deleteGlobalMappingTemplate();             // Not even going to think about this one right now
}
