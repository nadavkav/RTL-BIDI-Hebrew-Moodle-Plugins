# This file contains a complete database schema for all the
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data
# that may be used, especially new entries in the table log_display



-- phpMyAdmin SQL Dump
-- version 2.7.0-pl1
-- http://www.phpmyadmin.net
-- 
-- M�quina: localhost
-- Data de Cria��o: 25-Jul-2006 �s 20:42
-- Vers�o do servidor: 5.0.18
-- vers�o do PHP: 5.1.1
-- 
-- Base de Dados: `moodle`
-- 

-- --------------------------------------------------------

-- 
-- Estrutura da tabela `mdl_metadatalom`
-- 

CREATE TABLE IF NOT EXISTS `prefix_metadatalom` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `course` int(10) unsigned NOT NULL default 0,
  `userid` int(10) unsigned NOT NULL default 0,
  `resource` int(10) unsigned NOT NULL default 0,
  `name` varchar(250) NOT NULL,
  `General_Identifier_Catalog` text NOT NULL,
  `General_Identifier_Entry` text NOT NULL,
  `General_Title` text NOT NULL,
  `General_Language` text NOT NULL,
  `General_Description` text NOT NULL,
  `General_Keyword` text NOT NULL,
  `General_Coverage` text NOT NULL,
  `General_Structure` text NOT NULL,
  `General_AggregationLevel` text NOT NULL,
  `LifeCycle_Version` text NOT NULL,
  `LifeCycle_Status` text NOT NULL,
  `LifeCycle_Contribute_Role` text NOT NULL,
  `LifeCycle_Contribute_Entity` text NOT NULL,
  `LifeCycle_Contribute_Date` text NOT NULL,
  `MetaMetadata_Identifier_Catalog` text NOT NULL,
  `MetaMetadata_Identifier_Entry` text NOT NULL,
  `MetaMetadata_Contribute_Role` text NOT NULL,
  `MetaMetadata_Contribute_Entity` text NOT NULL,
  `MetaMetadata_Contribute_Date` text NOT NULL,
  `MetaMetadata_MetadataScheme` text NOT NULL,
  `MetaMetadata_Language` text NOT NULL,
  `Technical_Format` text NOT NULL,
  `Technical_Size` text NOT NULL,
  `Technical_Location` text NOT NULL,
  `Technical_Requirement_Type` text NOT NULL,
  `Technical_Requirement_Name` text NOT NULL,
  `Technical_Requirement_MinimumVersion` text NOT NULL,
  `Technical_Requirement_MaximumVersion` text NOT NULL,
  `Technical_InstalationRemarks` text NOT NULL,
  `Technical_OtherPlatformRequirements` text NOT NULL,
  `Technical_Duration` text NOT NULL,
  `Educational_InteractivityType` text NOT NULL,
  `Educational_LearningResourceType` text NOT NULL,
  `Educational_InteractivityLevel` text NOT NULL,
  `Educational_SemanticDensity` text NOT NULL,
  `Educational_IntendedEndUserRole` text NOT NULL,
  `Educational_Context` text NOT NULL,
  `Educational_TypicalAgeRange` text NOT NULL,
  `Educational_Difficulty` text NOT NULL,
  `Educational_TypicalLearningTime` text NOT NULL,
  `Educational_Description` text NOT NULL,
  `Educational_Language` text NOT NULL,
  `Rights_Cost` text NOT NULL,
  `Rights_CopyrightAndOtherRestrictions` text NOT NULL,
  `Rights_Description` text NOT NULL,
  `Relation_Kind` text NOT NULL,
  `Relation_Resource_Identifier_Catalog` text NOT NULL,
  `Relation_Resource_Identifier_Entry` text NOT NULL,
  `Relation_Resource_Description` text NOT NULL,
  `Annotation_Entity` text NOT NULL,
  `Annotation_Date` text NOT NULL,
  `Annotation_Description` text NOT NULL,
  `Classification_Purpose` text NOT NULL,
  `Classification_TaxonPath_Source` text NOT NULL,
  `Classification_TaxonPath_Taxon_ID` text NOT NULL,
  `Classification_TaxonPath_Taxon_Entry` text NOT NULL,
  `Classification_Description` text NOT NULL,
  `Classification_Keyword` text NOT NULL,
  `timemodified` int(10) unsigned NOT NULL default 0,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id_2` (`id`),
  KEY `id` (`id`),
  KEY `resource` (`resource`),
  KEY `course` (`course`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Created by VG';



#
# Dumping data for table `mdl_log_display`
#

-- INSERT INTO `prefix_log_display` (module, action, mtable, field) VALUES (`metadatalom`, `view`, `metadatalom`, `name`);
-- INSERT INTO `prefix_log_display` (module, action, mtable, field) VALUES (`metadatalom`, `update`, `metadatalom`, `name`);
-- INSERT INTO `prefix_log_display` (module, action, mtable, field) VALUES (`metadatalom`, `add`, `metadatalom`, `name`);

