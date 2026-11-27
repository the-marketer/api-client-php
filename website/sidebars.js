/**
 * Creating a sidebar enables you to:
 - create an ordered group of docs
 - render a sidebar for each doc of that group
 - provide next/previous navigation

 The sidebars can be generated from the filesystem, or explicitly defined here.

 Create as many sidebars as you want.
 */

// @ts-check

/** @type {import('@docusaurus/plugin-content-docs').SidebarsConfig} */
const sidebars = {
  tutorialSidebar: [
    'intro',
    'overview',
    'quickstart',
    'authentication',
    {
      type: 'category',
      label: 'API Reference',
      items: [
        'orders',
        'subscribers',
        'campaigns',
        'products',
        'transactionals',
        'reports',
        'events',
        'coupons',
        'loyalty',
        'reviews',
        'app-push',
        'account',
      ],
    },
    'credentials-utilities',
    'errors',
    'claude-skill',
    {
      type: 'category',
      label: 'Framework Integrations',
      items: ['laravel'],
    },
  ],
};

module.exports = sidebars;
