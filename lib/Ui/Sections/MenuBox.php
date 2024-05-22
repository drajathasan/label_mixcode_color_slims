<?php
namespace Mixcode\Ui\Sections;

class MenuBox extends Base
{
    public function __toString(): string
    {
        return <<<HTML
        <div class="menuBox">
            <div class="menuBoxInner biblioIcon">
                <div class="per_title">
                    <h2>{$this->title}</h2>
                </div>
                <div class="sub_section">
                    <div class="btn-group">
                        {$this->button}
                    </div>
                    {$this->form}
                </div>
                {$this->etc}
            </div>
        </div>
        HTML;
    }
}