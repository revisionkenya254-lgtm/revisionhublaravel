<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $name = data_get($this->resource, 'name');
        $email = data_get($this->resource, 'email');
        $phone = data_get($this->resource, 'phone');
        $age = data_get($this->resource, 'age');
        $image = data_get($this->resource, 'image');
        $jobTitle = data_get($this->resource, 'job_title');
        $shortBio = data_get($this->resource, 'short_bio');
        $bio = data_get($this->resource, 'bio');
        $gender = data_get($this->resource, 'gender');
        $countryId = data_get($this->resource, 'country_id');
        $state = data_get($this->resource, 'state');
        $city = data_get($this->resource, 'city');
        $address = data_get($this->resource, 'address');
        $facebook = data_get($this->resource, 'facebook');
        $twitter = data_get($this->resource, 'twitter');
        $linkedin = data_get($this->resource, 'linkedin');
        $website = data_get($this->resource, 'website');
        $github = data_get($this->resource, 'github');

        return [
            'id'         => (int) $id,
            'name'       => (string) $name,
            'email'      => (string) $email,
            'phone'      => (string) $phone,
            'age'        => (int) $age,
            'image'      => (string) $image,
            'job_title'  => (string) $jobTitle,
            'short_bio'  => (string) $shortBio,
            'bio'        => (string) $bio,
            'gender'     => (string) $gender,
            'country_id' => (int) $countryId,
            'state'      => (string) $state,
            'city'       => (string) $city,
            'address'    => (string) $address,
            "facebook"   => (string) $facebook,
            "twitter"    => (string) $twitter,
            "linkedin"   => (string) $linkedin,
            "website"    => (string) $website,
            "github"     => (string) $github,
        ];
    }
}
