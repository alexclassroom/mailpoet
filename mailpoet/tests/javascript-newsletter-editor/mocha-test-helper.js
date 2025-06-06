var sinon = require('sinon');
var _ = require('underscore');
var { JSDOM } = require('jsdom');
var URL = require('url').URL;
var nodeCrypto = require('crypto');

global.sinon = sinon;
global.URL = URL;

if (!global.document || !global.window) {
  global.document = new JSDOM(
    '<html><head><script></script></head><body></body></html>',
    {
      url: 'http://example.com',
      runScripts: 'dangerously',
      resources: 'usable',
      pretendToBeVisual: true,
    },
  ).window.document;

  global.window = global.document.defaultView;
  global.navigator = global.window.navigator;
}
const testHelpers = require('./load-helpers.js');
global.testHelpers = testHelpers;

const jQuery = require('jquery');
global.$ = jQuery;
global.jQuery = jQuery;
global.window.jQuery = jQuery;
global._ = _;
global.window.wp = global.window.wp || {
  i18n: {
    getLocaleData: () => {},
  },
};
global.MutationObserver = global.window.MutationObserver;
global.CustomEvent = global.window.CustomEvent;
global.HTMLElement = global.window.HTMLElement;
global.getComputedStyle = global.window.getComputedStyle;

testHelpers.loadScript(
  'tests/javascript-newsletter-editor/testBundles/vendor.js',
  global.window,
);
const Handlebars = global.window.Handlebars;
global.Handlebars = global.window.Handlebars;
// Fix global access for Element and Node. It is used in tinymce
global.Element = global.window.Element;
global.Node = global.window.Node;
// Fix global access for HTMLAnchorElement. It is used in FileSaver
global.HTMLAnchorElement = global.window.HTMLAnchorElement;

// Stub out interact.js
global.interact = () => {
  return {
    draggable: global.interact,
    restrict: global.interact,
    resizable: global.interact,
    on: global.interact,
    dropzone: global.interact,
    preventDefault: global.interact,
    actionChecker: global.interact,
    styleCursor: global.interact,
  };
};
global.spectrum = function spectrum() {
  return this;
};
jQuery.fn.spectrum = global.spectrum;

// Add global stubs for convenience
global.stubChannel = (EditorApplication, returnObject) => {
  var App = EditorApplication;
  App.getChannel = sinon.stub().returns(
    _.defaults(returnObject || {}, {
      request: () => {},
      trigger: () => {},
      on: () => {},
      off: () => {},
    }),
  );
};
global.stubConfig = (EditorApplication, opts) => {
  var App = EditorApplication;
  App.getConfig = sinon
    .stub()
    .returns(new global.Backbone.SuperModel(opts || {}));
};
global.stubAvailableStyles = (EditorApplication, styles) => {
  var App = EditorApplication;
  App.getAvailableStyles = sinon
    .stub()
    .returns(new global.Backbone.SuperModel(styles || {}));
};

global.stubImage = function stubImage(defaultWidth, defaultHeight) {
  global.Image = function Image() {
    this.onload = () => {};
    this.naturalWidth = defaultWidth;
    this.naturalHeight = defaultHeight;
    this.address = '';

    Object.defineProperty(this, 'src', {
      get: function get() {
        return this.address;
      },
      set: function set(src) {
        this.address = src;
        this.onload();
      },
    });
  };
};

// Add simple polyfill for crypto
global.crypto = {
  getRandomValues: (buffer) => nodeCrypto.randomFillSync(buffer),
};

global.window.matchMedia =
  window.matchMedia ||
  (() => {
    return {
      matches: false,
      addListener: () => {},
      removeListener: () => {},
    };
  });

global.window.fontsSelect = () => {};

testHelpers.loadTemplate('blocks/base/toolsGeneric.hbs', window, {
  id: 'newsletter_editor_template_tools_generic',
});

