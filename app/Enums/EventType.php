<?php

namespace App\Enums;

enum EventType: string
{
  case MEETING = 'Meeting';
  case TRAINING = 'Training';
  case HOLIDAY = 'Holiday';
  case DEADLINE = 'Deadline';
  case COMPANY_EVENT = 'Company Event';
  case INTERVIEW = 'Interview';
  case ONBOARDING_SESSION = 'Onboarding Session';
  case PERFORMANCE_REVIEW = 'Performance Review';
  case CLIENT_APPOINTMENT = 'Client Appointment';
  
  // Project Management Event Types
  case PROJECT_MEETING = 'Project Meeting';
  case PROJECT_DEADLINE = 'Project Deadline';
  case PROJECT_MILESTONE = 'Project Milestone';
  case PROJECT_KICKOFF = 'Project Kickoff';
  case PROJECT_REVIEW = 'Project Review';
  case SPRINT_PLANNING = 'Sprint Planning';
  case DAILY_STANDUP = 'Daily Standup';
  case RETROSPECTIVE = 'Retrospective';
  
  // Future HR Integration
  case LEAVE = 'Leave';
  case SICK_LEAVE = 'Sick Leave';
  case VACATION = 'Vacation';
  
  case OTHER = 'Other';

  // Helper for user-friendly labels if needed (e.g., for dropdowns)
  public function label(): string
  {
    // Returns the string value directly, which is already user-friendly
    return $this->value;
  }

  // Optional: Define default colors (can be overridden by user choice)
  public function defaultColor(): string
  {
    return match ($this) {
      self::MEETING => '#007bff',             // Blue
      self::TRAINING => '#ffc107',            // Yellow
      self::HOLIDAY => '#28a745',             // Green
      self::DEADLINE => '#dc3545',            // Red
      self::COMPANY_EVENT => '#17a2b8',       // Teal
      self::INTERVIEW => '#6f42c1',           // Purple
      self::ONBOARDING_SESSION => '#fd7e14',  // Orange
      self::PERFORMANCE_REVIEW => '#20c997',  // Cyan
      self::CLIENT_APPOINTMENT => '#6610f2',  // Indigo
      
      // Project Management Colors
      self::PROJECT_MEETING => '#0d6efd',     // Primary Blue
      self::PROJECT_DEADLINE => '#e74c3c',    // Urgent Red
      self::PROJECT_MILESTONE => '#f39c12',   // Achievement Orange
      self::PROJECT_KICKOFF => '#27ae60',     // Success Green
      self::PROJECT_REVIEW => '#9b59b6',      // Review Purple
      self::SPRINT_PLANNING => '#3498db',     // Planning Blue
      self::DAILY_STANDUP => '#16a085',       // Daily Teal
      self::RETROSPECTIVE => '#8e44ad',       // Retrospective Purple
      
      // HR Event Colors
      self::LEAVE => '#95a5a6',               // Neutral Gray
      self::SICK_LEAVE => '#e67e22',          // Orange
      self::VACATION => '#2ecc71',            // Vacation Green
      
      self::OTHER => '#6c757d',               // Gray
    };
  }
}
