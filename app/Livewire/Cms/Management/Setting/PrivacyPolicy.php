<?php

namespace App\Livewire\Cms\Management\Setting;

use App\Livewire\Forms\Cms\Management\Setting\FormPrivacyPolicy;
use BaseComponent;

class PrivacyPolicy extends BaseComponent
{
    public FormPrivacyPolicy $form;
    public $title = 'Privacy Policy';

    public function mount() {
        $this->isUpdate = true;
        $this->form->getData();
    }

    public function render()
    {
        return view('livewire.cms.management.setting.privacy-policy')->title($this->title);
    }
}