testHelpers.loadTemplate(
  'blocks/automatedLatestContentLayout/block.hbs',
  window,
  { id: 'newsletter_editor_template_automated_latest_content_layout_block' },
);
testHelpers.loadTemplate(
  'blocks/automatedLatestContentLayout/widget.hbs',
  window,
  { id: 'newsletter_editor_template_automated_latest_content_layout_widget' },
);
testHelpers.loadTemplate(
  'blocks/automatedLatestContentLayout/settings.hbs',
  window,
  { id: 'newsletter_editor_template_automated_latest_content_layout_settings' },
);

testHelpers.loadTemplate('blocks/dynamicProducts/block.hbs', window, {
  id: 'newsletter_editor_template_dynamic_products_block',
});
testHelpers.loadTemplate('blocks/dynamicProducts/widget.hbs', window, {
  id: 'newsletter_editor_template_dynamic_products_widget',
});
testHelpers.loadTemplate('blocks/dynamicProducts/settings.hbs', window, {
  id: 'newsletter_editor_template_dynamic_products_settings',
});

testHelpers.loadTemplate('blocks/button/block.hbs', window, {
  id: 'newsletter_editor_template_button_block',
});
testHelpers.loadTemplate('blocks/button/widget.hbs', window, {
  id: 'newsletter_editor_template_button_widget',
});
testHelpers.loadTemplate('blocks/button/settings.hbs', window, {
  id: 'newsletter_editor_template_button_settings',
});

testHelpers.loadTemplate('blocks/container/block.hbs', window, {
  id: 'newsletter_editor_template_container_block',
});
testHelpers.loadTemplate('blocks/container/emptyBlock.hbs', window, {
  id: 'newsletter_editor_template_container_block_empty',
});
testHelpers.loadTemplate('blocks/container/oneColumnLayoutWidget.hbs', window, {
  id: 'newsletter_editor_template_container_one_column_widget',
});
testHelpers.loadTemplate('blocks/container/twoColumnLayoutWidget.hbs', window, {
  id: 'newsletter_editor_template_container_two_column_widget',
});
testHelpers.loadTemplate(
  'blocks/container/twoColumnLayoutWidget12.hbs',
  window,
  { id: 'newsletter_editor_template_container_two_column_12_widget' },
);
testHelpers.loadTemplate(
  'blocks/container/twoColumnLayoutWidget21.hbs',
  window,
  { id: 'newsletter_editor_template_container_two_column_21_widget' },
);
testHelpers.loadTemplate(
  'blocks/container/threeColumnLayoutWidget.hbs',
  window,
  { id: 'newsletter_editor_template_container_three_column_widget' },
);
testHelpers.loadTemplate('blocks/container/settings.hbs', window, {
  id: 'newsletter_editor_template_container_settings',
});
testHelpers.loadTemplate('blocks/container/columnSettings.hbs', window, {
  id: 'newsletter_editor_template_container_column_settings',
});

testHelpers.loadTemplate('blocks/divider/block.hbs', window, {
  id: 'newsletter_editor_template_divider_block',
});
testHelpers.loadTemplate('blocks/divider/widget.hbs', window, {
  id: 'newsletter_editor_template_divider_widget',
});
testHelpers.loadTemplate('blocks/divider/settings.hbs', window, {
  id: 'newsletter_editor_template_divider_settings',
});

testHelpers.loadTemplate('blocks/footer/block.hbs', window, {
  id: 'newsletter_editor_template_footer_block',
});
testHelpers.loadTemplate('blocks/footer/widget.hbs', window, {
  id: 'newsletter_editor_template_footer_widget',
});
testHelpers.loadTemplate('blocks/footer/settings.hbs', window, {
  id: 'newsletter_editor_template_footer_settings',
});

testHelpers.loadTemplate('blocks/header/block.hbs', window, {
  id: 'newsletter_editor_template_header_block',
});
testHelpers.loadTemplate('blocks/header/widget.hbs', window, {
  id: 'newsletter_editor_template_header_widget',
});
testHelpers.loadTemplate('blocks/header/settings.hbs', window, {
  id: 'newsletter_editor_template_header_settings',
});

