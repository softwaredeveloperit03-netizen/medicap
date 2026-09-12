/** FEN-001-01-C Facility Interior Maintenance Monthly Checklist */
export const INTERIOR_CHECKPOINTS: string[] = [
  'Check the building interior, including wall, roof, door and window for any signs of damage or deterioration.',
  'Check the building wall and roof for any paint work.',
  'Check the condition of instrument/equipment/CRP panel for any dirt or damages.',
  'Check the condition of doors and their accessories.',
  'Check the window for glass breakage and proper fittings on wall.',
  'Check the condition of shutter and PVC curtains for any damage and gaps.',
  'Check the condition of all utility pipes and ensure there are no leakages.',
  'Check Ceiling or false ceiling for any damage.',
  'Check the flooring condition of tiles, carpet or Epoxy and ensure that is devoid of cracks.',
  'Check all light fixtures for damage and cleaning.',
  'Check stairs railing or supports for any loose nut and bolts.',
  'Check the Proper disposal of waste materials, including solid waste, hazardous waste, and industrial waste.',
  'Other Observation.',
];

/** FEN-001-01-D Facility Exterior Maintenance Monthly Checklist */
export const EXTERIOR_CHECKPOINTS: string[] = [
  'Check the cleaning of paved areas, sidewalks, and parking lots. Ensure no dust or debris present.',
  'Check the Proper disposal of waste materials, including solid waste, hazardous waste, and industrial waste.',
  'Check the building exteriors, including walls, roofs, doors, windows, and gutters, for any signs of damage or deterioration.',
  'Check the loading docks and other areas where materials are handled to ensure they are free from hazards.',
  'Check the Maintenance of landscaping to prevent the spread of weeds and other vegetation that could harbor pests or attract rodents.',
  'Check the Ensuring adequate lighting for exterior areas, especially during nighttime hours.',
  'Check the Maintenance of parking areas to ensure they are properly marked and maintained for efficient traffic flow.',
  'Other Observation.',
];

export interface FacilityChecklistItem {
  sr: number;
  checkpoint: string;
  observation: string;
  action_taken: string;
}

export function buildChecklistItems(checkpoints: string[]): FacilityChecklistItem[] {
  return checkpoints.map((checkpoint, i) => ({
    sr: i + 1,
    checkpoint,
    observation: '',
    action_taken: '',
  }));
}
