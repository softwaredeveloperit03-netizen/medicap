import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  department;
  departments: any[] = [];
  transports: any[] = [];
  isPickup = false;
  isVehicle = true;
  materials: any[] = [];
  batches: any[] = [];
  material_type = '';
  material_subtype = '';
  material_name: any = null;
  qty;
  ar_no = '';
  grn_no = '';
  m_name = '';
  lists: any[] = [];
  units: any[] = [];
  form;
  transport_by = 'By Transport';
  material_code;
  loadingMaterials = false;

  constructor(private service: DataAccessService, private router: Router) {
    this.materials = [];
    this.batches = [];
    this.lists = [];
    this.departments = [];
    this.transports = [];
    this.units = [];
  }

  ngOnInit(): void {
    this.getDepartments();
    this.getUnits();
    this.getTransport();
  }

  getUnits() {
    this.units = [];
    this.service.get('common.php?type=getUnits_List').subscribe({
      next: (response: any) => {
        this.units = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.units = [];
      }
    });
  }

  getTransport() {
    this.transports = [];
    this.service.get('marketing/transporter.php?type=getTransportersLog').subscribe({
      next: (response: any) => {
        const list = Array.isArray(response) ? response : [];
        this.transports = list.filter((t) => {
          const name = (t && (t.LglNm || t.company || t.Trdnm)) || '';
          return String(name).trim() !== '';
        });
      },
      error: () => {
        this.transports = [];
      }
    });
  }

  getTransportDisplayName(trans: any): string {
    if (!trans) {
      return '';
    }
    return String(trans.LglNm || trans.company || trans.Trdnm || '').trim();
  }

  getTransportVal(val) {
    if (val === 'By Transport') {
      this.isPickup = false;
      this.isVehicle = true;
    } else if (val === 'By Courier') {
      this.isVehicle = false;
      this.isPickup = true;
    }
  }

  number(value) {
    if (isNaN(value)) {
      alertify.error('Number 10 digit Only');
      return false;
    }
  }

  onMaterialTypeChange(type: string) {
    this.material_type = type || '';
    this.material_subtype = '';
    this.material_name = null;
    this.batches = [];
    this.material_code = null;
    this.qty = null;
    this.ar_no = '';
    this.grn_no = '';
    this.m_name = '';
    this.getMaterials(this.material_type);
  }

  getMaterials(materialType?: string) {
    const type = String(materialType || this.material_type || '').trim();
    if (!type) {
      this.materials = [];
      this.material_name = null;
      this.batches = [];
      this.loadingMaterials = false;
      return;
    }

    this.materials = [];
    this.material_name = null;
    this.batches = [];
    this.material_code = null;
    this.qty = null;
    this.ar_no = '';
    this.grn_no = '';
    this.m_name = '';
    this.loadingMaterials = true;

    // Same endpoint used by store/raw/receiving/log
    const url = 'store/raw.php?type=getReceivingLog&material_type=' + encodeURIComponent(type);

    this.service.getJsonArray(url).subscribe({
      next: (rows: any[]) => {
        this.materials = this.buildMaterialsFromReceivingLog(Array.isArray(rows) ? rows : []);
        this.loadingMaterials = false;
        if (this.materials.length === 0) {
          this.fetchMaterialsFallback(url);
        }
      },
      error: () => {
        this.fetchMaterialsFallback(url);
      }
    });
  }

  private fetchMaterialsFallback(url: string) {
    this.service.get(url).subscribe({
      next: (response: any) => {
        let rows: any[] = [];
        if (Array.isArray(response)) {
          rows = response;
        } else if (response && Array.isArray(response.data)) {
          rows = response.data;
        } else if (typeof response === 'string') {
          try {
            const parsed = JSON.parse(response);
            rows = Array.isArray(parsed) ? parsed : [];
          } catch (e) {
            rows = [];
          }
        }
        this.materials = this.buildMaterialsFromReceivingLog(rows);
        this.loadingMaterials = false;
        if (this.materials.length === 0) {
          alertify.warning('No materials found in receiving log for the selected type');
        }
      },
      error: () => {
        this.materials = [];
        this.loadingMaterials = false;
        alertify.error('Failed to load materials from Receiving Log.');
      }
    });
  }

  private buildMaterialsFromReceivingLog(rows: any[]): any[] {
    const byCode: { [code: string]: any } = {};

    for (const row of rows) {
      if (!row) {
        continue;
      }

      const code = String(
        row.material_code || row.Material_Code || row.materialCode || ''
      ).trim();
      const name = String(
        row.material_name || row.Material_Name || row.materialName || ''
      ).trim();
      if (!code && !name) {
        continue;
      }

      const key = code || name;
      if (!byCode[key]) {
        byCode[key] = {
          material_code: code || key,
          material_name: name || code,
          material_type: row.material_type,
          material_subtype: row.material_subtype,
          grade: row.grade,
          grns: [],
        };
      }

      const material = byCode[key];
      const batches = Array.isArray(row.batches) ? row.batches : [];

      if (batches.length > 0) {
        for (const batch of batches) {
          this.pushReceivingGrn(material.grns, {
            batch_no: batch.batch_no || batch.supplier_batch_no || row.receiving_no || '',
            qty: batch.qty_received != null && batch.qty_received !== ''
              ? batch.qty_received
              : (batch.qty != null ? batch.qty : (row.received_qty || row.qty || '')),
            ar_no: batch.ar_no || row.ar_no || '',
            grn_no: batch.grn_no || row.grn_no || '',
          });
        }
      } else {
        this.pushReceivingGrn(material.grns, {
          batch_no: row.batch_no || row.supplier_batch_no || row.receiving_no || row.ch_no || '',
          qty: row.received_qty != null && row.received_qty !== '' ? row.received_qty : (row.qty || ''),
          ar_no: row.ar_no || '',
          grn_no: row.grn_no || '',
        });
      }
    }

    return Object.keys(byCode)
      .map((code) => byCode[code])
      .sort((a, b) =>
        String(a.material_name || '').localeCompare(String(b.material_name || ''))
      );
  }

  private pushReceivingGrn(grns: any[], item: any) {
    const batchNo = String(item.batch_no || '').trim();
    const arNo = String(item.ar_no || '').trim();
    const grnNo = String(item.grn_no || '').trim();
    if (!batchNo && !arNo && !grnNo && (item.qty === null || item.qty === undefined || item.qty === '')) {
      return;
    }
    const exists = grns.some(
      (g) =>
        String(g.batch_no || '').trim() === batchNo &&
        String(g.ar_no || '').trim() === arNo &&
        String(g.grn_no || '').trim() === grnNo
    );
    if (!exists) {
      grns.push({
        batch_no: batchNo || '-',
        qty: item.qty,
        ar_no: arNo,
        grn_no: grnNo,
      });
    }
  }

  onMaterialNameChange(materialCode: any) {
    if (!materialCode) {
      this.batches = [];
      this.material_code = null;
      this.m_name = '';
      this.qty = null;
      this.ar_no = '';
      this.grn_no = '';
      return;
    }

    const selectedMaterial = this.materials.find(
      (m) => String(m.material_code) === String(materialCode)
    );
    if (!selectedMaterial) {
      return;
    }

    this.batches = selectedMaterial.grns || [];
    this.material_code = selectedMaterial.material_code || null;
    this.m_name = selectedMaterial.material_name || '';
    this.qty = null;
    this.ar_no = '';
    this.grn_no = '';
  }

  getBatch(index) {
    if (!this.materials || this.materials.length === 0) {
      return;
    }

    const materialIndex = index - 1;
    if (materialIndex < 0 || materialIndex >= this.materials.length) {
      return;
    }

    const selectedMaterial = this.materials[materialIndex];
    this.batches = selectedMaterial['grns'] || [];
    this.material_code = selectedMaterial['material_code'] || null;
    this.qty = null;
    this.ar_no = '';
    this.grn_no = '';
  }

  setName(val) {
    if (!this.materials || this.materials.length === 0) {
      return;
    }

    const materialIndex = val - 1;
    if (materialIndex < 0 || materialIndex >= this.materials.length) {
      return;
    }

    this.m_name = this.materials[materialIndex].material_name || '';
  }

  getQty(val) {
    if (!this.batches || this.batches.length === 0) {
      return;
    }

    const batchIndex = val - 1;
    if (batchIndex < 0 || batchIndex >= this.batches.length) {
      return;
    }

    const selectedBatch = this.batches[batchIndex];
    this.qty = selectedBatch['qty'] || null;
    this.ar_no = selectedBatch['ar_no'] || '';
    this.grn_no = selectedBatch['grn_no'] || '';
  }

  private isBlank(value: any): boolean {
    return value === null || value === undefined || String(value).trim() === '';
  }

  addmaterial(data) {
    if (!this.material_type) {
      alertify.error('Material Type is required');
      return;
    }
    if (this.isBlank(this.material_subtype)) {
      alertify.error('Material SubType is required');
      return;
    }
    if (this.isBlank(this.material_code)) {
      alertify.error('Material Name is required');
      return;
    }
    if (!data || this.isBlank(data.value?.batch_no)) {
      alertify.error('Medicap Lot No is required');
      return;
    }
    if (!data || this.isBlank(data.value?.unit)) {
      alertify.error('Unit is required');
      return;
    }

    const temp = { ...data.value };
    temp['material_type'] = this.material_type;
    temp['material_subtype'] = this.material_subtype;
    temp['material_name'] = this.m_name;
    temp['material_code'] = this.material_code;
    temp['ar_no'] = this.ar_no;
    temp['grn_no'] = this.grn_no;
    temp['qty'] = this.qty;

    this.lists.push(temp);

    data.reset();
    this.material_name = null;
    this.batches = [];
    this.material_code = null;
    this.qty = null;
    this.ar_no = '';
    this.grn_no = '';
    this.m_name = '';
  }

  saveoutward(data) {
    if (!data) {
      return;
    }

    const formValue = data.value || {};
    const requiredFields = [
      { key: 'department', label: 'Department' },
      { key: 'party_name', label: 'Party Name' },
      { key: 'address', label: 'Address' },
      { key: 'pin_code', label: 'Postal Code' },
      { key: 'outword_type', label: 'Outward Type' },
      { key: 'request_by', label: 'Request By' },
      { key: 'transport_by', label: 'Transport By' },
      { key: 'reason', label: 'Reason' },
    ];

    for (const field of requiredFields) {
      if (this.isBlank(formValue[field.key])) {
        alertify.error(field.label + ' is required');
        return;
      }
    }

    if (this.transport_by === 'By Transport') {
      if (this.isBlank(formValue.transport_company)) {
        alertify.error('Transport Name is required');
        return;
      }
      if (this.isBlank(formValue.driver_name)) {
        alertify.error('Driver Name is required');
        return;
      }
      if (this.isBlank(formValue.driver_mobile)) {
        alertify.error('Driver Mobile No is required');
        return;
      }
      if (this.isBlank(formValue.vehicle_no)) {
        alertify.error('Vehicle No is required');
        return;
      }
    } else if (this.transport_by === 'By Courier') {
      if (this.isBlank(formValue.pickup_by_name)) {
        alertify.error('PickUp By Name is required');
        return;
      }
      if (this.isBlank(formValue.pickup_by_mobile)) {
        alertify.error('PickUp By Mobile No is required');
        return;
      }
    }

    if (!this.lists || this.lists.length === 0) {
      alertify.error('Please add at least one material');
      return;
    }

    const temp = { ...formValue };
    if (this.transport_by === 'By Courier') {
      temp['driver_name'] = formValue.pickup_by_name;
      temp['driver_mobile'] = formValue.pickup_by_mobile;
    }
    temp['materials'] = this.lists;

    this.service.post('store/outward.php?type=saveMaterialOutForm', JSON.stringify(temp)).subscribe({
      next: (response: any) => {
        if (response['status'] == 'success') {
          alertify.success('Data saved successfully');
          data.resetForm();
          this.lists = [];
          this.router.navigate(['/store/material']);
        } else {
          alertify.error('Error occurred: ' + (response['message'] || 'Unknown error'));
        }
      },
      error: () => {
        alertify.error('Error occurred while saving. Please try again.');
      }
    });
  }

  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation').subscribe({
      next: (response: any) => {
        this.departments = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.departments = [];
      }
    });
  }
}