testHelpers.loadTemplate('blocks/image/block.hbs', window, {
  id: 'newsletter_editor_template_image_block',
});
testHelpers.loadTemplate('blocks/image/widget.hbs', window, {
  id: 'newsletter_editor_template_image_widget',
});
testHelpers.loadTemplate('blocks/image/settings.hbs', window, {
  id: 'newsletter_editor_template_image_settings',
});

testHelpers.loadTemplate('blocks/posts/block.hbs', window, {
  id: 'newsletter_editor_template_posts_block',
});
testHelpers.loadTemplate('blocks/posts/widget.hbs', window, {
  id: 'newsletter_editor_template_posts_widget',
});
testHelpers.loadTemplate('blocks/posts/settings.hbs', window, {
  id: 'newsletter_editor_template_posts_settings',
});
testHelpers.loadTemplate('blocks/posts/settingsDisplayOptions.hbs', window, {
  id: 'newsletter_editor_template_posts_settings_display_options',
});
testHelpers.loadTemplate('blocks/posts/settingsSelection.hbs', window, {
  id: 'newsletter_editor_template_posts_settings_selection',
});
testHelpers.loadTemplate('blocks/posts/settingsSelectionEmpty.hbs', window, {
  id: 'newsletter_editor_template_posts_settings_selection_empty',
});
testHelpers.loadTemplate('blocks/posts/settingsSinglePost.hbs', window, {
  id: 'newsletter_editor_template_posts_settings_single_post',
});

testHelpers.loadTemplate('blocks/products/block.hbs', window, {
  id: 'newsletter_editor_template_products_block',
});
testHelpers.loadTemplate('blocks/products/widget.hbs', window, {
  id: 'newsletter_editor_template_products_widget',
});
testHelpers.loadTemplate('blocks/products/settings.hbs', window, {
  id: 'newsletter_editor_template_products_settings',
});
testHelpers.loadTemplate('blocks/products/settingsDisplayOptions.hbs', window, {
  id: 'newsletter_editor_template_products_settings_display_options',
});
testHelpers.loadTemplate('blocks/products/settingsSelection.hbs', window, {
  id: 'newsletter_editor_template_products_settings_selection',
});
testHelpers.loadTemplate('blocks/products/settingsSelectionEmpty.hbs', window, {
  id: 'newsletter_editor_template_products_settings_selection_empty',
});
testHelpers.loadTemplate('blocks/products/settingsSinglePost.hbs', window, {
  id: 'newsletter_editor_template_products_settings_single_post',
});

testHelpers.loadTemplate('blocks/social/block.hbs', window, {
  id: 'newsletter_editor_template_social_block',
});
testHelpers.loadTemplate('blocks/social/blockIcon.hbs', window, {
  id: 'newsletter_editor_template_social_block_icon',
});
testHelpers.loadTemplate('blocks/social/widget.hbs', window, {
  id: 'newsletter_editor_template_social_widget',
});
testHelpers.loadTemplate('blocks/social/settings.hbs', window, {
  id: 'newsletter_editor_template_social_settings',
});
testHelpers.loadTemplate('blocks/social/settingsIcon.hbs', window, {
  id: 'newsletter_editor_template_social_settings_icon',
});
testHelpers.loadTemplate('blocks/social/settingsIconSelector.hbs', window, {
  id: 'newsletter_editor_template_social_settings_icon_selector',
});
testHelpers.loadTemplate('blocks/social/settingsStyles.hbs', window, {
  id: 'newsletter_editor_template_social_settings_styles',
});

testHelpers.loadTemplate('blocks/spacer/block.hbs', window, {
  id: 'newsletter_editor_template_spacer_block',
});
testHelpers.loadTemplate('blocks/spacer/widget.hbs', window, {
  id: 'newsletter_editor_template_spacer_widget',
});
testHelpers.loadTemplate('blocks/spacer/settings.hbs', window, {
  id: 'newsletter_editor_template_spacer_settings',
});

