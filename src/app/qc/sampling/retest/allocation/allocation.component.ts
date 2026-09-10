import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-retest-allocation',
  templateUrl: './allocation.component.html',
})
export class AllocationComponent implements OnInit {
  results: any[] = [];
  allResults: any[] = [];
  allocatedResults: any[] = [];
  allAllocatedResults: any[] = [];
  material_code = '';
  material_name = '';
  category = '';
  grn_no = '';
  selectedResult: any = {};
  isPerson = false;
  employees: any[] = [];
  loading = false;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.loadIntimations();
    this.loadAllocatedAwaitingGrn();
    this.getQcPersons();
  }

  loadAllocatedAwaitingGrn() {
    this.service.loadList('qc/retest.php?type=getAllocatedRetestsAwaitingGrn').subscribe({
      next: (response) => {
        this.allAllocatedResults = response || [];
        this.filterAllocated();
      },
      error: () => {
        this.allAllocatedResults = [];
        this.allocatedResults = [];
      },
    });
  }

  filterAllocated() {
    this.allocatedResults = (this.allAllocatedResults || []).filter((material) => {
      const name = (material.material_name || '').toString();
      const subtype = (material.material_subtype || '').toString();
      const code = (material.material_code || '').toString();
      const grn = (material.grn_no || '').toString();
      return (
        code.toUpperCase().includes(this.material_code.toUpperCase()) &&
        name.toUpperCase().includes(this.material_name.toUpperCase()) &&
        subtype.toUpperCase().includes(this.category.toUpperCase()) &&
        grn.toUpperCase().includes(this.grn_no.toUpperCase())
      );
    });
  }

  loadIntimations() {
    this.loading = true;
    this.service.loadList('qc/retest.php?type=getRetestIntimationsForAllocation').subscribe({
      next: (response) => {
        this.allResults = response || [];
        this.filterEquipment();
        this.loading = false;
      },
      error: () => {
        this.allResults = [];
        this.results = [];
        this.loading = false;
      },
    });
  }

  allocate(row: any) {
    this.selectedResult = row;
    this.isPerson = true;
  }

  filterEquipment() {
    this.results = (this.allResults || []).filter((material) => {
      const name = (material.material_name || '').toString();
      const subtype = (material.material_subtype || '').toString();
      const code = (material.material_code || '').toString();
      const grn = (material.grn_no || '').toString();
      return (
        code.toUpperCase().includes(this.material_code.toUpperCase()) &&
        name.toUpperCase().includes(this.material_name.toUpperCase()) &&
        subtype.toUpperCase().includes(this.category.toUpperCase()) &&
        grn.toUpperCase().includes(this.grn_no.toUpperCase())
      );
    });
    this.filterAllocated();
  }

  getQcPersons() {
    this.service.get('qc/sampling.php?type=getQcPersons').subscribe((response: any) => {
      this.employees = Array.isArray(response) ? response : [];
    });
  }

  savePerson(data: any) {
    if (!data?.valid) {
      alertify.error('All fields are required');
      return;
    }
    const retestId = this.selectedResult?.retest_id || this.selectedResult?.id;
    if (!retestId) {
      alertify.error('Retest line not found. Close and open Allocate Person again.');
      return;
    }
    const temp = { ...data.value };
    temp['retest_id'] = retestId;
    temp['grn_no'] = this.selectedResult['grn_no'];
    temp['material_code'] = this.selectedResult['material_code'];
    temp['batch_no'] = this.selectedResult['batch_no'];
    temp['ar_no'] = this.selectedResult['ar_no'];
    temp['release_date'] = this.selectedResult['release_date'];
    temp['retest_date'] = this.selectedResult['retest_date'];
    temp['qty'] = this.selectedResult['qty'];
    temp['unit'] = this.selectedResult['unit'];
    temp['mfg_date'] = this.selectedResult['mfg_date'];
    temp['exp_date'] = this.selectedResult['exp_date'];
    this.service.postTextResponse('qc/retest.php?type=allocateSamplingPerson', JSON.stringify(temp)).subscribe({
      next: (raw) => {
        let response: any = {};
        try {
          response = this.service.parsePhpJson(raw);
        } catch {
          alertify.error('Invalid server response while saving allocation.');
          return;
        }
        if (response['status'] === 'success') {
          alertify.success(response['msg'] || 'Sampling person allocated.');
          this.isPerson = false;
          this.router.navigate(['/qc/sampling/retest/sampling'], {
            queryParams: {
              grn_no: this.selectedResult['grn_no'] || '',
              returnUrl: '/qc/sampling/retest',
            },
          });
        } else if (response['status'] === 'partial') {
          alertify.warning(response['msg'] || 'Person allocated but could not open Retest Sampling.');
          this.isPerson = false;
          this.loadIntimations();
          this.loadAllocatedAwaitingGrn();
        } else {
          alertify.error(response['msg'] || 'Failed to allocate sampling person');
        }
      },
      error: () => alertify.error('Failed to allocate sampling person. Check network and try again.'),
    });
  }
}
