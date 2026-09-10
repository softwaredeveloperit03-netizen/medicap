import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-retest-sampling',
  templateUrl: './sampling.component.html',
})
export class SamplingComponent implements OnInit {
  results: any[] = [];
  allResults: any[] = [];
  selectedResult: any = {};
  isView = false;
  loading = false;
  searchQuery = '';

  balances: any[] = [];
  lafs: any[] = [];
  units: any[] = [];
  selectedBalance: any = {};
  selectedLAF: any = {};

  containers = '';
  sampling_containers = 0;
  sample_qty = '';
  reserve_qty = '';
  sample_unit = '';
  container_details: any[] = [];
  start_time = '';

  constructor(private service: DataAccessService, private route: ActivatedRoute) {
    this.service.observableUnit.subscribe((response) => {
      this.units = response || [];
    });
  }

  ngOnInit(): void {
    const grn = this.route.snapshot.queryParamMap.get('grn_no');
    if (grn) {
      this.searchQuery = grn;
    }
    this.getAwaitingSamplingRetests();
    this.getLAFEquipments();
  }

  getAwaitingSamplingRetests() {
    this.loading = true;
    this.service.getJsonArray('qc/retest.php?type=getAwaitingSamplingRetests').subscribe({
      next: (response) => {
        this.allResults = response || [];
        this.applyFilter();
        this.loading = false;
      },
      error: () => {
        this.allResults = [];
        this.results = [];
        this.loading = false;
      },
    });
  }

  applyFilter() {
    const q = (this.searchQuery || '').trim().toUpperCase();
    this.results = (this.allResults || []).filter((row) => {
      if (!q) {
        return true;
      }
      return (
        (row.material_code || '').toString().toUpperCase().includes(q) ||
        (row.material_name || '').toString().toUpperCase().includes(q) ||
        (row.grn_no || '').toString().toUpperCase().includes(q) ||
        (row.batch_no || '').toString().toUpperCase().includes(q) ||
        (row.ar_no || '').toString().toUpperCase().includes(q)
      );
    });
  }

  view(index: number) {
    this.selectedResult = this.results[index];
    this.containers = '';
    this.sampling_containers = 0;
    this.sample_qty = '';
    this.reserve_qty = '';
    this.sample_unit = this.selectedResult.unit || '';
    this.container_details = [];
    this.start_time = '';
    this.isView = true;
    this.getBalances();
  }

  getBalances() {
    this.service.get('balance.php?type=getSamplingBalances').subscribe((response: any) => {
      this.balances = Array.isArray(response) ? response : [];
    });
  }

  getLAFEquipments() {
    this.service.get('equipments.php?type=getLAFEquipments').subscribe((response: any) => {
      this.lafs = Array.isArray(response) ? response : [];
    });
  }

  selectBalance(index: number) {
    index = index - 1;
    this.selectedBalance = index !== -1 ? this.balances[index] : {};
  }

  selectLAF(index: number) {
    index = index - 1;
    this.selectedLAF = index !== -1 ? this.lafs[index] : {};
  }

  getCurrentTime() {
    if (!this.selectedLAF || !Object.keys(this.selectedLAF).length) {
      alertify.error('Select LAF');
      return;
    }
    const d = new Date();
    const h = (d.getHours() < 10 ? '0' : '') + d.getHours();
    const m = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
    this.start_time = h + ':' + m;
  }

  calculation() {
    let samplingContainers = 0;
    const totalContainers = +this.containers || 0;
    if (this.selectedResult['material_subtype'] === 'Key Starting Material') {
      samplingContainers = totalContainers;
    } else if (totalContainers > 10) {
      samplingContainers = Math.round(Math.sqrt(totalContainers)) + 1;
    } else {
      samplingContainers = totalContainers;
    }
    this.sampling_containers = samplingContainers;

    const containers: any[] = [];
    for (let i = 0; i < this.sampling_containers; i++) {
      containers.push({
        container_no: i + 1,
        sample_qty: Math.round((+this.sample_qty || 0) / (samplingContainers || 1)),
        reserve_qty: Math.round((+this.reserve_qty || 0) / (samplingContainers || 1)),
        total_qty: Math.round((+this.sample_qty || 0) / (samplingContainers || 1)) + Math.round((+this.reserve_qty || 0) / (samplingContainers || 1)),
        unit: this.sample_unit,
        status: 'pending',
      });
    }
    this.container_details = containers;
  }

  saveSampling(form: any) {
    if (!form.valid) {
      alertify.error('All fields are required');
      return;
    }
    const temp = {
      ...form.value,
      id: this.selectedResult.id,
      containers: this.containers,
      sampling_containers: this.sampling_containers,
      sample_qty: this.sample_qty,
      sample_unit: this.sample_unit,
      container_details: this.container_details,
    };
    this.service.post('qc/retest.php?type=saveRetestSampling', JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] === 'success') {
        alertify.success('Retest sampling saved successfully');
        this.isView = false;
        this.getAwaitingSamplingRetests();
      } else {
        alertify.error(response['msg'] || 'Failed to save retest sampling');
      }
    });
  }
}
