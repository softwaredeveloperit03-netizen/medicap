export interface StandardGlasswareTemplate {
  name: string;
  capacity: string;
  unit: string;
  glassware_class: string;
  description: string;
  make: string;
}

/** Common laboratory glassware master templates for QC lab. */
export const STANDARD_GLASSWARE_TEMPLATES: StandardGlasswareTemplate[] = [
  // Beakers
  { name: 'Beaker', capacity: '50', unit: 'ml', glassware_class: 'type A', description: 'Low form borosilicate beaker with spout', make: 'Borosil' },
  { name: 'Beaker', capacity: '100', unit: 'ml', glassware_class: 'type A', description: 'Low form borosilicate beaker with spout', make: 'Borosil' },
  { name: 'Beaker', capacity: '250', unit: 'ml', glassware_class: 'type A', description: 'Low form borosilicate beaker with spout', make: 'Borosil' },
  { name: 'Beaker', capacity: '500', unit: 'ml', glassware_class: 'type A', description: 'Low form borosilicate beaker with spout', make: 'Borosil' },
  { name: 'Beaker', capacity: '1000', unit: 'ml', glassware_class: 'type A', description: 'Low form borosilicate beaker with spout', make: 'Borosil' },
  { name: 'Beaker', capacity: '2000', unit: 'ml', glassware_class: 'type B', description: 'Low form borosilicate beaker with spout', make: 'Borosil' },

  // Conical / Erlenmeyer flasks
  { name: 'Conical Flask', capacity: '50', unit: 'ml', glassware_class: 'type A', description: 'Erlenmeyer flask, narrow mouth', make: 'Borosil' },
  { name: 'Conical Flask', capacity: '100', unit: 'ml', glassware_class: 'type A', description: 'Erlenmeyer flask, narrow mouth', make: 'Borosil' },
  { name: 'Conical Flask', capacity: '250', unit: 'ml', glassware_class: 'type A', description: 'Erlenmeyer flask, narrow mouth', make: 'Borosil' },
  { name: 'Conical Flask', capacity: '500', unit: 'ml', glassware_class: 'type A', description: 'Erlenmeyer flask, narrow mouth', make: 'Borosil' },
  { name: 'Conical Flask', capacity: '1000', unit: 'ml', glassware_class: 'type A', description: 'Erlenmeyer flask, narrow mouth', make: 'Borosil' },

  // Volumetric flasks Class A
  { name: 'Volumetric Flask', capacity: '5', unit: 'ml', glassware_class: 'type A', description: 'Class A volumetric flask with stopper', make: 'Borosil' },
  { name: 'Volumetric Flask', capacity: '10', unit: 'ml', glassware_class: 'type A', description: 'Class A volumetric flask with stopper', make: 'Borosil' },
  { name: 'Volumetric Flask', capacity: '25', unit: 'ml', glassware_class: 'type A', description: 'Class A volumetric flask with stopper', make: 'Borosil' },
  { name: 'Volumetric Flask', capacity: '50', unit: 'ml', glassware_class: 'type A', description: 'Class A volumetric flask with stopper', make: 'Borosil' },
  { name: 'Volumetric Flask', capacity: '100', unit: 'ml', glassware_class: 'type A', description: 'Class A volumetric flask with stopper', make: 'Borosil' },
  { name: 'Volumetric Flask', capacity: '200', unit: 'ml', glassware_class: 'type A', description: 'Class A volumetric flask with stopper', make: 'Borosil' },
  { name: 'Volumetric Flask', capacity: '250', unit: 'ml', glassware_class: 'type A', description: 'Class A volumetric flask with stopper', make: 'Borosil' },
  { name: 'Volumetric Flask', capacity: '500', unit: 'ml', glassware_class: 'type A', description: 'Class A volumetric flask with stopper', make: 'Borosil' },
  { name: 'Volumetric Flask', capacity: '1000', unit: 'ml', glassware_class: 'type A', description: 'Class A volumetric flask with stopper', make: 'Borosil' },
  { name: 'Volumetric Flask', capacity: '2000', unit: 'ml', glassware_class: 'type A', description: 'Class A volumetric flask with stopper', make: 'Borosil' },

  // Measuring cylinders
  { name: 'Measuring Cylinder', capacity: '10', unit: 'ml', glassware_class: 'type A', description: 'Graduated measuring cylinder', make: 'Borosil' },
  { name: 'Measuring Cylinder', capacity: '25', unit: 'ml', glassware_class: 'type A', description: 'Graduated measuring cylinder', make: 'Borosil' },
  { name: 'Measuring Cylinder', capacity: '50', unit: 'ml', glassware_class: 'type A', description: 'Graduated measuring cylinder', make: 'Borosil' },
  { name: 'Measuring Cylinder', capacity: '100', unit: 'ml', glassware_class: 'type A', description: 'Graduated measuring cylinder', make: 'Borosil' },
  { name: 'Measuring Cylinder', capacity: '250', unit: 'ml', glassware_class: 'type A', description: 'Graduated measuring cylinder', make: 'Borosil' },
  { name: 'Measuring Cylinder', capacity: '500', unit: 'ml', glassware_class: 'type A', description: 'Graduated measuring cylinder', make: 'Borosil' },
  { name: 'Measuring Cylinder', capacity: '1000', unit: 'ml', glassware_class: 'type A', description: 'Graduated measuring cylinder', make: 'Borosil' },

  // Pipettes
  { name: 'Volumetric Pipette', capacity: '1', unit: 'ml', glassware_class: 'type A', description: 'Class A one-mark volumetric pipette', make: 'Borosil' },
  { name: 'Volumetric Pipette', capacity: '2', unit: 'ml', glassware_class: 'type A', description: 'Class A one-mark volumetric pipette', make: 'Borosil' },
  { name: 'Volumetric Pipette', capacity: '5', unit: 'ml', glassware_class: 'type A', description: 'Class A one-mark volumetric pipette', make: 'Borosil' },
  { name: 'Volumetric Pipette', capacity: '10', unit: 'ml', glassware_class: 'type A', description: 'Class A one-mark volumetric pipette', make: 'Borosil' },
  { name: 'Volumetric Pipette', capacity: '20', unit: 'ml', glassware_class: 'type A', description: 'Class A one-mark volumetric pipette', make: 'Borosil' },
  { name: 'Volumetric Pipette', capacity: '25', unit: 'ml', glassware_class: 'type A', description: 'Class A one-mark volumetric pipette', make: 'Borosil' },
  { name: 'Volumetric Pipette', capacity: '50', unit: 'ml', glassware_class: 'type A', description: 'Class A one-mark volumetric pipette', make: 'Borosil' },
  { name: 'Graduated Pipette', capacity: '1', unit: 'ml', glassware_class: 'type A', description: 'Class A graduated pipette', make: 'Borosil' },
  { name: 'Graduated Pipette', capacity: '2', unit: 'ml', glassware_class: 'type A', description: 'Class A graduated pipette', make: 'Borosil' },
  { name: 'Graduated Pipette', capacity: '5', unit: 'ml', glassware_class: 'type A', description: 'Class A graduated pipette', make: 'Borosil' },
  { name: 'Graduated Pipette', capacity: '10', unit: 'ml', glassware_class: 'type A', description: 'Class A graduated pipette', make: 'Borosil' },
  { name: 'Graduated Pipette', capacity: '25', unit: 'ml', glassware_class: 'type A', description: 'Class A graduated pipette', make: 'Borosil' },

  // Burettes
  { name: 'Burette', capacity: '10', unit: 'ml', glassware_class: 'type A', description: 'Class A glass burette with PTFE stopcock', make: 'Borosil' },
  { name: 'Burette', capacity: '25', unit: 'ml', glassware_class: 'type A', description: 'Class A glass burette with PTFE stopcock', make: 'Borosil' },
  { name: 'Burette', capacity: '50', unit: 'ml', glassware_class: 'type A', description: 'Class A glass burette with PTFE stopcock', make: 'Borosil' },

  // Nessler / test / misc
  { name: 'Nessler Cylinder', capacity: '50', unit: 'ml', glassware_class: 'type A', description: 'Nessler comparison cylinder', make: 'Borosil' },
  { name: 'Nessler Cylinder', capacity: '100', unit: 'ml', glassware_class: 'type A', description: 'Nessler comparison cylinder', make: 'Borosil' },
  { name: 'Test Tube', capacity: '10', unit: 'ml', glassware_class: 'type B', description: 'Borosilicate test tube', make: 'Borosil' },
  { name: 'Test Tube', capacity: '15', unit: 'ml', glassware_class: 'type B', description: 'Borosilicate test tube', make: 'Borosil' },
  { name: 'Test Tube', capacity: '25', unit: 'ml', glassware_class: 'type B', description: 'Borosilicate test tube', make: 'Borosil' },
  { name: 'Centrifuge Tube', capacity: '15', unit: 'ml', glassware_class: 'type B', description: 'Glass centrifuge tube', make: 'Borosil' },
  { name: 'Centrifuge Tube', capacity: '50', unit: 'ml', glassware_class: 'type B', description: 'Glass centrifuge tube', make: 'Borosil' },

  // Funnels / separators
  { name: 'Glass Funnel', capacity: '50', unit: 'ml', glassware_class: 'type B', description: 'Short stem glass funnel', make: 'Borosil' },
  { name: 'Glass Funnel', capacity: '100', unit: 'ml', glassware_class: 'type B', description: 'Short stem glass funnel', make: 'Borosil' },
  { name: 'Separating Funnel', capacity: '125', unit: 'ml', glassware_class: 'type A', description: 'Pear shaped separating funnel with stopper', make: 'Borosil' },
  { name: 'Separating Funnel', capacity: '250', unit: 'ml', glassware_class: 'type A', description: 'Pear shaped separating funnel with stopper', make: 'Borosil' },
  { name: 'Separating Funnel', capacity: '500', unit: 'ml', glassware_class: 'type A', description: 'Pear shaped separating funnel with stopper', make: 'Borosil' },
  { name: 'Separating Funnel', capacity: '1000', unit: 'ml', glassware_class: 'type A', description: 'Pear shaped separating funnel with stopper', make: 'Borosil' },

  // Bottles / dishes
  { name: 'Reagent Bottle', capacity: '125', unit: 'ml', glassware_class: 'type B', description: 'Amber reagent bottle with stopper', make: 'Borosil' },
  { name: 'Reagent Bottle', capacity: '250', unit: 'ml', glassware_class: 'type B', description: 'Amber reagent bottle with stopper', make: 'Borosil' },
  { name: 'Reagent Bottle', capacity: '500', unit: 'ml', glassware_class: 'type B', description: 'Amber reagent bottle with stopper', make: 'Borosil' },
  { name: 'Reagent Bottle', capacity: '1000', unit: 'ml', glassware_class: 'type B', description: 'Amber reagent bottle with stopper', make: 'Borosil' },
  { name: 'Dropping Bottle', capacity: '60', unit: 'ml', glassware_class: 'type B', description: 'Glass dropping bottle with pipette', make: 'Borosil' },
  { name: 'Dropping Bottle', capacity: '125', unit: 'ml', glassware_class: 'type B', description: 'Glass dropping bottle with pipette', make: 'Borosil' },
  { name: 'Watch Glass', capacity: '75', unit: 'ml', glassware_class: 'type B', description: 'Watch glass 75 mm', make: 'Borosil' },
  { name: 'Watch Glass', capacity: '100', unit: 'ml', glassware_class: 'type B', description: 'Watch glass 100 mm', make: 'Borosil' },
  { name: 'Petri Dish', capacity: '90', unit: 'ml', glassware_class: 'type B', description: 'Glass petri dish 90 mm', make: 'Borosil' },
  { name: 'Petri Dish', capacity: '100', unit: 'ml', glassware_class: 'type B', description: 'Glass petri dish 100 mm', make: 'Borosil' },

  // Special QC glassware
  { name: 'Desiccator', capacity: '150', unit: 'ml', glassware_class: 'type A', description: 'Vacuum desiccator with porcelain plate', make: 'Borosil' },
  { name: 'Desiccator', capacity: '200', unit: 'ml', glassware_class: 'type A', description: 'Vacuum desiccator with porcelain plate', make: 'Borosil' },
  { name: 'Crucible with Lid', capacity: '25', unit: 'ml', glassware_class: 'type A', description: 'Silica / porcelain crucible with lid', make: 'Borosil' },
  { name: 'Crucible with Lid', capacity: '50', unit: 'ml', glassware_class: 'type A', description: 'Silica / porcelain crucible with lid', make: 'Borosil' },
  { name: 'Condenser Liebig', capacity: '300', unit: 'ml', glassware_class: 'type A', description: 'Liebig condenser for distillation', make: 'Borosil' },
  { name: 'Round Bottom Flask', capacity: '250', unit: 'ml', glassware_class: 'type A', description: 'Single neck round bottom flask', make: 'Borosil' },
  { name: 'Round Bottom Flask', capacity: '500', unit: 'ml', glassware_class: 'type A', description: 'Single neck round bottom flask', make: 'Borosil' },
  { name: 'Round Bottom Flask', capacity: '1000', unit: 'ml', glassware_class: 'type A', description: 'Single neck round bottom flask', make: 'Borosil' },
  { name: 'Iodine Flask', capacity: '250', unit: 'ml', glassware_class: 'type A', description: 'Iodine determination flask with stopper', make: 'Borosil' },
  { name: 'Iodine Flask', capacity: '500', unit: 'ml', glassware_class: 'type A', description: 'Iodine determination flask with stopper', make: 'Borosil' },
  { name: 'Karl Fischer Flask', capacity: '100', unit: 'ml', glassware_class: 'type A', description: 'KF titration vessel / flask', make: 'Borosil' },
  { name: 'Glass Rod', capacity: '1', unit: 'ml', glassware_class: 'type C', description: 'Stirring glass rod', make: 'Borosil' },
  { name: 'Glass Pipette Filler', capacity: '1', unit: 'ml', glassware_class: 'type C', description: 'Three-way pipette filler bulb', make: 'Generic' },
  { name: 'Buchner Funnel', capacity: '100', unit: 'ml', glassware_class: 'type B', description: 'Porcelain Buchner funnel', make: 'Generic' },
  { name: 'Filter Flask', capacity: '500', unit: 'ml', glassware_class: 'type A', description: 'Vacuum filter flask with side arm', make: 'Borosil' },
  { name: 'Filter Flask', capacity: '1000', unit: 'ml', glassware_class: 'type A', description: 'Vacuum filter flask with side arm', make: 'Borosil' },
  { name: 'Specific Gravity Bottle', capacity: '25', unit: 'ml', glassware_class: 'type A', description: 'Pycnometer / specific gravity bottle', make: 'Borosil' },
  { name: 'Specific Gravity Bottle', capacity: '50', unit: 'ml', glassware_class: 'type A', description: 'Pycnometer / specific gravity bottle', make: 'Borosil' },
  { name: 'Thermometer Pocket', capacity: '1', unit: 'ml', glassware_class: 'type C', description: 'Glass thermometer sleeve / pocket', make: 'Borosil' },
  { name: 'HPLC Sample Vial', capacity: '2', unit: 'ml', glassware_class: 'type A', description: 'Clear glass HPLC vial with cap', make: 'Generic' },
  { name: 'HPLC Sample Vial Amber', capacity: '2', unit: 'ml', glassware_class: 'type A', description: 'Amber glass HPLC vial with cap', make: 'Generic' },
];
