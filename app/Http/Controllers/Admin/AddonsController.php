<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomAddon;
use Nwidart\Modules\Facades\Module;

class AddonsController extends Controller
{
    public function syncModules()
    {
        foreach (Module::toCollection() as $module) {
            if ($module = Module::find($module)) {
                $getJsonFileLocation = $module->getPath().'/wsus.json';

                if (file_exists($getJsonFileLocation)) {
                    $wsusJsonData = json_decode(file_get_contents($getJsonFileLocation), true);

                    if (is_array($wsusJsonData) && count($wsusJsonData) > 0) {
                        $addon = CustomAddon::where('slug', $module)->first();
                        if (! $addon) {
                            $addon = new CustomAddon();
                            $addon->slug = $module;
                            foreach ($wsusJsonData as $key => $value) {
                                if (!in_array($key, ['name', 'is_default', 'isPaid', 'description', 'author', 'options', 'icon', 'url', 'version', 'last_update'], true)) {
                                    continue;
                                }

                                $addon->$key = is_array($value) ? json_encode($value) : $value;
                            }
                            $addon->status = 1;
                            $addon->save();
                        }
                    }
                }
            }
        }
    }
}
