<?php

namespace App\Enums;

/**
 * The 13 PAN action types (mirrors the mockup's Type of Action selects).
 */
enum ActionType: string
{
    case WageOrder = 'wage-order';
    case Regularization = 'regularization';
    case SalaryAlignment = 'salary-alignment';
    case LateralTransfer = 'lateral-transfer';
    case DevelopmentalAssignment = 'developmental-assignment';
    case InterimAllowance = 'interim-allowance';
    case Promotion = 'promotion';
    case TrainingStatus = 'training-status';
    case ConfirmationOfAppointment = 'confirmation-of-appointment';
    case ChangeOfPosition = 'change-of-position';
    case DiscontinuanceOfAllowance = 'discontinuance-of-allowance';
    case ConfirmationOfDevelopmentAssignment = 'confirmation-of-development-assignment';
    case OtherAllowances = 'other-allowances';

    public function label(): string
    {
        return match ($this) {
            self::WageOrder => 'Wage Order',
            self::Regularization => 'Regularization',
            self::SalaryAlignment => 'Salary Alignment',
            self::LateralTransfer => 'Lateral Transfer',
            self::DevelopmentalAssignment => 'Developmental Assignment',
            self::InterimAllowance => 'Interim Allowance',
            self::Promotion => 'Promotion',
            self::TrainingStatus => 'Training Status',
            self::ConfirmationOfAppointment => 'Confirmation of Appointment',
            self::ChangeOfPosition => 'Change of Position',
            self::DiscontinuanceOfAllowance => 'Discontinuance of Allowance',
            self::ConfirmationOfDevelopmentAssignment => 'Confirmation of Development Assignment',
            self::OtherAllowances => 'Other Allowances',
        };
    }

    /** Regularization final-approval auto-finalizes employment status to "Regular" (v1 rule). */
    public function autoFinalizesToRegular(): bool
    {
        return $this === self::Regularization;
    }

    /** Wage Order is the only action type carrying a Wage Order No. on the prepared form. */
    public function requiresWageNumber(): bool
    {
        return $this === self::WageOrder;
    }

    /** Leave Credits appears in the Action Reference only for Regularization. */
    public function includesLeaveCredits(): bool
    {
        return $this === self::Regularization;
    }

    /**
     * These three carry a real possibility of moving the employee to a
     * different department (confirmed 2026-09-16, after PAN-2026-00061 showed
     * employees.department_id never followed a department change anywhere).
     * Shows the "New Department" field on the prepare form; if HR actually
     * sets it, Final Approval writes it back to the employee record — see
     * GivesFinalApproval.
     */
    public function mayChangeDepartment(): bool
    {
        return in_array($this, [self::LateralTransfer, self::ChangeOfPosition, self::Promotion], true);
    }
}
