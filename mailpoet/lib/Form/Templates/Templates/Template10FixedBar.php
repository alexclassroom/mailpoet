<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form\Templates\Templates;

use MailPoet\Form\Templates\FormTemplate;

class Template10FixedBar extends FormTemplate {
  const ID = 'template_10_fixed_bar';

  /** @var string */
  protected $assetsDirectory = 'template-10';

  public function getName(): string {
    return _x('Keep in Touch', 'Form template name', 'mailpoet');
  }

  public function getThumbnailUrl(): string {
    return $this->getAssetUrl('fixedbar.png');
  }

  public function getBody(): array {
    return [
      [
        'type' => 'columns',
        'body' => [
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => '',
              'width' => '50',
            ],
            'body' => [
              [
                'type' => 'heading',
                'id' => 'heading',
                'params' => [
                  'content' => '<strong><span style="font-family: Concert One" data-font="Concert One" class="mailpoet-has-font">' . _x('LET’S KEEP IN TOUCH!', 'Text in a web form.', 'mailpoet') . '</span></strong> ' . $this->wp->wpStaticizeEmoji('😎'),
                  'level' => '2',
                  'align' => 'left',
                  'font_size' => '40',
                  'text_color' => '#ffffff',
                  'line_height' => '1.5',
                  'background_color' => '',
                  'anchor' => '',
                  'class_name' => '',
                ],
              ],
            ],
          ],
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => '',
              'width' => '50',
            ],
            'body' => [
              [
                'type' => 'paragraph',
                'id' => 'paragraph',
                'params' => [
                  'content' => '<span style="font-family: Concert One" data-font="Concert One" class="mailpoet-has-font">' . $this->replacePrivacyLinkTags(_x('We’d love to keep you updated with our latest news! We promise we’ll never spam. Take a look at our [link]Privacy Policy[/link] for more details.', 'Text in a web form.', 'mailpoet'), '#') . '</span>',
                  'drop_cap' => '0',
                  'align' => 'left',
                  'font_size' => '20',
                  'line_height' => '1.2',
                  'text_color' => '#ffffff',
                  'background_color' => '',
                  'class_name' => '',
                ],
              ],
            ],
          ],
        ],
        'params' => [
          'vertical_alignment' => '',
          'class_name' => '',
          'text_color' => '',
          'background_color' => '',
          'gradient' => '',
        ],
      ],
      [
        'type' => 'columns',
        'body' => [
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => '',
              'width' => '25',
            ],
            'body' => [
              [
                'type' => 'text',
                'params' => [
                  'label' => _x('What’s your name?', 'Form label', 'mailpoet'),
                  'class_name' => '',
                  'label_within' => '1',
                ],
                'id' => 'first_name',
                'name' => 'First name',
                'styles' => [
                  'full_width' => '1',
                  'bold' => '0',
                  'background_color' => '#ffffff',
                  'font_color' => '#5b8ba7',
                  'border_size' => '0',
                  'border_radius' => '4',
                ],
              ],
            ],
          ],
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => '',
              'width' => '25',
            ],
            'body' => [
              [
                'type' => 'text',
                'params' => [
                  'label' => _x('And your surname?', 'Form label', 'mailpoet'),
                  'class_name' => '',
                  'label_within' => '1',
                ],
                'id' => 'last_name',
                'name' => 'Last name',
                'styles' => [
                  'full_width' => '1',
                  'bold' => '0',
                  'background_color' => '#ffffff',
                  'font_color' => '#5b8ba7',
                  'border_size' => '0',
                  'border_radius' => '4',
                ],
              ],
            ],
          ],
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => '',
              'width' => '25',
            ],
            'body' => [
              [
                'type' => 'text',
                'params' => [
                  'label' => _x('Email Address', 'Form label', 'mailpoet'),
                  'class_name' => '',
                  'required' => '1',
                  'label_within' => '1',
                ],
                'id' => 'email',
                'name' => 'Email',
                'styles' => [
                  'full_width' => '1',
                  'bold' => '0',
                  'background_color' => '#ffffff',
                  'font_color' => '#5b8ba7',
                  'border_size' => '0',
                  'border_radius' => '4',
                ],
              ],
            ],
          ],
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => '',
              'width' => '25',
            ],
            'body' => [
              [
                'type' => 'submit',
                'params' => [
                  'label' => _x('Keep me posted!', 'Form label', 'mailpoet'),
                  'class_name' => '',
                ],
                'id' => 'submit',
                'name' => 'Submit',
                'styles' => [
                  'full_width' => '1',
                  'bold' => '1',
                  'background_color' => '#ff6900',
                  'font_size' => '21',
                  'font_color' => '#ffffff',
                  'border_size' => '0',
                  'border_radius' => '4',
                  'padding' => '12',
                  'font_family' => 'Ubuntu',
                ],
              ],
            ],
          ],
        ],
        'params' => [
          'vertical_alignment' => '',
          'class_name' => '',
          'text_color' => '',
          'background_color' => '',
          'gradient' => '',
        ],
      ],
    ];
  }

  public function getSettings(): array {
    return [
      'success_message' => '',
      'segments' => [],
      'alignment' => 'left',
      'fontSize' => '20',
      'form_placement' => [
        'popup' => ['enabled' => ''],
        'below_posts' => ['enabled' => ''],
        'fixed_bar' => [
          'enabled' => '1',
          'position' => 'bottom',
          'styles' => [
            'width' => [
              'unit' => 'pixel',
              'value' => '1100',
            ],
          ],
        ],
        'slide_in' => ['enabled' => ''],
        'others' => [],
      ],
      'border_radius' => '0',
      'border_size' => '0',
      'form_padding' => '40',
      'input_padding' => '12',
      'background_image_url' => '',
      'background_image_display' => 'scale',
      'close_button' => 'classic_white',
      'segments_selected_by' => 'admin',
      'gradient' => 'linear-gradient(180deg,rgb(70,219,232) 0%,rgb(197,222,213) 100%)',
      'success_validation_color' => '#00d084',
      'error_validation_color' => '#cf2e2e',
      'font_family' => 'Ubuntu',
    ];
  }

  public function getStyles(): string {
    return <<<EOL
/* form */
.mailpoet_form {
}

form {
  margin-bottom: 0;
}

p.mailpoet_form_paragraph.last {
    margin-bottom: 0px;
}

h2.mailpoet-heading {
    margin: -10px 0 10px 0;
}

/* columns */
.mailpoet_column_with_background {
  padding: 10px;
}
/* space between columns */
.mailpoet_form_column:not(:first-child) {
  margin-left: 20px;
}

/* input wrapper (label + input) */
.mailpoet_paragraph {
  line-height:20px;
  margin-bottom: 20px;
}

/* labels */
.mailpoet_segment_label,
.mailpoet_text_label,
.mailpoet_textarea_label,
.mailpoet_select_label,
.mailpoet_radio_label,
.mailpoet_checkbox_label,
.mailpoet_list_label,
.mailpoet_date_label {
  display:block;
  font-weight: normal;
}

/* inputs */
.mailpoet_text,
.mailpoet_textarea,
.mailpoet_select,
.mailpoet_date_month,
.mailpoet_date_day,
.mailpoet_date_year,
.mailpoet_date {
  display:block;
}

.mailpoet_text,
.mailpoet_textarea {
  width: 200px;
}

.mailpoet_checkbox {
}

.mailpoet_submit {
}

.mailpoet_divider {
}

.mailpoet_message {
}

.mailpoet_form_loading {
  width: 30px;
  text-align: center;
  line-height: normal;
}

.mailpoet_form_loading > span {
  width: 5px;
  height: 5px;
  background-color: #5b5b5b;
}
EOL;
  }
}
