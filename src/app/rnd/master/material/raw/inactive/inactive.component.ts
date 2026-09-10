import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-inactive',
  templateUrl: './inactive.component.html',
  styleUrls: ['./inactive.component.css']
})
export class InactiveComponent implements OnInit {


  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getMaterialsLog();
  }


  results;
  
  material_type = 'Raw Material';

  getMaterialsLog() {
    this.service.get('master/rnd_material.php?type=getMaterialsByStatus&material_type='+this.material_type+'&status=In-Active').subscribe((response) => {
        this.results = response;
    });
  }

  selectedResult = [];
  isView = false;
  
  view(id) {
    const materialIndex = this.results.findIndex(
      (material) => material.id === id
    );
    if (materialIndex !== -1) {
      this.selectedResult = this.results[materialIndex];
    } else {
      console.error('Material not found for the given result id.');
    }

    this.isView = true;
  }

  /** For view form: show value or 'NA' when undefined/null/empty */
  getDisplayVal(val: any): string | number | any {
    if (val === undefined || val === null) return 'NA';
    if (typeof val === 'string' && val.trim() === '') return 'NA';
    return val;
  }

 
 
  viewMsds(url) {
    url = this.service.url + '../../upload/product/' + url;
    window.open(url, '_blank');
  }
 


  changeStatus(status) {
  
    this.service.get('master/rnd_material.php?type=ChangeMaterialStatus&id=' +  this.selectedResult['id'] +'&status='+ status).subscribe((response) => {
      if (response['status']) {
        alertify.success('Material Satatus Change Successuly');
        this.getMaterialsLog();
        this.isView = false;
      } else {
        alertify.error('some error occured');
      }
    });
  }




  searchQuery;

  /** Format date for display; returns 'NA' when null/undefined/empty */
  formatEntryDate(val: any): string {
    if (val == null || val === '') return 'NA';
    const d = typeof val === 'string' ? new Date(val) : val;
    if (isNaN(d.getTime())) return 'NA';
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  /** Display "Name (ID)" for Entry By column */
  getEntryByDisplay(result: any): string {
    if (!result) return 'NA';
    const name = result.entry_by_name || result.created_by_name || result.entry_by || result.created_by || '';
    const id = result.entry_by_id || result.created_by_id || '';
    if (name && id) return name + ' (' + id + ')';
    if (name) return name;
    if (id) return 'ID: ' + id;
    return 'NA';
  }

  /** Display "Name (ID)" for InActivated By column; supports common API field names */
  getInactivatedByDisplay(result: any): string {
    if (!result) return 'NA';
    const name = result.inactivated_by_name || result.inactive_by_name || result.inactivated_by_emp_name || result.inactivated_by || '';
    const id = result.inactivated_by_id || result.inactive_by_id || result.inactivated_by_emp_id || '';
    if (name && id) return name + ' (' + id + ')';
    if (name) return name;
    if (id) return 'ID: ' + id;
    return 'NA';
  }

  get filteredMaterials(): any[] {
    if (!this.results) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }



}
