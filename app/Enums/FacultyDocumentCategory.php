<?php

namespace App\Enums;

enum FacultyDocumentCategory: string
{
    case Diploma = 'diploma';
    case TranscriptOfRecords = 'transcript_of_records';
    case ProfessionalLicense = 'professional_license';
    case AppointmentLetter = 'appointment_letter';
    case CertificateOfEmployment = 'certificate_of_employment';
    case PerformanceEvaluation = 'performance_evaluation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Diploma => 'Diploma',
            self::TranscriptOfRecords => 'Transcript of Records',
            self::ProfessionalLicense => 'Professional License',
            self::AppointmentLetter => 'Appointment Letter',
            self::CertificateOfEmployment => 'Certificate of Employment',
            self::PerformanceEvaluation => 'Performance Evaluation',
            self::Other => 'Other',
        };
    }
}
