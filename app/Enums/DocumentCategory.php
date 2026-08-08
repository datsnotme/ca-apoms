<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case BirthCertificate = 'birth_certificate';
    case Form137 = 'form_137';
    case Form138 = 'form_138';
    case GoodMoralCertificate = 'good_moral_certificate';
    case MedicalCertificate = 'medical_certificate';
    case IdPhoto = 'id_photo';
    case TranscriptOfRecords = 'transcript_of_records';
    case CertificateOfRegistration = 'certificate_of_registration';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BirthCertificate => 'Birth Certificate',
            self::Form137 => 'Form 137',
            self::Form138 => 'Form 138',
            self::GoodMoralCertificate => 'Good Moral Certificate',
            self::MedicalCertificate => 'Medical Certificate',
            self::IdPhoto => 'ID Photo',
            self::TranscriptOfRecords => 'Transcript of Records',
            self::CertificateOfRegistration => 'Certificate of Registration',
            self::Other => 'Other',
        };
    }
}
