import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-purchase-requisition-rmpm',
  templateUrl: './rmpm.component.html',
  styleUrls: ['./rmpm.component.css'],
})
export class PurchaseRequisitionRmpmComponent implements OnInit {

  vendors;
  /** RM/PM requisition: only these departments may be selected. */
  departments = [
    { department_name: 'Production' },
    { department_name: 'Purchase' },
    { department_name: 'R&D' },
  ];
  materials_data: any;
  show_materials = [];
  selectedMaterial: any;
  flag = false;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getallmaterial();
  }

  material_type = 'Raw Material';

  getallmaterial() {
    this.service.get('common.php?type=getallmatdataForIndent&material_type=' + this.material_type).subscribe(response => {
      this.materials_data = response;
    });
  }

  searchTerm: string;

  get filteredItems() {
    return this.materials_data.filter(item => item.material_name.toLowerCase().includes(this.searchTerm?.toLowerCase() || ''));
  }

  selectItem(item: any) {
    this.selectedMaterial = item;
  }

  materialGrade(item: any): string {
    if (!item) {
      return '';
    }
    return item.gradeName || item.grade || '';
  }

  add() {
    if (!this.selectedMaterial) {
      return;
    }
    if (this.selectedMaterial['artwork'] == 'YES') {
      this.getStockBookByMaterialCode(this.selectedMaterial['material_code']);
    } else {
      this.show_materials.push({ ...this.selectedMaterial });
    }
    this.getManufactures();
  }

  stock_data = {};
  isPopUp = false;

  getStockBookByMaterialCode(material_code) {
    this.service.get('store/opening.php?type=getStockBookByMaterialCode&material_code=' + material_code).subscribe(response => {
      this.stock_data = response;
      if (response['stock_version_no'] == response['curr_version_no']) {
        this.show_materials.push({ ...this.selectedMaterial });
      } else {
        if (response['balance_qty'] > 0) {
          this.isPopUp = true;
        } else {
          this.show_materials.push({ ...this.selectedMaterial });
        }
      }
    });
  }

  getManufactures() {
    this.service.get('common.php?type=getManufacturersForIndent&material_code=' + this.selectedMaterial['material_code']).subscribe(response => {
      this.vendors = response;
    });
  }

  CheckValue(value) {
    if (value == 'Map Material') {
      window.open('#/purchase/vendor/material/new', '_blank');
    }
  }

  requirement = '';

  final_material = [];

  addindent(data) {
    if (!this.show_materials.length) {
      return;
    }
    if (!data.valid) {
      alertify.error('Please enter required field');
      return;
    }
    const row = this.show_materials[0];
    if (!row.department) {
      alertify.error('Please select For Department');
      return;
    }
    const temp: any = {
      material_type: row.material_type,
      material_subtype: row.material_subtype,
      material_code: row.material_code,
      material_name: row.material_name,
      grade: row.grade,
      gradeName: this.materialGrade(row),
      qty: row.qty,
      unit: row.unit,
      vendor_no: row.vendor_no,
      department: row.department,
      requirement: this.requirement,
    };
    this.final_material.push(temp);
    this.show_materials = [];
    this.selectedMaterial = null;
  }

  deleteshowmat(index) {
    this.final_material.splice(index, 1);
  }

  save() {

    if (this.final_material.length == 0) {
      alert('Please Add Material!!!!');
      return;
    }

    this.service.post('purchase/indent.php?type=saveIndentStore', JSON.stringify(this.final_material)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Purchase Requisition records saved successfully');
        this.final_material = [];
        this.show_materials = [];
        this.selectedMaterial = null;
        this.requirement = '';
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

}
