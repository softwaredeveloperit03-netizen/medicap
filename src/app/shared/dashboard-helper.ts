/**
 * Dashboard Helper Utility
 * Provides helper functions for quickly setting up dashboards
 */

import { DashboardItem } from './dashboard-base.service';

export class DashboardHelper {
  /**
   * Generate dashboard items from HTML template
   * Extracts items from old HTML structure
   */
  static extractItemsFromHTML(htmlContent: string): DashboardItem[] {
    // This is a helper - in practice, you'd manually define items
    // based on your dashboard structure
    return [];
  }

  /**
   * Get default category icon mapping
   */
  static getDefaultCategoryIcons(): { [key: string]: string } {
    return {
      'All': 'fas fa-th',
      'Management': 'fas fa-briefcase',
      'Reports': 'fas fa-chart-bar',
      'Analytics': 'fas fa-chart-pie',
      'Operations': 'fas fa-cogs',
      'Inventory': 'fas fa-warehouse',
      'Account': 'fas fa-book',
      'Sales': 'fas fa-shopping-bag',
      'Purchase': 'fas fa-shopping-cart',
      'Stock': 'fas fa-warehouse',
      'Bank': 'fas fa-university',
      'Masters': 'fas fa-cog',
      'HR': 'fas fa-user-tie',
      'Quality': 'fas fa-check-circle',
      'Production': 'fas fa-industry',
      'Engineering': 'fas fa-tools',
      'IT': 'fas fa-laptop-code',
      'EHS': 'fas fa-shield-alt',
      'Security': 'fas fa-shield-alt',
      'Planning': 'fas fa-tasks',
      'Packing': 'fas fa-box',
      'QC': 'fas fa-vial',
      'IPQC': 'fas fa-clipboard-check',
      'QA': 'fas fa-check-square',
      'MRP': 'fas fa-warehouse',
      'Store': 'fas fa-archive',
      'Dispatch': 'fas fa-shipping-fast',
      'Master': 'fas fa-cog'
    };
  }

  /**
   * Generate storage key from dashboard name
   */
  static getStorageKey(dashboardName: string): string {
    return `${dashboardName.toLowerCase()}_dashboard_bg_color`;
  }

  /**
   * Default color palette
   */
  static getDefaultColors(): string[] {
    return [
      '#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe',
      '#43e97b', '#fa709a', '#fee140', '#30cfd0', '#a8edea',
      '#ff9a9e', '#fecfef', '#fecfef', '#ffecd2', '#fcb69f'
    ];
  }
}
