import { App } from 'newsletter-editor/app';
import { DynamicProductsBlock } from 'newsletter-editor/blocks/dynamic-products';
import { ContainerBlock } from 'newsletter-editor/blocks/container';
import { CommunicationComponent } from 'newsletter-editor/components/communication';
import DynamicProductsInjector from 'inject-loader!newsletter-editor/blocks/dynamic-products';

const expect = global.expect;
const sinon = global.sinon;
const Backbone = global.Backbone;
const jQuery = global.jQuery;

var EditorApplication = App;

describe('Dynamic Products Supervisor', function () {
  var model;
  var mock;
  var module;
  beforeEach(function () {
    model = new DynamicProductsBlock.DynamicProductsSupervisor();
  });

  it('fetches products in bulk from the server', function () {
    global.stubChannel(EditorApplication);
    EditorApplication.findModels = sinon
      .stub()
      .returns([new Backbone.SuperModel()]);

    mock = sinon
      .mock({ getBulkTransformedProducts: function () {} })
      .expects('getBulkTransformedProducts')
      .once()
      .returns(jQuery.Deferred());

    module = DynamicProductsInjector({
      'newsletter-editor/components/communication': {
        CommunicationComponent: {
          getBulkTransformedProducts: mock,
        },
      },
    }).DynamicProductsBlock;

    model = new module.DynamicProductsSupervisor();
    model.refresh();

    mock.verify();
  });

  it('refreshes products for given blocks', function () {
    var block1 = new Backbone.SuperModel();
    var block2 = new Backbone.SuperModel();
    var productsSet1 = [{ type: 'product' }];
    var productsSet2 = [{ type: 'product' }, { type: 'product' }];
    var mock1 = sinon.mock(block1);
    var mock2 = sinon.mock(block2);

    mock1.expects('trigger').once().withArgs('refreshPosts', productsSet1);
    mock2.expects('trigger').once().withArgs('refreshPosts', productsSet2);

    model.refreshBlocks([block1, block2], [productsSet1, productsSet2]);

    mock1.verify();
    mock2.verify();
  });
});

