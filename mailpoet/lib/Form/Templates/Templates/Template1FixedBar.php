<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form\Templates\Templates;

use MailPoet\Form\Templates\FormTemplate;

class Template1FixedBar extends FormTemplate {
  const ID = 'template_1_fixed_bar';

  /** @var string */
  protected $assetsDirectory = 'template-1';

  public function getName(): string {
    return _x('Join the Club', 'Form template name', 'mailpoet');
  }

  public function getThumbnailUrl(): string {
    return $this->getAssetUrl('fixedbar.png');
  }

  public function getBody(): array {
    return [
      [
        'type' => 'divider',
        'params' => [
          'class_name' => '',
          'height' => '1',
          'type' => 'spacer',
          'style' => 'solid',
          'divider_height' => '1',
          'divider_width' => '100',
          'color' => 'black',
        ],
        'id' => 'divider',
        'name' => 'Divider',
      ],
      [
        'type' => 'columns',
        'body' => [
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => 'center',
              'width' => '50',
            ],
            'body' => [
              [
                'type' => 'heading',
                'id' => 'heading',
                'params' => [
                  'content' => $this->wp->wpStaticizeEmoji('🤞') . ' <span style="font-family: BioRhyme" data-font="BioRhyme" class="mailpoet-has-font">' . _x('Don’t miss these tips!', 'Text in a web form.', 'mailpoet') . '</span>',
                  'level' => '1',
                  'align' => 'left',
                  'font_size' => '28',
                  'text_color' => '#313131',
                  'line_height' => '1.2',
                  'background_color' => '',
                  'anchor' => '',
                  'class_name' => '',
                ],
              ],
              [
                'type' => 'paragraph',
                'id' => 'paragraph',
                'params' => [
                  'content' => '<em><span style="font-family: Montserrat" data-font="Montserrat" class="mailpoet-has-font">' . $this->replacePrivacyLinkTags(_x('We don’t spam! Read our [link]privacy policy[/link] for more info.', 'Text in a web form.', 'mailpoet'), "#") . '</span></em>',
                  'drop_cap' => '0',
                  'align' => 'left',
                  'font_size' => '13',
                  'line_height' => '1.5',
                  'text_color' => '',
                  'background_color' => '',
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
                          'bold' => '1',
                          'background_color' => '#eeeeee',
                          'font_color' => '#abb8c3',
                          'border_size' => '0',
                          'border_radius' => '8',
                          'border_color' => '#313131',
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
                        'type' => 'submit',
                        'params' => [
                          'label' => _x('JOIN THE CLUB', 'Form label', 'mailpoet'),
                          'class_name' => '',
                        ],
                        'id' => 'submit',
                        'name' => 'Submit',
                        'styles' => [
                          'full_width' => '1',
                          'bold' => '1',
                          'background_color' => '#000000',
                          'font_size' => '12',
                          'font_color' => '#ffd456',
                          'border_size' => '0',
                          'border_radius' => '8',
                          'padding' => '20',
                          'font_family' => 'Montserrat',
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
      'on_success' => 'message',
      'success_message' => '',
      'segments' => [],
      'segments_selected_by' => 'admin',
      'alignment' => 'left',
      'fontColor' => '#313131',
      'form_placement' => [
        'popup' => ['enabled' => ''],
        'below_posts' => ['enabled' => ''],
        'fixed_bar' => [
          'enabled' => '1',
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
      'border_size' => '10',
      'form_padding' => '0',
      'input_padding' => '16',
      'background_image_display' => 'scale',
      'fontSize' => '16',
      'font_family' => 'Montserrat',
      'success_validation_color' => '#00d084',
      'error_validation_color' => '#cf2e2e',
      'backgroundColor' => '#ffffff',
      'background_image_url' => '',
      'close_button' => 'round_black',
      'border_color' => '#f7f7f7',
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

h1.mailpoet-heading {
	margin: 0 0 10px;
}

p.mailpoet_form_paragraph.last {
    margin-bottom: 0px;
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
