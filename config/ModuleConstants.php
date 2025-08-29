<?php

if (!class_exists('ModuleConstants')) {
  class ModuleConstants
  {
    // Module name constants for easy reference
    public const ACCOUNTING_CORE = 'AccountingCore';
    public const ACCOUNTING_PRO = 'AccountingPro';
    public const AGORA_CALL = 'AgoraCall';
    public const AI_CHAT = 'AiChat';
    public const AI_CORE = 'AICore';
    public const APPROVALS = 'Approvals';
    public const ASSETS = 'Assets';
    public const AUDIT_LOG = 'AuditLog';
    public const BILLING = 'Billing';
    public const CALENDAR = 'Calendar';
    public const COMMUNICATION_CENTER = 'CommunicationCenter';
    public const CRM_CORE = 'CRMCore';
    public const DATA_IMPORT_EXPORT = 'DataImportExport';
    public const DIGITAL_ID_CARD = 'DigitalIdCard';
    public const DISCIPLINARY_ACTIONS = 'DisciplinaryActions';
    public const DOCUMENT_MANAGEMENT = 'DocumentManagement';
    public const DYNAMIC_QR_ATTENDANCE = 'DynamicQrAttendance';
    public const FACE_ATTENDANCE = 'FaceAttendance';
    public const FIELD_MANAGER = 'FieldManager';
    public const FORM_BUILDER = 'FormBuilder';
    public const GEOFENCE_SYSTEM = 'GeofenceSystem';
    public const GOOGLE_RECAPTCHA = 'GoogleReCAPTCHA';
    public const HR_CORE = 'HRCore';
    public const HR_POLICIES = 'HRPolicies';
    public const IP_ADDRESS_ATTENDANCE = 'IpAddressAttendance';
    public const LMS = 'LMS';
    public const LOAN_MANAGEMENT = 'LoanManagement';
    public const MULTI_CURRENCY = 'MultiCurrency';
    public const NOTES = 'Notes';
    public const NOTICE_BOARD = 'NoticeBoard';
    public const OFFLINE_TRACKING = 'OfflineTracking';
    public const PAYMENT_COLLECTION = 'PaymentCollection';
    public const PAYROLL = 'Payroll';
    public const PM_CORE = 'PMCore';
    public const PRODUCT_ORDER = 'ProductOrder';
    public const QR_ATTENDANCE = 'QRAttendance';
    public const RECRUITMENT = 'Recruitment';
    public const SALES_TARGET = 'SalesTarget';
    public const SHIFT_PLUS = 'ShiftPlus';
    public const SITE_ATTENDANCE = 'SiteAttendance';
    public const SUBSCRIPTION_MANAGEMENT = 'SubscriptionManagement';
    public const SYSTEM_CORE = 'SystemCore';
    public const TASK_SYSTEM = 'TaskSystem';
    public const UID_LOGIN = 'UidLogin';
    public const SOS = 'SOS';
    public const WMS_INVENTORY_CORE = 'WMSInventoryCore';
    public const DESKTOP_TRACKER = 'DesktopTracker';
    public const SEARCH_PLUS = 'SearchPlus';

    // Module categories with icons and descriptions
    public const MODULE_CATEGORIES = [
      'Human Resources' => [
        'icon' => 'bx-group',
        'description' => 'Employee management, payroll, and HR operations',
        'order' => 1
      ],
      'Attendance & Time Management' => [
        'icon' => 'bx-time-five',
        'description' => 'Track employee attendance and working hours',
        'order' => 2
      ],
      'Employee Monitoring' => [
        'icon' => 'bx-desktop',
        'description' => 'Monitor and track employee productivity and desktop activities',
        'order' => 3
      ],
      'Field Operations' => [
        'icon' => 'bx-map',
        'description' => 'Manage field employees and remote operations',
        'order' => 4
      ],
      'Finance & Accounting' => [
        'icon' => 'bx-dollar-circle',
        'description' => 'Financial management and accounting features',
        'order' => 5
      ],
      'Sales & CRM' => [
        'icon' => 'bx-cart',
        'description' => 'Customer relationships and sales management',
        'order' => 6
      ],
      'Communication & Collaboration' => [
        'icon' => 'bx-conversation',
        'description' => 'Team communication and collaboration tools',
        'order' => 7
      ],
      'Document & Data Management' => [
        'icon' => 'bx-file',
        'description' => 'Document storage and data handling',
        'order' => 8
      ],
      'System & Administration' => [
        'icon' => 'bx-cog',
        'description' => 'System configuration and admin tools',
        'order' => 9
      ],
      'Asset Management' => [
        'icon' => 'bx-box',
        'description' => 'Track and manage company assets',
        'order' => 10
      ],
      'Learning & Development' => [
        'icon' => 'bx-book-open',
        'description' => 'Training and development programs',
        'order' => 11
      ],
      'Core' => [
        'icon' => 'bx-chip',
        'description' => 'Essential system modules',
        'order' => 12
      ],
      'Productivity & Tools' => [
        'icon' => 'bx-rocket',
        'description' => 'Enhance productivity with advanced tools and utilities',
        'order' => 13
      ],
      'Project Management' => [
        'icon' => 'bx-task',
        'description' => 'Plan, track, and manage projects efficiently',
        'order' => 14
      ],
      'Warehouse Management' => [
        'icon' => 'bx-package',
        'description' => 'Inventory and warehouse operations management',
        'order' => 15
      ],
      'Artificial Intelligence' => [
        'icon' => 'bx-brain',
        'description' => 'AI-powered features and integrations for enhanced business intelligence',
        'order' => 16
      ],
      'Payment Gateways' => [
        'icon' => 'bx-credit-card',
        'description' => 'Payment processing integrations for various payment methods',
        'order' => 17
      ],
      'Applications' => [
        'icon' => 'bx-mobile-alt',
        'description' => 'Mobile and web applications for your ERP system',
        'order' => 18
      ],
      'Other' => [
        'icon' => 'bx-dots-horizontal-rounded',
        'description' => 'Additional modules and features',
        'order' => 999
      ]
    ];

    // Core modules
    public const CORE_MODULES = [
      self::HR_CORE,
      self::SYSTEM_CORE,
      self::ACCOUNTING_CORE,
      self::CRM_CORE,
      self::PM_CORE,
      self::WMS_INVENTORY_CORE,
      self::AI_CORE,
    ];

    // Attendance-related modules
    public const ATTENDANCE_MODULES = [
      self::QR_ATTENDANCE,
      self::DYNAMIC_QR_ATTENDANCE,
      self::FACE_ATTENDANCE,
      self::IP_ADDRESS_ATTENDANCE,
      self::GEOFENCE_SYSTEM,
      self::SITE_ATTENDANCE,
    ];

    // All available addons with details for marketplace
    public const ALL_ADDONS_ARRAY = [
      // Productivity & Tools
      // 'SearchPlus' => [
      //   'name' => 'Search Plus',
      //   'description' => 'Advanced search with AI-powered indexing, category filters, and search history.',
      //   'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      // ],
      // Human Resources
      'Payroll' => [
        'name' => 'Payroll Management',
        'description' => 'Comprehensive payroll processing with salary calculations, bonuses, and deductions.',
        'purchase_link' => 'https://czappstudio.com/product/payroll-management-addon/',
      ],
      'LoanManagement' => [
        'name' => 'Loan Management',
        'description' => 'Manage employee loan requests, approvals, and repayment tracking.',
        'purchase_link' => 'https://czappstudio.com/product/loan-request-addon-laravel/',
      ],
      'Recruitment' => [
        'name' => 'Recruitment',
        'description' => 'Complete recruitment lifecycle from job posting to candidate onboarding.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'DisciplinaryActions' => [
        'name' => 'Disciplinary Actions',
        'description' => 'Track and manage employee disciplinary actions and warnings.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'HRPolicies' => [
        'name' => 'HR Policies',
        'description' => 'Create, manage, and track employee acknowledgment of company policies.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      // 'ShiftPlus' => [
      //   'name' => 'Shift Plus',
      //   'description' => 'Advanced shift management with rotation patterns and overtime tracking.',
      //   'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      // ],

      // Attendance & Time Management
      'QRAttendance' => [
        'name' => 'QR Attendance',
        'description' => 'Quick and secure attendance marking using QR code scanning.',
        'purchase_link' => 'https://czappstudio.com/product/qr-attendance-addon-laravel/',
      ],
      // 'DynamicQrAttendance' => [
      //   'name' => 'Dynamic QR Attendance',
      //   'description' => 'Enhanced security with dynamically generated QR codes for attendance.',
      //   'purchase_link' => 'https://czappstudio.com/product/dynamic-qr-attendance-addon/',
      // ],
      'FaceAttendance' => [
        'name' => 'Face Attendance',
        'description' => 'Biometric attendance using advanced face recognition technology.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'IpAddressAttendance' => [
        'name' => 'IP Address Attendance',
        'description' => 'Location-based attendance restricted to specific IP addresses.',
        'purchase_link' => 'https://czappstudio.com/product/ip-based-attendance-addon-laravel/',
      ],
      'GeofenceSystem' => [
        'name' => 'Geofence System',
        'description' => 'GPS-based attendance within defined geographical boundaries.',
        'purchase_link' => 'https://czappstudio.com/product/geofence-attendance-addon-laravel/',
      ],
      // 'SiteAttendance' => [
      //   'name' => 'Site Attendance',
      //   'description' => 'Multi-site attendance management for distributed teams.',
      //   'purchase_link' => 'https://czappstudio.com/product/site-attendance-addon-laravel/',
      // ],

      // Field Operations
      'FieldManager' => [
        'name' => 'Field Manager',
        'description' => 'Complete field force management with GPS tracking and task assignments.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      // 'TaskSystem' => [
      //   'name' => 'Task System',
      //   'description' => 'Create and manage tasks for field employees with real-time tracking.',
      //   'purchase_link' => 'https://czappstudio.com/product/task-system-addon-laravel/',
      // ],
      'OfflineTracking' => [
        'name' => 'Offline Tracking',
        'description' => 'Track field employee activities even without internet connectivity.',
        'purchase_link' => 'https://czappstudio.com/product/offline-tracking-addon-laravel/',
      ],
      'DigitalIdCard' => [
        'name' => 'Digital ID Card',
        'description' => 'Generate and manage digital identification cards for field employees.',
        'purchase_link' => 'https://czappstudio.com/product/digital-id-card-addon/',
      ],

      // Finance & Accounting
      'Billing' => [
        'name' => 'Billing',
        'description' => 'Complete billing solution with invoice generation and payment tracking.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'MultiCurrency' => [
        'name' => 'Multi Currency',
        'description' => 'Support multiple currencies with real-time exchange rate updates.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      // 'PaymentCollection' => [
      //   'name' => 'Payment Collection',
      //   'description' => 'Field payment collection with mobile app integration.',
      //   'purchase_link' => 'https://czappstudio.com/product/payment-collection-addon-laravel/',
      // ],
      // 'SubscriptionManagement' => [
      //   'name' => 'Subscription Management',
      //   'description' => 'Manage recurring subscriptions with automated billing.',
      //   'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      // ],

      // Sales & CRM
      // 'SalesTarget' => [
      //   'name' => 'Sales Target',
      //   'description' => 'Set and track sales targets with performance analytics.',
      //   'purchase_link' => 'https://czappstudio.com/product/sales-target-addon/',
      // ],
      'FieldProductOrder' => [
        'name' => 'Field Product Order',
        'description' => 'Mobile-based product ordering system for field sales.',
        'purchase_link' => 'https://czappstudio.com/product/product-order-system-addon-laravel/',
      ],

      // Communication & Collaboration
      'AgoraCall' => [
        'name' => 'Agora Call',
        'description' => 'Video calling and conferencing powered by Agora.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'AiChat' => [
        'name' => 'AI Business Assistant',
        'description' => 'AI-powered chat assistant for business intelligence.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'AICore' => [
        'name' => 'AI Core',
        'description' => 'Core AI infrastructure module that provides AI provider management, request routing, usage tracking, and foundational services for all AI-powered features.',
        'purchase_link' => '',
      ],
      'CommunicationCenter' => [
        'name' => 'Communication Center',
        'description' => 'Centralized communication hub for announcements and updates.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'Notes' => [
        'name' => 'Notes',
        'description' => 'Personal and shared notes management system.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'Calendar' => [
        'name' => 'Calendar',
        'description' => 'Event management with team calendar integration.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],

      // Document & Data Management
      'DocumentManagement' => [
        'name' => 'Document Management',
        'description' => 'Document request and management system with approval workflow.',
        'purchase_link' => 'https://czappstudio.com/product/document-request-addon-laravel/',
      ],
      'DataImportExport' => [
        'name' => 'Data Import Export',
        'description' => 'Bulk data import and export with multiple format support.',
        'purchase_link' => 'https://czappstudio.com/product/data-import-export-addon/',
      ],
      'FormBuilder' => [
        'name' => 'Form Builder',
        'description' => 'Dynamic form creation with drag-and-drop builder.',
        'purchase_link' => 'https://czappstudio.com/product/custom-form-addon-laravel/',
      ],
      'FileManagerPlus' => [
        'name' => 'File Manager Plus',
        'description' => 'Advanced file explorer interface with drag-and-drop, preview, and comprehensive file management capabilities.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],

      // System & Administration
      // 'Approvals' => [
      //   'name' => 'Approvals',
      //   'description' => 'Mobile-based approval system for requests and workflows.',
      //   'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      // ],
      'AuditLog' => [
        'name' => 'Audit Log',
        'description' => 'Comprehensive activity logging and audit trail.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'GoogleReCAPTCHA' => [
        'name' => 'Google reCAPTCHA',
        'description' => 'Enhanced security with Google reCAPTCHA integration.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'UidLogin' => [
        'name' => 'One Tap Login',
        'description' => 'Quick login using unique identifiers.',
        'purchase_link' => 'https://czappstudio.com/product/uid-login-addon-laravel/',
      ],
      'SOS' => [
        'name' => 'SOS Emergency',
        'description' => 'Emergency request system for field employees with location tracking.',
        'purchase_link' => 'https://czappstudio.com/product/sos-emergency-addon/',
      ],

      // Other
      'Assets' => [
        'name' => 'Asset Management',
        'description' => 'Track and manage company assets assigned to employees.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'LMS' => [
        'name' => 'Learning Management System',
        'description' => 'Complete training and learning management platform.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],

      // // Employee Monitoring
      // 'DesktopTracker' => [
      //   'name' => 'Desktop Tracker',
      //   'description' => 'Monitor employee desktop activities, track productivity, and capture screenshots for remote work management.',
      //   'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      // ],

      // Payment Gateways
      'PayPalGateway' => [
        'name' => 'PayPal Gateway',
        'description' => 'Accept payments through PayPal with secure integration and automatic payment tracking.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'StripeGateway' => [
        'name' => 'Stripe Gateway',
        'description' => 'Process credit card payments with Stripe, supporting multiple currencies and subscription billing.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'RazorpayGateway' => [
        'name' => 'Razorpay Gateway',
        'description' => 'Indian payment gateway integration with support for UPI, cards, and net banking.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],

      // System & Administration
      'LandingPage' => [
        'name' => 'Landing Page',
        'description' => 'Professional landing page with customizable sections for marketing your HRMS platform.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],

      // Core Modules
      'MultiTenancyCore' => [
        'name' => 'Multi-Tenancy Core',
        'description' => 'Enable SaaS functionality with multi-tenant architecture and subscription management.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'AccountingCore' => [
        'name' => 'Accounting Core',
        'description' => 'Core accounting module with chart of accounts, journal entries, and financial reports.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'CRMCore' => [
        'name' => 'CRM Core',
        'description' => 'Customer relationship management with leads, contacts, and deal tracking.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'PMCore' => [
        'name' => 'Project Management Core',
        'description' => 'Project planning, task management, and team collaboration tools.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'HRCore' => [
        'name' => 'HR Core',
        'description' => 'Essential HR features including employee management, attendance, and leave tracking.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'SystemCore' => [
        'name' => 'System Core',
        'description' => 'Core system functionality and utilities for the HRMS platform.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],
      'WMSInventoryCore' => [
        'name' => 'Warehouse Management Core',
        'description' => 'Inventory management with warehouses, stock tracking, and purchase/sales orders.',
        'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      ],

      // // Applications
      // 'POSSystem' => [
      //   'name' => 'POS System',
      //   'description' => 'Modern point-of-sale application with offline support, inventory integration, and multi-language capabilities.',
      //   'purchase_link' => 'https://czappstudio.com/cygnuz-erp-addons/',
      //   'type' => 'application',
      //   'platform' => 'Desktop/Web',
      //   'technology' => 'React, TypeScript, Electron',
      // ]
    ];
  }
}
