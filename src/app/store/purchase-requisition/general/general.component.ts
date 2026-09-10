import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-purchase-requisition-general',
  templateUrl: './general.component.html',
  styleUrls: ['./general.component.css'],
  providers: [DatePipe]

})
export class PurchaseRequisitionGeneralComponent implements OnInit {

  vendors;
  units;
  departments;
  materials_data: any[] = [];
  department: any;
  plant_id;
  show_materials = [];
  selectedMaterial: any;
  quotation_type = 'Local Purchase';

  minDate = '';

  constructor(private datePipe: DatePipe, private service: DataAccessService, private http: HttpClient, private router: Router) {
    this.minDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.department = localStorage.getItem('department');

    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });

    this.getallmaterial();
    this.getManufactures();
  }

  getallmaterial() {
    this.service.get('master/general.php?type=getGeneralMaterialsForRequisition&material_type=ALL').subscribe((response: any) => {
      this.materials_data = this.normalizeMaterials(response);
      this.selectedMaterial = null;
      this.searchTerm = '';
    }, () => {
      this.materials_data = [];
    });
  }

  private normalizeMaterials(response: any): any[] {
    let rows: any[] = [];
    if (Array.isArray(response)) {
      rows = response;
    } else if (response && Array.isArray(response.rows)) {
      rows = response.rows;
    }
    return rows.map((item: any) => ({
      ...item,
      material_code: String(item.material_code || item.chemical_no || '').trim() || (item.id ? 'GEN-OM-' + item.id : ''),
      uom: item.uom || item.unit || '',
      unit: item.unit || item.uom || '',
    }));
  }

  getManufactures() {
    this.service.get('common.php?type=getManufacturers').subscribe(response => {
      this.vendors = response;
    });
  }

  searchTerm = '';

  get filteredItems() {
    const list = Array.isArray(this.materials_data) ? this.materials_data : [];
    const term = (this.searchTerm || '').toLowerCase().trim();
    if (!term) {
      return list;
    }
    return list.filter((item: any) =>
      (item.material_name || '').toLowerCase().includes(term) ||
      (item.material_code || '').toLowerCase().includes(term) ||
      (item.material_type || '').toLowerCase().includes(term) ||
      (item.material_subtype || '').toLowerCase().includes(term)
    );
  }

  materialLabel(item: any): string {
    if (!item) {
      return '';
    }
    const code = item.material_code || item.chemical_no || '';
    const name = item.material_name || item.chemical_name || '';
    return code ? code + ' — ' + name : name;
  }

  selectItem(item: any) {
    this.selectedMaterial = item;
    this.searchTerm = '';
  }

  add() {
    if (!this.selectedMaterial) {
      return;
    }
    this.show_materials.push({ ...this.selectedMaterial });
    this.selectedMaterial = null;
    this.searchTerm = '';
  }

  deleteshowmat(index) {
    this.final_material.splice(index, 1);
  }

  final_material = [];
  requirement = '';

  addindent(data) {

    if (!data.valid) {
      alertify.error('Please enter required field');
      return;
    }
    let temp = data.value;
    temp['department'] = this.department;
    temp['requirement'] = this.requirement;
    temp['unit'] = temp['unit'] || temp['uom'] || this.show_materials[0]?.unit || '';

    this.final_material.push(temp);
    this.show_materials = [];

  }

  save() {

    if (this.final_material.length == 0) {
      alert('Please Add Material!!!!');
      return;
    }

    this.service.post('purchase/indent.php?type=saveIndent', JSON.stringify(this.final_material)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Purchase Requisition records saved successfully');
        this.final_material = [];

      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

}
