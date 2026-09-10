import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;
  results: any[] = [];
  loading = false;

  selectedReport = [];
  total = 0;
  damages = [];
  checkPointData ;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDamages();
  }


  docFIle1: File | null = null;
  previewUrl1: string | ArrayBuffer | null = null;
  docFIle2: File | null = null;
  previewUrl2: string | ArrayBuffer | null = null;
 
 
  onFileChangedpsb(event: any) {
    const file = event.target.files[0];
    if (!file) return;

    this.docFIle1 = file;

    // Preview only for images
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.previewUrl1 = e.target?.result;
      };
      reader.readAsDataURL(file);
    } else {
      this.previewUrl1 = null;
    }

    // Reset input so same file can be reselected if needed
    event.target.value = '';
  }

  onFileChangedpsb2(event: any) {
    const file = event.target.files[0];
    if (!file) return;

    this.docFIle2 = file;

    // Preview only for images
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.previewUrl2 = e.target?.result;
      };
      reader.readAsDataURL(file);
    } else {
      this.previewUrl2 = null;
    }

    // Reset input so same file can be reselected if needed
    event.target.value = '';
  }

 
  getCheckPointData1(){
    let checkType = 'Damage/Spillage Checklist PM';
    if(this.material_type == 'Raw Material')
      checkType = 'Damage/Spillage Checklist RM'
    this.service.get('master/checklist.php?type=getCheckPointByForm&module=Receiving&form='+encodeURIComponent(checkType)).subscribe(response => {
      this.checkPointData = response;
    });
  }

  material_type = 'Raw Material';
  getPendingDamages() {
    this.loading = true;
    this.getCheckPointData1();
    this.service.get('store/raw.php?type=getPendingDamages&material_type=' + encodeURIComponent(this.material_type)).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => {
      this.results = [];
      this.loading = false;
    });
  }

  view(data) {
    this.damages = [];
    this.selectedReport = this.normalizeDamageReport(data);
    this.isView = true;
    this.total = +this.selectedReport['outer_damage'];

    for (let i = 0; i < +this.total; i++) {
      const temp: any = {};
      temp['container_no'] = i + 1;
      temp['status'] = 'Outer Damage';
      temp['remark'] = '';
      this.damages[this.damages.length] = temp;
    }
  }

  private normalizeDamageReport(data: any): any {
    const report = { ...(data || {}) };
    if (typeof report.receiving_details === 'string') {
      try {
        report.receiving_details = JSON.parse(report.receiving_details);
      } catch {
        report.receiving_details = {};
      }
    }
    if (!report.receiving_details || typeof report.receiving_details !== 'object') {
      report.receiving_details = {};
    }
    report.batch_no = this.resolveBatchNo(report);
    return report;
  }

  getBatchNo(): string {
    return this.resolveBatchNo(this.selectedReport);
  }

  private resolveBatchNo(report: any): string {
    if (!report) {
      return '';
    }

    const direct = String(report.batch_no || report.damage_batch_no || '').trim();
    if (direct) {
      return direct;
    }

    const details = report.receiving_details || {};
    const fromDetails = String(details.damage_batch_no || details.batch_no || '').trim();
    if (fromDetails) {
      return fromDetails;
    }

    const batches = Array.isArray(report.batches) ? report.batches : [];
    if (batches.length === 1) {
      return String(batches[0]?.batch_no || '').trim();
    }

    if (batches.length > 1) {
      const batchNos = batches
        .map((batch: any) => String(batch?.batch_no || '').trim())
        .filter((batchNo: string) => !!batchNo);
      return [...new Set(batchNos)].join(', ');
    }

    return '';
  }

  save(data) {

    if(!data.valid){
      alertify.error('All Field Required !!!!!');
      return;
    }

    const uploadData = new FormData();
   
    if (this.docFIle1) {
      uploadData.append('damaeImg1', this.docFIle1, this.docFIle1.name);
    } 

    if (this.docFIle2) {
      uploadData.append('damaeImg2', this.docFIle2, this.docFIle2.name);
    } 

    uploadData.append('po_no', this.selectedReport['po_no'] );
    uploadData.append('challan_no', this.selectedReport['challan_no'] );
    uploadData.append('total_damage', this.selectedReport['outer_damage'] );
    uploadData.append('containers', JSON.stringify(this.damages));
    uploadData.append('checkPointData', JSON.stringify(this.checkPointData));

    this.service.post('store/raw.php?type=saveDamageInspection&id=' + this.selectedReport['id'], uploadData).subscribe({
      next: (response) => {
        const result = this.parseSaveResponse(response);
        if (result['status'] === 'success') {
          alertify.success('Damage Container Inspection form send for QA Approval');
          this.isView = false;
          this.docFIle1 = null;
          this.docFIle2 = null;
          this.previewUrl1 = null;
          this.previewUrl2 = null;
          this.getPendingDamages();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      },
      error: () => {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  private parseSaveResponse(response: any): any {
    const body = response?.body ?? response;
    if (typeof body === 'string') {
      try {
        return JSON.parse(body);
      } catch {
        return { status: 'failed' };
      }
    }
    return body || { status: 'failed' };
  }







  searchQuery = '';

  get filteredMaterials(): any[] {
    const list = Array.isArray(this.results) ? this.results : [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return list;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return list.filter((material) => {
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
