/* eslint-disable func-names */
import { App } from 'newsletter-editor/app';
import _ from 'underscore';
import { MailPoet } from 'mailpoet';
import 'ajax';

var Module = {};

Module._query = function (args) {
  return MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'automatedLatestContent',
    action: args.action,
    data: args.options || {},
  });
};
Module._cachedQuery = _.memoize(Module._query, JSON.stringify);

Module._dynamicProductsQuery = function (args) {
  return MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'dynamicProducts',
    action: args.action,
    data: args.options || {},
  });
};
Module._cachedDynamicProductsQuery = _.memoize(
  Module._dynamicProductsQuery,
  JSON.stringify,
);

Module.getNewsletter = function (options) {
  return Module._query({
    action: 'get',
    options: options,
  });
};

Module.getPostTypes = function () {
  return Module._cachedQuery({
    action: 'getPostTypes',
    options: {},
  }).then(function (response) {
    return _.values(response.data);
  });
};

Module.getTaxonomies = function (postType) {
  return Module._cachedQuery({
    action: 'getTaxonomies',
    options: {
      postType: postType,
    },
  }).then(function (response) {
    return response.data;
  });
};

Module.getProductTaxonomies = function (postType) {
  return Module._cachedDynamicProductsQuery({
    action: 'getTaxonomies',
    options: {
      postType: postType,
    },
  }).then(function (response) {
    return response.data;
  });
};

Module.getTerms = function (options) {
  return Module._cachedQuery({
    action: 'getTerms',
    options: options,
  }).then(function (response) {
    return response.data;
  });
};

Module.getProductTerms = function (options) {
  return Module._cachedDynamicProductsQuery({
    action: 'getTerms',
    options: options,
  }).then(function (response) {
    return response.data;
  });
};

Module.getPosts = function (options) {
  return Module._cachedQuery({
    action: 'getPosts',
    options: options,
  }).then(function (response) {
    return response.data;
  });
};

Module.getTransformedPosts = function (options) {
  return Module._cachedQuery({
    action: 'getTransformedPosts',
    options: options,
  }).then(function (response) {
    return response.data;
  });
};

Module.getBulkTransformedPosts = function (options) {
  return Module._query({
    action: 'getBulkTransformedPosts',
    options: options,
  }).then(function (response) {
    return response.data;
  });
};

Module.getBulkTransformedProducts = function (options) {
  return Module._dynamicProductsQuery({
    action: 'getBulkTransformedProducts',
    options: options,
  }).then(function (response) {
    return response.data;
  });
};

Module.saveNewsletter = function (options) {
  return MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'newsletters',
    action: 'save',
    data: options || {},
  });
};

Module.previewNewsletter = function (options) {
  return MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'newsletters',
    action: 'sendPreview',
    data: options || {},
  });
};

App.on('start', function () {
  // Prefetch post types
  Module.getPostTypes();
});

export { Module as CommunicationComponent };
