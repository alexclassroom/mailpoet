/**
 * DraggableBehavior
 *
 * Allows View instances to be draggable.
 * Part of the drag&drop behavior.
 */
import Marionette from 'backbone.marionette';
import _ from 'underscore';
import jQuery from 'jquery';
import { BehaviorsLookup } from 'newsletter-editor/behaviors/behaviors-lookup';
import interact from 'interactjs';
import { App } from 'newsletter-editor/app';

var BL = BehaviorsLookup;

BL.DraggableBehavior = Marionette.Behavior.extend({
  defaults: {
    cloneOriginal: false,
    hideOriginal: false,
    ignoreSelector: '.mailpoet_ignore_drag, .mailpoet_ignore_drag *',
    onDragSubstituteBy: undefined,
    /**
     * Constructs a model that will be passed to the receiver on drop
     *
     * @return Backbone.Model A model that will be passed to the receiver
     */
    getDropModel: function getDropModel() {
      throw new Error("Missing 'drop' function for DraggableBehavior");
    },

    onDrop: function onDrop() {},
    testAttachToInstance: function testAttachToInstance() {
      return true;
    },
  },
  onRender: function onRender() {
    var that = this;
    var interactable;

    // Give instances more control over whether Draggable should be applied
    if (!this.options.testAttachToInstance(this.view.model, this.view)) return;

    interactable = interact(this.$el.get(0))
      .draggable({
        ignoreFrom: this.options.ignoreSelector,
        // allow dragging of multiple elements at the same time
        max: Infinity,
        // Scroll when dragging near edges of a window
        autoScroll: true,

        onstart: function onstart(startEvent) {
          var event = startEvent;
          var centerXOffset;
          var centerYOffset;
          var tempClone;
          var clone;
          var $clone;

          if (event.target.__clone) {
            return;
          }

          // Prevent text selection while dragging
          document.body.classList.add('mailpoet-is-dragging');

          if (that.options.cloneOriginal === true) {
            // Use substitution instead of a clone
            if (_.isFunction(that.options.onDragSubstituteBy)) {
              tempClone = that.options.onDragSubstituteBy(that);
            }
            // Or use a clone
            clone = tempClone || event.target.cloneNode(true);
            $clone = jQuery(clone);

            $clone.addClass('mailpoet_droppable_active');
            $clone.css('position', 'absolute');
            $clone.css('top', 0);
            $clone.css('left', 0);
            document.body.appendChild(clone);

            // Position the clone over the target element with a slight
            // offset to center the clone under the mouse cursor.
            // Accurate dimensions can only be taken after insertion to document
            centerXOffset = $clone.width() / 2;
            centerYOffset = $clone.height() / 2;
            $clone.css('top', event.pageY - centerYOffset);
            $clone.css('left', event.pageX - centerXOffset);

            event.target.__clone = clone;

            if (that.options.hideOriginal === true) {
              that.view.$el.addClass('mailpoet_hidden');
            }
            App.getChannel().trigger('dragStart');
            document.activeElement.blur();
          }
        },
        // call this function on every dragmove event
        onmove: function onmove(event) {
          var target = event.target.__clone;
          var x;
          var y;
          if (!target) {
            return;
          }
          // keep the dragged position in the data-x/data-y attributes
          x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
          y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

          // translate the element
          target.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
          target.style.webkitTransform = target.style.transform;

          // update the posiion attributes
          target.setAttribute('data-x', x);
          target.setAttribute('data-y', y);
        },
        onend: function onend(event) {
          var endEvent = event;
          var target = endEvent.target.__clone;

          // Allow text selection when not dragging
          document.body.classList.remove('mailpoet-is-dragging');

          if (!target) {
            return;
          }
          target.style.transform = '';
          target.style.webkitTransform = target.style.transform;
          target.removeAttribute('data-x');
          target.removeAttribute('data-y');

          if (that.options.cloneOriginal === true) {
            jQuery(target).remove();
            endEvent.target.__clone = undefined;

            if (that.options.hideOriginal === true) {
              that.view.$el.removeClass('mailpoet_hidden');
            }
          }
        },
      })
      .preventDefault('auto')
      .styleCursor(false)
      .actionChecker(function actionChecker(pointer, event, action) {
        // Disable dragging with right click
        if (event.button !== 0) {
          return null;
        }

        return action;
      });

    if (this.options.drop !== undefined) {
      interactable.getDropModel = this.options.drop;
    } else {
      interactable.getDropModel = this.view.getDropFunc();
    }
    interactable.onDrop = function onDrop(opts) {
      var options = opts;
      if (_.isObject(options)) {
        // Inject Draggable behavior if possible
        options.dragBehavior = that;
      }
      // Delegate to view's event handler
      that.options.onDrop.apply(that, [options]);
    };
  },
});
