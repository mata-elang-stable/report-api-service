<?php

if (!function_exists('prettifyTemplateName')) {
    function prettifyTemplateName($templateName)
    {
        switch ($templateName) {
            case 'monthly_report':
                return 'Monthly Report';
            case 'quarterly_report':
                return 'Quarterly Report';
            case 'annual_report':
                return 'Annual Report';
            default:
                return $templateName;
        }
    }
}
