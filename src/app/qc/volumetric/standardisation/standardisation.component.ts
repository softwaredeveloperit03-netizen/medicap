import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-standardisation',
  templateUrl: './standardisation.component.html',
  styleUrls: ['./standardisation.component.css']
})
export class StandardisationComponent implements OnInit {

  isView = false;
  materials;
  solutions: any[] = [];
  standards;
  emp_id: string;
  isDIGI: boolean = false;
  materialForm: any;
  isbutton: boolean = true;
  weigh_slip_req = '';

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.getVolumetricSolutions();
    this.get_rights();
    this.getemployee();
  }

  selectedResult: any = {
    chemicals: [],
    Reagent: [],
    Equipment: [],
    procedures: [],
    Safty: [],
  };

  getdetails(index: number) {
    index = index - 1;
    if (index < 0 || !this.solutions?.[index]) {
      this.resetSelectedResult();
      return;
    }
    const row = JSON.parse(JSON.stringify(this.solutions[index]));
    row.chemicals = Array.isArray(row.chemicals) ? row.chemicals : [];
    row.Reagent = Array.isArray(row.Reagent) ? row.Reagent : [];
    row.Equipment = Array.isArray(row.Equipment) ? row.Equipment : [];
    row.procedures = Array.isArray(row.procedures) ? row.procedures : [];
    row.Safty = Array.isArray(row.Safty) ? row.Safty : [];
    row.chemicals.forEach((item: any) => {
      item.stock_data = Array.isArray(item.stock_data) ? item.stock_data : [];
    });
    row.Reagent.forEach((item: any) => {
      item.stock_data = Array.isArray(item.stock_data) ? item.stock_data : [];
    });
    this.selectedResult = row;
    this.attachLabelingBatches(this.selectedResult);
  }

  /** One API per row — same Labeling Details as engi-store/receivingNew/log. */
  private attachLabelingBatches(selected: any): void {
    if (!selected) {
      return;
    }
    const loadRows = (items: any[], nameKey: string) => {
      if (!Array.isArray(items)) {
        return;
      }
      items.forEach((item: any) => {
        const materialName = String(item?.[nameKey] || item?.material_name || '').trim();
        const materialCode = String(item?.material_code || '').trim();
        if (!materialName && !materialCode) {
          return;
        }
        let url = 'store/raw.php?type=getLabelingBatchesForMaterial';
        if (materialCode) {
          url += '&material_code=' + encodeURIComponent(materialCode);
        }
        if (materialName) {
          url += '&material_name=' + encodeURIComponent(materialName);
        }
        this.service.get(url).subscribe((response: any) => {
          const batches = Array.isArray(response) ? response : [];
          if (batches.length === 0) {
            return;
          }
          item.stock_data = batches.map((b: any) => ({
            material_code: b.material_code || item.material_code,
            batch_no: b.batch_no || b.ar_no || '',
            ar_no: b.ar_no || '',
            grn_no: b.grn_no || '',
            mfg_date: b.mfg_date || '',
            exp_date: b.exp_date || '',
            qty: b.qty_received != null && b.qty_received !== '' ? b.qty_received : b.qty || '',
            qty_received: b.qty_received != null && b.qty_received !== '' ? b.qty_received : b.qty || '',
            unit: b.unit || '',
          }));
          if (!item.material_code && item.stock_data[0]?.material_code) {
            item.material_code = item.stock_data[0].material_code;
          }
          this.selectedResult = { ...this.selectedResult };
        });
      });
    };
    loadRows(selected.Reagent, 'reagent');
    loadRows(selected.chemicals, 'chemical_name');
  }

  private resetSelectedResult(): void {
    this.selectedResult = {
      chemicals: [],
      Reagent: [],
      Equipment: [],
      procedures: [],
      Safty: [],
    };
  }

  getbatches(selectedIndex: number, indexMain: number) {
    const stock = this.selectedResult?.Reagent?.[indexMain]?.stock_data || [];
    const batch = stock[selectedIndex - 1];
    if (!batch) {
      return;
    }
    this.applyLabelingBatchToReagent(batch, indexMain);
  }

  getbatchesChem(selectedIndex: number, indexMain: number) {
    const stock = this.selectedResult?.chemicals?.[indexMain]?.stock_data || [];
    const batch = stock[selectedIndex - 1];
    if (!batch) {
      return;
    }
    this.applyLabelingBatchToChem(batch, indexMain);
  }

  onBatchChangeChem(batchNo: string, indexMain: number) {
    const stock = this.selectedResult?.chemicals?.[indexMain]?.stock_data || [];
    const batch = stock.find(
      (item: any) =>
        String(item.batch_no) === String(batchNo) || String(item.ar_no || '') === String(batchNo)
    );
    if (!batch) {
      return;
    }
    this.applyLabelingBatchToChem(batch, indexMain);
  }

  onBatchChangeReagent(batchNo: string, indexMain: number) {
    const stock = this.selectedResult?.Reagent?.[indexMain]?.stock_data || [];
    const batch = stock.find(
      (item: any) =>
        String(item.batch_no) === String(batchNo) || String(item.ar_no || '') === String(batchNo)
    );
    if (!batch) {
      return;
    }
    this.applyLabelingBatchToReagent(batch, indexMain);
  }

  private applyLabelingBatchToReagent(batch: any, indexMain: number): void {
    const row = this.selectedResult.Reagent[indexMain];
    row.exp_date = batch.exp_date || '';
    row.mfg_date = batch.mfg_date || '';
    row.grn_no = batch.grn_no || '';
    row.material_code = batch.material_code || row.material_code;
    const qty = batch.qty_received != null && batch.qty_received !== '' ? batch.qty_received : batch.qty;
    if (qty !== undefined && qty !== null && qty !== '') {
      row.usedQty = qty;
    }
  }

  private applyLabelingBatchToChem(batch: any, indexMain: number): void {
    const row = this.selectedResult.chemicals[indexMain];
    row.exp_date = batch.exp_date || '';
    row.mfg_date = batch.mfg_date || '';
    row.grn_no = batch.grn_no || '';
    row.material_code = batch.material_code || row.material_code;
    const qty = batch.qty_received != null && batch.qty_received !== '' ? batch.qty_received : batch.qty;
    if (qty !== undefined && qty !== null && qty !== '') {
      row.usedQty = qty;
    }
  }

  employee;

  getemployee() {
    this.service.get('qc/volumetric.php?type=getemployees').subscribe((response) => {
      this.employee = response;
    });
  }

  getVolumetricSolutions() {
    this.service.getJsonArray('qc/volumetric.php?type=getVolumetricSol').subscribe({
      next: (response) => {
        this.solutions = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.solutions = [];
      },
    });
  }

  weigh_slip: File;
  onFileChanged(event) {
    this.weigh_slip = event.target.files[0];
  }
  sol_type = 'Normal';

  saveVolumetricPreparation(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    const temp = data.value;
    const uploadData = new FormData();

    for (const key in temp) {
      uploadData.append(key, temp[key]);
    }

    uploadData.append('Reagent', JSON.stringify(this.selectedResult['Reagent'] || []));
    uploadData.append('chemicals', JSON.stringify(this.selectedResult['chemicals'] || []));
    uploadData.append('Equipment', JSON.stringify(this.selectedResult['Equipment'] || []));
    uploadData.append('procedures', JSON.stringify(this.selectedResult['procedures'] || []));
    uploadData.append('sol_type', this.sol_type);

    if (this.weigh_slip !== undefined) {
      uploadData.append('weigh_slip', this.weigh_slip, this.weigh_slip.name);
    }

    this.service.postForm('qc/volumetric.php?type=saveVolumetricPreparation', uploadData).subscribe(
      (resp) => {
        let response: { status?: string } = {};
        try {
          response = JSON.parse(String(resp.body || '').trim());
        } catch {
          alertify.error('Unexpected server response.');
          return;
        }
        if (response.status === 'success') {
          alertify.success(this.service.t('common.savedSuccess'));
          this.isbutton = true;
          this.router.navigate(['/qc/volumetric']);
        } else {
          alertify.error(response.status || 'An error occured, Please try again!');
        }
      },
      () => alertify.error('Unable to save volumetric preparation.')
    );
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  rights;

  get_rights() {
    this.service.get('login_client.php?type=getrights').subscribe((response) => {
      this.rights = response;
    });
  }

  close() {
    this.router.navigate(['/qc/volumetric']);
  }
}
