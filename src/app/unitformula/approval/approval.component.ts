import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {
  MEDICAP_PRODUCTION_FLOW,
  offerNextStep,
  productionDirectPlanRoute,
} from 'src/app/shared/medicap-production-flow';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView: boolean = false;
  results: any[] = [];

  packingList: any[] = [];
  raw_materials: any[] = [];
  packing_List_All: any[] = [];
  selectedResult: any = {};
  plant_id: any;
  plant_type: any;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingUnitFormulas();
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.get_rights();
  }

  
  isuser: string = 'No';
  ischecker: string = 'No';
  isapprover: string = 'No';
  qms_approver: string = 'No';
  dept_head: string = 'No';
  isauditor: string = 'No';
  plant_head: string = 'No';
  shift_allocator: string = 'No';
  rights: any;

  get_rights(): void {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id')).subscribe(
      (response: any) => {
        if (response && Array.isArray(response) && response.length > 0) {
          this.rights = response;
          this.isuser = this.rights[0].isuser || 'No';
          this.ischecker = this.rights[0].ischecker || 'No';
          this.isapprover = this.rights[0].isapprover || 'No';
          this.qms_approver = this.rights[0].qms_approver || 'No';
          this.dept_head = this.rights[0].dept_head || 'No';
          this.isauditor = this.rights[0].isauditor || 'No';
          this.plant_head = this.rights[0].plant_head || 'No';
          this.shift_allocator = this.rights[0].shift_allocator || 'No';
        }
      },
      (error: any) => {
        console.error('Error loading user rights:', error);
      }
    );
  }

  getPendingUnitFormulas(): void {
    this.service.get('production/unitformula.php?type=getUnitFormulasforApprovalZuma').subscribe(
      (response: any) => {
        this.results = Array.isArray(response) ? response : [];
      },
      (error: any) => {
        console.error('Error loading pending formulas:', error);
        this.results = [];
        alert('Error loading pending formulas. Please refresh the page.');
      }
    );
  }
  
  groupedMaterials: any = {};
  consumeableMaterial: any[] = [];
  primary_pm_list: any[] = [];

  view(index: number): void {
    if (!this.results || index < 0 || index >= this.results.length) {
      alert('Invalid selection');
      return;
    }

    this.groupedMaterials = {};
    this.selectedResult = this.results[index];
    this.primary_pm_list = this.selectedResult['primary_pm_list'] || [];
    this.consumeableMaterial = this.selectedResult['consumeableMaterial'] || [];

    // Parse raw_materials with error handling
    try {
      let rawMaterialsStr = this.selectedResult['raw_materials'];
      
      // Check if it's already an object/array
      if (typeof rawMaterialsStr === 'object' && rawMaterialsStr !== null) {
        this.raw_materials = Array.isArray(rawMaterialsStr) ? rawMaterialsStr : [];
      } else if (typeof rawMaterialsStr === 'string') {
        // Clean the JSON string - remove control characters and fix malformed escapes
        rawMaterialsStr = this.cleanJsonString(rawMaterialsStr);
        this.raw_materials = JSON.parse(rawMaterialsStr);
      } else {
        this.raw_materials = [];
      }

      // Ensure it's an array
      if (!Array.isArray(this.raw_materials)) {
        this.raw_materials = [];
      }

      this.groupedMaterials = {};
    } catch (error) {
      console.error('Error parsing raw_materials:', error);
      console.error('Raw materials string:', this.selectedResult['raw_materials']);
      this.raw_materials = [];
      this.groupedMaterials = {};
      alert('Error loading raw materials data. Some data may not display correctly.');
    }

    // Parse packing_materials with error handling
    try {
      let packingMaterialsStr = this.selectedResult['packing_materials'];
      
      if (typeof packingMaterialsStr === 'object' && packingMaterialsStr !== null) {
        this.packingList = Array.isArray(packingMaterialsStr) ? packingMaterialsStr : [];
      } else if (typeof packingMaterialsStr === 'string') {
        packingMaterialsStr = this.cleanJsonString(packingMaterialsStr);
        this.packingList = JSON.parse(packingMaterialsStr);
      } else {
        this.packingList = [];
      }

      if (!Array.isArray(this.packingList)) {
        this.packingList = [];
      }
    } catch (error) {
      console.error('Error parsing packing_materials:', error);
      this.packingList = [];
    }

    this.packing_List_All = this.selectedResult['packing_configuration'] || [];
    this.isView = true;
  }

  /**
   * Clean JSON string by removing/replacing problematic characters
   */
  private cleanJsonString(jsonStr: string): string {
    if (!jsonStr || typeof jsonStr !== 'string') {
      return '[]';
    }

    try {
      // First, try to parse as-is (might already be valid)
      try {
        JSON.parse(jsonStr);
        return jsonStr;
      } catch {
        // If parsing fails, clean the string
      }

      // Process character by character to handle tabs and control chars inside string values
      let inString = false;
      let escapeNext = false;
      let cleaned = '';
      
      for (let i = 0; i < jsonStr.length; i++) {
        const char = jsonStr[i];
        const code = char.charCodeAt(0);
        
        if (escapeNext) {
          cleaned += char;
          escapeNext = false;
          continue;
        }
        
        if (char === '\\') {
          escapeNext = true;
          cleaned += char;
          continue;
        }
        
        if (char === '"') {
          inString = !inString;
          cleaned += char;
          continue;
        }
        
        if (inString) {
          // Inside string value: replace tabs and control chars with space
          if (code === 9) {
            // Tab character - replace with space
            cleaned += ' ';
          } else if (code >= 0 && code <= 31) {
            // Other control characters - replace with space
            cleaned += ' ';
          } else if (code === 127) {
            // DEL character - replace with space
            cleaned += ' ';
          } else {
            cleaned += char;
          }
        } else {
          // Outside string: keep whitespace but remove other control chars
          if (code >= 32 || code === 9 || code === 10 || code === 13) {
            cleaned += char;
          }
          // Skip other control characters outside strings
        }
      }
      
      jsonStr = cleaned;
      
      // Fix malformed Unicode escape sequences (like u00e2u0084u0096)
      jsonStr = jsonStr.replace(/\\u([0-9a-fA-F]{4})\\u([0-9a-fA-F]{4})\\u([0-9a-fA-F]{4})/g, (match, a, b, c) => {
        try {
          const char1 = String.fromCharCode(parseInt(a, 16));
          const char2 = String.fromCharCode(parseInt(b, 16));
          const char3 = String.fromCharCode(parseInt(c, 16));
          return char1 + char2 + char3;
        } catch {
          return match;
        }
      });

      // Fix single Unicode escapes
      jsonStr = jsonStr.replace(/\\u([0-9a-fA-F]{4})/g, (match, hex) => {
        try {
          const code = parseInt(hex, 16);
          if (code >= 32 && code !== 127) {
            return String.fromCharCode(code);
          }
          return match;
        } catch {
          return match;
        }
      });
      
      // Final validation - try to parse      
      JSON.parse(jsonStr);
      return jsonStr;
    } catch (error) {
      console.error('Error cleaning JSON string:', error);
      console.error('Original string (first 1000 chars):', jsonStr.substring(0, 1000));
      // Return empty array as fallback
      return '[]';
    }
  }

  approveUnitFormula(status: string): void {             
    if (!this.selectedResult || !this.selectedResult['id']) {
      alert('No formula selected');
      return;
    }
    
    if (!confirm(`Are you sure you want to ${status.toUpperCase()} this formula?`)) {
      return;
    }
    
    this.service.get('production/unitformula.php?type=approveUnitFormula&status=' + status + '&id=' + this.selectedResult['id']).subscribe(
      (response: any) => {
        if (response && response['status'] == 'success') {
          alert('Data Updated Successfully!');
          this.isView = false;
          this.selectedResult = {};
          this.groupedMaterials = {};
          this.consumeableMaterial = [];
          this.primary_pm_list = [];
          this.raw_materials = [];
          this.packingList = [];
          this.packing_List_All = [];
          this.getPendingUnitFormulas();
          if (status === 'approve' || status === 'approved' || status === 'Approved') {
            offerNextStep(
              MEDICAP_PRODUCTION_FLOW.masterFormula,
              'Planning → Master Formula (create BFR)'
            );
          }
        } else {
          alert(response && response['message'] ? response['message'] : 'An Error Occurred, Please try again!');
        }
      },
      (error: any) => {
        console.error('Error updating formula:', error);
        alert('Failed to update formula. Please try again.');
      }
    );
  }
}