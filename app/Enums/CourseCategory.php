<?php

namespace App\Enums;

enum CourseCategory: string
{
    case CropScience = 'crop_science';
    case AnimalScience = 'animal_science';
    case SoilScience = 'soil_science';
    case AgriculturalEconomics = 'agricultural_economics';
    case AgriculturalEngineering = 'agricultural_engineering';
    case Agribusiness = 'agribusiness';
    case AgriculturalExtension = 'agricultural_extension';
    case Horticulture = 'horticulture';
    case PlantPathology = 'plant_pathology';
    case Entomology = 'entomology';
    case FoodScience = 'food_science';
    case FarmManagement = 'farm_management';
    case Research = 'research';
    case Thesis = 'thesis';
    case Practicum = 'practicum';
    case Internship = 'internship';
    // Practical additions beyond the spec's agriculture-specific list — every real
    // curriculum also has general-education and NSTP/PE courses. See ASSUMPTIONS.md.
    case GeneralEducation = 'general_education';
    case NstpPe = 'nstp_pe';

    public function label(): string
    {
        return match ($this) {
            self::CropScience => 'Crop Science',
            self::AnimalScience => 'Animal Science',
            self::SoilScience => 'Soil Science',
            self::AgriculturalEconomics => 'Agricultural Economics',
            self::AgriculturalEngineering => 'Agricultural Engineering',
            self::Agribusiness => 'Agribusiness',
            self::AgriculturalExtension => 'Agricultural Extension',
            self::Horticulture => 'Horticulture',
            self::PlantPathology => 'Plant Pathology',
            self::Entomology => 'Entomology',
            self::FoodScience => 'Food Science',
            self::FarmManagement => 'Farm Management',
            self::Research => 'Research',
            self::Thesis => 'Thesis',
            self::Practicum => 'Practicum',
            self::Internship => 'Internship',
            self::GeneralEducation => 'General Education',
            self::NstpPe => 'NSTP / PE',
        };
    }
}
