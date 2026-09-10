import { Component, OnInit } from '@angular/core';
import { ClrLoadingState } from '@clr/angular';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-rejected',
  templateUrl: './rejected.component.html',
  styleUrls: ['./rejected.component.css'],
})
export class RejectedComponent implements OnInit {
  isView = false;
  isUpincedent = false;
  isupdate = false;
  isupdate2 = false;

  loading = false;

  results: any[] = [];
  materials: any[] = [];

  selectedReport: any = null;
  receiveDetails: any = null;
  devdetails: any = null;
  productdetails: any = null;
  batches: any[] = [];
  insdetails: any = null;
  incidentProduct: any = null;
  checklist: any[] = [];

  material_code = '';
  challan_date = '';

  plant_id: any;

  submitBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;
  validateBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.getInprocessReceivings();
    this.getMaterials();
  }

  private normalizeArrayResponse(res: any): any[] {
    // Log the raw response to see what PHP is actually returning
    console.log('Raw API Response:', res);
    console.log('Response type:', typeof res);
    console.log('Is Array?', Array.isArray(res));
    
    // Common backend shapes: [] OR {data: []} OR {results: []} OR {status: 'success', data: []}
    if (Array.isArray(res)) return res;
    if (res && Array.isArray(res.data)) return res.data;
    if (res && Array.isArray(res.results)) return res.results;
    if (res && Array.isArray(res.response)) return res.response;
    if (res && Array.isArray(res.list)) return res.list;
    // If it's an object with a single array property, try to find it
    if (res && typeof res === 'object') {
      const keys = Object.keys(res);
      for (const key of keys) {
        if (Array.isArray(res[key])) {
          console.log('Found array in key:', key);
          return res[key];
        }
      }
    }
    console.warn('Could not normalize response to array. Returning empty array.');
    return [];
  }

  getInprocessReceivings(): void {
    this.loading = true;
    const qs =
      'store/raw.php?type=getRejectedReceivings' +
      '&material_code=' +
      encodeURIComponent(this.material_code || '') +
      (this.plant_id ? '&plant_id=' + encodeURIComponent(String(this.plant_id)) : '');

    console.log('Calling API:', qs);

    this.service.get(qs).subscribe({
      next: (response: any) => {
        console.log('=== getRejectedReceivings API Response ===');
        console.log('Full response:', JSON.stringify(response, null, 2));
        this.results = this.normalizeArrayResponse(response);
        console.log('Normalized results:', this.results);
        console.log('Results length:', this.results.length);
        if (this.results.length > 0) {
          console.log('First result:', this.results[0]);
        }
        this.loading = false;
      },
      error: (err: any) => {
        console.error('=== getRejectedReceivings API Error ===');
        console.error('Error object:', err);
        console.error('Error message:', err?.message);
        console.error('Error status:', err?.status);
        this.results = [];
        this.loading = false;
        alertify.error('Failed to load rejected materials. Check console for details.');
      },
    });
  }

  AllRecord(): void {
    this.material_code = '';
    this.loading = true;
    // keeping your original endpoint, but normalize response
    this.service.get('store/raw.php?type=getAllInprocessReceivings').subscribe({
      next: (response: any) => {
        this.results = this.normalizeArrayResponse(response);
        this.loading = false;
      },
      error: (err: any) => {
        console.error('getAllInprocessReceivings error:', err);
        this.results = [];
        this.loading = false;
      },
    });
  }

  getMaterials(): void {
    this.service.get('common.php?type=getRawMaterials').subscribe({
      next: (response: any) => {
        this.materials = this.normalizeArrayResponse(response);
      },
      error: (err: any) => {
        console.error('getRawMaterials error:', err);
        this.materials = [];
      },
    });
  }

  view(index: number): void {
    const row = this.results && this.results[index] ? this.results[index] : null;
    this.selectedReport = row;
    if (!this.selectedReport) return;

    const batches = this.selectedReport['batches'];
    this.batches = Array.isArray(batches) ? batches : [];

    this.receiveDetails = this.selectedReport['receiving_details'] || null;

    this.devdetails = null;
    this.productdetails = null;
    this.insdetails = null;
    this.incidentProduct = null;

    if (this.selectedReport['error_type'] === 'error2') {
      this.devdetails = this.selectedReport['deviations'] || null;
      this.productdetails = this.devdetails ? this.devdetails['product_details'] : null;
    } else if (this.selectedReport['error_type'] === 'error1') {
      this.insdetails = this.selectedReport['incidents'] || null;
      this.incidentProduct = this.insdetails ? this.insdetails['product_details'] : null;
    }

    if (this.selectedReport['receiving_no']) {
      this.getChkListData(this.selectedReport['receiving_no']);
    } else {
      this.checklist = [];
    }

    this.isView = true;
  }

  viewCoafile(url: string): void {
    if (!url) return;
    const full = this.service.url + '../../../../upload/coa/' + url;
    window.open(full, '_blank');
  }

  viewChallan(url: string): void {
    if (!url) return;
    const full = this.service.url + 'upload/challan/' + url;
    window.open(full, '_blank');
  }

  update(status: string): void {
    if (!this.selectedReport) return;
    this.submitBtnState = ClrLoadingState.LOADING;
    this.service
      .get(
        'store/raw.php?type=checkReceivedMaterial&status=' +
          encodeURIComponent(status) +
          '&id=' +
          encodeURIComponent(String(this.selectedReport['id'])) +
          '&challan_id=' +
          encodeURIComponent(String(this.selectedReport['challan_id'])) +
          '&pack_size=' +
          encodeURIComponent(String(this.selectedReport['pack_size']))
      )
      .subscribe({
        next: (response: any) => {
          if (response && response['status'] === 'success') {
            this.submitBtnState = ClrLoadingState.DEFAULT;
            alertify.success('Material updated successfully');
            this.isView = false;
            this.getInprocessReceivings();
          } else {
            this.submitBtnState = ClrLoadingState.DEFAULT;
            alertify.error('Failed: An error occured, please try again!');
          }
        },
        error: () => {
          this.submitBtnState = ClrLoadingState.DEFAULT;
          alertify.error('Failed: An error occured, please try again!');
        },
      });
  }

  getChkListData(rec_no: any): void {
    this.service.get('master/checklist.php?type=get_rec_ChkListByTranID&rec_no=' + encodeURIComponent(String(rec_no))).subscribe({
      next: (response: any) => {
        this.checklist = this.normalizeArrayResponse(response);
      },
      error: (err: any) => {
        console.error('get_rec_ChkListByTranID error:', err);
        this.checklist = [];
      },
    });
  }
}