testHelpers.loadTemplate('blocks/text/block.hbs', window, {
  id: 'newsletter_editor_template_text_block',
});
testHelpers.loadTemplate('blocks/text/widget.hbs', window, {
  id: 'newsletter_editor_template_text_widget',
});
testHelpers.loadTemplate('blocks/text/settings.hbs', window, {
  id: 'newsletter_editor_template_text_settings',
});

testHelpers.loadTemplate('components/heading.hbs', window, {
  id: 'newsletter_editor_template_heading',
});
testHelpers.loadTemplate('components/history.hbs', window, {
  id: 'newsletter_editor_template_history',
});
testHelpers.loadTemplate('components/save.hbs', window, {
  id: 'newsletter_editor_template_save',
});
testHelpers.loadTemplate('components/styles.hbs', window, {
  id: 'newsletter_editor_template_styles',
});

testHelpers.loadTemplate('components/sidebar/sidebar.hbs', window, {
  id: 'newsletter_editor_template_sidebar',
});
testHelpers.loadTemplate('components/sidebar/content.hbs', window, {
  id: 'newsletter_editor_template_sidebar_content',
});
testHelpers.loadTemplate('components/sidebar/layout.hbs', window, {
  id: 'newsletter_editor_template_sidebar_layout',
});
testHelpers.loadTemplate('components/sidebar/styles.hbs', window, {
  id: 'newsletter_editor_template_sidebar_styles',
});

testHelpers.loadTemplate('blocks/coupon/block.hbs', window, {
  id: 'newsletter_editor_template_coupon_block',
});
testHelpers.loadTemplate('blocks/coupon/widget.hbs', window, {
  id: 'newsletter_editor_template_coupon_widget',
});
testHelpers.loadTemplate('blocks/coupon/settings.hbs', window, {
  id: 'newsletter_editor_template_coupon_settings',
});