describe('Dynamic Products', function () {
  describe('model', function () {
    var model;
    var module;
    var sandbox;

    before(function () {
      module = DynamicProductsBlock;
    });

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      EditorApplication.getBlockTypeModel = sinon
        .stub()
        .returns(Backbone.SuperModel);
      model = new module.DynamicProductsBlockModel();
      sandbox = sinon.createSandbox();
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
      delete EditorApplication.getBlockTypeModel;
      sandbox.restore();
    });

    it('has dynamicProducts type', function () {
      expect(model.get('type')).to.equal('dynamicProducts');
    });

    it('has product amount limit', function () {
      expect(model.get('amount')).to.match(/^\d+$/);
    });

    it('has product type filter', function () {
      expect(model.get('contentType')).to.equal('product');
    });

    it('has terms filter', function () {
      expect(model.get('terms')).to.have.length(0);
    });

    it('has inclusion filter', function () {
      expect(model.get('inclusionType')).to.match(/^(include|exclude)$/);
    });

    it('has display type', function () {
      expect(model.get('displayType')).to.match(/^(excerpt|full|titleOnly)$/);
    });

    it('has title heading format', function () {
      expect(model.get('titleFormat')).to.match(/^(h1|h2|h3|ul)$/);
    });

    it('has title alignment', function () {
      expect(model.get('titleAlignment')).to.match(/^(left|center|right)$/);
    });

    it('optionally has title as link', function () {
      expect(model.get('titleIsLink')).to.be.a('boolean');
    });

    it('has image width', function () {
      expect(model.get('imageFullWidth')).to.be.a('boolean');
    });

    it('has featured image position', function () {
      expect(model.get('featuredImagePosition')).to.match(
        /^(centered|left|right|alternate|none)$/,
      );
    });

    it('has title position', function () {
      expect(model.get('titlePosition')).to.match(/^(abovePost|aboveExcerpt)$/);
    });

    it('has price position', function () {
      expect(model.get('pricePosition')).to.match(/^(hidden|above|below)$/);
    });

    it('has dynamic products type', function () {
      expect(model.get('dynamicProductsType')).to.match(
        /^(cross-sell|order|selected|cart)$/,
      );
    });

    it('has a link or a button type for read more', function () {
      expect(model.get('readMoreType')).to.match(/^(link|button)$/);
    });

    it('has read more text', function () {
      expect(model.get('readMoreText')).to.be.a('string');
    });

    it('has a read more button', function () {
      expect(model.get('readMoreButton')).to.be.instanceof(Backbone.Model);
    });

    it('has sorting', function () {
      expect(model.get('sortBy')).to.match(/^(newest|oldest)$/);
    });

    it('has an option to display divider', function () {
      expect(model.get('showDivider')).to.be.a('boolean');
    });

    it('has a divider', function () {
      expect(model.get('divider')).to.be.instanceof(Backbone.Model);
    });

    it('uses defaults from config when they are set', function () {
      global.stubConfig(EditorApplication, {
        blockDefaults: {
          dynamicProducts: {
            amount: '17',
            contentType: 'product',
            inclusionType: 'exclude',
            displayType: 'full',
            titleFormat: 'h3',
            titleAlignment: 'right',
            titleIsLink: true,
            imageFullWidth: false,
            featuredImagePosition: 'aboveTitle',
            titlePosition: 'aboveExcerpt',
            pricePosition: 'below',
            dynamicProductsType: 'selected',
            readMoreType: 'button',
            readMoreText: 'Custom Config read more text',
            readMoreButton: {
              text: 'Custom config read more',
              url: '[postLink]',
              styles: {
                block: {
                  backgroundColor: '#123456',
                  borderColor: '#234567',
                },
                link: {
                  fontColor: '#345678',
                  fontFamily: 'Tahoma',
                  fontSize: '37px',
                },
              },
            },
            sortBy: 'oldest',
            showDivider: true,
            divider: {
              src: 'http://example.org/someConfigDividerImage.png',
              styles: {
                block: {
                  backgroundColor: '#456789',
                  padding: '38px',
                },
              },
            },
          },
        },
      });
      model = new module.DynamicProductsBlockModel();

      expect(model.get('amount')).to.equal('17');
      expect(model.get('contentType')).to.equal('product');
      expect(model.get('inclusionType')).to.equal('exclude');
      expect(model.get('displayType')).to.equal('full');
      expect(model.get('titleFormat')).to.equal('h3');
      expect(model.get('titleAlignment')).to.equal('right');
      expect(model.get('titleIsLink')).to.equal(true);
      expect(model.get('imageFullWidth')).to.equal(false);
      expect(model.get('featuredImagePosition')).to.equal('aboveTitle');
      expect(model.get('titlePosition')).to.equal('aboveExcerpt');
      expect(model.get('pricePosition')).to.equal('below');
      expect(model.get('dynamicProductsType')).to.equal('selected');
      expect(model.get('readMoreType')).to.equal('button');
      expect(model.get('readMoreText')).to.equal(
        'Custom Config read more text',
      );
      expect(model.get('readMoreButton.text')).to.equal(
        'Custom config read more',
      );
      expect(model.get('readMoreButton.url')).to.equal('[postLink]');
      expect(model.get('readMoreButton.styles.block.backgroundColor')).to.equal(
        '#123456',
      );
      expect(model.get('readMoreButton.styles.block.borderColor')).to.equal(
        '#234567',
      );
      expect(model.get('readMoreButton.styles.link.fontColor')).to.equal(
        '#345678',
      );
      expect(model.get('readMoreButton.styles.link.fontFamily')).to.equal(
        'Tahoma',
      );
      expect(model.get('readMoreButton.styles.link.fontSize')).to.equal('37px');
      expect(model.get('sortBy')).to.equal('oldest');
      expect(model.get('showDivider')).to.equal(true);
      expect(model.get('divider.src')).to.equal(
        'http://example.org/someConfigDividerImage.png',
      );
      expect(model.get('divider.styles.block.backgroundColor')).to.equal(
        '#456789',
      );
      expect(model.get('divider.styles.block.padding')).to.equal('38px');
    });

    it('accepts displayable products', function () {
      EditorApplication.getBlockTypeModel = sinon
        .stub()
        .returns(ContainerBlock.ContainerBlockModel);
      model = new module.DynamicProductsBlockModel();

      model.updatePosts([
        {
          type: 'product',
        },
      ]);

      expect(model.get('_container.blocks').size()).to.equal(1);
      expect(model.get('_container.blocks').first().get('type')).to.equal(
        'product',
      );
    });

    it('updates blockDefaults.dynamicProducts when handling changes', function () {
      var stub = sandbox.stub(EditorApplication.getConfig(), 'set');
      model.set('amount', '17');
      model.set('contentType', 'product');
      model.set('terms', []);
      model.set('inclusionType', 'exclude');
      model.set('titleFormat', 'h3');
      model.set('titleAlignment', 'right');
      model.set('titleIsLink', true);
      model.set('imageFullWidth', true);
      model.set('featuredImagePosition', 'aboveTitle');
      model.set('titlePosition', 'aboveExcerpt');
      model.set('pricePosition', 'below');
      model.set('dynamicProductsType', 'selected');
      model.set('showDivider', false);
      expect(stub.callCount).to.equal(10);
      expect(stub.getCall(9).args[0]).to.equal('blockDefaults.dynamicProducts');
      expect(stub.getCall(9).args[1]).to.deep.equal(model.toJSON());
    });
  });

  describe('block view', function () {
    var model;
    var view;
    var module;

    before(function () {
      module = DynamicProductsBlock;
    });

    beforeEach(function () {
      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication);
      EditorApplication.getBlockTypeModel = sinon
        .stub()
        .returns(Backbone.Model);
      EditorApplication.getBlockTypeView = sinon.stub().returns(Backbone.View);
      model = new module.DynamicProductsBlockModel();
      view = new module.DynamicProductsBlockView({ model: model });
    });

    afterEach(function () {
      delete EditorApplication.getChannel;
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
      expect(view.$('.mailpoet_content')).to.have.length(1);
    });
  });

  describe('replaceAllButtonStyles', function () {
    var onStub;
    var view;

    beforeEach(function () {
      onStub = sinon.stub();
      global.stubChannel(EditorApplication, { on: onStub });
      global.stubConfig(EditorApplication);
      EditorApplication.getBlockTypeModel = sinon
        .stub()
        .returns(Backbone.Model);
      EditorApplication.getBlockTypeView = sinon.stub().returns(Backbone.View);
      view = new DynamicProductsBlock.DynamicProductsBlockView({
        model: { set: sinon.stub() },
      });
    });

    it('listens to the event', function () {
      expect(onStub).to.have.been.callCount(1);
      expect(onStub).to.have.been.calledWith(
        'replaceAllButtonStyles',
        sinon.match.func,
      );
    });

    it('updates the model', function () {
      const callback = onStub.getCall(0).args[1];
      const data = {
        styles: {
          block: {
            borderRadius: '14px',
          },
        },
      };
      callback(data);
      expect(view.model.set).to.have.been.callCount(1);
      expect(view.model.set).to.have.been.calledWithMatch(
        sinon.match.has('readMoreButton', data),
      );
    });
  });

  describe('block settings view', function () {
    var model;
    var view;
    var module;

    before(function () {
      module = DynamicProductsInjector({
        'newsletter-editor/components/communication': {
          CommunicationComponent: {
            getPostTypes: function () {
              return jQuery.Deferred();
            },
          },
        },
      }).DynamicProductsBlock;
    });

    before(function () {
      CommunicationComponent.getPostTypes = function () {
        var deferred = jQuery.Deferred();
        deferred.resolve([
          {
            name: 'product',
            labels: {
              singular_name: 'Product',
            },
          },
        ]);
        return deferred;
      };

      global.stubChannel(EditorApplication);
      global.stubConfig(EditorApplication, {
        blockDefaults: {},
      });
      EditorApplication.getBlockTypeModel = sinon
        .stub()
        .returns(Backbone.Model);
      EditorApplication.getBlockTypeView = sinon.stub().returns(Backbone.View);
    });

    beforeEach(function () {
      model = new module.DynamicProductsBlockModel();
      view = new module.DynamicProductsBlockSettingsView({
        model: model,
      });
    });

    after(function () {
      delete EditorApplication.getChannel;
    });

    it('renders', function () {
      expect(view.render).to.not.throw();
    });

    describe('once rendered', function () {
      beforeEach(function () {
        model = new module.DynamicProductsBlockModel();
        view = new module.DynamicProductsBlockSettingsView({
          model: model,
        });
        view.render();
      });

      it('changes the model if product amount changes', function () {
        var newValue = '11';
        view
          .$('.mailpoet_dynamic_products_show_amount')
          .val(newValue)
          .trigger('input');
        expect(model.get('amount')).to.equal(newValue);
      });

      it('changes the model if dynamic products type changes', function () {
        var newValue = 'cross-sell';
        view
          .$('.mailpoet_dynamic_products_dynamic_products_type')
          .val(newValue)
          .trigger('change');
        expect(model.get('dynamicProductsType')).to.equal(newValue);
      });

      it('changes the model if inclusion type changes', function () {
        var newValue = 'exclude';
        view
          .$('.mailpoet_dynamic_products_include_or_exclude')
          .val(newValue)
          .trigger('change');
        expect(model.get('inclusionType')).to.equal(newValue);
      });

      it('changes the model if display type changes', function () {
        var newValue = 'full';
        view
          .$('.mailpoet_dynamic_products_display_type')
          .val(newValue)
          .trigger('change');
        expect(model.get('displayType')).to.equal(newValue);
      });

      it('changes the model if title format changes', function () {
        var newValue = 'h3';
        view
          .$('.mailpoet_dynamic_products_title_format')
          .val(newValue)
          .trigger('change');
        expect(model.get('titleFormat')).to.equal(newValue);
      });

      it('changes the model if title alignment changes', function () {
        var newValue = 'right';
        view
          .$('.mailpoet_dynamic_products_title_alignment')
          .val(newValue)
          .trigger('change');
        expect(model.get('titleAlignment')).to.equal(newValue);
      });

      it('changes the model if title link changes', function () {
        var newValue = true;
        view
          .$('.mailpoet_dynamic_products_title_as_links')
          .val(newValue)
          .trigger('change');
        expect(model.get('titleIsLink')).to.equal(newValue);
      });

      it('changes the model if image alignment changes', function () {
        var newValue = false;
        view
          .$('.mailpoet_dynamic_products_image_full_width')
          .val(newValue)
          .trigger('change');
        expect(model.get('imageFullWidth')).to.equal(newValue);
      });

      it('changes the model if price position changes', function () {
        var newValue = 'above';
        view
          .$('.mailpoet_dynamic_products_price_position')
          .val(newValue)
          .trigger('change');
        expect(model.get('pricePosition')).to.equal(newValue);
      });

      it('changes the model if featured image position changes for excerpt display type', function () {
        var newValue = 'right';
        model.set('displayType', 'excerpt');
        view
          .$('.mailpoet_dynamic_products_featured_image_position')
          .val(newValue)
          .trigger('change');
        expect(model.get('featuredImagePosition')).to.equal(newValue);
        expect(model.get('_featuredImagePosition')).to.equal(newValue);
      });

      it('changes the model if featured image position changes', function () {
        var newValue = 'aboveExcerpt';
        view
          .$('.mailpoet_dynamic_products_title_position')
          .val(newValue)
          .trigger('change');
        expect(model.get('titlePosition')).to.equal(newValue);
      });

      it('changes the model if read more button type changes', function () {
        var newValue = 'link';
        view
          .$('.mailpoet_dynamic_products_read_more_type')
          .val(newValue)
          .trigger('change');
        expect(model.get('readMoreType')).to.equal(newValue);
      });

      it('changes the model if read more text changes', function () {
        var newValue = 'New read more text';
        view
          .$('.mailpoet_dynamic_products_read_more_text')
          .val(newValue)
          .trigger('input');
        expect(model.get('readMoreText')).to.equal(newValue);
      });

      it('changes the model if show divider changes', function () {
        var newValue = true;
        view
          .$('.mailpoet_dynamic_products_show_divider')
          .val(newValue)
          .trigger('change');
        expect(model.get('showDivider')).to.equal(newValue);
      });

      describe('when "title only" display type is selected', function () {
        beforeEach(function () {
          model = new module.DynamicProductsBlockModel();
          view = new module.DynamicProductsBlockSettingsView({
            model: model,
          });
          view.render();
          view
            .$('.mailpoet_dynamic_products_display_type')
            .val('titleOnly')
            .trigger('change');
        });

        it('shows "title as list" option', function () {
          expect(
            view.$('.mailpoet_dynamic_products_title_as_list'),
          ).to.not.have.$class('mailpoet_hidden');
        });

        describe('when "title as list" is selected', function () {
          beforeEach(function () {
            model = new module.DynamicProductsBlockModel();
            view = new module.DynamicProductsBlockSettingsView({
              model: model,
            });
            view.render();
            view
              .$('.mailpoet_dynamic_products_display_type')
              .val('titleOnly')
              .trigger('change');
            view
              .$('.mailpoet_dynamic_products_title_format')
              .val('ul')
              .trigger('change');
          });

          describe('"title is link" option', function () {
            it('is set to "yes"', function () {
              expect(model.get('titleIsLink')).to.equal(true);
            });
          });
        });

        describe('when "title as list" is deselected', function () {
          before(function () {
            view
              .$('.mailpoet_dynamic_products_title_format')
              .val('ul')
              .trigger('change');
            view
              .$('.mailpoet_dynamic_products_title_format')
              .val('h3')
              .trigger('change');
          });

          describe('"title is link" option', function () {
            it('is visible', function () {
              expect(
                view.$('.mailpoet_dynamic_products_title_as_link'),
              ).to.not.have.$class('mailpoet_hidden');
            });
          });
        });
      });
    });
  });
});
