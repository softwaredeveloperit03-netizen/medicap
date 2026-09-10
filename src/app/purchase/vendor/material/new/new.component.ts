import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { forkJoin } from 'rxjs';
declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.checkType();
    this.getVendorType();
  }

  manufacturers;
  checkType() {
    this.service.get('common.php?type=getManufacturers').subscribe(response => {
      this.manufacturers = response;
    });
  }

  vendors: any[] = [];
  getVendorType() {
    this.service.get('common.php?type=getVendorForMaterialMappaing').subscribe(
      (response: any) => {
        if (response && response.status === 'invalid') {
          this.vendors = [];
          return;
        }
        this.vendors = Array.isArray(response) ? response : [];
      },
      () => { this.vendors = []; }
    );
  }

  materials: any[] = [];
  selectedMaterialCode = '';
  getallmaterials(_matType?: string) {
    forkJoin([
      this.service.get('common.php?type=getMaterialForMapping&matType=' + encodeURIComponent('Raw Material')),
      this.service.get('common.php?type=getMaterialForMapping&matType=' + encodeURIComponent('Packing Material')),
    ]).subscribe(
      ([rawResponse, packingResponse]: any[]) => {
        if ((rawResponse && rawResponse.status === 'invalid') || (packingResponse && packingResponse.status === 'invalid')) {
          this.materials = [];
          alertify.error('Session expired. Please log in again.');
          return;
        }
        const combined = [
          ...(Array.isArray(rawResponse) ? rawResponse : []),
          ...(Array.isArray(packingResponse) ? packingResponse : []),
        ].map((item: any) => ({
          ...item,
          material_name: item?.material_name || item?.Material_name || '',
          material_code: item?.material_code || item?.Material_code || '',
          material_type: item?.material_type || item?.mat_type || '',
          material_subtype: item?.material_subtype || item?.mat_subtype || '',
        }));

        const uniqueByCode = new Set<string>();
        this.materials = combined.filter((item: any) => {
          const code = String(item?.material_code || '').trim();
          if (!code || uniqueByCode.has(code)) {
            return false;
          }
          uniqueByCode.add(code);
          return true;
        });
      },
      () => {
        this.materials = [];
        alertify.error('Failed to load materials from master.');
      }
    );
  }

  lead_time = 0;
  searchTerm: string;

  get filteredItems() {
    const list = this.materials || [];
    const term = (this.searchTerm || '').toLowerCase();
    return list.filter(item =>
      (item.material_name || '').toLowerCase().includes(term) ||
      (item.material_code || '').toLowerCase().includes(term) ||
      (item.material_type || '').toLowerCase().includes(term) ||
      (item.material_subtype || '').toLowerCase().includes(term)
    );
  }

  selectedMaterial: any = {};
  selectItem(item: any) {
    this.selectedMaterial = {
      ...item,
      material_type: item?.material_type || '',
      material_subtype: item?.material_subtype || '',
      material_name: item?.material_name || '',
      material_code: item?.material_code || '',
    };
    this.selectedMaterialCode = this.selectedMaterial.material_code || '';
  }

  onMaterialCodeChange(code: string): void {
    this.selectedMaterialCode = code || '';
    const picked = (this.materials || []).find((x: any) => String(x?.material_code || '') === this.selectedMaterialCode);
    if (picked) {
      this.selectItem(picked);
    } else {
      this.selectedMaterial = {};
    }
  }

  materialList = [];
  deletmapping(index) {
    this.materialList.splice(index, 1);
  }

  vendor_no = '';
  mapped_materials: any[] = [];
  materialsLoading = false;

  addMaterial(data) {
    if (!this.vendor_no) {
      alertify.error('Please select a vendor');
      return;
    }
    if (!this.selectedMaterial?.material_code) {
      alertify.error('Please select a material');
      return;
    }
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    const code = this.selectedMaterial['material_code'];
    let found1 = this.materialList.some(material => material.material_code === code);
    if (found1) {
      alertify.error(`Material with code ${code} is present in the List.`);
      return;
    }
    let found = (this.mapped_materials || []).some(material => material.material_code === code);
    if (found) {
      alertify.error(`Material with code ${code} is already mapped to this vendor.`);
      return;
    }

    let temp = data.value;
    temp['vendor_no'] = this.vendor_no;
    temp['clientGrpCode'] = '';
    temp['clientSubGrpCode'] = '';
    temp['lead_time'] = this.lead_time;
    temp['material_name'] = this.selectedMaterial['material_name'];
    temp['material_code'] = code;
    temp['material_type'] = this.selectedMaterial['material_type'] || temp['material_type'] || '';
    temp['material_subtype'] = this.selectedMaterial['material_subtype'] || temp['material_subtype'] || '';
    this.materialList.push(temp);
    data.reset();
    this.selectedMaterial = {};
    this.selectedMaterialCode = '';
    this.lead_time = 0;
  }

  selectedVendor = {};

  getmappedmaterial(ind: number) {
    this.materials = [];
    this.mapped_materials = [];
    this.searchTerm = '';
    this.selectedMaterial = {};
    this.selectedMaterialCode = '';
    if (!this.vendor_no) {
      return;
    }
    this.materialsLoading = true;
    this.service
      .get('master/material.php?type=get_materials_by_supplier&vendor_no=' + encodeURIComponent(this.vendor_no))
      .subscribe(
        (response: any) => {
          if (response && response.status === 'invalid') {
            this.mapped_materials = [];
            alertify.error('Session expired. Please log in again.');
            this.materialsLoading = false;
            return;
          }
          const rows = Array.isArray(response)
            ? response
            : (response?.data && Array.isArray(response.data) ? response.data : []);
          this.mapped_materials = rows.map((row: any) => this.normalizeMappedRow(row));
          this.materialsLoading = false;
        },
        () => {
          this.mapped_materials = [];
          this.materialsLoading = false;
          alertify.error('Failed to load mapped materials from server.');
        }
      );

    const vendor = this.vendors && ind > 0 ? this.vendors[ind - 1] : null;
    this.getallmaterials(vendor?.material_type || '');
  }

  private normalizeMappedRow(row: any): any {
    if (!row || typeof row !== 'object') {
      return row;
    }
    return {
      ...row,
      material_code: row.material_code || row.Material_code || '',
      material_name: row.material_name || row.Material_name || row.material_code || 'NA',
      material_type: row.material_type || row.mat_type || row.vmaterial_type || 'NA',
      material_subtype: row.material_subtype || row.mat_subtype || 'NA',
    };
  }

  saveMaterial() {
    if (this.materialList.length == 0) {
      alertify.error('At least 1 material required in list');
      return;
    }

    this.service.post('master/material.php?type=save_vendor_material', JSON.stringify(this.materialList)).subscribe(
      (response: any) => {
        if (response && response['status'] == 'success') {
          alertify.success('Material mapping has been saved successfully');
          this.materialList = [];
          this.router.navigate(['/purchase/vendor/material/map']);
        } else {
          const msg = response?.message || response?.status || 'An error occured, please try again!';
          alertify.error('Failed: ' + msg);
        }
      },
      () => {
        alertify.error('Failed: Could not reach server. Please try again!');
      }
    );
  }
}