global.templates = {
  styles: Handlebars.compile(
    jQuery('#newsletter_editor_template_styles').html(),
  ),
  save: Handlebars.compile(jQuery('#newsletter_editor_template_save').html()),
  heading: Handlebars.compile(
    jQuery('#newsletter_editor_template_heading').html(),
  ),
  history: Handlebars.compile(
    jQuery('#newsletter_editor_template_history').html(),
  ),

  sidebar: Handlebars.compile(
    jQuery('#newsletter_editor_template_sidebar').html(),
  ),
  sidebarContent: Handlebars.compile(
    jQuery('#newsletter_editor_template_sidebar_content').html(),
  ),
  sidebarLayout: Handlebars.compile(
    jQuery('#newsletter_editor_template_sidebar_layout').html(),
  ),
  sidebarStyles: Handlebars.compile(
    jQuery('#newsletter_editor_template_sidebar_styles').html(),
  ),

  genericBlockTools: Handlebars.compile(
    jQuery('#newsletter_editor_template_tools_generic').html(),
  ),

  containerBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_container_block').html(),
  ),
  containerEmpty: Handlebars.compile(
    jQuery('#newsletter_editor_template_container_block_empty').html(),
  ),
  oneColumnLayoutInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_container_one_column_widget').html(),
  ),
  twoColumnLayoutInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_container_two_column_widget').html(),
  ),
  twoColumn12LayoutInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_container_two_column_12_widget').html(),
  ),
  twoColumn21LayoutInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_container_two_column_21_widget').html(),
  ),
  threeColumnLayoutInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_container_three_column_widget').html(),
  ),
  containerBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_container_settings').html(),
  ),
  containerBlockColumnSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_container_column_settings').html(),
  ),

  buttonBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_button_block').html(),
  ),
  buttonInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_button_widget').html(),
  ),
  buttonBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_button_settings').html(),
  ),

  dividerBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_divider_block').html(),
  ),
  dividerInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_divider_widget').html(),
  ),
  dividerBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_divider_settings').html(),
  ),

  footerBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_footer_block').html(),
  ),
  footerInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_footer_widget').html(),
  ),
  footerBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_footer_settings').html(),
  ),

  headerBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_header_block').html(),
  ),
  headerInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_header_widget').html(),
  ),
  headerBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_header_settings').html(),
  ),

  imageBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_image_block').html(),
  ),
  imageInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_image_widget').html(),
  ),
  imageBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_image_settings').html(),
  ),

  socialBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_social_block').html(),
  ),
  socialIconBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_social_block_icon').html(),
  ),
  socialInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_social_widget').html(),
  ),
  socialBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_social_settings').html(),
  ),
  socialSettingsIconSelector: Handlebars.compile(
    jQuery('#newsletter_editor_template_social_settings_icon_selector').html(),
  ),
  socialSettingsIcon: Handlebars.compile(
    jQuery('#newsletter_editor_template_social_settings_icon').html(),
  ),
  socialSettingsStyles: Handlebars.compile(
    jQuery('#newsletter_editor_template_social_settings_styles').html(),
  ),

  spacerBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_spacer_block').html(),
  ),
  spacerInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_spacer_widget').html(),
  ),
  spacerBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_spacer_settings').html(),
  ),

  automatedLatestContentLayoutBlock: Handlebars.compile(
    jQuery(
      '#newsletter_editor_template_automated_latest_content_layout_block',
    ).html(),
  ),
  automatedLatestContentLayoutInsertion: Handlebars.compile(
    jQuery(
      '#newsletter_editor_template_automated_latest_content_layout_widget',
    ).html(),
  ),
  automatedLatestContentLayoutBlockSettings: Handlebars.compile(
    jQuery(
      '#newsletter_editor_template_automated_latest_content_layout_settings',
    ).html(),
  ),

  dynamicProductsBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_dynamic_products_block').html(),
  ),
  dynamicProductsInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_dynamic_products_widget').html(),
  ),
  dynamicProductsBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_dynamic_products_settings').html(),
  ),

  postsBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_posts_block').html(),
  ),
  postsInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_posts_widget').html(),
  ),
  postsBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_posts_settings').html(),
  ),
  postSelectionPostsBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_posts_settings_selection').html(),
  ),
  emptyPostPostsBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_posts_settings_selection_empty').html(),
  ),
  singlePostPostsBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_posts_settings_single_post').html(),
  ),
  displayOptionsPostsBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_posts_settings_display_options').html(),
  ),

  productsBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_products_block').html(),
  ),
  productsInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_products_widget').html(),
  ),
  productsBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_products_settings').html(),
  ),
  postSelectionProductsBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_products_settings_selection').html(),
  ),
  emptyPostProductsBlockSettings: Handlebars.compile(
    jQuery(
      '#newsletter_editor_template_products_settings_selection_empty',
    ).html(),
  ),
  singlePostProductsBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_products_settings_single_post').html(),
  ),
  displayOptionsProductsBlockSettings: Handlebars.compile(
    jQuery(
      '#newsletter_editor_template_products_settings_display_options',
    ).html(),
  ),

  textBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_text_block').html(),
  ),
  textInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_text_widget').html(),
  ),
  textBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_text_settings').html(),
  ),

  couponBlock: Handlebars.compile(
    jQuery('#newsletter_editor_template_coupon_block').html(),
  ),
  couponInsertion: Handlebars.compile(
    jQuery('#newsletter_editor_template_coupon_widget').html(),
  ),
  couponBlockSettings: Handlebars.compile(
    jQuery('#newsletter_editor_template_coupon_settings').html(),
  ),
};
global.window.templates = global.templates;
global.window.mailpoet_feature_flags = { brand_templates: true };
