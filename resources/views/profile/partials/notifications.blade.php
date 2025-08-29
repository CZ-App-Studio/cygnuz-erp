<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Notification Preferences') }}</h5>
            </div>
            <div class="card-body">
                <form id="notificationPreferencesForm">
                    @csrf
                    
                    <div class="mb-4">
                        <h6 class="mb-3">{{ __('Communication Channels') }}</h6>
                        
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" id="email_notifications" name="email_notifications" checked>
                            <label class="form-check-label" for="email_notifications">
                                <strong>{{ __('Email Notifications') }}</strong><br>
                                <small class="text-muted">{{ __('Receive notifications via email') }}</small>
                            </label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" id="push_notifications" name="push_notifications">
                            <label class="form-check-label" for="push_notifications">
                                <strong>{{ __('Push Notifications') }}</strong><br>
                                <small class="text-muted">{{ __('Receive browser push notifications') }}</small>
                            </label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" id="sms_notifications" name="sms_notifications">
                            <label class="form-check-label" for="sms_notifications">
                                <strong>{{ __('SMS Notifications') }}</strong><br>
                                <small class="text-muted">{{ __('Receive important notifications via SMS') }}</small>
                            </label>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-4">
                        <h6 class="mb-3">{{ __('Notification Types') }}</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="security_alerts" checked>
                                    <label class="form-check-label" for="security_alerts">
                                        {{ __('Security Alerts') }}
                                        <small class="text-muted d-block">{{ __('New login attempts, password changes') }}</small>
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="account_updates" checked>
                                    <label class="form-check-label" for="account_updates">
                                        {{ __('Account Updates') }}
                                        <small class="text-muted d-block">{{ __('Profile changes, settings updates') }}</small>
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="system_notifications" checked>
                                    <label class="form-check-label" for="system_notifications">
                                        {{ __('System Notifications') }}
                                        <small class="text-muted d-block">{{ __('Maintenance, updates, announcements') }}</small>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="task_reminders">
                                    <label class="form-check-label" for="task_reminders">
                                        {{ __('Task Reminders') }}
                                        <small class="text-muted d-block">{{ __('Deadlines, assignments, updates') }}</small>
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="team_updates">
                                    <label class="form-check-label" for="team_updates">
                                        {{ __('Team Updates') }}
                                        <small class="text-muted d-block">{{ __('Team member activities, mentions') }}</small>
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="report_notifications">
                                    <label class="form-check-label" for="report_notifications">
                                        {{ __('Reports & Analytics') }}
                                        <small class="text-muted d-block">{{ __('Weekly reports, monthly summaries') }}</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-4">
                        <h6 class="mb-3">{{ __('Marketing & Promotional') }}</h6>
                        
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                            <label class="form-check-label" for="newsletter">
                                <strong>{{ __('Newsletter') }}</strong><br>
                                <small class="text-muted">{{ __('Receive our monthly newsletter with updates and tips') }}</small>
                            </label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" id="marketing_emails" name="marketing_emails">
                            <label class="form-check-label" for="marketing_emails">
                                <strong>{{ __('Marketing Emails') }}</strong><br>
                                <small class="text-muted">{{ __('Receive promotional offers and product updates') }}</small>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> {{ __('Save Preferences') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>