<?php

namespace Modules\FileManagerCore\Enums;

enum FileType: string
{
    case EMPLOYEE_PROFILE_PICTURE = 'employee_profile_picture';
    case EMPLOYEE_DOCUMENT = 'employee_document';
    case LEAVE_DOCUMENT = 'leave_document';
    case CHAT_FILE = 'chat_file';
    case DESKTOP_SCREENSHOT = 'desktop_screenshot';
    case ONBOARDING_FILE = 'onboarding_file';
    case INVOICE_ATTACHMENT = 'invoice_attachment';
    case PROPOSAL_ATTACHMENT = 'proposal_attachment';
    case PROJECT_FILE = 'project_file';
    case COMPANY_LOGO = 'company_logo';
    case PRODUCT_IMAGE = 'product_image';
    case AUDIT_DOCUMENT = 'audit_document';
    case TRAINING_MATERIAL = 'training_material';
    case POLICY_DOCUMENT = 'policy_document';
    case ATTENDANCE_DOCUMENT = 'attendance_document';
    case PAYROLL_DOCUMENT = 'payroll_document';
    case EXPENSE_RECEIPT = 'expense_receipt';
    case ASSET_IMAGE = 'asset_image';
    case ASSET_DOCUMENT = 'asset_document';
    case DIGITAL_PRODUCT = 'digital_product';
    case GENERAL = 'general';

    /**
     * Get human-readable label for the file type
     */
    public function label(): string
    {
        return match ($this) {
            self::EMPLOYEE_PROFILE_PICTURE => 'Employee Profile Picture',
            self::EMPLOYEE_DOCUMENT => 'Employee Document',
            self::LEAVE_DOCUMENT => 'Leave Document',
            self::CHAT_FILE => 'Chat File',
            self::DESKTOP_SCREENSHOT => 'Desktop Screenshot',
            self::ONBOARDING_FILE => 'Onboarding File',
            self::INVOICE_ATTACHMENT => 'Invoice Attachment',
            self::PROPOSAL_ATTACHMENT => 'Proposal Attachment',
            self::PROJECT_FILE => 'Project File',
            self::COMPANY_LOGO => 'Company Logo',
            self::PRODUCT_IMAGE => 'Product Image',
            self::AUDIT_DOCUMENT => 'Audit Document',
            self::TRAINING_MATERIAL => 'Training Material',
            self::POLICY_DOCUMENT => 'Policy Document',
            self::ATTENDANCE_DOCUMENT => 'Attendance Document',
            self::PAYROLL_DOCUMENT => 'Payroll Document',
            self::EXPENSE_RECEIPT => 'Expense Receipt',
            self::ASSET_IMAGE => 'Asset Image',
            self::ASSET_DOCUMENT => 'Asset Document',
            self::DIGITAL_PRODUCT => 'Digital Product File',
            self::GENERAL => 'General File',
        };
    }

    /**
     * Get the storage directory for this file type
     */
    public function directory(): string
    {
        return match ($this) {
            self::EMPLOYEE_PROFILE_PICTURE => 'employees/profiles',
            self::EMPLOYEE_DOCUMENT => 'employees/documents',
            self::LEAVE_DOCUMENT => 'leave/documents',
            self::CHAT_FILE => 'chat/files',
            self::DESKTOP_SCREENSHOT => 'desktop/screenshots',
            self::ONBOARDING_FILE => 'hr/onboarding',
            self::INVOICE_ATTACHMENT => 'accounting/invoices',
            self::PROPOSAL_ATTACHMENT => 'sales/proposals',
            self::PROJECT_FILE => 'projects/files',
            self::COMPANY_LOGO => 'company/logos',
            self::PRODUCT_IMAGE => 'products/images',
            self::AUDIT_DOCUMENT => 'audit/documents',
            self::TRAINING_MATERIAL => 'training/materials',
            self::POLICY_DOCUMENT => 'hr/policies',
            self::ATTENDANCE_DOCUMENT => 'attendance/documents',
            self::PAYROLL_DOCUMENT => 'payroll/documents',
            self::EXPENSE_RECEIPT => 'expenses/receipts',
            self::ASSET_IMAGE => 'assets/images',
            self::ASSET_DOCUMENT => 'assets/documents',
            self::DIGITAL_PRODUCT => 'digital-products/files',
            self::GENERAL => 'general',
        };
    }

    /**
     * Check if this file type allows public access
     */
    public function isPublicByDefault(): bool
    {
        return match ($this) {
            self::COMPANY_LOGO,
            self::PRODUCT_IMAGE => true,
            default => false,
        };
    }

    /**
     * Get maximum file size for this type in KB
     */
    public function maxSize(): ?int
    {
        return match ($this) {
            self::EMPLOYEE_PROFILE_PICTURE,
            self::COMPANY_LOGO,
            self::PRODUCT_IMAGE,
            self::ASSET_IMAGE => 5120, // 5MB for images
            self::DESKTOP_SCREENSHOT => 2048, // 2MB for screenshots
            self::CHAT_FILE => 10240, // 10MB for chat files
            self::DIGITAL_PRODUCT => 104857600, // 100MB for digital products
            default => null, // Use system default
        };
    }
}
